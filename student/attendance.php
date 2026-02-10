<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Get student ID from students table using user_id from session
$user_id = $_SESSION['user_id'];

// Fetch student details
$student_query = "SELECT * FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($student_query);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit;
}

$student_id = $student['id'];

// Get month and year filters (default to current month)
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_skill = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;

// Get all enrolled skills for filter dropdown
$skills_query = "
SELECT DISTINCT s.id, s.skill_name 
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name
";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $student_id);
$stmt_skills->execute();
$all_skills = $stmt_skills->get_result();

// Get attendance summary for the selected period
$summary_query = "
SELECT 
    s.id as skill_id,
    s.skill_name,
    s.description as skill_description,
    COUNT(DISTINCT DATE(sa.attendance_date)) as total_days,
    SUM(CASE WHEN sa.attendance_status = 'present' OR sa.attendance_status = 1 THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN sa.attendance_status = 'absent' OR sa.attendance_status = 0 THEN 1 ELSE 0 END) as absent_days,
    SUM(CASE WHEN sa.attendance_status = 'late' THEN 1 ELSE 0 END) as late_days,
    ROUND(
        AVG(sa.attendance_percentage), 2
    ) as attendance_percentage,
    b.batch_name,
    ses.session_name
FROM student_attendance sa
JOIN skills s ON sa.skill_id = s.id
LEFT JOIN batches b ON sa.batch_id = b.id
LEFT JOIN sessions ses ON sa.session_id = ses.id
WHERE sa.student_id = ?
    AND MONTH(sa.attendance_date) = ?
    AND YEAR(sa.attendance_date) = ?
    AND (? = 0 OR sa.skill_id = ?)
    AND sa.status = 'active'
GROUP BY s.id, s.skill_name, s.description, b.batch_name, ses.session_name
ORDER BY s.skill_name
";
$stmt_summary = $conn->prepare($summary_query);
$stmt_summary->bind_param("iiiii", $student_id, $selected_month, $selected_year, $selected_skill, $selected_skill);
$stmt_summary->execute();
$attendance_summary = $stmt_summary->get_result();

// Get daily attendance details with batch and session info
$daily_query = "
SELECT 
    DATE(sa.attendance_date) as attendance_date,
    s.id as skill_id,
    s.skill_name,
    s.description as skill_description,
    b.batch_name,
    b.start_time,
    b.end_time,
    ses.session_name,
    sa.attendance_status as status,
    sa.remarks,
    sa.attendance_percentage,
    t.name as teacher_name,
    t.teacher_code,
    se.id as enrollment_id
FROM student_attendance sa
JOIN skills s ON sa.skill_id = s.id
JOIN student_enrollments se ON sa.enrollment_id = se.id
LEFT JOIN batches b ON sa.batch_id = b.id
LEFT JOIN sessions ses ON sa.session_id = ses.id
LEFT JOIN teachers t ON sa.marked_by = t.id
WHERE sa.student_id = ?
    AND MONTH(sa.attendance_date) = ?
    AND YEAR(sa.attendance_date) = ?
    AND (? = 0 OR sa.skill_id = ?)
    AND sa.status = 'active'
ORDER BY sa.attendance_date DESC, s.skill_name, b.start_time
";
$stmt_daily = $conn->prepare($daily_query);
$stmt_daily->bind_param("iiiii", $student_id, $selected_month, $selected_year, $selected_skill, $selected_skill);
$stmt_daily->execute();
$daily_attendance = $stmt_daily->get_result();

// Calculate overall statistics
$overall_stats = [
    'total_days' => 0,
    'present_days' => 0,
    'absent_days' => 0,
    'late_days' => 0,
    'attendance_percentage' => 0
];

while ($row = $attendance_summary->fetch_assoc()) {
    $overall_stats['total_days'] += $row['total_days'];
    $overall_stats['present_days'] += $row['present_days'];
    $overall_stats['absent_days'] += $row['absent_days'];
    $overall_stats['late_days'] += $row['late_days'];
}

if ($overall_stats['total_days'] > 0) {
    // Calculate weighted average percentage
    $total_percentage = 0;
    $attendance_summary->data_seek(0);
    while ($row = $attendance_summary->fetch_assoc()) {
        $total_percentage += $row['attendance_percentage'] * $row['total_days'];
    }
    $overall_stats['attendance_percentage'] = round($total_percentage / $overall_stats['total_days'], 2);
}

// Reset pointer for attendance summary
$attendance_summary->data_seek(0);

// Get list of months for dropdown
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

// Get list of years (last 3 years and next 1 year)
$current_year = date('Y');
$years = [];
for ($i = $current_year - 3; $i <= $current_year + 1; $i++) {
    $years[$i] = $i;
}

// Get enrolled courses count
$enrolled_courses_query = "
SELECT COUNT(DISTINCT se.skill_id) as course_count 
FROM student_enrollments se 
WHERE se.student_id = ? AND se.status = 'active'
";
$stmt_courses = $conn->prepare($enrolled_courses_query);
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();
$courses_row = $courses_result->fetch_assoc();
$enrolled_courses_count = $courses_row['course_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Attendance | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .attendance-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-present {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-absent {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #7f1d1d;
            border: 1px solid #ef4444;
        }

        .status-late {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .status-holiday {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #6366f1;
        }

        .skill-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-right: 4px;
        }

        .skill-web-dev {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .skill-cyber {
            background-color: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #d8b4fe;
        }

        .skill-data {
            background-color: #f0f9ff;
            color: #0c4a6e;
            border: 1px solid #bae6fd;
        }

        .skill-graphics {
            background-color: #fef7cd;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .percentage-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .percentage-excellent {
            background: linear-gradient(135deg, #d1fae5 0%, #10b981 100%);
            color: #065f46;
        }

        .percentage-good {
            background: linear-gradient(135deg, #fef3c7 0%, #f59e0b 100%);
            color: #92400e;
        }

        .percentage-poor {
            background: linear-gradient(135deg, #fee2e2 0%, #ef4444 100%);
            color: #7f1d1d;
        }

        .course-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .course-web-dev {
            border-left-color: #3b82f6;
        }

        .course-cyber {
            border-left-color: #8b5cf6;
        }

        .course-data {
            border-left-color: #0ea5e9;
        }

        .course-graphics {
            border-left-color: #f59e0b;
        }

        .day-header {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">My Attendance</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-calendar-check text-green-500 mr-2"></i>
                            Track your attendance records across <?php echo $enrolled_courses_count; ?> enrolled courses
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="my_courses.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Courses
                        </a>
                    </div>
                </div>

                <!-- Current Enrollment Status -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600 font-medium">Currently Enrolled In:</span>
                    <?php
                    $all_skills->data_seek(0);
                    $skill_colors = ['skill-web-dev', 'skill-cyber', 'skill-data', 'skill-graphics'];
                    $color_index = 0;
                    while ($skill = $all_skills->fetch_assoc()):
                        $color_class = $skill_colors[$color_index % count($skill_colors)];
                        $color_index++;
                    ?>
                        <span class="skill-badge <?php echo $color_class; ?>">
                            <?php echo htmlspecialchars($skill['skill_name']); ?>
                        </span>
                    <?php endwhile; ?>
                    <?php if ($enrolled_courses_count == 0): ?>
                        <span class="text-sm text-gray-500 italic">No active enrollments</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-filter text-blue-500"></i>
                    Filter Attendance Records
                </h2>

                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Month Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <select name="month"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $selected_month == $num ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <select name="year"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Skill Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course/Skill</label>
                        <select name="skill_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="0">All Courses</option>
                            <?php
                            $all_skills->data_seek(0);
                            while ($skill = $all_skills->fetch_assoc()): ?>
                                <option value="<?php echo $skill['id']; ?>" <?php echo $selected_skill == $skill['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i> Apply Filters
                        </button>
                        <a href="attendance.php"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Overall Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total Days -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Days</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $overall_stats['total_days']; ?></h3>
                            <p class="text-xs text-gray-500 mt-1">Across all courses</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Present Days -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Present</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $overall_stats['present_days']; ?></h3>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo $overall_stats['total_days'] > 0 ? round(($overall_stats['present_days'] / $overall_stats['total_days']) * 100, 1) : 0; ?>% of total
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Absent Days -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Absent</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $overall_stats['absent_days']; ?></h3>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo $overall_stats['total_days'] > 0 ? round(($overall_stats['absent_days'] / $overall_stats['total_days']) * 100, 1) : 0; ?>% of total
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Attendance Percentage -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Overall Attendance</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $overall_stats['attendance_percentage']; ?>%</h3>
                            <p class="text-xs text-gray-500 mt-1">Average across courses</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary by Course -->
            <?php if ($attendance_summary->num_rows > 0): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-chart-bar text-blue-500"></i>
                            Course-wise Attendance Summary (<?php echo $months[$selected_month] . ' ' . $selected_year; ?>)
                        </h2>
                        <div class="text-sm text-gray-500">
                            <?php echo $attendance_summary->num_rows; ?> course(s)
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php
                        $course_colors = ['course-web-dev', 'course-cyber', 'course-data', 'course-graphics'];
                        $color_index = 0;
                        while ($summary = $attendance_summary->fetch_assoc()):
                            $percentage = $summary['attendance_percentage'];
                            $status_class = '';
                            $status_text = '';
                            $circle_class = '';

                            if ($percentage >= 90) {
                                $status_class = 'status-present';
                                $status_text = 'Excellent';
                                $circle_class = 'percentage-excellent';
                            } elseif ($percentage >= 75) {
                                $status_class = 'status-present';
                                $status_text = 'Good';
                                $circle_class = 'percentage-good';
                            } elseif ($percentage >= 60) {
                                $status_class = 'status-late';
                                $status_text = 'Average';
                                $circle_class = 'percentage-good';
                            } else {
                                $status_class = 'status-absent';
                                $status_text = 'Poor';
                                $circle_class = 'percentage-poor';
                            }

                            $course_color = $course_colors[$color_index % count($course_colors)];
                            $color_index++;
                        ?>
                            <div class="course-card <?php echo $course_color; ?> bg-white rounded-lg border border-gray-200 p-5">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-lg mb-1"><?php echo htmlspecialchars($summary['skill_name']); ?></h3>
                                        <?php if (!empty($summary['skill_description'])): ?>
                                            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($summary['skill_description']); ?></p>
                                        <?php endif; ?>
                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <?php if (!empty($summary['batch_name'])): ?>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-users"></i> <?php echo htmlspecialchars($summary['batch_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($summary['session_name'])): ?>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($summary['session_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="percentage-circle <?php echo $circle_class; ?>">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>

                                <div class="grid grid-cols-4 gap-2 mb-3">
                                    <div class="text-center p-2 bg-blue-50 rounded">
                                        <div class="text-lg font-bold text-blue-700"><?php echo $summary['total_days']; ?></div>
                                        <div class="text-xs text-blue-600">Total Days</div>
                                    </div>
                                    <div class="text-center p-2 bg-green-50 rounded">
                                        <div class="text-lg font-bold text-green-700"><?php echo $summary['present_days']; ?></div>
                                        <div class="text-xs text-green-600">Present</div>
                                    </div>
                                    <div class="text-center p-2 bg-red-50 rounded">
                                        <div class="text-lg font-bold text-red-700"><?php echo $summary['absent_days']; ?></div>
                                        <div class="text-xs text-red-600">Absent</div>
                                    </div>
                                    <div class="text-center p-2 bg-yellow-50 rounded">
                                        <div class="text-lg font-bold text-yellow-700"><?php echo $summary['late_days']; ?></div>
                                        <div class="text-xs text-yellow-600">Late</div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="attendance-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                    <a href="attendance.php?skill_id=<?php echo $summary['skill_id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>"
                                        class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Attendance Records -->
                <div class="bg-white rounded-xl border border-gray-200 p-12 text-center mb-8">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar-times text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-4">No Attendance Records Found</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        No attendance records found for <?php echo $months[$selected_month] . ' ' . $selected_year; ?>.
                        Attendance records will appear here once your teacher marks them.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="my_courses.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-book"></i>
                            View My Courses
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daily Attendance Details -->
            <?php if ($daily_attendance->num_rows > 0): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-calendar-day text-green-500"></i>
                            Daily Attendance Details
                            <?php if ($selected_skill > 0): ?>
                                <span class="text-sm font-normal text-gray-600 ml-2">
                                    (Filtered by:
                                    <?php
                                    $all_skills->data_seek(0);
                                    while ($skill = $all_skills->fetch_assoc()) {
                                        if ($skill['id'] == $selected_skill) {
                                            echo htmlspecialchars($skill['skill_name']);
                                            break;
                                        }
                                    }
                                    ?>
                                    )
                                </span>
                            <?php endif; ?>
                        </h2>
                        <div class="text-sm text-gray-500">
                            <?php echo $daily_attendance->num_rows; ?> record(s)
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date & Day</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch & Timing</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $current_date = null;
                                while ($record = $daily_attendance->fetch_assoc()):
                                    $date = new DateTime($record['attendance_date']);
                                    $day_name = $date->format('l');
                                    $formatted_date = $date->format('d M Y');

                                    // Show date header if it's a new date
                                    if ($current_date != $record['attendance_date']) {
                                        $current_date = $record['attendance_date'];
                                ?>
                                        <tr class="day-header">
                                            <td colspan="7" class="py-2 px-4">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-calendar-day text-blue-500"></i>
                                                    <span class="font-medium"><?php echo $day_name . ', ' . $formatted_date; ?></span>
                                                    <span class="text-xs text-gray-500 ml-2">
                                                        <?php
                                                        // Count records for this date
                                                        $date_count = 0;
                                                        $temp_pointer = $daily_attendance->num_rows;
                                                        $daily_attendance->data_seek(0);
                                                        while ($temp = $daily_attendance->fetch_assoc()) {
                                                            if ($temp['attendance_date'] == $current_date) {
                                                                $date_count++;
                                                            }
                                                        }
                                                        // Reset pointer back
                                                        $daily_attendance->data_seek($temp_pointer);
                                                        echo $date_count . ' class' . ($date_count > 1 ? 'es' : '');
                                                        ?>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>

                                    <?php
                                    // Determine status badge class
                                    $status_value = $record['status'];
                                    $status_class = '';
                                    $status_text = '';

                                    if ($status_value === 'present' || $status_value == '1' || $status_value == 1) {
                                        $status_class = 'status-present';
                                        $status_text = 'Present';
                                    } elseif ($status_value === 'absent' || $status_value == '0' || $status_value == 0) {
                                        $status_class = 'status-absent';
                                        $status_text = 'Absent';
                                    } elseif ($status_value === 'late') {
                                        $status_class = 'status-late';
                                        $status_text = 'Late';
                                    } elseif ($status_value === 'holiday') {
                                        $status_class = 'status-holiday';
                                        $status_text = 'Holiday';
                                    } else {
                                        // Default to present if numeric 1
                                        $status_class = $status_value == 1 ? 'status-present' : 'status-absent';
                                        $status_text = $status_value == 1 ? 'Present' : 'Absent';
                                    }

                                    // Determine percentage circle color
                                    $percentage = $record['attendance_percentage'] ?? 0;
                                    $circle_class = '';
                                    if ($percentage >= 90) {
                                        $circle_class = 'percentage-excellent';
                                    } elseif ($percentage >= 75) {
                                        $circle_class = 'percentage-good';
                                    } else {
                                        $circle_class = 'percentage-poor';
                                    }

                                    // Get skill color class
                                    $skill_color_class = '';
                                    switch (strtolower($record['skill_name'])) {
                                        case strpos(strtolower($record['skill_name']), 'web') !== false:
                                            $skill_color_class = 'skill-web-dev';
                                            break;
                                        case strpos(strtolower($record['skill_name']), 'cyber') !== false:
                                            $skill_color_class = 'skill-cyber';
                                            break;
                                        case strpos(strtolower($record['skill_name']), 'data') !== false:
                                            $skill_color_class = 'skill-data';
                                            break;
                                        case strpos(strtolower($record['skill_name']), 'graphic') !== false:
                                            $skill_color_class = 'skill-graphics';
                                            break;
                                        default:
                                            $skill_color_class = 'skill-web-dev';
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <!-- Date already shown in header -->
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($record['skill_name']); ?></span>
                                                <?php if (!empty($record['skill_description'])): ?>
                                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars($record['skill_description']); ?></span>
                                                <?php endif; ?>
                                                <span class="<?php echo $skill_color_class; ?> skill-badge mt-1">
                                                    <?php echo htmlspecialchars($record['skill_name']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-col">
                                                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($record['batch_name'] ?? 'N/A'); ?></span>
                                                <?php if (!empty($record['start_time']) && !empty($record['end_time'])): ?>
                                                    <span class="text-sm text-gray-500">
                                                        <?php echo date("h:i A", strtotime($record['start_time'])) . " - " . date("h:i A", strtotime($record['end_time'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($record['session_name'])): ?>
                                                    <span class="text-xs text-gray-400"><?php echo htmlspecialchars($record['session_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="attendance-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="percentage-circle <?php echo $circle_class; ?>">
                                                <?php echo $percentage; ?>%
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <?php echo htmlspecialchars($record['teacher_name'] ?? 'N/A'); ?>
                                                <?php if (!empty($record['teacher_code'])): ?>
                                                    <br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($record['teacher_code']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700 text-sm max-w-xs" title="<?php echo htmlspecialchars($record['remarks'] ?? '-'); ?>">
                                                <?php echo htmlspecialchars($record['remarks'] ?? '-'); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Legend -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-key text-gray-500"></i>
                            Attendance Status Legend
                        </h3>
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center gap-2">
                                <span class="attendance-badge status-present">Present</span>
                                <span class="text-sm text-gray-600">Attended class on time</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="attendance-badge status-absent">Absent</span>
                                <span class="text-sm text-gray-600">Did not attend class</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="attendance-badge status-late">Late</span>
                                <span class="text-sm text-gray-600">Attended but arrived late</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="attendance-badge status-holiday">Holiday</span>
                                <span class="text-sm text-gray-600">Official holiday</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Percentage Color Code:</h4>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <div class="percentage-circle percentage-excellent">90%</div>
                                <span class="text-sm text-gray-600">Excellent (90%+)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="percentage-circle percentage-good">75%</div>
                                <span class="text-sm text-gray-600">Good (75-89%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="percentage-circle percentage-poor">60%</div>
                                <span class="text-sm text-gray-600">Poor (Below 75%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add tooltips for course descriptions
            const courseDescriptions = document.querySelectorAll('[title]');
            courseDescriptions.forEach(desc => {
                desc.addEventListener('mouseenter', function(e) {
                    if (this.scrollWidth > this.clientWidth) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'fixed bg-gray-800 text-white text-sm px-2 py-1 rounded shadow-lg z-50';
                        tooltip.textContent = this.title;
                        document.body.appendChild(tooltip);

                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = rect.left + 'px';
                        tooltip.style.top = (rect.bottom + 5) + 'px';

                        this._tooltip = tooltip;
                    }
                });

                desc.addEventListener('mouseleave', function() {
                    if (this._tooltip) {
                        this._tooltip.remove();
                        this._tooltip = null;
                    }
                });
            });
        });
    </script>

</body>

</html>
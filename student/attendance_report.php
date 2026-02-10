<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch student info
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

// Get filters from URL
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get enrolled courses for dropdown
$enrolled_query = "
SELECT DISTINCT 
    s.id AS skill_id,
    s.skill_name,
    b.id AS batch_id,
    b.batch_name,
    b.start_time,
    b.end_time,
    t.name AS teacher_name,
    ses.session_name
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
LEFT JOIN batch_teachers bt ON b.id = bt.batch_id
LEFT JOIN teachers t ON bt.teacher_id = t.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name, b.batch_name
";

$stmt_enrolled = $conn->prepare($enrolled_query);
$stmt_enrolled->bind_param("i", $student_id);
$stmt_enrolled->execute();
$enrolled_courses = $stmt_enrolled->get_result();

// Default to first course if none selected
if ($skill_id === 0 && $enrolled_courses->num_rows > 0) {
    $first_course = $enrolled_courses->fetch_assoc();
    $skill_id = $first_course['skill_id'];
    $batch_id = $first_course['batch_id'];
    mysqli_data_seek($enrolled_courses, 0); // Reset pointer
}

// Initialize overall stats
$overall_stats = [
    'total_courses' => 0,
    'total_classes' => 0,
    'total_present' => 0,
    'total_absent' => 0,
    'total_late' => 0,
    'total_leave' => 0,
    'overall_percentage' => 0
];

$course_stats = [];

// Fetch stats for all courses
$stats_query = "
SELECT 
    s.id AS skill_id,
    s.skill_name,
    b.id AS batch_id,
    b.batch_name,
    COUNT(sa.id) AS total_classes,
    SUM(CASE WHEN sa.attendance_status = 'present' THEN 1 ELSE 0 END) AS present,
    SUM(CASE WHEN sa.attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent,
    SUM(CASE WHEN sa.attendance_status = 'late' THEN 1 ELSE 0 END) AS late,
    SUM(CASE WHEN sa.attendance_status = 'leave' THEN 1 ELSE 0 END) AS on_leave
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
LEFT JOIN student_attendance sa 
    ON se.student_id = sa.student_id
   AND se.skill_id = sa.skill_id
   AND se.batch_id = sa.batch_id
   AND sa.status = 'active'
   AND YEAR(sa.attendance_date) = ?
WHERE se.student_id = ? AND se.status = 'active'
";

$params = [$year, $student_id];
$types = "ii";

if ($skill_id > 0) {
    $stats_query .= " AND s.id = ?";
    $params[] = $skill_id;
    $types .= "i";
}

if ($batch_id > 0) {
    $stats_query .= " AND b.id = ?";
    $params[] = $batch_id;
    $types .= "i";
}

$stats_query .= " GROUP BY s.id, b.id ORDER BY s.skill_name, b.batch_name";

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param($types, ...$params);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();

while ($row = $stats_result->fetch_assoc()) {
    $percentage = $row['total_classes'] > 0 ? round(($row['present'] / $row['total_classes']) * 100, 1) : 0;

    $course_stats[] = [
        'skill_id' => $row['skill_id'],
        'skill_name' => $row['skill_name'],
        'batch_id' => $row['batch_id'],
        'batch_name' => $row['batch_name'],
        'total_classes' => $row['total_classes'],
        'present' => $row['present'],
        'absent' => $row['absent'],
        'late' => $row['late'],
        'leave' => $row['on_leave'], // <- mapped correctly
        'percentage' => $percentage
    ];

    // Update overall stats
    $overall_stats['total_courses']++;
    $overall_stats['total_classes'] += $row['total_classes'];
    $overall_stats['total_present'] += $row['present'];
    $overall_stats['total_absent'] += $row['absent'];
    $overall_stats['total_late'] += $row['late'];
    $overall_stats['total_leave'] += $row['on_leave'];
}

// Calculate overall percentage
if ($overall_stats['total_classes'] > 0) {
    $overall_stats['overall_percentage'] = round(($overall_stats['total_present'] / $overall_stats['total_classes']) * 100, 1);
}

// Fetch monthly trends for selected course
$monthly_trends = [];
if ($skill_id > 0 && $batch_id > 0) {
    $monthly_query = "
    SELECT 
        MONTH(attendance_date) AS month_num,
        YEAR(attendance_date) AS year_num,
        COUNT(*) AS total_classes,
        SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) AS late,
        SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) AS on_leave
    FROM student_attendance
    WHERE student_id = ? AND skill_id = ? AND batch_id = ?
      AND YEAR(attendance_date) = ? AND status = 'active'
    GROUP BY YEAR(attendance_date), MONTH(attendance_date)
    ORDER BY month_num
    ";

    $stmt_monthly = $conn->prepare($monthly_query);
    $stmt_monthly->bind_param("iiii", $student_id, $skill_id, $batch_id, $year);
    $stmt_monthly->execute();
    $monthly_result = $stmt_monthly->get_result();

    while ($row = $monthly_result->fetch_assoc()) {
        $percentage = $row['total_classes'] > 0 ? round(($row['present'] / $row['total_classes']) * 100, 1) : 0;

        $monthly_trends[] = [
            'month' => date('F', mktime(0, 0, 0, $row['month_num'], 1)),
            'month_num' => $row['month_num'],
            'total' => $row['total_classes'],
            'present' => $row['present'],
            'absent' => $row['absent'],
            'late' => $row['late'],
            'leave' => $row['on_leave'], // <- mapped correctly
            'percentage' => $percentage
        ];
    }
}

// Fetch course info if selected
$course_info = null;
if ($skill_id > 0 && $batch_id > 0) {
    $course_info_query = "
    SELECT 
        s.skill_name,
        b.batch_name,
        b.start_time,
        b.end_time,
        t.name AS teacher_name,
        ses.session_name
    FROM student_enrollments se
    JOIN skills s ON se.skill_id = s.id
    JOIN batches b ON se.batch_id = b.id
    JOIN sessions ses ON se.session_id = ses.id
    LEFT JOIN batch_teachers bt ON b.id = bt.batch_id
    LEFT JOIN teachers t ON bt.teacher_id = t.id
    WHERE se.student_id = ? AND se.skill_id = ? AND se.batch_id = ?
    LIMIT 1
    ";

    $stmt_course = $conn->prepare($course_info_query);
    $stmt_course->bind_param("iii", $student_id, $skill_id, $batch_id);
    $stmt_course->execute();
    $course_info = $stmt_course->get_result()->fetch_assoc();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>Attendance Report | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .report-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
        }

        .percentage-circle {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .percentage-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        .trend-stable { color: #6b7280; }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .dot-excellent { background: #10b981; }
        .dot-good { background: #3b82f6; }
        .dot-average { background: #f59e0b; }
        .dot-poor { background: #ef4444; }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .filter-tab.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-tab:hover:not(.active) {
            background: #f1f5f9;
            border-color: #e5e7eb;
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
                        <h1 class="text-3xl font-bold text-gray-800">Attendance Report</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                            Comprehensive attendance analysis and statistics
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span class="font-medium">Year: <?php echo $year; ?></span>
                        </div>
                        <a href="attendance.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-calendar-check"></i>
                            View Calendar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="report-card p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Overall Attendance Summary</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-white/90 mb-2">Overall Attendance</h3>
                                <p class="text-3xl font-bold text-white">
                                    <?php echo $overall_stats['overall_percentage']; ?>%
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-white/80 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php echo $overall_stats['total_present']; ?> of <?php echo $overall_stats['total_classes']; ?> classes
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Courses</h3>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $overall_stats['total_courses']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-book-open text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Total enrolled courses
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Present</h3>
                                <p class="text-3xl font-bold text-green-600"><?php echo $overall_stats['total_present']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Total classes attended
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Absent</h3>
                                <p class="text-3xl font-bold text-red-600"><?php echo $overall_stats['total_absent']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Total classes missed
                        </p>
                    </div>
                </div>

                <!-- Status Indicators -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <span class="status-dot dot-excellent"></span>
                        <span class="text-sm text-gray-600">Excellent (90%+)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="status-dot dot-good"></span>
                        <span class="text-sm text-gray-600">Good (75-89%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="status-dot dot-average"></span>
                        <span class="text-sm text-gray-600">Average (50-74%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="status-dot dot-poor"></span>
                        <span class="text-sm text-gray-600">Needs Improvement (&lt;50%)</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="report-card p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 mb-2">Filter Report</h2>
                        <p class="text-sm text-gray-600">View detailed reports for specific courses and years</p>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex gap-2">
                        <?php if ($skill_id > 0): ?>
                            <a href="print_report.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>&year=<?php echo $year; ?>" 
                               target="_blank"
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                                <i class="fas fa-print"></i>
                                Print Report
                            </a>
                        <?php endif; ?>
                        <a href="export_attendance.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>&year=<?php echo $year; ?>" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-download"></i>
                            Export CSV
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Course Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Course</label>
                        <select id="courseSelect" onchange="changeCourse()" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- All Courses --</option>
                            <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['skill_id']; ?>_<?php echo $course['batch_id']; ?>"
                                        <?php echo ($skill_id == $course['skill_id'] && $batch_id == $course['batch_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['skill_name'] . ' - ' . $course['batch_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Year Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Year</label>
                        <select id="yearSelect" onchange="changeYear()" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- View Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">View Options</label>
                        <div class="flex gap-2">
                            <a href="attendance_report.php" 
                               class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-center py-2 rounded-lg">
                                Reset
                            </a>
                            <button onclick="toggleChartView()" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Toggle Chart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Information -->
            <?php if ($course_info): ?>
                <div class="report-card p-6 mb-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($course_info['skill_name']); ?></h2>
                            <div class="flex flex-wrap gap-3">
                                <span class="text-gray-600">
                                    <i class="fas fa-users mr-1"></i>
                                    Batch: <?php echo htmlspecialchars($course_info['batch_name']); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="far fa-clock mr-1"></i>
                                    Timing: <?php echo date("h:i A", strtotime($course_info['start_time'])); ?> - <?php echo date("h:i A", strtotime($course_info['end_time'])); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                                    Teacher: <?php echo htmlspecialchars($course_info['teacher_name'] ?? 'Not Assigned'); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Session: <?php echo htmlspecialchars($course_info['session_name']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600"><?php echo $year; ?></div>
                            <div class="text-sm text-gray-500">Academic Year</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Course-wise Statistics -->
            <div class="report-card overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800">Course-wise Attendance Statistics</h2>
                    <p class="text-sm text-gray-600 mt-1">Detailed breakdown by course for <?php echo $year; ?></p>
                </div>

                <?php if (count($course_stats) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Classes</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Absent</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Late/Leave</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Attendance %</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($course_stats as $stat): 
                                    $percentage_class = '';
                                    $status_dot = '';
                                    
                                    if ($stat['percentage'] >= 90) {
                                        $percentage_class = 'text-green-600';
                                        $status_dot = 'dot-excellent';
                                    } elseif ($stat['percentage'] >= 75) {
                                        $percentage_class = 'text-blue-600';
                                        $status_dot = 'dot-good';
                                    } elseif ($stat['percentage'] >= 50) {
                                        $percentage_class = 'text-yellow-600';
                                        $status_dot = 'dot-average';
                                    } else {
                                        $percentage_class = 'text-red-600';
                                        $status_dot = 'dot-poor';
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($stat['skill_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($stat['batch_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700 font-medium"><?php echo $stat['total_classes']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                                    <?php echo $stat['present']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                                    <?php echo $stat['absent']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                                    <?php echo $stat['late'] + $stat['leave']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <div class="<?php echo $percentage_class; ?> font-bold"><?php echo $stat['percentage']; ?>%</div>
                                                <div class="progress-bar flex-1">
                                                    <div class="progress-fill <?php echo str_replace('text-', 'bg-', $percentage_class); ?>" 
                                                         style="width: <?php echo min($stat['percentage'], 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <span class="status-dot <?php echo $status_dot; ?>"></span>
                                                <span class="text-sm text-gray-600">
                                                    <?php 
                                                    if ($stat['percentage'] >= 90) echo 'Excellent';
                                                    elseif ($stat['percentage'] >= 75) echo 'Good';
                                                    elseif ($stat['percentage'] >= 50) echo 'Average';
                                                    else echo 'Needs Improvement';
                                                    ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="attendance.php?skill_id=<?php echo $stat['skill_id']; ?>&batch_id=<?php echo $stat['batch_id']; ?>"
                                               class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center gap-1">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-line text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Attendance Data</h3>
                        <p class="text-gray-500 mb-6">No attendance records found for the selected filters.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Charts Section -->
            <?php if (count($monthly_trends) > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Monthly Trend Chart -->
                    <div class="report-card p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Monthly Attendance Trend - <?php echo $year; ?></h2>
                        <div class="chart-container">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Attendance Distribution Chart -->
                    <div class="report-card p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Attendance Distribution</h2>
                        <div class="chart-container">
                            <canvas id="attendanceDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Monthly Breakdown -->
            <?php if (count($monthly_trends) > 0): ?>
                <div class="report-card p-6 mb-8">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Monthly Breakdown - <?php echo $year; ?></h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Month</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Classes</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Absent</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Late</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Leave</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Attendance %</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Trend</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                $prev_percentage = null;
                                foreach ($monthly_trends as $index => $trend): 
                                    $percentage_class = $trend['percentage'] >= 90 ? 'text-green-600' : 
                                                       ($trend['percentage'] >= 75 ? 'text-blue-600' : 
                                                       ($trend['percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600'));
                                    
                                    // Determine trend
                                    $trend_icon = '';
                                    $trend_class = 'trend-stable';
                                    
                                    if ($prev_percentage !== null) {
                                        if ($trend['percentage'] > $prev_percentage) {
                                            $trend_icon = 'fas fa-arrow-up';
                                            $trend_class = 'trend-up';
                                        } elseif ($trend['percentage'] < $prev_percentage) {
                                            $trend_icon = 'fas fa-arrow-down';
                                            $trend_class = 'trend-down';
                                        }
                                    }
                                    $prev_percentage = $trend['percentage'];
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo $trend['month']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo $trend['total']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo $trend['present']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo $trend['absent']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo $trend['late']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo $trend['leave']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <div class="<?php echo $percentage_class; ?> font-bold"><?php echo $trend['percentage']; ?>%</div>
                                                <div class="progress-bar flex-1">
                                                    <div class="progress-fill <?php echo str_replace('text-', 'bg-', $percentage_class); ?>" 
                                                         style="width: <?php echo min($trend['percentage'], 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($trend_icon): ?>
                                                <i class="<?php echo $trend_icon; ?> <?php echo $trend_class; ?>"></i>
                                                <span class="text-sm <?php echo $trend_class; ?>">
                                                    <?php 
                                                    if ($prev_percentage !== null && $index > 0) {
                                                        $change = $trend['percentage'] - $monthly_trends[$index-1]['percentage'];
                                                        echo ($change > 0 ? '+' : '') . round($change, 1) . '%';
                                                    }
                                                    ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Summary & Recommendations -->
            <div class="report-card p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Summary & Recommendations</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Performance Summary -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Performance Summary</h3>
                        <div class="space-y-3">
                            <?php if ($overall_stats['overall_percentage'] >= 90): ?>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-trophy text-green-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-800 mb-1">Excellent Performance!</h4>
                                            <p class="text-sm text-gray-600">Your attendance is outstanding. Keep up the great work!</p>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($overall_stats['overall_percentage'] >= 75): ?>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-thumbs-up text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-800 mb-1">Good Performance</h4>
                                            <p class="text-sm text-gray-600">Your attendance is good. Try to maintain consistency.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($overall_stats['overall_percentage'] >= 50): ?>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-800 mb-1">Needs Attention</h4>
                                            <p class="text-sm text-gray-600">Your attendance needs improvement. Try to attend more classes.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-exclamation-circle text-red-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-800 mb-1">Critical Attention Needed</h4>
                                            <p class="text-sm text-gray-600">Your attendance is very low. Immediate improvement is required.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Recommendations</h3>
                        <div class="space-y-3">
                            <?php 
                            $recommendations = [];
                            
                            if ($overall_stats['total_absent'] > 0) {
                                $recommendations[] = "You have missed {$overall_stats['total_absent']} classes. Try to reduce absences.";
                            }
                            
                            if ($overall_stats['total_late'] > 0) {
                                $recommendations[] = "You were late {$overall_stats['total_late']} times. Try to arrive on time.";
                            }
                            
                            if ($overall_stats['overall_percentage'] < 75) {
                                $recommendations[] = "Aim for at least 75% attendance to maintain good academic standing.";
                            }
                            
                            if (empty($recommendations)) {
                                $recommendations[] = "You're doing great! Maintain your current attendance level.";
                            }
                            ?>
                            
                            <?php foreach ($recommendations as $rec): ?>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center mt-1">
                                        <i class="fas fa-lightbulb text-gray-400 text-xs"></i>
                                    </div>
                                    <p class="text-sm text-gray-600"><?php echo $rec; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Change course function
        function changeCourse() {
            const select = document.getElementById('courseSelect');
            const yearSelect = document.getElementById('yearSelect');
            const value = select.value;
            const year = yearSelect.value;
            
            if (value) {
                const [skill_id, batch_id] = value.split('_');
                window.location.href = `attendance_report.php?skill_id=${skill_id}&batch_id=${batch_id}&year=${year}`;
            } else {
                window.location.href = `attendance_report.php?year=${year}`;
            }
        }

        // Change year function
        function changeYear() {
            const yearSelect = document.getElementById('yearSelect');
            const courseSelect = document.getElementById('courseSelect');
            const year = yearSelect.value;
            
            if (courseSelect.value) {
                const [skill_id, batch_id] = courseSelect.value.split('_');
                window.location.href = `attendance_report.php?skill_id=${skill_id}&batch_id=${batch_id}&year=${year}`;
            } else {
                window.location.href = `attendance_report.php?year=${year}`;
            }
        }

        // Toggle chart view
        function toggleChartView() {
            const charts = document.querySelectorAll('.chart-container');
            charts.forEach(chart => {
                chart.classList.toggle('hidden');
            });
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (count($monthly_trends) > 0): ?>
                // Monthly Trend Chart
                const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
                const monthlyChart = new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: [<?php echo implode(',', array_map(function($t) { return "'" . $t['month'] . "'"; }, $monthly_trends)); ?>],
                        datasets: [{
                            label: 'Attendance %',
                            data: [<?php echo implode(',', array_column($monthly_trends, 'percentage')); ?>],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Attendance: ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        }
                    }
                });

                // Attendance Distribution Chart
                <?php 
                if (count($course_stats) > 0 && $skill_id > 0 && $batch_id > 0) {
                    $current_course = array_filter($course_stats, function($c) use ($skill_id, $batch_id) {
                        return $c['skill_id'] == $skill_id && $c['batch_id'] == $batch_id;
                    });
                    $current_course = reset($current_course);
                }
                ?>
                
                const distributionCtx = document.getElementById('attendanceDistributionChart').getContext('2d');
                const distributionChart = new Chart(distributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Present', 'Absent', 'Late', 'Leave'],
                        datasets: [{
                            data: [
                                <?php echo $current_course['present'] ?? 0; ?>,
                                <?php echo $current_course['absent'] ?? 0; ?>,
                                <?php echo $current_course['late'] ?? 0; ?>,
                                <?php echo $current_course['leave'] ?? 0; ?>
                            ],
                            backgroundColor: [
                                '#10b981',
                                '#ef4444',
                                '#f59e0b',
                                '#6366f1'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>

</body>

</html>
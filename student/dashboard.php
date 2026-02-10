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
$_SESSION['student_id'] = $student_id;

// Fetch enrolled courses with skill details
$enrollment_query = "
SELECT 
    se.id as enrollment_id,
    se.admission_date,
    se.status,
    s.id as skill_id,
    s.skill_name,
    s.duration_months,
    s.level,
    b.batch_name,
    b.start_time,
    b.end_time,
    ses.session_name,
    fs.total_fee,
    t.name as teacher_name
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
LEFT JOIN fee_structures fs ON s.id = fs.skill_id AND se.session_id = fs.session_id
LEFT JOIN batch_teachers bt ON b.id = bt.batch_id
LEFT JOIN teachers t ON bt.teacher_id = t.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY se.admission_date DESC
";

$stmt = $conn->prepare($enrollment_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrollments = $stmt->get_result();
$total_courses = $enrollments->num_rows;

// Calculate total fees
$total_fees = 0;
$paid_fees = 0;
$pending_fees = 0;

if ($total_courses > 0) {
    mysqli_data_seek($enrollments, 0);
    while ($course = $enrollments->fetch_assoc()) {
        $total_fees += $course['total_fee'] ?? 0;
    }
    mysqli_data_seek($enrollments, 0);
}

// Get paid fees
$fee_query = "SELECT SUM(amount_paid) as total_paid FROM fee_collections WHERE student_id = ?";
$stmt_fee = $conn->prepare($fee_query);
$stmt_fee->bind_param("i", $student_id);
$stmt_fee->execute();
$fee_result = $stmt_fee->get_result()->fetch_assoc();
$paid_fees = $fee_result['total_paid'] ?? 0;
$pending_fees = max(0, $total_fees - $paid_fees);

// Get attendance summary
$attendance_query = "
SELECT 
    COUNT(*) as total_classes,
    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_classes
FROM student_attendance 
WHERE student_id = ? AND status = 'active'
";

$stmt_att = $conn->prepare($attendance_query);
$stmt_att->bind_param("i", $student_id);
$stmt_att->execute();
$att_result = $stmt_att->get_result()->fetch_assoc();

$total_classes = $att_result['total_classes'] ?? 0;
$present_classes = $att_result['present_classes'] ?? 0;
$attendance_percentage = $total_classes > 0 ? round(($present_classes / $total_classes) * 100, 1) : 0;

// Get today's schedule
$today = date('Y-m-d');
$current_time = date('H:i:s');

$today_schedule_query = "
SELECT 
    s.skill_name,
    b.batch_name,
    b.start_time,
    b.end_time,
    ses.session_name
FROM student_enrollments se
JOIN batches b ON se.batch_id = b.id
JOIN skills s ON se.skill_id = s.id
JOIN sessions ses ON se.session_id = ses.id
WHERE se.student_id = ? 
  AND se.status = 'active'
  AND TIME('$current_time') BETWEEN b.start_time AND b.end_time
LIMIT 1
";


$stmt_schedule = $conn->prepare($today_schedule_query);
$stmt_schedule->bind_param("i", $student_id);
$stmt_schedule->execute();
$today_class = $stmt_schedule->get_result()->fetch_assoc();

// Get recent announcements
$announcements_query = "
SELECT title, message, created_at, priority 
FROM announcements 
WHERE (target_role = 'all' OR target_role = 'student')
AND status = 'active' 
AND (start_date IS NULL OR start_date <= NOW())
AND (end_date IS NULL OR end_date >= NOW())
AND is_expired = 0
ORDER BY created_at DESC 
LIMIT 3
";
$announcements = $conn->query($announcements_query);

// Get progress data
$progress_query = "
SELECT 
    sp.skill_id,
    s.skill_name,
    sp.topics_completed,
    sp.total_topics,
    ROUND(
        (sp.topics_completed / NULLIF(sp.total_topics, 0)) * 100, 
        2
    ) AS completion_percent
FROM skill_progress sp
JOIN skills s ON sp.skill_id = s.id
WHERE sp.enrollment_id IN (
    SELECT id 
    FROM student_enrollments 
    WHERE student_id = ?
)
ORDER BY sp.last_updated DESC
LIMIT 3
";


$progress_data = [];
if ($stmt_progress = $conn->prepare($progress_query)) {
    $stmt_progress->bind_param("i", $student_id);
    $stmt_progress->execute();
    $progress_result = $stmt_progress->get_result();
    while ($row = $progress_result->fetch_assoc()) {
        $progress_data[] = $row;
    }
} else {
    // If skill_progress table doesn't exist, calculate from enrollments
    $progress_data = [];
}

// Get average progress
$avg_progress = 0;
if (!empty($progress_data)) {
    $total = 0;
    foreach ($progress_data as $progress) {
        $total += $progress['completion_percent'];
    }
    $avg_progress = round($total / count($progress_data), 1);
}

// Check today's attendance
$today_attendance_query = "
SELECT attendance_status 
FROM student_attendance 
WHERE student_id = ? AND attendance_date = CURDATE() AND status = 'active'
LIMIT 1
";
$stmt_today = $conn->prepare($today_attendance_query);
$stmt_today->bind_param("i", $student_id);
$stmt_today->execute();
$today_att_result = $stmt_today->get_result()->fetch_assoc();
$today_present = isset($today_att_result['attendance_status']) && $today_att_result['attendance_status'] == 'present';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Student Dashboard | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
            --warning: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
    </style>
</head>

<body class="min-h-screen">

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
                        <h1 class="text-3xl font-bold text-gray-800">Student Dashboard</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-user-graduate text-blue-500 mr-2"></i>
                            Welcome back, <?php echo htmlspecialchars($student['name'] ?? 'Student'); ?>!
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <span class="text-sm font-medium">Student Code:</span>
                            <span class="font-bold ml-2"><?php echo htmlspecialchars($student['student_code'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-calendar-day mr-1"></i>
                            <?php echo date('F j, Y'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Enrolled Courses -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Enrolled Courses</h3>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $total_courses; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-book-open text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Total active courses you're enrolled in
                    </p>
                    <a href="my_courses.php" class="text-xs text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        <i class="fas fa-arrow-right mr-1"></i> View all courses
                    </a>
                </div>

                <!-- Attendance Percentage -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Attendance</h3>
                            <p class="text-3xl font-bold <?php echo $attendance_percentage >= 75 ? 'text-green-600' : ($attendance_percentage >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                <?php echo $attendance_percentage; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo $present_classes; ?>/<?php echo $total_classes; ?> classes
                            </p>
                        </div>
                        <div class="w-12 h-12 <?php echo $attendance_percentage >= 75 ? 'bg-green-100' : ($attendance_percentage >= 50 ? 'bg-yellow-100' : 'bg-red-100'); ?> rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-check <?php echo $attendance_percentage >= 75 ? 'text-green-600' : ($attendance_percentage >= 50 ? 'text-yellow-600' : 'text-red-600'); ?> text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($attendance_percentage, 100); ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <?php echo $attendance_percentage >= 75 ? 'Great attendance!' : ($attendance_percentage >= 50 ? 'Good, keep it up!' : 'Try to improve attendance'); ?>
                        </p>
                    </div>
                    <a href="attendance.php" class="text-xs text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        <i class="fas fa-calendar-alt mr-1"></i> View attendance details
                    </a>
                </div>

                <!-- Fee Status -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Fee Status</h3>
                            <p class="text-3xl font-bold <?php echo $pending_fees == 0 ? 'text-green-600' : 'text-yellow-600'; ?>">
                                Rs<?php echo number_format($paid_fees); ?>
                            </p>
                            <p class="text-xs <?php echo $pending_fees == 0 ? 'text-green-600' : 'text-gray-500'; ?> mt-1">
                                <?php echo $pending_fees == 0 ? 'All fees paid' : 'Rs' . number_format($pending_fees) . ' pending'; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 <?php echo $pending_fees == 0 ? 'bg-green-100' : 'bg-yellow-100'; ?> rounded-full flex items-center justify-center">
                            <i class="fas fa-rupee-sign <?php echo $pending_fees == 0 ? 'text-green-600' : 'text-yellow-600'; ?> text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Total fees: Rs<?php echo number_format($total_fees); ?>
                    </p>
                    <a href="fees.php" class="text-xs text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        <i class="fas fa-receipt mr-1"></i> View fee details
                    </a>
                </div>

                <!-- Today's Status -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Today's Status</h3>
                            <p class="text-2xl font-bold <?php echo $today_present ? 'text-green-600' : ($today_class ? 'text-blue-600' : 'text-gray-600'); ?>">
                                <?php 
                                if ($today_present) {
                                    echo "Present";
                                } elseif ($today_class) {
                                    echo "Class Active";
                                } else {
                                    echo "No Classes";
                                }
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 <?php echo $today_present ? 'bg-green-100' : ($today_class ? 'bg-blue-100' : 'bg-gray-100'); ?> rounded-full flex items-center justify-center">
                            <i class="fas <?php echo $today_present ? 'fa-check-circle text-green-600' : ($today_class ? 'fa-chalkboard-teacher text-blue-600' : 'fa-calendar-times text-gray-600'); ?> text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?php 
                        if ($today_present) {
                            echo "Attendance marked as present";
                        } elseif ($today_class) {
                            echo "Currently in class";
                        } else {
                            echo "No classes scheduled today";
                        }
                        ?>
                    </p>
                    <?php if ($today_class): ?>
                        <a href="my_courses.php" class="text-xs text-blue-600 hover:text-blue-800 mt-2 inline-block">
                            <i class="fas fa-external-link-alt mr-1"></i> Go to class
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Schedule & Announcements -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Today's Schedule -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-day text-blue-500"></i>
                        Today's Schedule
                    </h2>
                    <?php if ($today_class): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                            Current Class
                                        </span>
                                        <?php if ($today_present): ?>
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                                <i class="fas fa-check mr-1"></i>Present
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-1">
                                        <?php echo htmlspecialchars($today_class['skill_name']); ?>
                                    </h3>
                                    <p class="text-gray-600">
                                        <i class="fas fa-users mr-2"></i>
                                        Batch: <?php echo htmlspecialchars($today_class['batch_name']); ?>
                                    </p>
                                    <?php if (!empty($today_class['teacher_name'])): ?>
                                        <p class="text-gray-600">
                                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                                            Teacher: <?php echo htmlspecialchars($today_class['teacher_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Session</div>
                                    <div class="font-bold text-blue-600"><?php echo htmlspecialchars($today_class['session_name']); ?></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-blue-100">
                                <div class="text-center">
                                    <div class="text-sm text-gray-500">Start Time</div>
                                    <div class="font-bold text-gray-800">
                                        <?php echo date("h:i A", strtotime($today_class['start_time'])); ?>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-gray-500">End Time</div>
                                    <div class="font-bold text-gray-800">
                                        <?php echo date("h:i A", strtotime($today_class['end_time'])); ?>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-gray-500">Duration</div>
                                    <div class="font-bold text-gray-800">
                                        <?php
                                        $start = strtotime($today_class['start_time']);
                                        $end = strtotime($today_class['end_time']);
                                        $duration = round(($end - $start) / 3600, 1);
                                        echo $duration . ' hrs';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="my_courses.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-book-open"></i>
                                    Go to Course Materials
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-times text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No classes scheduled now</h3>
                            <p class="text-gray-500 text-sm mb-4">You don't have any classes at this time</p>
                            <a href="my_courses.php" class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center gap-1">
                                <i class="fas fa-calendar-alt"></i>
                                View weekly schedule
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Upcoming Classes -->
                    <?php
                    $upcoming_query = "
                    SELECT 
                        s.skill_name,
                        b.batch_name,
                        b.start_time,
                        b.end_time
                    FROM student_enrollments se
                    JOIN batches b ON se.batch_id = b.id
                    JOIN skills s ON se.skill_id = s.id
                    WHERE se.student_id = ? AND se.status = 'active'
                    AND TIME(b.start_time) > TIME('$current_time')
                    AND DATE(NOW()) = CURDATE()
                    ORDER BY b.start_time ASC
                    LIMIT 2
                    ";

                    if ($stmt_upcoming = $conn->prepare($upcoming_query)) {
                        $stmt_upcoming->bind_param("i", $student_id);
                        $stmt_upcoming->execute();
                        $upcoming_result = $stmt_upcoming->get_result();
                        
                        if ($upcoming_result->num_rows > 0): ?>
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                    <i class="fas fa-arrow-right text-green-500"></i>
                                    Upcoming Classes Today
                                </h3>
                                <div class="space-y-3">
                                    <?php while ($upcoming = $upcoming_result->fetch_assoc()): ?>
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($upcoming['skill_name']); ?></h4>
                                                    <p class="text-sm text-gray-600">
                                                        <i class="fas fa-users mr-1"></i>
                                                        <?php echo htmlspecialchars($upcoming['batch_name']); ?>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm text-gray-500">Starts at</div>
                                                    <div class="font-bold text-green-600">
                                                        <?php echo date("h:i A", strtotime($upcoming['start_time'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        <?php endif;
                    }
                    ?>
                </div>

                <!-- Announcements -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-bullhorn text-orange-500"></i>
                            Recent Announcements
                        </h2>
                        <a href="../announcements.php" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="fas fa-external-link-alt"></i>
                            View All
                        </a>
                    </div>

                    <?php if ($announcements->num_rows > 0): ?>
                        <div class="space-y-4">
                            <?php while ($announcement = $announcements->fetch_assoc()): 
                                $priority_class = '';
                                $icon = 'fas fa-bullhorn';
                                
                                switch ($announcement['priority']) {
                                    case 'high':
                                        $priority_class = 'border-l-4 border-red-500 bg-red-50';
                                        $icon = 'fas fa-exclamation-triangle text-red-500';
                                        break;
                                    case 'urgent':
                                        $priority_class = 'border-l-4 border-red-600 bg-red-50';
                                        $icon = 'fas fa-exclamation-circle text-red-600';
                                        break;
                                    case 'medium':
                                        $priority_class = 'border-l-4 border-yellow-500 bg-yellow-50';
                                        $icon = 'fas fa-info-circle text-yellow-500';
                                        break;
                                    default:
                                        $priority_class = 'border-l-4 border-green-500 bg-green-50';
                                        $icon = 'fas fa-info-circle text-green-500';
                                        break;
                                }
                            ?>
                                <div class="<?php echo $priority_class; ?> p-4 rounded-r-lg">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                            <p class="text-sm text-gray-600 mb-2">
                                                <?php 
                                                $message = $announcement['message'];
                                                if (strlen($message) > 100) {
                                                    echo htmlspecialchars(substr($message, 0, 100)) . '...';
                                                } else {
                                                    echo htmlspecialchars($message);
                                                }
                                                ?>
                                            </p>
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-gray-500">
                                                    <i class="far fa-clock mr-1"></i>
                                                    <?php echo date("M j, h:i A", strtotime($announcement['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-bullhorn text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No announcements</h3>
                            <p class="text-gray-500 text-sm">Check back later for updates</p>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Links -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Quick Links</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="my_courses.php" class="bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-3 text-center transition-colors">
                                <i class="fas fa-book-open text-blue-600 text-lg mb-1 block"></i>
                                <span class="text-xs font-medium text-gray-700">My Courses</span>
                            </a>
                            <a href="attendance.php" class="bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-3 text-center transition-colors">
                                <i class="fas fa-calendar-check text-green-600 text-lg mb-1 block"></i>
                                <span class="text-xs font-medium text-gray-700">Attendance</span>
                            </a>
                            <a href="fees.php" class="bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg p-3 text-center transition-colors">
                                <i class="fas fa-credit-card text-yellow-600 text-lg mb-1 block"></i>
                                <span class="text-xs font-medium text-gray-700">Fee Payment</span>
                            </a>
                            <a href="certificates.php" class="bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-3 text-center transition-colors">
                                <i class="fas fa-certificate text-purple-600 text-lg mb-1 block"></i>
                                <span class="text-xs font-medium text-gray-700">Certificates</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Courses Progress -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">My Courses</h2>
                    <a href="my_courses.php" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                        <i class="fas fa-external-link-alt"></i>
                        View All Courses
                    </a>
                </div>

                <?php if ($total_courses > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        $display_count = 0;
                        mysqli_data_seek($enrollments, 0);
                        while ($course = $enrollments->fetch_assoc()):
                            if ($display_count >= 3) break;
                            $display_count++;

                            // Get progress for this course
                            $course_progress = 0;
                            foreach ($progress_data as $progress) {
                                if ($progress['skill_id'] == $course['skill_id']) {
                                    $course_progress = $progress['completion_percent'];
                                    break;
                                }
                            }

                            // Determine progress color
                            $progress_color = $course_progress >= 75 ? 'from-green-500 to-emerald-500' : 
                                            ($course_progress >= 50 ? 'from-yellow-500 to-orange-500' : 
                                            'from-red-500 to-pink-500');
                        ?>
                            <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-shadow">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="text-xs font-medium px-2 py-1 rounded-full <?php echo $course['level'] == 'Beginner' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo htmlspecialchars($course['level']); ?>
                                        </span>
                                        <h3 class="text-lg font-bold text-gray-800 mt-2"><?php echo htmlspecialchars($course['skill_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-chalkboard-teacher mr-1"></i>
                                            <?php echo htmlspecialchars($course['teacher_name'] ?? 'Teacher Not Assigned'); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500">Batch</div>
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($course['batch_name']); ?></div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Progress</span>
                                        <span class="font-bold <?php echo $course_progress >= 75 ? 'text-green-600' : ($course_progress >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                            <?php echo $course_progress; ?>%
                                        </span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill bg-gradient-to-r <?php echo $progress_color; ?>" 
                                             style="width: <?php echo min($course_progress, 100); ?>%"></div>
                                    </div>
                                </div>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex justify-between">
                                        <span><i class="far fa-clock mr-2"></i>Timing</span>
                                        <span class="font-medium">
                                            <?php echo date("h:i A", strtotime($course['start_time'])) . " - " . date("h:i A", strtotime($course['end_time'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><i class="fas fa-calendar-alt mr-2"></i>Session</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($course['session_name']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><i class="fas fa-rupee-sign mr-2"></i>Course Fee</span>
                                        <span class="font-medium">Rs<?php echo number_format($course['total_fee'] ?? 0); ?></span>
                                    </div>
                                </div>

                                <div class="mt-5 pt-4 border-t border-gray-200 flex gap-2">
                                    <a href="my_courses.php?skill_id=<?php echo $course['skill_id']; ?>" 
                                       class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg transition-colors">
                                       View Course
                                    </a>
                                    <a href="progress.php?skill_id=<?php echo $course['skill_id']; ?>" 
                                       class="flex-1 border border-blue-600 text-blue-600 hover:bg-blue-50 text-center py-2 rounded-lg transition-colors">
                                       Progress
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-book-open text-gray-300 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Courses Enrolled</h3>
                        <p class="text-gray-500 mb-4">You haven't enrolled in any courses yet.</p>
                        <a href="../skills/skills.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-search"></i>
                            Browse Available Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Deadlines -->
            <?php if ($total_courses > 0): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-tasks text-blue-500"></i>
                        Upcoming Deadlines & Tasks
                    </h2>
                    <div class="space-y-3">
                        <!-- Fee Payment Deadline -->
                        <?php if ($pending_fees > 0): ?>
                            <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-rupee-sign text-red-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-800">Fee Payment Due</h3>
                                        <p class="text-sm text-gray-600">Rs<?php echo number_format($pending_fees); ?> pending</p>
                                    </div>
                                </div>
                                <a href="fees.php" class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
                                    Pay Now
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Assignment Deadline (Example) -->
                        <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Web Development Project</h3>
                                    <p class="text-sm text-gray-600">Due: Tomorrow, 11:59 PM</p>
                                </div>
                            </div>
                            <a href="#" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                                Submit
                            </a>
                        </div>

                        <!-- Quiz Reminder (Example) -->
                        <div class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-question-circle text-yellow-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Python Quiz</h3>
                                    <p class="text-sm text-gray-600">Starts in 2 days</p>
                                </div>
                            </div>
                            <a href="quizzes.php" class="text-sm bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                Prepare
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>
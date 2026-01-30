<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Get teacher details
$teacher_query = "SELECT * FROM teachers WHERE id = ?";
$stmt_teacher = $conn->prepare($teacher_query);
$stmt_teacher->bind_param("i", $teacher_id);
$stmt_teacher->execute();
$teacher = $stmt_teacher->get_result()->fetch_assoc();

// Get current month and year
$current_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

// Get teacher performance for current month
$performance_query = "
SELECT * FROM teacher_performance 
WHERE teacher_id = ? AND month_year = ?
";
$stmt_perf = $conn->prepare($performance_query);
$stmt_perf->bind_param("is", $teacher_id, $current_month);
$stmt_perf->execute();
$current_performance = $stmt_perf->get_result()->fetch_assoc();

// Get last month's performance
$stmt_perf->bind_param("is", $teacher_id, $last_month);
$stmt_perf->execute();
$last_performance = $stmt_perf->get_result()->fetch_assoc();

// If no performance data exists, create mock data for demonstration
if (!$current_performance) {
    // Calculate performance metrics
    $total_batches_query = "SELECT COUNT(*) as count FROM teacher_assignments WHERE teacher_id = ?";
    $stmt_batches = $conn->prepare($total_batches_query);
    $stmt_batches->bind_param("i", $teacher_id);
    $stmt_batches->execute();
    $total_batches = $stmt_batches->get_result()->fetch_assoc()['count'];

    $total_students_query = "
    SELECT COUNT(DISTINCT se.student_id) as count 
    FROM student_enrollments se
    JOIN teacher_assignments ta ON se.batch_id = ta.batch_id
    WHERE ta.teacher_id = ? AND se.status = 'active'
    ";
    $stmt_students = $conn->prepare($total_students_query);
    $stmt_students->bind_param("i", $teacher_id);
    $stmt_students->execute();
    $total_students = $stmt_students->get_result()->fetch_assoc()['count'];

    $avg_attendance_query = "
    SELECT AVG(sa.status = 'present') * 100 as avg_attendance
    FROM student_attendance sa
    JOIN teacher_assignments ta ON sa.batch_id = ta.batch_id
    WHERE ta.teacher_id = ? AND MONTH(sa.attendance_date) = MONTH(CURRENT_DATE())
    ";
    $stmt_attendance = $conn->prepare($avg_attendance_query);
    $stmt_attendance->bind_param("i", $teacher_id);
    $stmt_attendance->execute();
    $avg_attendance_result = $stmt_attendance->get_result()->fetch_assoc();
    $avg_attendance = $avg_attendance_result['avg_attendance'] ?? 0;

    $avg_quiz_query = "
    SELECT AVG(qr.score) as avg_score
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
    WHERE q.teacher_id = ? AND MONTH(qr.submitted_at) = MONTH(CURRENT_DATE())
    ";
    $stmt_quiz = $conn->prepare($avg_quiz_query);
    $stmt_quiz->bind_param("i", $teacher_id);
    $stmt_quiz->execute();
    $avg_quiz_result = $stmt_quiz->get_result()->fetch_assoc();
    $avg_quiz_score = $avg_quiz_result['avg_score'] ?? 0;

    $materials_query = "
    SELECT COUNT(*) as count FROM teaching_materials 
    WHERE teacher_id = ? AND MONTH(uploaded_at) = MONTH(CURRENT_DATE())
    ";
    $stmt_materials = $conn->prepare($materials_query);
    $stmt_materials->bind_param("i", $teacher_id);
    $stmt_materials->execute();
    $materials_uploaded = $stmt_materials->get_result()->fetch_assoc()['count'];

    $quizzes_query = "
    SELECT COUNT(*) as count FROM quizzes 
    WHERE teacher_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ";
    $stmt_quizzes = $conn->prepare($quizzes_query);
    $stmt_quizzes->bind_param("i", $teacher_id);
    $stmt_quizzes->execute();
    $quizzes_created = $stmt_quizzes->get_result()->fetch_assoc()['count'];

    // Calculate performance score (weighted average)
    $performance_score = (
        ($avg_attendance * 0.3) +
        ($avg_quiz_score * 0.4) +
        (min($materials_uploaded, 10) * 2.5) +
        (min($quizzes_created, 4) * 5)
    );

    $current_performance = [
        'total_batches' => $total_batches,
        'total_students' => $total_students,
        'avg_attendance' => round($avg_attendance, 2),
        'avg_quiz_score' => round($avg_quiz_score, 2),
        'materials_uploaded' => $materials_uploaded,
        'quizzes_created' => $quizzes_created,
        'performance_score' => round($performance_score, 2)
    ];
}

// Get performance trend data for chart
$trend_query = "
SELECT 
    DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL n MONTH), '%Y-%m') as month,
    COALESCE(tp.performance_score, 0) as score
FROM (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
    UNION SELECT 4 UNION SELECT 5
) months
LEFT JOIN teacher_performance tp ON DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL n MONTH), '%Y-%m') = tp.month_year 
    AND tp.teacher_id = ?
ORDER BY month
";

$stmt_trend = $conn->prepare($trend_query);
$stmt_trend->bind_param("i", $teacher_id);
$stmt_trend->execute();
$trend_result = $stmt_trend->get_result();
$performance_trend = [];
$trend_labels = [];
$trend_data = [];

while ($row = $trend_result->fetch_assoc()) {
    $performance_trend[] = $row;
    $trend_labels[] = date('M', strtotime($row['month']));
    $trend_data[] = $row['score'];
}

// Get assigned batches with stats
$batches_query = "
SELECT 
    b.id,
    b.batch_name,
    s.skill_name,
    COUNT(DISTINCT se.student_id) as student_count,
    COUNT(DISTINCT q.id) as quiz_count,
    COUNT(DISTINCT tm.id) as material_count,
    COALESCE(AVG(qr.score), 0) as avg_quiz_score
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
LEFT JOIN student_enrollments se ON b.id = se.batch_id AND se.status = 'active'
LEFT JOIN quizzes q ON b.id = q.batch_id AND q.teacher_id = ta.teacher_id
LEFT JOIN teaching_materials tm ON b.id = tm.batch_id AND tm.teacher_id = ta.teacher_id
LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
WHERE ta.teacher_id = ?
GROUP BY b.id
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$batches = $stmt_batches->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Performance Report | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .performance-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .performance-excellent {
            background: #d1fae5;
            color: #065f46;
        }

        .performance-good {
            background: #fef3c7;
            color: #92400e;
        }

        .performance-average {
            background: #fee2e2;
            color: #991b1b;
        }

        .performance-poor {
            background: #f3f4f6;
            color: #6b7280;
        }

        .trend-up {
            color: #10b981;
        }

        .trend-down {
            color: #ef4444;
        }

        .trend-neutral {
            color: #6b7280;
        }

        .grade-a {
            background: #10b981;
            color: white;
        }

        .grade-b {
            background: #3b82f6;
            color: white;
        }

        .grade-c {
            background: #f59e0b;
            color: white;
        }

        .grade-d {
            background: #ef4444;
            color: white;
        }

        .grade-f {
            background: #7f1d1d;
            color: white;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">My Performance Report</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                            Overview of your teaching performance and metrics
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <span class="text-sm font-medium">Teacher:</span>
                            <span class="font-bold ml-2"><?php echo htmlspecialchars($teacher['name'] ?? 'Teacher'); ?></span>
                        </div>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('F Y'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Overall Performance Score -->
                <div class="metric-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Performance Score</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                            <?php
                            $score = $current_performance['performance_score'] ?? 0;
                            if ($score >= 80) echo 'Excellent';
                            elseif ($score >= 60) echo 'Good';
                            elseif ($score >= 40) echo 'Average';
                            else echo 'Needs Improvement';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($current_performance['performance_score'] ?? 0, 1); ?>/100
                            </p>
                            <div class="flex items-center mt-2">
                                <?php if ($last_performance): ?>
                                    <?php
                                    $change = ($current_performance['performance_score'] ?? 0) - ($last_performance['performance_score'] ?? 0);
                                    $trend_class = $change > 0 ? 'trend-up' : ($change < 0 ? 'trend-down' : 'trend-neutral');
                                    $trend_icon = $change > 0 ? 'fas fa-arrow-up' : ($change < 0 ? 'fas fa-arrow-down' : 'fas fa-minus');
                                    ?>
                                    <i class="<?php echo $trend_icon . ' ' . $trend_class; ?> text-xs mr-1"></i>
                                    <span class="text-xs <?php echo $trend_class; ?>">
                                        <?php echo abs($change) > 0 ? round(abs($change), 1) . ' pts' : 'No change'; ?>
                                    </span>
                                    <span class="text-xs text-gray-500 ml-1">from last month</span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">No previous data</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-trophy text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Average Quiz Score -->
                <div class="metric-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Avg Quiz Score</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                            <?php
                            $quiz_score = $current_performance['avg_quiz_score'] ?? 0;
                            if ($quiz_score >= 80) echo 'High';
                            elseif ($quiz_score >= 60) echo 'Good';
                            else echo 'Needs Focus';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($current_performance['avg_quiz_score'] ?? 0, 1); ?>%
                            </p>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">Across all quizzes</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-bar text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Average Attendance -->
                <div class="metric-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Avg Attendance</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                            <?php
                            $attendance = $current_performance['avg_attendance'] ?? 0;
                            if ($attendance >= 90) echo 'Excellent';
                            elseif ($attendance >= 75) echo 'Good';
                            else echo 'Monitor';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($current_performance['avg_attendance'] ?? 0, 1); ?>%
                            </p>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">Student attendance rate</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Materials & Quizzes -->
                <div class="metric-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Content Created</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800">
                            Active
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo $current_performance['materials_uploaded'] ?? 0; ?> Materials
                            </p>
                            <p class="text-lg font-semibold text-gray-700">
                                <?php echo $current_performance['quizzes_created'] ?? 0; ?> Quizzes
                            </p>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-gray-500">This month</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-file-alt text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Performance Trend Chart -->
                <div class="metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Performance Trend (Last 6 Months)</h3>
                    <div class="h-64">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <!-- Batch Distribution -->
                <div class="metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Batch Overview</h3>
                    <div class="space-y-4">
                        <?php if ($batches->num_rows > 0): ?>
                            <?php while ($batch = $batches->fetch_assoc()): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-users text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($batch['batch_name']); ?></h4>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($batch['skill_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-center">
                                            <p class="text-sm font-medium text-gray-700"><?php echo $batch['student_count']; ?></p>
                                            <p class="text-xs text-gray-500">Students</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-sm font-medium text-gray-700"><?php echo $batch['quiz_count']; ?></p>
                                            <p class="text-xs text-gray-500">Quizzes</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-sm font-medium text-gray-700"><?php echo $batch['material_count']; ?></p>
                                            <p class="text-xs text-gray-500">Materials</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile;
                            mysqli_data_seek($batches, 0); ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-users text-gray-300 text-2xl"></i>
                                </div>
                                <p class="text-gray-500">No batches assigned yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Detailed Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Student Statistics -->
                <div class="metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Student Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Students:</span>
                            <span class="font-medium"><?php echo $current_performance['total_students'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Batches:</span>
                            <span class="font-medium"><?php echo $current_performance['total_batches'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Students/Batch:</span>
                            <span class="font-medium">
                                <?php
                                $students = $current_performance['total_students'] ?? 0;
                                $batches = $current_performance['total_batches'] ?? 1;
                                echo $batches > 0 ? round($students / $batches, 1) : 0;
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Active Students:</span>
                            <span class="font-medium"><?php echo $current_performance['total_students'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">This Month's Activity</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-question-circle text-green-600"></i>
                                </div>
                                <span class="text-gray-700">Quizzes Created</span>
                            </div>
                            <span class="font-medium"><?php echo $current_performance['quizzes_created'] ?? 0; ?></span>
                        </div>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-file-upload text-blue-600"></i>
                                </div>
                                <span class="text-gray-700">Materials Uploaded</span>
                            </div>
                            <span class="font-medium"><?php echo $current_performance['materials_uploaded'] ?? 0; ?></span>
                        </div>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-clipboard-check text-purple-600"></i>
                                </div>
                                <span class="text-gray-700">Attendance Marked</span>
                            </div>
                            <span class="font-medium">
                                <?php
                                $attendance_query = "SELECT COUNT(DISTINCT attendance_date) as days FROM student_attendance sa JOIN teacher_assignments ta ON sa.batch_id = ta.batch_id WHERE ta.teacher_id = ? AND MONTH(sa.attendance_date) = MONTH(CURRENT_DATE())";
                                $stmt_att = $conn->prepare($attendance_query);
                                $stmt_att->bind_param("i", $teacher_id);
                                $stmt_att->execute();
                                $att_result = $stmt_att->get_result()->fetch_assoc();
                                echo $att_result['days'] ?? 0;
                                ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-line text-yellow-600"></i>
                                </div>
                                <span class="text-gray-700">Quiz Submissions</span>
                            </div>
                            <span class="font-medium">
                                <?php
                                $submissions_query = "SELECT COUNT(*) as count FROM quiz_results qr JOIN quizzes q ON qr.quiz_id = q.id WHERE q.teacher_id = ? AND MONTH(qr.submitted_at) = MONTH(CURRENT_DATE())";
                                $stmt_sub = $conn->prepare($submissions_query);
                                $stmt_sub->bind_param("i", $teacher_id);
                                $stmt_sub->execute();
                                $sub_result = $stmt_sub->get_result()->fetch_assoc();
                                echo $sub_result['count'] ?? 0;
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="metric-card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Recommendations</h3>
                    <div class="space-y-3">
                        <?php
                        $score = $current_performance['performance_score'] ?? 0;
                        $attendance = $current_performance['avg_attendance'] ?? 0;
                        $quiz_score = $current_performance['avg_quiz_score'] ?? 0;
                        $materials = $current_performance['materials_uploaded'] ?? 0;
                        $quizzes = $current_performance['quizzes_created'] ?? 0;

                        $recommendations = [];

                        if ($score < 60) {
                            $recommendations[] = "Focus on improving overall performance score";
                        }
                        if ($attendance < 80) {
                            $recommendations[] = "Monitor student attendance more closely";
                        }
                        if ($quiz_score < 70) {
                            $recommendations[] = "Consider revising quiz difficulty or teaching methods";
                        }
                        if ($materials < 3) {
                            $recommendations[] = "Upload more study materials for students";
                        }
                        if ($quizzes < 2) {
                            $recommendations[] = "Create more quizzes to assess student learning";
                        }

                        if (empty($recommendations)) {
                            $recommendations[] = "Great work! Keep maintaining your current performance level.";
                        }
                        ?>

                        <?php foreach ($recommendations as $index => $rec): ?>
                            <div class="flex items-start">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">
                                    <i class="fas fa-lightbulb text-blue-600 text-xs"></i>
                                </div>
                                <p class="text-sm text-gray-700"><?php echo $rec; ?></p>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="batch_report.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-arrow-right mr-1"></i> View Detailed Batch Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-gray-800">Export Report</h4>
                        <p class="text-sm text-gray-600">Download your performance report for records</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="exportToPDF()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm">
                            <i class="fas fa-file-pdf mr-2"></i> Export as PDF
                        </button>
                        <button onclick="exportToCSV()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm">
                            <i class="fas fa-file-csv mr-2"></i> Export as CSV
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Performance Trend Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Performance Score',
                    data: <?php echo json_encode($trend_data); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        titleFont: {
                            size: 12
                        },
                        bodyFont: {
                            size: 14
                        },
                        callbacks: {
                            label: function(context) {
                                return `Score: ${context.parsed.y}/100`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Export functions
        function exportToPDF() {
            alert('PDF export functionality would be implemented here. This is a demo.');
            // In production, you would use a library like jsPDF or make an API call
        }

        function exportToCSV() {
            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Metric,Value,Grade\n";
            csvContent += `Performance Score,${<?php echo $current_performance['performance_score'] ?? 0; ?>},${getGrade(<?php echo $current_performance['performance_score'] ?? 0; ?>)}\n`;
            csvContent += `Average Quiz Score,${<?php echo $current_performance['avg_quiz_score'] ?? 0; ?>}%,${getGrade(<?php echo $current_performance['avg_quiz_score'] ?? 0; ?>)}\n`;
            csvContent += `Average Attendance,${<?php echo $current_performance['avg_attendance'] ?? 0; ?>}%,${getGrade(<?php echo $current_performance['avg_attendance'] ?? 0; ?>)}\n`;
            csvContent += `Materials Uploaded,${<?php echo $current_performance['materials_uploaded'] ?? 0; ?>},N/A\n`;
            csvContent += `Quizzes Created,${<?php echo $current_performance['quizzes_created'] ?? 0; ?>},N/A\n`;
            csvContent += `Total Students,${<?php echo $current_performance['total_students'] ?? 0; ?>},N/A\n`;
            csvContent += `Total Batches,${<?php echo $current_performance['total_batches'] ?? 0; ?>},N/A\n`;

            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `teacher_report_<?php echo date('Y_m'); ?>.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function getGrade(score) {
            if (score >= 90) return 'A';
            if (score >= 80) return 'B';
            if (score >= 70) return 'C';
            if (score >= 60) return 'D';
            return 'F';
        }
    </script>
</body>

</html>
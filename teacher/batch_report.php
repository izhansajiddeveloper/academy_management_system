<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Fetch teacher's batches
$batches_query = "
SELECT b.id, b.batch_name, s.skill_name 
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE ta.teacher_id = ? AND b.status = 'active'
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$teacher_batches = $stmt_batches->get_result();

// If no batch selected, get the first one
if ($batch_id === 0 && $teacher_batches->num_rows > 0) {
    $first_batch = $teacher_batches->fetch_assoc();
    $batch_id = $first_batch['id'];
    mysqli_data_seek($teacher_batches, 0);
}

// Get batch details
$batch_details = null;
$batch_students = [];
$batch_performance = [];

if ($batch_id > 0) {
    // Get batch details
    $batch_query = "
    SELECT b.*, s.skill_name, COUNT(DISTINCT se.student_id) as student_count
    FROM batches b
    JOIN skills s ON b.skill_id = s.id
    LEFT JOIN student_enrollments se ON b.id = se.batch_id AND se.status = 'active'
    WHERE b.id = ?
    GROUP BY b.id
    ";

    $stmt_batch = $conn->prepare($batch_query);
    $stmt_batch->bind_param("i", $batch_id);
    $stmt_batch->execute();
    $batch_details = $stmt_batch->get_result()->fetch_assoc();

    // Get students in batch
    $students_query = "
  SELECT 
    s.id,
    s.name,
    sp.progress_score,
    sp.attendance_percentage,
    sp.quiz_score,
    sp.overall_grade,
    sp.comments
FROM student_enrollments se
JOIN students s ON se.student_id = s.id
LEFT JOIN student_progress sp ON s.id = sp.student_id AND sp.batch_id = ?
WHERE se.batch_id = ? AND se.status = 'active'
ORDER BY s.name
";
    $stmt_students = $conn->prepare($students_query);
    $stmt_students->bind_param("ii", $batch_id, $batch_id);
    $stmt_students->execute();
    $batch_students = $stmt_students->get_result();

    // Calculate batch performance
    $performance_query = "
    SELECT 
        AVG(sp.attendance_percentage) as avg_attendance,
        AVG(sp.quiz_score) as avg_quiz_score,
        AVG(sp.progress_score) as avg_progress,
        COUNT(CASE WHEN sp.overall_grade IN ('A', 'B') THEN 1 END) as top_students,
        COUNT(CASE WHEN sp.overall_grade IN ('D', 'F') THEN 1 END) as weak_students,
        COUNT(*) as total_students
    FROM student_progress sp
    WHERE sp.batch_id = ?
    ";

    $stmt_perf = $conn->prepare($performance_query);
    $stmt_perf->bind_param("i", $batch_id);
    $stmt_perf->execute();
    $batch_performance = $stmt_perf->get_result()->fetch_assoc();

    // Get recent quizzes for this batch
    $quizzes_query = "
    SELECT q.*, AVG(qr.score) as avg_score
    FROM quizzes q
    LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
    WHERE q.batch_id = ? AND q.teacher_id = ?
    GROUP BY q.id
    ORDER BY q.created_at DESC
    LIMIT 5
    ";

    $stmt_quizzes = $conn->prepare($quizzes_query);
    $stmt_quizzes->bind_param("ii", $batch_id, $teacher_id);
    $stmt_quizzes->execute();
    $batch_quizzes = $stmt_quizzes->get_result();

    // Get attendance summary
    $attendance_query = "
    SELECT 
        DATE(sa.attendance_date) as date,
        COUNT(CASE WHEN sa.status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN sa.status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN sa.status = 'late' THEN 1 END) as late
    FROM student_attendance sa
    WHERE sa.batch_id = ? AND sa.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(sa.attendance_date)
    ORDER BY sa.attendance_date DESC
    LIMIT 7
    ";

    $stmt_attendance = $conn->prepare($attendance_query);
    $stmt_attendance->bind_param("i", $batch_id);
    $stmt_attendance->execute();
    $attendance_summary = $stmt_attendance->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Batch Report | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .grade-badge {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .attendance-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }

        .dot-present {
            background: #10b981;
        }

        .dot-absent {
            background: #ef4444;
        }

        .dot-late {
            background: #f59e0b;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
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
                        <h1 class="text-3xl font-bold text-gray-800">Batch Performance Report</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-chart-pie text-blue-500 mr-2"></i>
                            Detailed analysis of batch performance
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <span class="text-sm font-medium">Teacher:</span>
                            <span class="font-bold ml-2"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Teacher'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Batch Selection -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch</label>
                        <select id="batchSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php if ($teacher_batches->num_rows > 0): ?>
                                <?php while ($batch = $teacher_batches->fetch_assoc()): ?>
                                    <option value="<?php echo $batch['id']; ?>" <?php echo $batch['id'] == $batch_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($batch['skill_name'] . ' - ' . $batch['batch_name']); ?>
                                    </option>
                                <?php endwhile;
                                mysqli_data_seek($teacher_batches, 0); ?>
                            <?php else: ?>
                                <option value="">No batches assigned</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="printReport()" class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                            <i class="fas fa-print mr-2"></i> Print
                        </button>
                        <button onclick="exportReport()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            <i class="fas fa-download mr-2"></i> Export Report
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($batch_id > 0 && $batch_details): ?>
                <!-- Batch Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Students</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo $batch_details['student_count'] ?? 0; ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo date('g:i A', strtotime($batch_details['start_time'])); ?> -
                                <?php echo date('g:i A', strtotime($batch_details['end_time'])); ?>
                            </p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Avg Attendance</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($batch_performance['avg_attendance'] ?? 0, 1); ?>%
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-check text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="progress-bar mt-3">
                            <div class="progress-fill bg-green-500"
                                style="width: <?php echo min($batch_performance['avg_attendance'] ?? 0, 100); ?>%"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Avg Quiz Score</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($batch_performance['avg_quiz_score'] ?? 0, 1); ?>%
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="progress-bar mt-3">
                            <div class="progress-fill bg-purple-500"
                                style="width: <?php echo min($batch_performance['avg_quiz_score'] ?? 0, 100); ?>%"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Performance</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($batch_performance['avg_progress'] ?? 0, 1); ?>%
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-xs text-gray-500">
                                <?php echo $batch_performance['top_students'] ?? 0; ?> Top •
                                <?php echo $batch_performance['weak_students'] ?? 0; ?> Weak
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Charts and Details -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Students List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-bold text-gray-800">Students Performance</h3>
                                <p class="text-sm text-gray-500 mt-1">Individual student progress and grades</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Attendance</th>
                                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Quiz Score</th>
                                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Progress</th>
                                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Grade</th>
                                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php if ($batch_students->num_rows > 0): ?>
                                            <?php while ($student = $batch_students->fetch_assoc()): ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="py-4 px-6">
                                                        <div class="flex items-center">
                                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                                            </div>
                                                            <div>
                                                                <div class="font-medium text-gray-900">
                                                                    <?php echo htmlspecialchars($student['name']); ?>
                                                                </div>
                                                               
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-6">
                                                        <div class="flex items-center">
                                                            <span class="font-medium text-gray-900 mr-2">
                                                                <?php echo round($student['attendance_percentage'] ?? 0, 1); ?>%
                                                            </span>
                                                            <div class="progress-bar w-20">
                                                                <div class="progress-fill bg-green-500"
                                                                    style="width: <?php echo min($student['attendance_percentage'] ?? 0, 100); ?>%"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-6">
                                                        <span class="font-medium text-gray-900">
                                                            <?php echo round($student['quiz_score'] ?? 0, 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-6">
                                                        <span class="font-medium text-gray-900">
                                                            <?php echo round($student['progress_score'] ?? 0, 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-6">
                                                        <?php if (!empty($student['overall_grade'])): ?>
                                                            <span class="grade-badge grade-<?php echo strtolower($student['overall_grade']); ?>">
                                                                <?php echo $student['overall_grade']; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-sm text-gray-400">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-4 px-6">
                                                        <a href="student_report.php?student_id=<?php echo $student['id']; ?>&batch_id=<?php echo $batch_id; ?>"
                                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                            View Report
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                                    No students enrolled in this batch
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Batch Details & Recent Activity -->
                    <div class="space-y-6">
                        <!-- Batch Information -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Batch Information</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Batch Name:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($batch_details['batch_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Skill/Course:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($batch_details['skill_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Timing:</span>
                                    <span class="font-medium">
                                        <?php echo date('h:i A', strtotime($batch_details['start_time'])); ?> -
                                        <?php echo date('h:i A', strtotime($batch_details['end_time'])); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Max Students:</span>
                                    <span class="font-medium"><?php echo $batch_details['max_students']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        <?php echo ucfirst($batch_details['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Quizzes -->
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Quizzes</h3>
                            <div class="space-y-3">
                                <?php if ($batch_quizzes->num_rows > 0): ?>
                                    <?php while ($quiz = $batch_quizzes->fetch_assoc()): ?>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-800 text-sm">
                                                    <?php echo htmlspecialchars($quiz['title']); ?>
                                                </h4>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo date('M d', strtotime($quiz['created_at'])); ?> •
                                                    <?php echo $quiz['total_questions']; ?> questions
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900">
                                                    <?php echo round($quiz['avg_score'] ?? 0, 1); ?>%
                                                </p>
                                                <p class="text-xs text-gray-500">Avg Score</p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm">No quizzes created yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Attendance (Last 7 Days)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Absent</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Late</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Attendance %</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($attendance_summary->num_rows > 0): ?>
                                    <?php while ($attendance = $attendance_summary->fetch_assoc()):
                                        $total = $attendance['present'] + $attendance['absent'] + $attendance['late'];
                                        $attendance_percent = $total > 0 ? ($attendance['present'] / $total) * 100 : 0;
                                        $status_class = $attendance_percent >= 90 ? 'bg-green-100 text-green-800' : ($attendance_percent >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                    ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <?php echo date('D, M d', strtotime($attendance['date'])); ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-green-600"><?php echo $attendance['present']; ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-red-600"><?php echo $attendance['absent']; ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-yellow-600"><?php echo $attendance['late']; ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <span class="font-medium mr-2"><?php echo round($attendance_percent, 1); ?>%</span>
                                                    <div class="progress-bar w-20">
                                                        <div class="progress-fill bg-green-500"
                                                            style="width: <?php echo $attendance_percent; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $status_class; ?>">
                                                    <?php echo $attendance_percent >= 90 ? 'Good' : ($attendance_percent >= 75 ? 'Average' : 'Poor'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                            No attendance records for the last 30 days
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- No Batch Selected -->
                <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-chart-pie text-gray-300 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-3">No Batch Selected</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        Select a batch from the dropdown above to view its performance report.
                    </p>
                    <?php if ($teacher_batches->num_rows === 0): ?>
                        <p class="text-yellow-600">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            No batches assigned to you yet.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Batch selection change
        document.getElementById('batchSelect').addEventListener('change', function() {
            const batchId = this.value;
            if (batchId) {
                window.location.href = `batch_report.php?batch_id=${batchId}`;
            }
        });

        // Print report
        function printReport() {
            window.print();
        }

        // Export report
        function exportReport() {
            const batchName = "<?php echo addslashes($batch_details['batch_name'] ?? 'Batch'); ?>";
            const currentDate = new Date().toISOString().split('T')[0];

            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += `Batch Report - ${batchName}\n`;
            csvContent += `Generated on: ${currentDate}\n\n`;
            csvContent += "Metric,Value\n";
            csvContent += `Total Students,${<?php echo $batch_details['student_count'] ?? 0; ?>}\n`;
            csvContent += `Average Attendance,${<?php echo round($batch_performance['avg_attendance'] ?? 0, 1); ?>}%\n`;
            csvContent += `Average Quiz Score,${<?php echo round($batch_performance['avg_quiz_score'] ?? 0, 1); ?>}%\n`;
            csvContent += `Average Progress,${<?php echo round($batch_performance['avg_progress'] ?? 0, 1); ?>}%\n`;
            csvContent += `Top Students (A/B),${<?php echo $batch_performance['top_students'] ?? 0; ?>}\n`;
            csvContent += `Weak Students (D/F),${<?php echo $batch_performance['weak_students'] ?? 0; ?>}\n\n`;
            csvContent += "Student Name,Roll Number,Attendance %,Quiz Score %,Progress %,Grade\n";

            <?php if ($batch_students->num_rows > 0): ?>
                <?php mysqli_data_seek($batch_students, 0); ?>
                <?php while ($student = $batch_students->fetch_assoc()): ?>
                    csvContent += `"<?php echo addslashes($student['name']); ?>",`;
                    csvContent += `"<?php echo addslashes($student['roll_number']); ?>",`;
                    csvContent += `<?php echo round($student['attendance_percentage'] ?? 0, 1); ?>,`;
                    csvContent += `<?php echo round($student['quiz_score'] ?? 0, 1); ?>,`;
                    csvContent += `<?php echo round($student['progress_score'] ?? 0, 1); ?>,`;
                    csvContent += `"<?php echo $student['overall_grade'] ?? '-'; ?>"\n`;
                <?php endwhile; ?>
            <?php endif; ?>

            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `batch_report_${batchName}_${currentDate}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>
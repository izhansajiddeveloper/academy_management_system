<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

if (!$student_id || !$batch_id) {
    $_SESSION['error'] = "Student ID and Batch ID are required.";
    header("Location: batch_report.php");
    exit;
}

// Verify teacher has access to this student in this batch
$verify_query = "
SELECT s.*, b.batch_name, sk.skill_name
FROM students s
JOIN student_enrollments se ON s.id = se.student_id
JOIN batches b ON se.batch_id = b.id
JOIN skills sk ON b.skill_id = sk.id
JOIN teacher_assignments ta ON b.id = ta.batch_id
WHERE s.id = ? AND b.id = ? AND ta.teacher_id = ? AND se.status = 'active'
";

$stmt_verify = $conn->prepare($verify_query);
$stmt_verify->bind_param("iii", $student_id, $batch_id, $teacher_id);
$stmt_verify->execute();
$student_result = $stmt_verify->get_result();

if ($student_result->num_rows === 0) {
    $_SESSION['error'] = "Student not found or unauthorized access.";
    header("Location: batch_report.php");
    exit;
}

$student = $student_result->fetch_assoc();

// Get student progress data
$progress_query = "
SELECT * FROM student_progress 
WHERE student_id = ? AND batch_id = ?
ORDER BY report_date DESC LIMIT 1
";

$stmt_progress = $conn->prepare($progress_query);
$stmt_progress->bind_param("ii", $student_id, $batch_id);
$stmt_progress->execute();
$progress_data = $stmt_progress->get_result()->fetch_assoc();

// Get quiz results
$quiz_results_query = "
SELECT 
    q.title,
    qr.score,
    q.total_marks,
    qr.correct_answers,
    qr.total_questions,
    qr.submitted_at,
    (qr.score / q.total_marks * 100) as percentage
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
WHERE qr.student_id = ? AND qr.batch_id = ?
ORDER BY qr.submitted_at DESC
LIMIT 10
";

$stmt_quizzes = $conn->prepare($quiz_results_query);
$stmt_quizzes->bind_param("ii", $student_id, $batch_id);
$stmt_quizzes->execute();
$quiz_results = $stmt_quizzes->get_result();

// Get attendance records
$attendance_query = "
SELECT 
    attendance_date,
    status,
    notes
FROM student_attendance
WHERE student_id = ? AND batch_id = ?
AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY attendance_date DESC
";

$stmt_attendance = $conn->prepare($attendance_query);
$stmt_attendance->bind_param("ii", $student_id, $batch_id);
$stmt_attendance->execute();
$attendance_records = $stmt_attendance->get_result();

// Calculate attendance stats
$attendance_stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
while ($record = $attendance_records->fetch_assoc()) {
    $attendance_stats[$record['status']]++;
    $attendance_stats['total']++;
}
$attendance_percentage = $attendance_stats['total'] > 0 ?
    ($attendance_stats['present'] / $attendance_stats['total']) * 100 : 0;

mysqli_data_seek($attendance_records, 0);

// Get materials downloaded
$downloads_query = "
SELECT 
    tm.title,
    tm.file_type,
    md.downloaded_at
FROM material_downloads md
JOIN teaching_materials tm ON md.material_id = tm.id
WHERE md.student_id = ? AND tm.batch_id = ?
ORDER BY md.downloaded_at DESC
LIMIT 5
";

$stmt_downloads = $conn->prepare($downloads_query);
$stmt_downloads->bind_param("ii", $student_id, $batch_id);
$stmt_downloads->execute();
$downloads = $stmt_downloads->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Student Report | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .grade-badge {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .attendance-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .icon-present {
            background: #d1fae5;
            color: #065f46;
        }

        .icon-absent {
            background: #fee2e2;
            color: #dc2626;
        }

        .icon-late {
            background: #fef3c7;
            color: #d97706;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .subject-tag {
            display: inline-block;
            padding: 4px 12px;
            background: #e0e7ff;
            color: #4f46e5;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
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
                        <h1 class="text-3xl font-bold text-gray-800">Student Progress Report</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-user-graduate text-blue-500 mr-2"></i>
                            Individual student performance analysis
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="batch_report.php?batch_id=<?php echo $batch_id; ?>"
                            class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Batch
                        </a>
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <span class="text-sm font-medium">Batch:</span>
                            <span class="font-bold ml-2"><?php echo htmlspecialchars($student['batch_name']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Information -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user-graduate text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($student['name']); ?></h2>
                            <div class="flex items-center gap-4 mt-2">
                                <span class="text-gray-600">
                                    <i class="fas fa-id-card mr-1"></i>
                                    Roll No: <?php echo htmlspecialchars($student['roll_number']); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="fas fa-book mr-1"></i>
                                    Course: <?php echo htmlspecialchars($student['skill_name']); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="fas fa-envelope mr-1"></i>
                                    <?php echo htmlspecialchars($student['email']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($progress_data && !empty($progress_data['overall_grade'])): ?>
                        <div class="text-center">
                            <div class="text-sm text-gray-500 mb-1">Overall Grade</div>
                            <div class="grade-badge grade-<?php echo strtolower($progress_data['overall_grade']); ?> mx-auto">
                                <?php echo $progress_data['overall_grade']; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Overall Progress -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Overall Progress</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                            <?php
                            $progress = $progress_data['progress_score'] ?? 0;
                            if ($progress >= 80) echo 'Excellent';
                            elseif ($progress >= 60) echo 'Good';
                            elseif ($progress >= 40) echo 'Average';
                            else echo 'Needs Improvement';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($progress, 1); ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-2">Based on all metrics</p>
                        </div>
                        <div class="relative w-16 h-16">
                            <svg class="w-16 h-16" viewBox="0 0 36 36">
                                <path class="text-gray-200" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" stroke="currentColor" stroke-width="3" />
                                <path class="text-blue-500" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="<?php echo $progress; ?>, 100"
                                    transform="rotate(-90 18 18)" />
                                <text x="18" y="20.5" text-anchor="middle" class="text-lg font-bold fill-gray-700">
                                    <?php echo round($progress); ?>%
                                </text>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Attendance -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Attendance</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                            <?php
                            if ($attendance_percentage >= 90) echo 'Excellent';
                            elseif ($attendance_percentage >= 75) echo 'Good';
                            else echo 'Needs Improvement';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($attendance_percentage, 1); ?>%
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs text-green-600">
                                    <i class="fas fa-check mr-1"></i><?php echo $attendance_stats['present']; ?>
                                </span>
                                <span class="text-xs text-red-600">
                                    <i class="fas fa-times mr-1"></i><?php echo $attendance_stats['absent']; ?>
                                </span>
                                <span class="text-xs text-yellow-600">
                                    <i class="fas fa-clock mr-1"></i><?php echo $attendance_stats['late']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Quiz Performance -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Quiz Performance</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800">
                            <?php
                            $quiz_score = $progress_data['quiz_score'] ?? 0;
                            if ($quiz_score >= 80) echo 'High';
                            elseif ($quiz_score >= 60) echo 'Good';
                            else echo 'Needs Focus';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($quiz_score, 1); ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-2">
                                <?php
                                $quiz_count = $quiz_results->num_rows;
                                echo $quiz_count . ' quiz' . ($quiz_count !== 1 ? 'zes' : '') . ' taken';
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Assignment Score -->
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-500">Assignment Score</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                            <?php
                            $assignment_score = $progress_data['assignment_score'] ?? 0;
                            if ($assignment_score >= 80) echo 'Excellent';
                            elseif ($assignment_score >= 60) echo 'Good';
                            else echo 'Needs Work';
                            ?>
                        </span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo round($assignment_score, 1); ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-2">Based on submitted work</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-tasks text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Quiz History -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-800">Quiz Performance History</h3>
                            <p class="text-sm text-gray-500 mt-1">Recent quiz scores and performance</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Quiz Title</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Score</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Correct Answers</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Date Taken</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-600 uppercase">Grade</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if ($quiz_results->num_rows > 0): ?>
                                        <?php while ($quiz = $quiz_results->fetch_assoc()):
                                            $percentage = ($quiz['score'] / $quiz['total_marks']) * 100;
                                            $grade = getGrade($percentage);
                                        ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="py-4 px-6">
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span class="font-medium text-gray-900">
                                                        <?php echo round($quiz['score'], 1); ?>/<?php echo $quiz['total_marks']; ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span class="text-gray-700">
                                                        <?php echo $quiz['correct_answers']; ?>/<?php echo $quiz['total_questions']; ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span class="font-medium <?php echo $percentage >= 60 ? 'text-green-600' : 'text-red-600'; ?>">
                                                        <?php echo round($percentage, 1); ?>%
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo date('M d, Y', strtotime($quiz['submitted_at'])); ?>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span class="grade-badge grade-<?php echo strtolower($grade); ?> inline-flex">
                                                        <?php echo $grade; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                                No quiz results found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="space-y-6">
                    <!-- Attendance History -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Attendance</h3>
                        <div class="space-y-3">
                            <?php if ($attendance_records->num_rows > 0): ?>
                                <?php while ($record = $attendance_records->fetch_assoc()):
                                    $icon_class = 'icon-' . $record['status'];
                                    $text_class = $record['status'] === 'present' ? 'text-green-600' : ($record['status'] === 'absent' ? 'text-red-600' : 'text-yellow-600');
                                ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="attendance-icon <?php echo $icon_class; ?> mr-3">
                                                <?php
                                                $icons = [
                                                    'present' => 'fas fa-check',
                                                    'absent' => 'fas fa-times',
                                                    'late' => 'fas fa-clock'
                                                ];
                                                ?>
                                                <i class="<?php echo $icons[$record['status']]; ?>"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">
                                                    <?php echo date('D, M d', strtotime($record['attendance_date'])); ?>
                                                </p>
                                                <p class="text-sm <?php echo $text_class; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if (!empty($record['notes'])): ?>
                                            <div class="text-xs text-gray-500" title="<?php echo htmlspecialchars($record['notes']); ?>">
                                                <i class="fas fa-sticky-note"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No recent attendance records</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Materials Downloaded -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Downloads</h3>
                        <div class="space-y-3">
                            <?php if ($downloads->num_rows > 0): ?>
                                <?php while ($download = $downloads->fetch_assoc()): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                <?php
                                                $icons = [
                                                    'pdf' => 'fas fa-file-pdf text-red-500',
                                                    'docx' => 'fas fa-file-word text-blue-500',
                                                    'ppt' => 'fas fa-file-powerpoint text-orange-500',
                                                    'video' => 'fas fa-file-video text-purple-500',
                                                    'image' => 'fas fa-file-image text-green-500'
                                                ];
                                                $icon = $icons[$download['file_type']] ?? 'fas fa-file text-gray-500';
                                                ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 text-sm truncate max-w-[180px]">
                                                    <?php echo htmlspecialchars($download['title']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo date('M d', strtotime($download['downloaded_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                            <?php echo strtoupper($download['file_type']); ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No materials downloaded yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teacher Comments & Recommendations -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Teacher Comments & Recommendations</h3>

                <?php if ($progress_data && !empty($progress_data['comments'])): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <i class="fas fa-comment text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-blue-800 mb-1">Latest Feedback</h4>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($progress_data['comments'])); ?></p>
                                <p class="text-xs text-gray-500 mt-2">
                                    Last updated: <?php echo date('F j, Y', strtotime($progress_data['report_date'] ?? 'now')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recommendations -->
                <div class="mt-6">
                    <h4 class="font-medium text-gray-700 mb-3">Recommendations for Improvement</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $recommendations = [];

                        if ($attendance_percentage < 75) {
                            $recommendations[] = "Improve attendance rate (currently {$attendance_percentage}%)";
                        }

                        $quiz_score = $progress_data['quiz_score'] ?? 0;
                        if ($quiz_score < 60) {
                            $recommendations[] = "Focus on quiz preparation and understanding concepts";
                        }

                        if ($downloads->num_rows < 2) {
                            $recommendations[] = "Download and study more teaching materials";
                        }

                        if (empty($recommendations)) {
                            $recommendations[] = "Student is performing well. Continue current efforts.";
                        }
                        ?>

                        <?php foreach ($recommendations as $rec): ?>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-lightbulb text-yellow-500 mt-1 mr-3"></i>
                                <span class="text-sm text-gray-700"><?php echo $rec; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Add/Update Comments Form -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-medium text-gray-700 mb-3">Add/Update Comments</h4>
                    <form method="POST" action="update_student_progress.php">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Overall Grade</label>
                            <select name="overall_grade" class="border border-gray-300 rounded-lg px-4 py-2 w-full md:w-40">
                                <option value="">Select Grade</option>
                                <option value="A" <?php echo ($progress_data['overall_grade'] ?? '') === 'A' ? 'selected' : ''; ?>>A (90-100%)</option>
                                <option value="B" <?php echo ($progress_data['overall_grade'] ?? '') === 'B' ? 'selected' : ''; ?>>B (80-89%)</option>
                                <option value="C" <?php echo ($progress_data['overall_grade'] ?? '') === 'C' ? 'selected' : ''; ?>>C (70-79%)</option>
                                <option value="D" <?php echo ($progress_data['overall_grade'] ?? '') === 'D' ? 'selected' : ''; ?>>D (60-69%)</option>
                                <option value="F" <?php echo ($progress_data['overall_grade'] ?? '') === 'F' ? 'selected' : ''; ?>>F (Below 60%)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comments & Feedback</label>
                            <textarea name="comments" rows="4"
                                class="border border-gray-300 rounded-lg px-4 py-2 w-full"
                                placeholder="Enter your feedback and recommendations for the student..."><?php echo htmlspecialchars($progress_data['comments'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                                <i class="fas fa-save mr-2"></i> Save Progress Report
                            </button>
                            <button type="button" onclick="printReport()"
                                class="px-6 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium">
                                <i class="fas fa-print mr-2"></i> Print Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Export Options -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-gray-800">Export Student Report</h4>
                        <p class="text-sm text-gray-600">Download detailed report for records or sharing</p>
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
        // Print report
        function printReport() {
            window.print();
        }

        // Export to PDF (demo)
        function exportToPDF() {
            alert('PDF export functionality would be implemented here. This is a demo.');
            // In production, use jsPDF or make an API call
        }

        // Export to CSV
        function exportToCSV() {
            const studentName = "<?php echo addslashes($student['name']); ?>";
            const batchName = "<?php echo addslashes($student['batch_name']); ?>";
            const currentDate = new Date().toISOString().split('T')[0];

            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += `Student Report - ${studentName}\n`;
            csvContent += `Batch: ${batchName}\n`;
            csvContent += `Generated on: ${currentDate}\n\n`;
            csvContent += "Category,Metric,Value,Grade/Status\n";
            csvContent += `Overall,Progress Score,${<?php echo round($progress_data['progress_score'] ?? 0, 1); ?>}%,${getGrade(<?php echo $progress_data['progress_score'] ?? 0; ?>)}\n`;
            csvContent += `Attendance,Attendance Rate,${<?php echo round($attendance_percentage, 1); ?>}%,${getAttendanceStatus(<?php echo $attendance_percentage; ?>)}\n`;
            csvContent += `Attendance,Present Days,${<?php echo $attendance_stats['present']; ?>},-\n`;
            csvContent += `Attendance,Absent Days,${<?php echo $attendance_stats['absent']; ?>},-\n`;
            csvContent += `Attendance,Late Days,${<?php echo $attendance_stats['late']; ?>},-\n`;
            csvContent += `Performance,Quiz Score,${<?php echo round($progress_data['quiz_score'] ?? 0, 1); ?>}%,${getGrade(<?php echo $progress_data['quiz_score'] ?? 0; ?>)}\n`;
            csvContent += `Performance,Assignment Score,${<?php echo round($progress_data['assignment_score'] ?? 0, 1); ?>}%,${getGrade(<?php echo $progress_data['assignment_score'] ?? 0; ?>)}\n`;
            csvContent += `Overall,Overall Grade,${<?php echo $progress_data['overall_grade'] ?? '-'; ?>},${<?php echo $progress_data['overall_grade'] ?? '-'; ?>}\n\n`;

            // Quiz history
            csvContent += "Quiz History\n";
            csvContent += "Quiz Title,Score,Total Marks,Percentage,Date Taken,Grade\n";

            <?php if ($quiz_results->num_rows > 0): ?>
                <?php mysqli_data_seek($quiz_results, 0); ?>
                <?php while ($quiz = $quiz_results->fetch_assoc()):
                    $percentage = ($quiz['score'] / $quiz['total_marks']) * 100;
                    $grade = getGrade($percentage);
                ?>
                    csvContent += `"<?php echo addslashes($quiz['title']); ?>",`;
                    csvContent += `<?php echo round($quiz['score'], 1); ?>,`;
                    csvContent += `<?php echo $quiz['total_marks']; ?>,`;
                    csvContent += `<?php echo round($percentage, 1); ?>%,`;
                    csvContent += `"<?php echo date('Y-m-d', strtotime($quiz['submitted_at'])); ?>",`;
                    csvContent += `"<?php echo $grade; ?>"\n`;
                <?php endwhile; ?>
            <?php endif; ?>

            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `student_report_${studentName.replace(/\s+/g, '_')}_${currentDate}.csv`);
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

        function getAttendanceStatus(percentage) {
            if (percentage >= 90) return 'Excellent';
            if (percentage >= 75) return 'Good';
            if (percentage >= 60) return 'Average';
            return 'Poor';
        }
    </script>
</body>

</html>

<?php
// Helper function for grade calculation
function getGrade($percentage)
{
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}
?>
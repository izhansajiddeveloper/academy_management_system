<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

if (!$quiz_id) {
    $_SESSION['error'] = "Quiz ID is required.";
    header("Location: my_quiz.php");
    exit;
}

// Handle manual marking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_marks') {
        $result_id = intval($_POST['result_id']);
        $marks_data = $_POST['marks'] ?? [];
        $total_marks = 0;

        // Verify teacher owns this quiz result
        $verify_query = "
        SELECT qr.id 
        FROM quiz_results qr
        JOIN quizzes q ON qr.quiz_id = q.id
        WHERE qr.id = ? AND q.teacher_id = ?
        ";

        $stmt_verify = $conn->prepare($verify_query);
        $stmt_verify->bind_param("ii", $result_id, $teacher_id);
        $stmt_verify->execute();
        $verify_result = $stmt_verify->get_result();

        if ($verify_result->num_rows > 0) {
            // Update each question's marks
            foreach ($marks_data as $question_id => $marks) {
                $marks = floatval($marks);
                $total_marks += $marks;

                // Update or insert manual marks
                $update_query = "
                INSERT INTO quiz_question_marks (result_id, question_id, marks_awarded)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE marks_awarded = ?
                ";

                $stmt_update = $conn->prepare($update_query);
                $stmt_update->bind_param("iidd", $result_id, $question_id, $marks, $marks);
                $stmt_update->execute();
            }

            // Update total score in quiz_results
            $update_total_query = "
            UPDATE quiz_results 
            SET score = ?, 
                correct_answers = (
                    SELECT COUNT(*) 
                    FROM quiz_question_marks 
                    WHERE result_id = ? AND marks_awarded > 0
                )
            WHERE id = ?
            ";

            $stmt_total = $conn->prepare($update_total_query);
            $stmt_total->bind_param("dii", $total_marks, $result_id, $result_id);

            if ($stmt_total->execute()) {
                $_SESSION['success'] = "Marks saved successfully!";
            } else {
                $_SESSION['error'] = "Failed to save marks.";
            }
        } else {
            $_SESSION['error'] = "Unauthorized to mark this quiz.";
        }

        header("Location: quiz_result.php?quiz_id=" . $quiz_id . ($student_id ? "&student_id=" . $student_id : ""));
        exit;
    }
}

// Fetch quiz details and verify ownership
$quiz_query = "
SELECT q.*, b.batch_name, s.skill_name 
FROM quizzes q
LEFT JOIN batches b ON q.batch_id = b.id
LEFT JOIN skills s ON b.skill_id = s.id
WHERE q.id = ? AND q.teacher_id = ?
";

$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("ii", $quiz_id, $teacher_id);
$stmt->execute();
$quiz_result = $stmt->get_result();

if ($quiz_result->num_rows === 0) {
    $_SESSION['error'] = "Quiz not found or unauthorized.";
    header("Location: my_quiz.php");
    exit;
}

$quiz = $quiz_result->fetch_assoc();

// Fetch questions
$questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id";
$stmt_questions = $conn->prepare($questions_query);
$stmt_questions->bind_param("i", $quiz_id);
$stmt_questions->execute();
$questions_result = $stmt_questions->get_result();
$questions = $questions_result->fetch_all(MYSQLI_ASSOC);

// If viewing specific student's result
if ($student_id > 0 || $result_id > 0) {
    if ($result_id > 0) {
        $results_query = "
       SELECT 
    qr.*,
    s.name AS student_name,
    s.student_code,
    s.phone
FROM quiz_results qr
JOIN students s ON qr.student_id = s.id
WHERE qr.quiz_id = ?
ORDER BY qr.score DESC, qr.submitted_at DESC

        ";
        $stmt_results = $conn->prepare($results_query);
        $stmt_results->bind_param("i", $result_id);
    } else {
        $results_query = "
        SELECT 
            qr.*,
            s.name as student_name,
            s.roll_number,
            s.email
        FROM quiz_results qr
        JOIN students s ON qr.student_id = s.id
        WHERE qr.quiz_id = ? AND qr.student_id = ?
        ";
        $stmt_results = $conn->prepare($results_query);
        $stmt_results->bind_param("ii", $quiz_id, $student_id);
    }

    $stmt_results->execute();
    $student_result = $stmt_results->get_result()->fetch_assoc();
    $answers = $student_result ? json_decode($student_result['answers'], true) : [];

    // Fetch manually awarded marks
    $manual_marks_query = "
    SELECT question_id, marks_awarded 
    FROM quiz_question_marks 
    WHERE result_id = ?
    ";
    $stmt_manual = $conn->prepare($manual_marks_query);
    $stmt_manual->bind_param("i", $student_result['id']);
    $stmt_manual->execute();
    $manual_marks_result = $stmt_manual->get_result();
    $manual_marks = [];
    while ($row = $manual_marks_result->fetch_assoc()) {
        $manual_marks[$row['question_id']] = $row['marks_awarded'];
    }

    $mode = 'student_view';
} else {
    // Fetch all quiz results with student details
    $all_results_query = "
  SELECT 
    qr.*,
    s.name AS student_name,
    s.student_code,
    s.phone
FROM quiz_results qr
JOIN students s ON qr.student_id = s.id
WHERE qr.quiz_id = ?
ORDER BY qr.score DESC, qr.submitted_at DESC

    ";

    $stmt_all_results = $conn->prepare($all_results_query);
    $stmt_all_results->bind_param("i", $quiz_id);
    $stmt_all_results->execute();
    $results = $stmt_all_results->get_result();

    // Calculate statistics
    $stats_query = "
    SELECT 
        COUNT(*) as total_submissions,
        AVG(score) as avg_score,
        MAX(score) as max_score,
        MIN(score) as min_score
    FROM quiz_results 
    WHERE quiz_id = ?
    ";

    $stmt_stats = $conn->prepare($stats_query);
    $stmt_stats->bind_param("i", $quiz_id);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result()->fetch_assoc();

    // Fetch enrolled students
    $enrolled_query = "
    SELECT COUNT(*) as total_enrolled
    FROM student_enrollments 
    WHERE batch_id = ? AND status = 'active'
    ";

    $stmt_enrolled = $conn->prepare($enrolled_query);
    $stmt_enrolled->bind_param("i", $quiz['batch_id']);
    $stmt_enrolled->execute();
    $enrolled_result = $stmt_enrolled->get_result()->fetch_assoc();

    $total_enrolled = $enrolled_result['total_enrolled'] ?? 0;
    $submission_rate = $total_enrolled > 0 ? ($stats_result['total_submissions'] / $total_enrolled) * 100 : 0;

    $mode = 'overview';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $mode === 'student_view' ? 'Mark Quiz' : 'Quiz Results'; ?> | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .score-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 4px;
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

        .tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
        }

        .tab.active {
            border-bottom-color: #3b82f6;
            color: #3b82f6;
        }

        .question-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .question-card.correct {
            border-color: #10b981;
            background-color: #f0fdf4;
        }

        .question-card.incorrect {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .mark-input {
            width: 80px;
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-align: center;
        }

        .mark-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
                        <h1 class="text-3xl font-bold text-gray-800">
                            <?php echo $mode === 'student_view' ? 'Mark Student Quiz' : 'Quiz Results'; ?>
                        </h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                            <?php echo htmlspecialchars($quiz['title']); ?>
                            <span class="mx-2">•</span>
                            <?php echo htmlspecialchars($quiz['skill_name'] . ' - ' . $quiz['batch_name']); ?>
                        </p>
                    </div>
                    <div>
                        <?php if ($mode === 'student_view'): ?>
                            <a href="quiz_result.php?quiz_id=<?php echo $quiz_id; ?>" class="text-gray-600 hover:text-gray-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i> Back to All Results
                            </a>
                        <?php else: ?>
                            <a href="my_quiz.php" class="text-gray-600 hover:text-gray-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Quizzes
                            </a>
                            <button onclick="exportResults()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                                <i class="fas fa-download mr-2"></i> Export Results
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'student_view'): ?>
                <!-- STUDENT QUIZ MARKING VIEW -->
                <?php if ($student_result): ?>
                    <!-- Student Info -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Student Information</h3>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($student_result['student_name']); ?></p>
                                <p class="text-sm text-gray-600">Roll No: <?php echo htmlspecialchars($student_result['roll_number']); ?></p>
                                <p class="text-sm text-gray-600">Email: <?php echo htmlspecialchars($student_result['email']); ?></p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Current Score</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($student_result['score'], 1); ?>/<?php echo $quiz['total_marks']; ?>
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php echo round(($student_result['score'] / $quiz['total_marks']) * 100, 1); ?>%
                                </p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Submission Details</h3>
                                <p class="text-sm text-gray-800">
                                    Submitted: <?php echo date('M d, Y H:i', strtotime($student_result['submitted_at'])); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Correct Answers: <?php echo $student_result['correct_answers']; ?>/<?php echo $student_result['total_questions']; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Marking Form -->
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="save_marks">
                        <input type="hidden" name="result_id" value="<?php echo $student_result['id']; ?>">

                        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-lg font-bold text-gray-800">Mark Questions</h2>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                    <i class="fas fa-save mr-2"></i> Save All Marks
                                </button>
                            </div>

                            <div class="space-y-4">
                                <?php foreach ($questions as $index => $question):
                                    $student_answer = $answers[$question['id']] ?? 'Not answered';
                                    $is_correct = $student_answer == $question['correct_answer'];
                                    $awarded_marks = $manual_marks[$question['id']] ?? ($is_correct ? $question['marks'] : 0);
                                    $options = $question['options'] ? json_decode($question['options'], true) : [];
                                ?>
                                    <div class="question-card <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                                        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                                            <div class="flex items-center">
                                                <span class="font-medium text-gray-700 mr-3">Q<?php echo $index + 1; ?></span>
                                                <span class="text-sm text-gray-500">
                                                    (<?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>)
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm text-gray-600">
                                                    Max Marks: <?php echo $question['marks']; ?>
                                                </span>
                                                <div>
                                                    <label class="text-sm text-gray-600 mr-2">Award:</label>
                                                    <input type="number"
                                                        name="marks[<?php echo $question['id']; ?>]"
                                                        class="mark-input"
                                                        value="<?php echo $awarded_marks; ?>"
                                                        min="0"
                                                        max="<?php echo $question['marks']; ?>"
                                                        step="0.5">
                                                    <span class="text-sm text-gray-600 ml-1">/<?php echo $question['marks']; ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-4">
                                            <p class="text-gray-800 mb-3"><?php echo htmlspecialchars($question['question']); ?></p>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-500 mb-1">Student's Answer:</p>
                                                    <div class="p-3 bg-white border rounded">
                                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                            <div class="flex items-center">
                                                                <div class="w-6 h-6 border rounded flex items-center justify-center mr-2 
                                                                    <?php echo $is_correct ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'; ?>">
                                                                    <?php echo htmlspecialchars($student_answer); ?>
                                                                </div>
                                                                <span>
                                                                    <?php echo htmlspecialchars($options[$student_answer] ?? $student_answer); ?>
                                                                </span>
                                                            </div>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($student_answer); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-sm font-medium text-gray-500 mb-1">Correct Answer:</p>
                                                    <div class="p-3 bg-white border border-green-200 rounded">
                                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                            <div class="flex items-center">
                                                                <div class="w-6 h-6 border border-green-500 rounded flex items-center justify-center mr-2 bg-green-50">
                                                                    <?php echo htmlspecialchars($question['correct_answer']); ?>
                                                                </div>
                                                                <span class="font-medium">
                                                                    <?php echo htmlspecialchars($options[$question['correct_answer']] ?? $question['correct_answer']); ?>
                                                                </span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="font-medium"><?php echo htmlspecialchars($question['correct_answer']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Feedback Section -->
                                            <div class="mt-3">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Feedback (Optional):</label>
                                                <textarea name="feedback[<?php echo $question['id']; ?>]"
                                                    rows="2"
                                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                                    placeholder="Add feedback for this answer..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex justify-between items-center mt-6 pt-6 border-t">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Total Marks: <span id="totalMarks"><?php echo $student_result['score']; ?></span>/<?php echo $quiz['total_marks']; ?>
                                    </p>
                                </div>
                                <div>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                        <i class="fas fa-save mr-2"></i> Save All Marks
                                    </button>
                                    <a href="quiz_result.php?quiz_id=<?php echo $quiz_id; ?>" class="ml-3 text-gray-600 hover:text-gray-800">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-slash text-gray-300 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Student Result Not Found</h3>
                        <p class="text-gray-500">This student hasn't submitted the quiz yet or the result doesn't exist.</p>
                        <a href="quiz_result.php?quiz_id=<?php echo $quiz_id; ?>" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-2"></i> Back to All Results
                        </a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- OVERVIEW VIEW (List of all students) -->

                <!-- Quiz Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Submissions</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo $stats_result['total_submissions'] ?? 0; ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <?php echo round($submission_rate, 1); ?>% of enrolled students
                        </p>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Average Score</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($stats_result['avg_score'] ?? 0, 1); ?>/<?php echo $quiz['total_marks']; ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="score-bar mt-3">
                            <div class="score-fill" style="width: <?php echo $quiz['total_marks'] > 0 ? (($stats_result['avg_score'] ?? 0) / $quiz['total_marks']) * 100 : 0; ?>%"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Highest Score</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($stats_result['max_score'] ?? 0, 1); ?>/<?php echo $quiz['total_marks']; ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <?php echo round(($stats_result['max_score'] ?? 0) / $quiz['total_marks'] * 100, 1); ?>%
                        </p>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Lowest Score</h3>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo round($stats_result['min_score'] ?? 0, 1); ?>/<?php echo $quiz['total_marks']; ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <?php echo round(($stats_result['min_score'] ?? 0) / $quiz['total_marks'] * 100, 1); ?>%
                        </p>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Rank</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Roll No.</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Score</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Grade</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Submitted At</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($results->num_rows > 0): ?>
                                    <?php $rank = 1;
                                    while ($result = $results->fetch_assoc()):
                                        $percentage = ($result['score'] / $quiz['total_marks']) * 100;
                                        $grade = getGrade($percentage);
                                    ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <?php if ($rank <= 3): ?>
                                                        <div class="w-8 h-8 rounded-full flex items-center justify-center 
                                                            <?php echo $rank == 1 ? 'bg-yellow-100 text-yellow-800' : ($rank == 2 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800'); ?>">
                                                            <?php echo $rank; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-600"><?php echo $rank; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($result['student_name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($result['email']); ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="text-gray-700"><?php echo htmlspecialchars($result['roll_number']); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-gray-900">
                                                    <?php echo round($result['score'], 1); ?>/<?php echo $quiz['total_marks']; ?>
                                                </span>
                                                <div class="score-bar mt-1">
                                                    <div class="score-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium <?php echo $percentage >= 60 ? 'text-green-600' : ($percentage >= 40 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                    <?php echo round($percentage, 1); ?>%
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 text-xs rounded-full grade-<?php echo strtolower($grade); ?>">
                                                    <?php echo $grade; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('M d, Y H:i', strtotime($result['submitted_at'])); ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <a href="quiz_result.php?quiz_id=<?php echo $quiz_id; ?>&result_id=<?php echo $result['id']; ?>"
                                                    class="text-blue-600 hover:text-blue-800 mr-3"
                                                    title="Mark/View Quiz">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="viewDetailedResult(<?php echo $result['id']; ?>)"
                                                    class="text-green-600 hover:text-green-800"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php $rank++;
                                    endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="py-8 px-4 text-center">
                                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-chart-bar text-gray-300 text-3xl"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Submissions Yet</h3>
                                            <p class="text-gray-500">Students haven't taken this quiz yet.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Detailed Result Modal (for overview view) -->
    <div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Student Result Details</h3>
                    <button type="button" onclick="closeResultModal()"
                        class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="resultModalContent" class="p-6">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        <?php if ($mode === 'student_view'): ?>
            // Auto-calculate total marks
            function updateTotalMarks() {
                let total = 0;
                document.querySelectorAll('.mark-input').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('totalMarks').textContent = total.toFixed(1);
            }

            // Update total when marks change
            document.querySelectorAll('.mark-input').forEach(input => {
                input.addEventListener('input', updateTotalMarks);
            });

            // Initialize total
            updateTotalMarks();
        <?php endif; ?>

        // For overview view
        function viewDetailedResult(resultId) {
            fetch(`get_result_details.php?result_id=${resultId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('resultModalContent').innerHTML = html;
                    document.getElementById('resultModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load result details');
                });
        }

        function closeResultModal() {
            document.getElementById('resultModal').classList.add('hidden');
        }

        function exportResults() {
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Rank,Student Name,Roll Number,Email,Score,Total Marks,Percentage,Grade,Submitted At\n";

            <?php
            if (isset($results) && $results->num_rows > 0) {
                mysqli_data_seek($results, 0);
                $rank = 1;
                while ($result = $results->fetch_assoc()):
                    $percentage = ($result['score'] / $quiz['total_marks']) * 100;
                    $grade = getGrade($percentage);
            ?>
                    csvContent += "<?php echo $rank; ?>,";
                    csvContent += "\"<?php echo addslashes($result['student_name']); ?>\",";
                    csvContent += "\"<?php echo addslashes($result['roll_number']); ?>\",";
                    csvContent += "\"<?php echo addslashes($result['email']); ?>\",";
                    csvContent += "<?php echo round($result['score'], 1); ?>,<?php echo $quiz['total_marks']; ?>,<?php echo round($percentage, 1); ?>%,";
                    csvContent += "<?php echo $grade; ?>,";
                    csvContent += "\"<?php echo date('M d, Y H:i', strtotime($result['submitted_at'])); ?>\"\n";
            <?php
                    $rank++;
                endwhile;
            }
            ?>

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "quiz_results_<?php echo $quiz_id; ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Close modal when clicking outside
        document.getElementById('resultModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeResultModal();
            }
        });
    </script>
</body>

</html>

<?php
// Helper function
function getGrade($percentage)
{
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}
?>
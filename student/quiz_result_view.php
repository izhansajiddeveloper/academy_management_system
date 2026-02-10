<?php
// quiz_result_view.php
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
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
$result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

if (!$quiz_id && !$result_id) {
    header("Location: student_quiz.php");
    exit;
}

// Fetch result details
if ($result_id) {
    $result_query = "
    SELECT 
        qr.*,
        q.title as quiz_title,
        q.description,
        q.total_marks,
        q.total_questions,
        b.batch_name,
        s.skill_name,
        s.id as skill_id,
        b.id as batch_id,
        ROUND((qr.score / q.total_marks) * 100, 1) as percentage,
        CASE 
            WHEN (qr.score / q.total_marks) >= 0.9 THEN 'A'
            WHEN (qr.score / q.total_marks) >= 0.8 THEN 'B'
            WHEN (qr.score / q.total_marks) >= 0.7 THEN 'C'
            WHEN (qr.score / q.total_marks) >= 0.6 THEN 'D'
            ELSE 'F'
        END as grade
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
    JOIN batches b ON q.batch_id = b.id
    JOIN skills s ON b.skill_id = s.id
    WHERE qr.id = ? AND qr.student_id = ?
    ";

    $stmt = $conn->prepare($result_query);
    $stmt->bind_param("ii", $result_id, $student_id);
} else {
    $result_query = "
    SELECT 
        qr.*,
        q.title as quiz_title,
        q.description,
        q.total_marks,
        q.total_questions,
        b.batch_name,
        s.skill_name,
        s.id as skill_id,
        b.id as batch_id,
        ROUND((qr.score / q.total_marks) * 100, 1) as percentage,
        CASE 
            WHEN (qr.score / q.total_marks) >= 0.9 THEN 'A'
            WHEN (qr.score / q.total_marks) >= 0.8 THEN 'B'
            WHEN (qr.score / q.total_marks) >= 0.7 THEN 'C'
            WHEN (qr.score / q.total_marks) >= 0.6 THEN 'D'
            ELSE 'F'
        END as grade
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
    JOIN batches b ON q.batch_id = b.id
    JOIN skills s ON b.skill_id = s.id
    WHERE qr.quiz_id = ? AND qr.student_id = ?
    ORDER BY qr.submitted_at DESC
    LIMIT 1
    ";

    $stmt = $conn->prepare($result_query);
    $stmt->bind_param("ii", $quiz_id, $student_id);
}

$stmt->execute();
$result_data = $stmt->get_result();

if ($result_data->num_rows === 0) {
    header("Location: student_quiz.php?error=result_not_found");
    exit;
}

$result = $result_data->fetch_assoc();
$answers = json_decode($result['answers'], true);

// Fetch questions with correct answers
$questions_query = "
SELECT qq.*, qqm.marks_awarded, qqm.feedback
FROM quiz_questions qq
LEFT JOIN quiz_question_marks qqm ON qq.id = qqm.question_id AND qqm.result_id = ?
WHERE qq.quiz_id = ?
ORDER BY qq.id
";

$stmt_questions = $conn->prepare($questions_query);
$stmt_questions->bind_param("ii", $result['id'], $result['quiz_id']);
$stmt_questions->execute();
$questions = $stmt_questions->get_result();

// Calculate statistics
$stats_query = "
SELECT 
    score,
    COUNT(*) OVER() as total_students,
    AVG(score) OVER() as class_avg,
    MAX(score) OVER() as top_score
FROM quiz_results 
WHERE quiz_id = ?
";

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("i", $result['quiz_id']);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();

$all_scores = [];
$total_students = 0;
$class_avg = 0;
$top_score = 0;

while ($row = $stats_result->fetch_assoc()) {
    $all_scores[] = $row['score'];
    $total_students = $row['total_students'];
    $class_avg = $row['class_avg'];
    $top_score = $row['top_score'];
}

// Find student's rank
$student_rank_query = "
SELECT COUNT(*) + 1 as rank_position
FROM quiz_results 
WHERE quiz_id = ? AND score > ?
";

$stmt_rank = $conn->prepare($student_rank_query);
$stmt_rank->bind_param("id", $result['quiz_id'], $result['score']);
$stmt_rank->execute();
$rank_result = $stmt_rank->get_result()->fetch_assoc();
$student_rank = $rank_result['rank_position'] ?? 1;

// Store questions for review section
$questions_review = [];
$questions->data_seek(0);
while ($question = $questions->fetch_assoc()) {
    $questions_review[] = $question;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quiz Result: <?php echo htmlspecialchars($result['quiz_title']); ?> | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .result-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
        }

        .score-excellent {
            background: #d1fae5;
            color: #065f46;
            border: 4px solid #10b981;
        }

        .score-good {
            background: #dbeafe;
            color: #1e40af;
            border: 4px solid #3b82f6;
        }

        .score-average {
            background: #fef3c7;
            color: #92400e;
            border: 4px solid #f59e0b;
        }

        .score-poor {
            background: #fee2e2;
            color: #991b1b;
            border: 4px solid #ef4444;
        }

        .question-review {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .question-review.correct {
            border-color: #10b981;
            background-color: #f0fdf4;
        }

        .question-review.incorrect {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .answer-label {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .answer-student {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .answer-correct {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .answer-wrong {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .performance-bar {
            height: 24px;
            background: #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .performance-fill {
            height: 100%;
            border-radius: 12px;
            position: absolute;
            top: 0;
            left: 0;
            transition: width 0.5s ease;
        }

        .rank-badge {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .rank-1 {
            background: #fef3c7;
            color: #92400e;
            border: 3px solid #f59e0b;
        }

        .rank-2 {
            background: #e5e7eb;
            color: #4b5563;
            border: 3px solid #9ca3af;
        }

        .rank-3 {
            background: #fcd9b8;
            color: #92400e;
            border: 3px solid #f59e0b;
        }

        .rank-other {
            background: #dbeafe;
            color: #1e40af;
            border: 3px solid #3b82f6;
        }

        .grade-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
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
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Quiz Result</h1>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                            <?php echo htmlspecialchars($result['quiz_title']); ?>
                            <span class="mx-2">•</span>
                            <?php echo htmlspecialchars($result['skill_name'] . ' - ' . $result['batch_name']); ?>
                        </p>
                    </div>
                    <div>
                        <a href="student_quiz.php" class="text-gray-600 hover:text-gray-800 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Quizzes
                        </a>
                        <button onclick="printResult()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-print mr-2"></i> Print Result
                        </button>
                    </div>
                </div>
            </div>

            <!-- Score Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Main Score Card -->
                <div class="result-card p-6 lg:col-span-2">
                    <div class="flex flex-col lg:flex-row items-center lg:items-start gap-6">
                        <!-- Score Circle -->
                        <div class="text-center">
                            <?php
                            $percentage = $result['percentage'];
                            $score_class = $percentage >= 90 ? 'score-excellent' : ($percentage >= 75 ? 'score-good' : ($percentage >= 60 ? 'score-average' : 'score-poor'));
                            ?>
                            <div class="<?php echo $score_class; ?> score-circle mx-auto mb-4">
                                <span class="text-3xl"><?php echo round($percentage, 0); ?>%</span>
                                <span class="text-sm mt-1">Score</span>
                            </div>
                            <div class="grade-badge grade-<?php echo strtolower($result['grade']); ?> inline-block mb-2">
                                Grade: <?php echo $result['grade']; ?>
                            </div>
                            <p class="text-sm text-gray-600">
                                <?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?> marks
                            </p>
                        </div>

                        <!-- Score Details -->
                        <div class="flex-1">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Performance Summary</h2>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Correct Answers</span>
                                        <span><?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?></span>
                                    </div>
                                    <div class="performance-bar">
                                        <div class="performance-fill bg-green-500"
                                            style="width: <?php echo ($result['correct_answers'] / $result['total_questions']) * 100; ?>%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Score Percentage</span>
                                        <span><?php echo round($percentage, 1); ?>%</span>
                                    </div>
                                    <div class="performance-bar">
                                        <div class="performance-fill bg-blue-500"
                                            style="width: <?php echo min($percentage, 100); ?>%"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-6">
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <div class="text-sm text-gray-500">Your Rank</div>
                                        <div class="text-2xl font-bold text-gray-800">#<?php echo $student_rank; ?></div>
                                        <div class="text-xs text-gray-500">out of <?php echo $total_students; ?> students</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <div class="text-sm text-gray-500">Submitted On</div>
                                        <div class="text-lg font-medium text-gray-800">
                                            <?php echo date('M d, Y', strtotime($result['submitted_at'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo date('h:i A', strtotime($result['submitted_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="result-card p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Class Statistics</h3>
                    <div class="space-y-4">
                        <?php
                        $class_avg_percentage = ($class_avg / $result['total_marks']) * 100;
                        $top_score_percentage = ($top_score / $result['total_marks']) * 100;
                        ?>
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Class Average</span>
                                <span><?php echo round($class_avg, 1); ?>/<?php echo $result['total_marks']; ?></span>
                            </div>
                            <div class="performance-bar">
                                <div class="performance-fill bg-yellow-500"
                                    style="width: <?php echo min($class_avg_percentage, 100); ?>%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Top Score</span>
                                <span><?php echo round($top_score, 1); ?>/<?php echo $result['total_marks']; ?></span>
                            </div>
                            <div class="performance-bar">
                                <div class="performance-fill bg-purple-500"
                                    style="width: <?php echo min($top_score_percentage, 100); ?>%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Your Performance</span>
                                <span><?php echo round($percentage, 1); ?>%</span>
                            </div>
                            <div class="performance-bar">
                                <div class="performance-fill <?php echo $percentage >= $class_avg_percentage ? 'bg-green-500' : 'bg-red-500'; ?>"
                                    style="width: <?php echo min($percentage, 100); ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php
                                if ($percentage > $class_avg_percentage) {
                                    echo "You scored " . round($percentage - $class_avg_percentage, 1) . "% above class average!";
                                } elseif ($percentage < $class_avg_percentage) {
                                    echo "You scored " . round($class_avg_percentage - $percentage, 1) . "% below class average.";
                                } else {
                                    echo "You scored equal to class average.";
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Performance Tips -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Performance Tips</h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <?php if ($percentage < 60): ?>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-yellow-500"></i>
                                    Review the course materials thoroughly
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-yellow-500"></i>
                                    Practice more questions from this topic
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-yellow-500"></i>
                                    Ask your teacher for clarification
                                </li>
                            <?php elseif ($percentage < 80): ?>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-blue-500"></i>
                                    Good effort! Focus on improving weak areas
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-blue-500"></i>
                                    Review incorrect answers carefully
                                </li>
                            <?php else: ?>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-green-500"></i>
                                    Excellent performance! Keep up the good work
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-lightbulb text-green-500"></i>
                                    Share your study techniques with classmates
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Question Review -->
            <div class="result-card p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Question Review</h2>

                <div class="space-y-6" id="questionReview">
                    <?php
                    $question_num = 0;
                    foreach ($questions_review as $question):
                        $question_num++;
                        $student_answer = $answers[$question['id']] ?? 'Not answered';
                        $is_correct = $student_answer == $question['correct_answer'];
                        $awarded_marks = $question['marks_awarded'] ?? ($is_correct ? $question['marks'] : 0);
                        $options = $question['options'] ? json_decode($question['options'], true) : [];
                    ?>
                        <div class="question-review <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800">Question <?php echo $question_num; ?></h3>
                                    <p class="text-gray-700 mt-2"><?php echo htmlspecialchars($question['question']); ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm text-gray-500">Marks: </span>
                                    <span class="font-bold <?php echo $awarded_marks > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $awarded_marks; ?>/<?php echo $question['marks']; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <!-- Student's Answer -->
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-2">Your Answer:</p>
                                    <div class="p-3 bg-white border rounded-lg">
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            <?php if (isset($options[$student_answer])): ?>
                                                <span class="answer-label <?php echo $is_correct ? 'answer-correct' : 'answer-wrong'; ?>">
                                                    <?php echo htmlspecialchars($student_answer); ?>. <?php echo htmlspecialchars($options[$student_answer]); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="answer-label answer-wrong">
                                                    <?php echo htmlspecialchars($student_answer); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-gray-800"><?php echo htmlspecialchars($student_answer); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Correct Answer -->
                                <div>
                                    <p class="text-sm font-medium text-gray-500 mb-2">Correct Answer:</p>
                                    <div class="p-3 bg-white border border-green-200 rounded-lg">
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            <?php if (isset($options[$question['correct_answer']])): ?>
                                                <span class="answer-label answer-correct">
                                                    <?php echo htmlspecialchars($question['correct_answer']); ?>. <?php echo htmlspecialchars($options[$question['correct_answer']]); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="answer-label answer-correct">
                                                    <?php echo htmlspecialchars($question['correct_answer']); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-green-700 font-medium"><?php echo htmlspecialchars($question['correct_answer']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Teacher Feedback -->
                            <?php if (!empty($question['feedback'])): ?>
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-comment text-blue-500 mt-1"></i>
                                        <div>
                                            <p class="text-sm font-medium text-blue-800">Teacher's Feedback:</p>
                                            <p class="text-sm text-blue-700"><?php echo htmlspecialchars($question['feedback']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Explanation -->
                            <div class="mt-4">
                                <p class="text-sm text-gray-600">
                                    <?php if ($is_correct): ?>
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        Your answer is correct. You earned <?php echo $awarded_marks; ?> marks.
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-red-500 mr-1"></i>
                                        Your answer is incorrect. The correct answer is shown above.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Review Summary -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600"><?php echo $result['correct_answers']; ?></div>
                            <div class="text-sm text-gray-600">Correct Answers</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-600"><?php echo $result['total_questions'] - $result['correct_answers']; ?></div>
                            <div class="text-sm text-gray-600">Incorrect Answers</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600"><?php echo round($percentage, 1); ?>%</div>
                            <div class="text-sm text-gray-600">Accuracy Rate</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center gap-4">
                <a href="student_quiz.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Quizzes
                </a>
                <button onclick="printResult()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-print mr-2"></i> Print Result
                </button>
                <?php if ($result['skill_id']): ?>
                    <a href="../course_material.php?skill_id=<?php echo $result['skill_id']; ?>&batch_id=<?php echo $result['batch_id']; ?>"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                        <i class="fas fa-book mr-2"></i> Review Course Material
                    </a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Print result function
        function printResult() {
            const printContent = document.createElement('div');
            printContent.innerHTML = `
                <style>
                    @media print {
                        body * { visibility: hidden; }
                        .print-area, .print-area * { visibility: visible; }
                        .print-area { position: absolute; left: 0; top: 0; }
                        .no-print { display: none !important; }
                    }
                    .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                    .print-score { text-align: center; margin: 30px 0; }
                    .print-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .print-table th, .print-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    .print-table th { background-color: #f5f5f5; }
                </style>
                <div class="print-area">
                    <div class="print-header">
                        <h1>Quiz Result - <?php echo htmlspecialchars($result['quiz_title']); ?></h1>
                        <p><?php echo htmlspecialchars($result['skill_name'] . ' - ' . $result['batch_name']); ?></p>
                        <p>Student: <?php echo htmlspecialchars($student['name']); ?> | Date: <?php echo date('M d, Y', strtotime($result['submitted_at'])); ?></p>
                    </div>
                    
                    <div class="print-score">
                        <h2>Score: <?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?> (<?php echo round($percentage, 1); ?>%)</h2>
                        <p>Grade: <?php echo $result['grade']; ?> | Rank: #<?php echo $student_rank; ?> of <?php echo $total_students; ?></p>
                    </div>
                    
                    <table class="print-table">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Your Answer</th>
                                <th>Correct Answer</th>
                                <th>Marks</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q_num = 0;
                            foreach ($questions_review as $q):
                                $q_num++;
                                $s_answer = $answers[$q['id']] ?? 'Not answered';
                                $is_correct = $s_answer == $q['correct_answer'];
                                $a_marks = $q['marks_awarded'] ?? ($is_correct ? $q['marks'] : 0);
                            ?>
                            <tr>
                                <td>Q<?php echo $q_num; ?></td>
                                <td><?php echo htmlspecialchars($s_answer); ?></td>
                                <td><?php echo htmlspecialchars($q['correct_answer']); ?></td>
                                <td><?php echo $a_marks; ?>/<?php echo $q['marks']; ?></td>
                                <td><?php echo $is_correct ? 'Correct' : 'Incorrect'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; text-align: center;">
                        <p>Generated on <?php echo date('M d, Y H:i:s'); ?></p>
                        <p>Academy Management System</p>
                    </div>
                </div>
            `;

            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Quiz Result</title></head><body>');
            printWindow.document.write(printContent.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        // Export result as PDF (simplified)
        function exportResult() {
            alert('In a real application, this would generate and download a PDF file with your quiz result.');
        }

        // Share result (simplified)
        function shareResult() {
            const shareText = `I scored ${<?php echo $result['score']; ?>}/${<?php echo $result['total_marks']; ?>} (${<?php echo round($percentage, 1); ?>}%) on "${<?php echo json_encode($result['quiz_title']); ?>" quiz!`;
            if (navigator.share) {
                navigator.share({
                    title: 'My Quiz Result',
                    text: shareText,
                    url: window.location.href
                });
            } else {
                alert('Share feature is not available in your browser. You can copy this text:\n\n' + shareText);
            }
        }
    </script>
</body>

</html>
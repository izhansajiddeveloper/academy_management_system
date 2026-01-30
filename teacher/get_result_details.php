<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'teacher') {
    exit('Unauthorized');
}

$result_id = intval($_GET['result_id'] ?? 0);
$teacher_id = $_SESSION['teacher_id'];

// Verify teacher owns this result
$query = "
SELECT 
    qr.*,
    s.name as student_name,
    s.roll_number,
    s.email,
    q.title as quiz_title,
    q.total_marks,
    q.time_limit,
    b.batch_name,
    sk.skill_name
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
JOIN students s ON qr.student_id = s.id
JOIN batches b ON qr.batch_id = b.id
JOIN skills sk ON b.skill_id = sk.id
WHERE qr.id = ? AND q.teacher_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $result_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Result not found');
}

$quiz_result = $result->fetch_assoc();
$answers = json_decode($quiz_result['answers'], true);

// Fetch quiz questions
$questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id";
$stmt_q = $conn->prepare($questions_query);
$stmt_q->bind_param("i", $quiz_result['quiz_id']);
$stmt_q->execute();
$questions_result = $stmt_q->get_result();
?>

<div class="space-y-6">
    <!-- Student Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="text-sm font-medium text-gray-500">Student Information</h4>
            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($quiz_result['student_name']); ?></p>
            <p class="text-sm text-gray-600">Roll No: <?php echo htmlspecialchars($quiz_result['roll_number']); ?></p>
            <p class="text-sm text-gray-600">Email: <?php echo htmlspecialchars($quiz_result['email']); ?></p>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-500">Quiz Information</h4>
            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($quiz_result['quiz_title']); ?></p>
            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($quiz_result['skill_name'] . ' - ' . $quiz_result['batch_name']); ?></p>
            <p class="text-sm text-gray-600">Time Limit: <?php echo $quiz_result['time_limit']; ?> minutes</p>
        </div>
    </div>

    <!-- Score Summary -->
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-500">Score</p>
                <p class="text-2xl font-bold text-gray-800">
                    <?php echo round($quiz_result['score'], 1); ?>/<?php echo $quiz_result['total_marks']; ?>
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Percentage</p>
                <p class="text-2xl font-bold text-<?php echo ($quiz_result['score'] / $quiz_result['total_marks'] * 100) >= 60 ? 'green' : 'red'; ?>-600">
                    <?php echo round(($quiz_result['score'] / $quiz_result['total_marks']) * 100, 1); ?>%
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Correct Answers</p>
                <p class="text-2xl font-bold text-gray-800">
                    <?php echo $quiz_result['correct_answers']; ?>/<?php echo $quiz_result['total_questions']; ?>
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Submitted At</p>
                <p class="text-lg font-medium text-gray-800">
                    <?php echo date('M d, Y H:i', strtotime($quiz_result['submitted_at'])); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Detailed Answers -->
    <div>
        <h4 class="text-lg font-bold text-gray-800 mb-4">Detailed Answers</h4>

        <div class="space-y-4">
            <?php
            $question_num = 1;
            while ($question = $questions_result->fetch_assoc()):
                $student_answer = $answers[$question['id']] ?? 'Not answered';
                $is_correct = $student_answer == $question['correct_answer'];
                $options = $question['options'] ? json_decode($question['options'], true) : [];
            ?>
                <div class="border border-gray-200 rounded-lg p-4 <?php echo $is_correct ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h5 class="font-medium text-gray-800">
                                Q<?php echo $question_num; ?>: <?php echo htmlspecialchars($question['question']); ?>
                            </h5>
                            <p class="text-sm text-gray-500 mt-1">
                                Type: <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>
                                | Marks: <?php echo $question['marks']; ?>
                            </p>
                        </div>
                        <div>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $is_correct ? 'Correct' : 'Incorrect'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Student's Answer:</p>
                            <div class="p-2 bg-white border rounded">
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <?php echo htmlspecialchars($options[$student_answer] ?? $student_answer); ?>
                                    (<?php echo htmlspecialchars($student_answer); ?>)
                                <?php else: ?>
                                    <?php echo htmlspecialchars($student_answer); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Correct Answer:</p>
                            <div class="p-2 bg-white border border-green-200 rounded">
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <?php echo htmlspecialchars($options[$question['correct_answer']] ?? $question['correct_answer']); ?>
                                    (<?php echo htmlspecialchars($question['correct_answer']); ?>)
                                <?php else: ?>
                                    <?php echo htmlspecialchars($question['correct_answer']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                $question_num++;
            endwhile;
            ?>
        </div>
    </div>
</div>
<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'teacher') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$question_id = intval($_GET['question_id'] ?? 0);
$teacher_id = $_SESSION['teacher_id'];

// Verify teacher owns this question
$query = "
SELECT qq.* 
FROM quiz_questions qq
JOIN quizzes q ON qq.quiz_id = q.id
WHERE qq.id = ? AND q.teacher_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $question_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');

if ($result->num_rows > 0) {
    $question = $result->fetch_assoc();
    echo json_encode($question);
} else {
    echo json_encode(['error' => 'Question not found']);
}

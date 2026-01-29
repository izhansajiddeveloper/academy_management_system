<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'teacher') {
    echo json_encode([]);
    exit;
}

$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$teacher_id = $_SESSION['user_id'];

if ($batch_id > 0) {
    $query = "
    SELECT 
        sh.*,
        t.name as changed_by_name
    FROM syllabus_history sh
    JOIN teachers t ON sh.changed_by = t.id
    WHERE sh.batch_id = ?
    ORDER BY sh.created_at DESC
    LIMIT 50
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    echo json_encode($history);
} else {
    echo json_encode([]);
}

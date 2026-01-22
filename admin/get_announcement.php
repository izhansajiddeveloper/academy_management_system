<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Announcement ID required']);
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT a.*, s.session_name, u.name as created_by_name 
        FROM announcements a 
        LEFT JOIN sessions s ON a.session_id = s.id 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE a.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($announcement = mysqli_fetch_assoc($result)) {
    echo json_encode($announcement);
} else {
    echo json_encode(['error' => 'Announcement not found']);
}

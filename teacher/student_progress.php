<?php
require_once "../config/db.php";
require_once "../includes/auth_check.php";

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $skill_id   = $_POST['skill_id'];
    $session_id = $_POST['session_id'];
    $progress   = $_POST['progress'];

    $stmt = mysqli_prepare($conn, "
        INSERT INTO skill_progress (student_id, skill_id, session_id, progress_percentage)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE progress_percentage = VALUES(progress_percentage)
    ");
    mysqli_stmt_bind_param($stmt, "iiid", $student_id, $skill_id, $session_id, $progress);
    mysqli_stmt_execute($stmt);

    echo "Progress updated";
}
?>

<h2>Update Student Progress</h2>

<form method="post">
    <input type="number" name="student_id" placeholder="Student ID" required>
    <input type="number" name="skill_id" placeholder="Skill ID" required>
    <input type="number" name="session_id" placeholder="Session ID" required>
    <input type="number" name="progress" placeholder="Progress %" required>
    <button type="submit">Save</button>
</form>
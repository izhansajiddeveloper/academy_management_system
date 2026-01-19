<?php
require_once "../config/db.php";
require_once "../includes/auth_check.php";

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill_id   = $_POST['skill_id'];
    $session_id = $_POST['session_id'];
    $title      = $_POST['title'];

    $stmt = mysqli_prepare($conn, "
        INSERT INTO skill_quizzes (skill_id, session_id, title)
        VALUES (?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iis", $skill_id, $session_id, $title);
    mysqli_stmt_execute($stmt);

    echo "Quiz created";
}
?>

<h2>Create Quiz</h2>

<form method="post">
    <input type="number" name="skill_id" placeholder="Skill ID" required>
    <input type="number" name="session_id" placeholder="Session ID" required>
    <input type="text" name="title" placeholder="Quiz Title" required>
    <button type="submit">Create</button>
</form>
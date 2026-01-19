<?php
require_once "../config/db.php";
require_once "../includes/auth_check.php";

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill_id = $_POST['skill_id'];
    $content  = $_POST['content'];

    $stmt = mysqli_prepare($conn, "
        INSERT INTO skill_syllabus (skill_id, content)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE content = VALUES(content)
    ");
    mysqli_stmt_bind_param($stmt, "is", $skill_id, $content);
    mysqli_stmt_execute($stmt);

    echo "Syllabus saved";
}
?>

<h2>Skill Syllabus</h2>

<form method="post">
    <input type="number" name="skill_id" placeholder="Skill ID" required>
    <textarea name="content" placeholder="Syllabus content" required></textarea>
    <button type="submit">Save</button>
</form>
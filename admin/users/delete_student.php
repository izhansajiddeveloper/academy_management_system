<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id) {
    // Get user_id for student
    $result = mysqli_query($conn, "SELECT user_id FROM students WHERE id=$id");
    $student = mysqli_fetch_assoc($result);

    if ($student) {
        $user_id = $student['user_id'];

        // Mark student as inactive instead of deleting
        mysqli_query($conn, "UPDATE students SET status='inactive' WHERE id=$id");
        mysqli_query($conn, "UPDATE users SET status='inactive' WHERE id=$user_id");
    }
}

header("Location: students.php");
exit;

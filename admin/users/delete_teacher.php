<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id) {
    // Get user_id for teacher
    $result = mysqli_query($conn, "SELECT user_id FROM teachers WHERE id=$id");
    $teacher = mysqli_fetch_assoc($result);

    if ($teacher) {
        $user_id = $teacher['user_id'];

        // Mark teacher as inactive instead of deleting
        mysqli_query($conn, "UPDATE teachers SET status='inactive' WHERE id=$id");
        mysqli_query($conn, "UPDATE users SET status='inactive' WHERE id=$user_id");
    }
}

header("Location: teachers.php");
exit;

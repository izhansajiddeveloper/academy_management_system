<?php
// delete_progress.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['progress_id'])) {
    $progress_id = intval($_POST['progress_id']);

    // Verify the teacher owns this progress record
    $teacher_query = "SELECT * FROM teachers WHERE user_id = ?";
    $stmt_teacher = $conn->prepare($teacher_query);
    $stmt_teacher->bind_param("i", $_SESSION['user_id']);
    $stmt_teacher->execute();
    $teacher_result = $stmt_teacher->get_result()->fetch_assoc();
    $teacher_id = $teacher_result['id'];

    // Check if this progress record belongs to this teacher
    $check_query = "SELECT id FROM skill_progress WHERE id = ? AND updated_by = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $progress_id, $teacher_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        // Delete from progress_history first (foreign key constraint)
        $delete_history = "DELETE FROM progress_history WHERE progress_id = ?";
        $stmt_history = $conn->prepare($delete_history);
        $stmt_history->bind_param("i", $progress_id);
        $stmt_history->execute();

        // Delete from skill_progress
        $delete_progress = "DELETE FROM skill_progress WHERE id = ?";
        $stmt_progress = $conn->prepare($delete_progress);
        $stmt_progress->bind_param("i", $progress_id);

        if ($stmt_progress->execute()) {
            $_SESSION['success_message'] = "Progress record deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting progress record.";
        }
    } else {
        $_SESSION['error_message'] = "You don't have permission to delete this record.";
    }
}

// Redirect back to the progress page
header("Location: student_progress.php");
exit;

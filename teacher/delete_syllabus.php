<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == '1') {
    $syllabus_id = intval($_GET['id']);
    $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

    // Get teacher info
    $teacher_query = "SELECT id FROM teachers WHERE user_id = ?";
    $stmt_teacher = $conn->prepare($teacher_query);
    $stmt_teacher->bind_param("i", $_SESSION['user_id']);
    $stmt_teacher->execute();
    $teacher_result = $stmt_teacher->get_result()->fetch_assoc();
    $teacher_id = $teacher_result['id'];

    // Check if syllabus belongs to teacher
    $check_query = "SELECT id, file_path FROM skill_syllabus WHERE id = ? AND created_by = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $syllabus_id, $teacher_id);
    $stmt_check->execute();
    $syllabus = $stmt_check->get_result()->fetch_assoc();

    if ($syllabus) {
        // Delete file if exists
        if ($syllabus['file_path'] && file_exists($syllabus['file_path'])) {
            unlink($syllabus['file_path']);
        }

        // Delete from database
        $delete_query = "DELETE FROM skill_syllabus WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_query);
        $stmt_delete->bind_param("i", $syllabus_id);

        if ($stmt_delete->execute()) {
            $_SESSION['success_message'] = "Syllabus topic permanently deleted!";
        } else {
            $_SESSION['error_message'] = "Error deleting syllabus topic.";
        }
    } else {
        $_SESSION['error_message'] = "Syllabus not found or you don't have permission.";
    }

    header("Location: syllabus_management.php?batch_id=" . $batch_id);
    exit;
}

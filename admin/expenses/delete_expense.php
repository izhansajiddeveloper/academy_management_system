<?php
require_once __DIR__ . '/../../config/db.php';
session_start();

// Get the expense ID
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Soft delete: update status to 'inactive'
    $query = "UPDATE expenses SET status='inactive', updated_at=NOW() WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        // Redirect back to expenses list with success message
        header("Location: expenses.php?deleted=1");
        exit;
    } else {
        die("Error deleting expense: " . mysqli_error($conn));
    }
} else {
    die("Invalid Expense ID.");
}

<?php
require_once __DIR__ . '/../../config/db.php';
session_start();

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Soft delete by setting status to inactive
    $query = "UPDATE expense_categories SET status='inactive', updated_at=NOW() WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        header("Location: expense_categories.php?deleted=1");
        exit;
    } else {
        die("Error deleting category: " . mysqli_error($conn));
    }
} else {
    die("Invalid Category ID.");
}

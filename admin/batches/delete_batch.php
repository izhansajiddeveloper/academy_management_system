<?php
require_once __DIR__ . '/../../config/db.php';

// Check if batch ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Soft delete: mark batch as completed
    $query = "UPDATE batches SET status='completed' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        // Redirect back to active batches page
        header("Location: batches.php?msg=Batch marked as completed");
        exit;
    } else {
        echo "Error updating batch: " . mysqli_error($conn);
    }
} else {
    // No ID provided
    header("Location: batches.php");
    exit;
}

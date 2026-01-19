<?php
require_once __DIR__ . '/../../config/db.php';

// Check if session ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Soft delete: set status to inactive
    mysqli_query($conn, "UPDATE sessions SET status='inactive' WHERE id=$id");
}

// Redirect back to sessions list
header("Location: sessions.php");
exit;

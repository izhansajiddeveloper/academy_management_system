<?php
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $delete_query = "UPDATE monthly_profit SET status='inactive', updated_at=NOW() WHERE id=$id";
    mysqli_query($conn, $delete_query);
}

header("Location: profit.php");
exit;

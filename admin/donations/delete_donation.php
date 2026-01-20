<?php
require_once __DIR__ . '/../../config/db.php';

$id = intval($_GET['id']);
mysqli_query($conn, "UPDATE donations SET status='inactive' WHERE id=$id");

header("Location: donations.php");
exit;

<?php
// Include DB config (session already started)
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

// Optional: role-based access
// Example: restrict admin pages
// if ($_SESSION['user_type'] !== 'admin') {
//     header("Location: /index.php");
//     exit;
// }

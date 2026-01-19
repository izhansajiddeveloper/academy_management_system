<?php
require_once "../config/db.php";

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: /academy_management_system/index.php");
exit;

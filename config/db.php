<?php
// Start session
session_start();

// Database credentials
$server   = "localhost";
$username = "root";
$password = "";
$db_name  = "academy_management_system"; // fixed typo

// Enable MySQLi error reporting for debugging (optional)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Create connection
$conn = mysqli_connect($server, $username, $password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// Connection successful
// echo "Connected successfully";

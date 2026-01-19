<?php
require_once "../config/db.php";
require_once "../includes/auth_check.php";

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_user_id = $_SESSION['user_id'];

/* Get teacher info */
$stmt = mysqli_prepare($conn, "
    SELECT id, name 
    FROM teachers 
    WHERE user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacher_user_id);
mysqli_stmt_execute($stmt);
$teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$teacher_id = $teacher['id'];

/* Count batches */
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) total 
    FROM teacher_assignments 
    WHERE teacher_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$total_batches = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
?>

<h2>Welcome, <?php echo htmlspecialchars($teacher['name']); ?></h2>
<p>Total Assigned Batches: <strong><?php echo $total_batches; ?></strong></p>
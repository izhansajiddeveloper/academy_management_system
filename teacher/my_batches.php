<?php
require_once "../config/db.php";
require_once "../includes/auth_check.php";

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Get teacher id */
$stmt = mysqli_prepare($conn, "SELECT id FROM teachers WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$teacher_id = $teacher['id'];

/* Get assigned batches */
$query = "
    SELECT 
        b.id AS batch_id,
        b.batch_name,
        s.skill_name,
        se.session_name
    FROM teacher_assignments ta
    JOIN batches b ON ta.batch_id = b.id
    JOIN skills s ON b.skill_id = s.id
    JOIN sessions se ON b.session_id = se.id
    WHERE ta.teacher_id = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<h2>My Batches</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Batch</th>
        <th>Skill</th>
        <th>Session</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['batch_name']); ?></td>
            <td><?php echo htmlspecialchars($row['skill_name']); ?></td>
            <td><?php echo htmlspecialchars($row['session_name']); ?></td>
        </tr>
    <?php endwhile; ?>
</table>
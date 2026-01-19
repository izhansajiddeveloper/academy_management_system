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

/* Get class schedules */
$query = "
    SELECT 
        cs.class_day,
        cs.start_time,
        cs.end_time,
        cs.room_location,
        b.batch_name,
        s.skill_name,
        se.session_name
    FROM class_schedules cs
    JOIN batches b ON cs.batch_id = b.id
    JOIN skills s ON cs.skill_id = s.id
    JOIN sessions se ON cs.session_id = se.id
    WHERE cs.teacher_id = ?
    ORDER BY FIELD(cs.class_day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), cs.start_time
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $teacher_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<h2>My Class Schedule</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Day</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Batch</th>
        <th>Skill</th>
        <th>Session</th>
        <th>Room</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['class_day']); ?></td>
            <td><?php echo htmlspecialchars(substr($row['start_time'], 0, 5)); ?></td>
            <td><?php echo htmlspecialchars(substr($row['end_time'], 0, 5)); ?></td>
            <td><?php echo htmlspecialchars($row['batch_name']); ?></td>
            <td><?php echo htmlspecialchars($row['skill_name']); ?></td>
            <td><?php echo htmlspecialchars($row['session_name']); ?></td>
            <td><?php echo htmlspecialchars($row['room_location']); ?></td>
        </tr>
    <?php endwhile; ?>
</table>
<?php
require_once "../config/db.php";
require_once "../includes/auth_check.php";

if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$batch_id = $_GET['batch_id'] ?? null;

if (!$batch_id) {
    echo "Batch not selected";
    exit;
}

/* Fetch students of batch */
$query = "
    SELECT 
        se.student_id,
        st.name
    FROM student_enrollments se
    JOIN students st ON se.student_id = st.id
    WHERE se.batch_id = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);

/* Save attendance */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = date('Y-m-d');

    foreach ($_POST['attendance'] as $student_id => $status) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO attendance (student_id, batch_id, date, status)
            VALUES (?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iiss", $student_id, $batch_id, $date, $status);
        mysqli_stmt_execute($stmt);
    }
    echo "Attendance saved successfully";
}
?>

<h2>Mark Attendance</h2>

<form method="post">
    <?php while ($s = mysqli_fetch_assoc($students)): ?>
        <div>
            <?php echo htmlspecialchars($s['name']); ?>
            <select name="attendance[<?php echo $s['student_id']; ?>]">
                <option value="present">Present</option>
                <option value="absent">Absent</option>
            </select>
        </div>
    <?php endwhile; ?>
    <button type="submit">Save Attendance</button>
</form>
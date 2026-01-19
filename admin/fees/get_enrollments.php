<?php
require_once __DIR__ . '/../../config/db.php';

$student_id = intval($_GET['student_id'] ?? 0);

if ($student_id > 0) {
    $query = "SELECT id, skill_id, session_id, batch_id 
              FROM student_enrollments 
              WHERE student_id = $student_id AND status='active' 
              ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    
    echo '<option value="">Select Enrollment (Optional)</option>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . $row['id'] . '">Enrollment #' . $row['id'] . '</option>';
    }
} else {
    echo '<option value="">Select Student First</option>';
}
?>
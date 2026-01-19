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

/* Handle file upload */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material_file'])) {
    $skill_id = $_POST['skill_id'];
    $session_id = $_POST['session_id'];
    $batch_id = $_POST['batch_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $file = $_FILES['material_file'];
    $upload_dir = "../uploads/materials/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_path = $upload_dir . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO study_materials 
            (skill_id, session_id, batch_id, teacher_id, title, file_path, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iiiisss", $skill_id, $session_id, $batch_id, $teacher_id, $title, $file_path, $description);
        mysqli_stmt_execute($stmt);
        echo "Material uploaded successfully!";
    } else {
        echo "Failed to upload file.";
    }
}

/* Get teacher batches + skills for dropdown */
$query = "
    SELECT DISTINCT 
        b.id AS batch_id, 
        s.id AS skill_id, 
        b.batch_name, 
        s.skill_name,
        se.id AS session_id,
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
$assignments = mysqli_stmt_get_result($stmt);
?>

<h2>Upload Study Material</h2>

<form method="post" enctype="multipart/form-data">
    <label>Title:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="3"></textarea><br><br>

    <label>Batch / Skill / Session:</label><br>
    <select name="batch_id" required>
        <option value="">Select Batch</option>
        <?php while ($row = mysqli_fetch_assoc($assignments)): ?>
            <option value="<?php echo $row['batch_id']; ?>">
                <?php echo htmlspecialchars($row['batch_name'] . " - " . $row['skill_name'] . " (" . $row['session_name'] . ")"); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <select name="skill_id" required>
        <option value="">Select Skill</option>
        <?php mysqli_data_seek($assignments, 0); ?>
        <?php while ($row = mysqli_fetch_assoc($assignments)): ?>
            <option value="<?php echo $row['skill_id']; ?>">
                <?php echo htmlspecialchars($row['skill_name']); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <select name="session_id" required>
        <option value="">Select Session</option>
        <?php mysqli_data_seek($assignments, 0); ?>
        <?php while ($row = mysqli_fetch_assoc($assignments)): ?>
            <option value="<?php echo $row['session_id']; ?>">
                <?php echo htmlspecialchars($row['session_name']); ?>
            </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Choose File:</label><br>
    <input type="file" name="material_file" required><br><br>

    <button type="submit">Upload</button>
</form>
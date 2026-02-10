<?php
// generate_certificate.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Get student ID from students table using user_id from session
$user_id = $_SESSION['user_id'];

// Fetch student details
$student_query = "SELECT * FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($student_query);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit;
}

$student_id = $student['id'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skill_id']) && isset($_POST['batch_id'])) {
    $skill_id = intval($_POST['skill_id']);
    $batch_id = intval($_POST['batch_id']);
    $progress_percent = floatval($_POST['progress_percent'] ?? 0);
    $performance_score = floatval($_POST['performance_score'] ?? 0);

    // Verify student eligibility based on skill progress
    $eligibility_query = "
    SELECT 
        sp.*,
        s.skill_name,
        b.batch_name
    FROM skill_progress sp
    JOIN skills s ON sp.skill_id = s.id
    JOIN batches b ON sp.batch_id = b.id
    WHERE sp.student_id = ?
        AND sp.skill_id = ?
        AND sp.batch_id = ?
        AND sp.progress_percent >= 85
        AND sp.overall_performance >= 80
        AND NOT EXISTS (
            SELECT 1 FROM certificates c 
            WHERE c.student_id = sp.student_id 
            AND c.skill_id = sp.skill_id 
            AND c.batch_id = sp.batch_id
        )
    ";

    $stmt_eligibility = $conn->prepare($eligibility_query);
    $stmt_eligibility->bind_param("iii", $student_id, $skill_id, $batch_id);
    $stmt_eligibility->execute();
    $eligibility_result = $stmt_eligibility->get_result();

    if ($eligibility_result->num_rows > 0) {
        $eligible_data = $eligibility_result->fetch_assoc();

        // Generate certificate number
        $certificate_number = 'CERT-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-' . date('Ymd');
        $verification_code = strtoupper(bin2hex(random_bytes(8)));

        // Calculate expiry date (1 year from now)
        $issued_date = date('Y-m-d H:i:s');
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));

        // Use progress_percent from form or from database
        $avg_score = $progress_percent > 0 ? $progress_percent : $eligible_data['progress_percent'];

        // Insert certificate
        $insert_query = "
        INSERT INTO certificates (
            student_id, 
            skill_id, 
            batch_id, 
            certificate_number, 
            verification_code, 
            issued_date, 
            expiry_date,
            avg_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt_insert = $conn->prepare($insert_query);
        $stmt_insert->bind_param(
            "iiissssd",
            $student_id,
            $skill_id,
            $batch_id,
            $certificate_number,
            $verification_code,
            $issued_date,
            $expiry_date,
            $avg_score
        );

        if ($stmt_insert->execute()) {
            $certificate_id = $stmt_insert->insert_id;

            // Record certificate generation in audit log
            $audit_query = "INSERT INTO certificate_audit_log (certificate_id, student_id, action, action_date) VALUES (?, ?, 'generated', NOW())";
            $stmt_audit = $conn->prepare($audit_query);
            $stmt_audit->bind_param("ii", $certificate_id, $student_id);
            $stmt_audit->execute();

            // Redirect to download page
            header("Location: download_certificate.php?id=$certificate_id");
            exit;
        } else {
            $error = "Failed to generate certificate. Database error: " . $conn->error;
        }
    } else {
        // Check why not eligible
        $check_query = "
        SELECT 
            sp.progress_percent,
            sp.overall_performance,
            CASE 
                WHEN sp.progress_percent < 85 THEN 'Progress is below 85%'
                WHEN sp.overall_performance < 80 THEN 'Performance is below 80%'
                WHEN EXISTS (SELECT 1 FROM certificates c WHERE c.student_id = sp.student_id AND c.skill_id = sp.skill_id AND c.batch_id = sp.batch_id) 
                THEN 'Certificate already exists'
                ELSE 'Not eligible'
            END as reason
        FROM skill_progress sp
        WHERE sp.student_id = ?
            AND sp.skill_id = ?
            AND sp.batch_id = ?
        ";

        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bind_param("iii", $student_id, $skill_id, $batch_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows > 0) {
            $check_data = $check_result->fetch_assoc();
            $error = "You are not eligible for this certificate. Reason: " . $check_data['reason'];
        } else {
            $error = "You are not eligible for this certificate. Progress record not found.";
        }
    }
} else {
    header("Location: certificates.php");
    exit;
}

// If there's an error, show it
if (isset($error)): ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <title>Certificate Generation Failed</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>

    <body class="min-h-screen bg-gray-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
            <div class="text-center mb-6">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800">Certificate Generation Failed</h1>
            </div>
            <div class="mb-6">
                <p class="text-gray-600"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <div class="space-y-3">
                <a href="certificates.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Certificates
                </a>
                <a href="student_progress.php" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-medium text-center">
                    <i class="fas fa-chart-line mr-2"></i> Check Progress
                </a>
            </div>
        </div>
    </body>

    </html>
<?php endif; ?>
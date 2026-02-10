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

    // Verify student eligibility
    $eligibility_query = "
    SELECT 
        s.id as skill_id,
        s.skill_name,
        s.description as skill_description,
        b.id as batch_id,
        b.batch_name,
        COUNT(DISTINCT q.id) as total_quizzes_in_skill,
        COUNT(DISTINCT qr.quiz_id) as completed_quizzes,
        AVG((qr.score / q.total_marks) * 100) as avg_score,
        MIN((qr.score / q.total_marks) * 100) as min_score
    FROM skills s
    JOIN batches b ON b.skill_id = s.id
    JOIN quizzes q ON q.batch_id = b.id
    LEFT JOIN quiz_results qr ON qr.quiz_id = q.id AND qr.student_id = ?
    WHERE s.id = ? AND b.id = ?
    GROUP BY s.id, b.id
    HAVING 
        completed_quizzes = total_quizzes_in_skill 
        AND avg_score >= 80
        AND min_score >= 60
        AND NOT EXISTS (
            SELECT 1 FROM certificates c 
            WHERE c.student_id = ? 
            AND c.skill_id = s.id 
            AND c.batch_id = b.id
        )
    ";

    $stmt_eligibility = $conn->prepare($eligibility_query);
    $stmt_eligibility->bind_param("iiii", $student_id, $skill_id, $batch_id, $student_id);
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
            $eligible_data['avg_score']
        );

        if ($stmt_insert->execute()) {
            $certificate_id = $stmt_insert->insert_id;

            // Redirect to download page
            header("Location: download_certificate.php?id=$certificate_id");
            exit;
        } else {
            $error = "Failed to generate certificate. Please try again.";
        }
    } else {
        $error = "You are not eligible for this certificate. Make sure you have completed all quizzes with at least 80% average score.";
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
            <div class="text-center">
                <a href="certificates.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Certificates
                </a>
            </div>
        </div>
    </body>

    </html>
<?php endif; ?>
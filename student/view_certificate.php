<?php
// view_certificate.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Check if certificate ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: certificates.php?error=invalid_certificate");
    exit;
}

$certificate_id = intval($_GET['id']);

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

// Fetch certificate details
$certificate_query = "
SELECT 
    c.*,
    s.skill_name,
    s.description as skill_description,
    s.level as skill_level,
    b.batch_name,
    sp.progress_percent as avg_score,
    sp.overall_performance,
    sp.last_updated as issued_date,
    t.name as trainer_name
FROM certificates c
JOIN skills s ON c.skill_id = s.id
JOIN batches b ON c.batch_id = b.id
LEFT JOIN skill_progress sp ON sp.student_id = c.student_id 
    AND sp.skill_id = c.skill_id 
    AND sp.batch_id = c.batch_id
LEFT JOIN teacher_assignments ta ON ta.batch_id = b.id
LEFT JOIN teachers t ON ta.teacher_id = t.id
WHERE c.id = ? AND c.student_id = ?
";

$stmt_certificate = $conn->prepare($certificate_query);
$stmt_certificate->bind_param("ii", $certificate_id, $student_id);
$stmt_certificate->execute();
$certificate_result = $stmt_certificate->get_result();
$certificate = $certificate_result->fetch_assoc();

if (!$certificate) {
    header("Location: certificates.php?error=certificate_not_found");
    exit;
}

// Format dates
$issue_date = date('F d, Y', strtotime($certificate['issued_date']));
$expiry_date = $certificate['expiry_date'] ? date('F d, Y', strtotime($certificate['expiry_date'])) : 'No Expiry';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Certificate | <?php echo htmlspecialchars($certificate['skill_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;500;600;700&family=Cinzel:wght@400;700&display=swap');

        /* Landscape Certificate dimensions: 11" x 8.5" */
        .certificate-container {
            width: 11in;
            height: 8.5in;
            background: white;
            margin: 20px auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 15px solid #1e40af;
            font-family: 'Montserrat', sans-serif;
        }

        /* Decorative border corners */
        .border-corner {
            position: absolute;
            width: 40px;
            height: 40px;
            border-color: #1e40af;
        }

        .corner-tl {
            top: 10px;
            left: 10px;
            border-top: 3px solid #1e40af;
            border-left: 3px solid #1e40af;
        }

        .corner-tr {
            top: 10px;
            right: 10px;
            border-top: 3px solid #1e40af;
            border-right: 3px solid #1e40af;
        }

        .corner-bl {
            bottom: 10px;
            left: 10px;
            border-bottom: 3px solid #1e40af;
            border-left: 3px solid #1e40af;
        }

        .corner-br {
            bottom: 10px;
            right: 10px;
            border-bottom: 3px solid #1e40af;
            border-right: 3px solid #1e40af;
        }

        /* Certificate content */
        .certificate-content {
            padding: 30px 50px;
            height: 100%;
            position: relative;
        }

        /* Institute Header - Deep Indigo Blue */
        .institute-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .institute-name {
            font-family: 'Cinzel', serif;
            font-size: 32px;
            font-weight: 700;
            color: #2563eb;
            /* Bright Blue */
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .institute-tagline {
            font-size: 12px;
            color: #4f46e5;
            /* Indigo */
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* Divider line - Blue Gray */
        .divider-line {
            height: 1px;
            background: #64748b;
            margin: 15px 0;
            width: 100%;
        }

        /* Certificate Title - Navy Blue */
        .certificate-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #1e40af;
            /* Navy Blue */
            text-align: center;
            margin: 15px 0 5px 0;
        }

        .certificate-subtitle {
            font-size: 16px;
            color: #3b82f6;
            /* Royal Blue */
            text-align: center;
            margin-bottom: 20px;
            font-style: italic;
        }

        /* Student Name Section - Dark Blue Gray */
        .student-section {
            text-align: center;
            margin: 20px 0;
        }

        .presented-text {
            font-size: 14px;
            color: #1e293b;
            /* Dark Blue Gray */
            margin-bottom: 5px;
        }

        .student-name {
            font-size: 40px;
            font-weight: 700;
            color: #0284c7;
            /* Ocean Blue */
            margin: 10px 0;
            line-height: 1.2;
        }

        .completion-text {
            font-size: 16px;
            color: #334155;
            /* Steel Blue */
            margin: 10px 0;
        }

        /* Skill Name - Vibrant Blue */
        .skill-name {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            /* Vibrant Blue */
            text-align: center;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Description - Slate Blue */
        .description {
            font-size: 14px;
            color: #475569;
            /* Slate Blue */
            text-align: center;
            max-width: 800px;
            margin: 20px auto;
            line-height: 1.6;
        }

        /* Performance Metrics */
        .performance-metrics {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }

        .metric-box {
            background: #f8fafc;
            /* Light Blue background */
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            min-width: 180px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .metric-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Completion Score - Teal Blue */
        .completion-score .metric-value {
            color: #0899b2;
            /* Teal Blue */
        }

        .completion-score .metric-label {
            color: #0f766e;
            /* Dark Teal */
        }

        /* Performance Score - Royal Blue */
        .performance-score .metric-value {
            color: #3b82f6;
            /* Royal Blue */
        }

        .performance-score .metric-label {
            color: #1e40af;
            /* Navy Blue */
        }

        /* Proficiency Level - Indigo Blue */
        .proficiency-level .metric-value {
            color: #6366f1;
            /* Indigo Blue */
        }

        .proficiency-level .metric-label {
            color: #4338ca;
            /* Dark Indigo */
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .signature-box {
            text-align: center;
            flex: 1;
        }

        .signature-line {
            width: 200px;
            height: 1px;
            background: #3b82f6;
            /* Royal Blue */
            margin: 15px auto;
        }

        .signature-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .signature-title {
            font-size: 11px;
            font-style: italic;
        }

        /* Instructor signature - Navy Blue */
        .instructor-signature .signature-name {
            color: #1e40af;
            /* Navy Blue */
        }

        .instructor-signature .signature-title {
            color: #4f46e5;
            /* Indigo */
        }

        /* Director signature - Steel Blue */
        .director-signature .signature-name {
            color: #334155;
            /* Steel Blue */
        }

        .director-signature .signature-title {
            color: #475569;
            /* Slate Blue */
        }

        /* Footer with verification info */
        .certificate-footer {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            padding: 0 50px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
        }

        .certificate-id {
            font-family: monospace;
            font-weight: 600;
            color: #1e293b;
            /* Dark Blue Gray */
        }

        .verification {
            text-align: center;
        }

        .verification-text {
            color: #0284c7;
            /* Ocean Blue */
        }

        .verification-code {
            font-family: monospace;
            font-weight: 600;
            color: #0284c7;
            /* Ocean Blue */
        }

        .dates {
            text-align: right;
        }

        .issued-date {
            color: #3b82f6;
            /* Royal Blue */
        }

        .valid-date {
            color: #2563eb;
            /* Bright Blue */
        }

        /* Final border line - Light Blue Gray */
        .final-border {
            height: 1px;
            background: #e2e8f0;
            margin-top: 10px;
        }

        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }

            .certificate-container,
            .certificate-container * {
                visibility: visible;
            }

            .certificate-container {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-width: 20px;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Responsive scaling */
        @media screen and (max-width: 1200px) {
            .certificate-container {
                transform: scale(0.9);
                transform-origin: top center;
            }
        }

        @media screen and (max-width: 992px) {
            .certificate-container {
                transform: scale(0.8);
            }
        }

        @media screen and (max-width: 768px) {
            .certificate-container {
                transform: scale(0.7);
            }

            .performance-metrics {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .metric-box {
                min-width: 200px;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Certificate Preview</h1>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-certificate text-blue-500 mr-2"></i>
                            View and download your certificate
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="certificates.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Certificates
                        </a>
                        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-print mr-2"></i> Print
                        </button>
                        <a href="download_certificate.php?id=<?php echo $certificate_id; ?>"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-download mr-2"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Certificate Container -->
            <div class="certificate-container">
                <!-- Decorative Corners -->
                <div class="border-corner corner-tl"></div>
                <div class="border-corner corner-tr"></div>
                <div class="border-corner corner-bl"></div>
                <div class="border-corner corner-br"></div>

                <!-- Certificate Content -->
                <div class="certificate-content">
                    <!-- Institute Header -->
                    <div class="institute-header">
                        <div class="institute-name">EDUMASTER ACADEMY</div>
                        <div class="institute-tagline">Center for Professional Excellence</div>
                    </div>

                    <!-- Divider Line -->
                    <div class="divider-line"></div>

                    <!-- Certificate Title -->
                    <div class="certificate-title">Certificate of Completion</div>
                    <div class="certificate-subtitle">This Certificate is Awarded To</div>

                    <!-- Student Section -->
                    <div class="student-section">
                        <div class="presented-text">This is to certify that</div>
                        <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                        <div class="completion-text">has successfully completed the training course in</div>
                        <div class="skill-name"><?php echo htmlspecialchars($certificate['skill_name']); ?></div>
                    </div>

                    <!-- Description -->
                    <div class="description">
                        Having demonstrated proficiency, commitment, and excellence throughout the program,
                        achieving all required learning outcomes with outstanding performance.
                    </div>

                    <!-- Performance Metrics -->
                    <div class="performance-metrics">
                        <div class="metric-box completion-score">
                            <div class="metric-value"><?php echo round($certificate['avg_score'], 1); ?>%</div>
                            <div class="metric-label">COMPLETION SCORE</div>
                        </div>

                        <div class="metric-box performance-score">
                            <div class="metric-value"><?php echo round($certificate['overall_performance'], 1); ?>%</div>
                            <div class="metric-label">PERFORMANCE SCORE</div>
                        </div>

                        <div class="metric-box proficiency-level">
                            <div class="metric-value"><?php echo strtoupper($certificate['skill_level']); ?></div>
                            <div class="metric-label">PROFICIENCY LEVEL</div>
                        </div>
                    </div>

                    <!-- Signatures -->
                    <div class="signatures">
                        <div class="signature-box instructor-signature">
                            <div class="signature-line"></div>
                            <div class="signature-name"><?php echo htmlspecialchars($certificate['trainer_name'] ?? 'Course Instructor'); ?></div>
                            <div class="signature-title">Certified Trainer</div>
                        </div>

                        <div class="signature-box director-signature">
                            <div class="signature-line"></div>
                            <div class="signature-name">Academic Director</div>
                            <div class="signature-title">EduMaster Academy</div>
                        </div>
                    </div>

                    <!-- Footer with Verification Info -->
                    <div class="certificate-footer">
                        <div class="footer-content">
                            <div class="certificate-id">
                                Certificate ID: <?php echo $certificate['certificate_number']; ?>
                            </div>

                            <div class="verification">
                                <div class="verification-text">Verify online:</div>
                                <div class="verification-code">verify.edumaster.org/<?php echo $certificate['verification_code']; ?></div>
                            </div>

                            <div class="dates">
                                <div class="issued-date">Issued: <?php echo $issue_date; ?></div>
                                <div class="valid-date">Valid: <?php echo $expiry_date; ?></div>
                            </div>
                        </div>

                        <div class="final-border"></div>
                    </div>
                </div>
            </div>

            <!-- Certificate Info -->
            <div class="mt-8 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Certificate Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Skill Name</div>
                        <div class="font-medium"><?php echo htmlspecialchars($certificate['skill_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Batch</div>
                        <div class="font-medium"><?php echo htmlspecialchars($certificate['batch_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Certificate Number</div>
                        <div class="font-mono"><?php echo $certificate['certificate_number']; ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Verification Code</div>
                        <div class="font-mono"><?php echo $certificate['verification_code']; ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Issued Date</div>
                        <div class="font-medium"><?php echo $issue_date; ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Valid Until</div>
                        <div class="font-medium"><?php echo $expiry_date; ?></div>
                    </div>
                </div>

                <div class="mt-6 flex space-x-3">
                    <a href="../verify_certificate.php?code=<?php echo $certificate['verification_code']; ?>"
                        target="_blank"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center font-medium">
                        <i class="fas fa-shield-alt mr-2"></i> Verify Certificate Online
                    </a>
                    <button onclick="window.print()"
                        class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-print mr-2"></i> Print Certificate
                    </button>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Print functionality
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                setTimeout(() => {
                    window.print();
                }, 100);
            }
        });

        // Scale certificate for smaller screens
        function scaleCertificate() {
            const certificate = document.querySelector('.certificate-container');
            const screenWidth = window.innerWidth;

            if (screenWidth < 768) {
                certificate.style.transform = 'scale(0.7)';
            } else if (screenWidth < 992) {
                certificate.style.transform = 'scale(0.8)';
            } else if (screenWidth < 1200) {
                certificate.style.transform = 'scale(0.9)';
            } else {
                certificate.style.transform = 'scale(1)';
            }
        }

        window.addEventListener('resize', scaleCertificate);
        window.addEventListener('load', scaleCertificate);

        // Print button handler
        const printButton = document.querySelector('[onclick="window.print()"]');
        if (printButton) {
            printButton.addEventListener('click', function(e) {
                e.preventDefault();
                setTimeout(() => {
                    window.print();
                }, 100);
            });
        }
    </script>
</body>

</html>
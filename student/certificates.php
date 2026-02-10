<?php
// certificates.php
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

// Fetch certificates for the student (based on skill progress)
$certificates_query = "
SELECT 
    c.*,
    s.skill_name,
    s.description as skill_description,
    b.batch_name,
    sp.progress_percent as avg_score,
    sp.last_updated as issued_date
FROM certificates c
JOIN skills s ON c.skill_id = s.id
JOIN batches b ON c.batch_id = b.id
LEFT JOIN skill_progress sp ON sp.student_id = c.student_id 
    AND sp.skill_id = c.skill_id 
    AND sp.batch_id = c.batch_id
WHERE c.student_id = ?
ORDER BY c.issued_date DESC
";

$stmt_certificates = $conn->prepare($certificates_query);
$stmt_certificates->bind_param("i", $student_id);
$stmt_certificates->execute();
$certificates = $stmt_certificates->get_result();

// Available certificates (based on skill progress > 85%)
$available_certificates_query = "
SELECT 
    sp.skill_id,
    s.skill_name,
    s.description as skill_description,
    sp.batch_id,
    b.batch_name,
    sp.progress_percent as avg_score,
    sp.overall_performance,
    sp.last_updated,
    sp.topics_completed,
    sp.total_topics
FROM skill_progress sp
JOIN skills s ON sp.skill_id = s.id
JOIN batches b ON sp.batch_id = b.id
WHERE sp.student_id = ?
    AND sp.progress_percent >= 85
    AND sp.overall_performance >= 80
    AND NOT EXISTS (
        SELECT 1 
        FROM certificates c 
        WHERE c.student_id = sp.student_id 
        AND c.skill_id = sp.skill_id 
        AND c.batch_id = sp.batch_id
    )
ORDER BY sp.progress_percent DESC
";

$stmt_available = $conn->prepare($available_certificates_query);
$stmt_available->bind_param("i", $student_id);
$stmt_available->execute();
$available_certificates = $stmt_available->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Certificates | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .certificate-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .certificate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .certificate-preview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .certificate-preview::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .certificate-border {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 20px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-premium {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(90deg);
            transform-origin: 50% 50%;
        }

        .certificate-seal {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: gold;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -20px;
            right: -20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .progress-ring-container {
            width: 80px;
            height: 80px;
            position: relative;
        }

        .progress-ring-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 16px;
            font-weight: bold;
            color: #6366f1;
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
                        <h1 class="text-3xl font-bold text-gray-800">My Certificates</h1>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-certificate text-blue-500 mr-2"></i>
                            Your earned certificates based on skill progress
                        </p>
                    </div>
                    <div>
                        <a href="student_progress.php" class="text-gray-600 hover:text-gray-800 mr-4">
                            <i class="fas fa-chart-line mr-2"></i> View Progress
                        </a>
                    </div>
                </div>
            </div>

            <!-- Available Certificates -->
            <?php if ($available_certificates->num_rows > 0): ?>
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Available Certificates</h2>
                        <span class="badge badge-premium">
                            <i class="fas fa-gem mr-1"></i> Ready to Claim
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php while ($available = $available_certificates->fetch_assoc()): ?>
                            <div class="certificate-card p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($available['skill_name']); ?></h3>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($available['batch_name']); ?></p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">Skill Progress</div>
                                            <div class="text-xl font-bold text-green-600"><?php echo round($available['avg_score'], 1); ?>%</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">Performance</div>
                                            <div class="text-lg font-bold text-blue-600"><?php echo round($available['overall_performance'], 1); ?>%</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                                        <span>Topic Completion</span>
                                        <span><?php echo $available['topics_completed']; ?>/<?php echo $available['total_topics']; ?> topics</span>
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-green-500 to-blue-500 rounded-full"
                                            style="width: <?php echo ($available['topics_completed'] / $available['total_topics']) * 100; ?>%"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="badge badge-success">
                                            <i class="fas fa-trophy mr-1"></i> Eligible for Certificate
                                        </span>
                                        <span class="text-xs text-gray-500 ml-2">
                                            Last updated: <?php echo date('M d, Y', strtotime($available['last_updated'])); ?>
                                        </span>
                                    </div>
                                    <form action="generate_certificate.php" method="POST">
                                        <input type="hidden" name="skill_id" value="<?php echo $available['skill_id']; ?>">
                                        <input type="hidden" name="batch_id" value="<?php echo $available['batch_id']; ?>">
                                        <input type="hidden" name="progress_percent" value="<?php echo $available['avg_score']; ?>">
                                        <input type="hidden" name="performance_score" value="<?php echo $available['overall_performance']; ?>">
                                        <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2 rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-200">
                                            <i class="fas fa-download mr-2"></i> Generate Certificate
                                        </button>
                                    </form>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                        You have achieved <?php echo round($available['avg_score'], 1); ?>% progress with <?php echo round($available['overall_performance'], 1); ?>% overall performance.
                                        You qualify for a certificate!
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Earned Certificates -->
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-4">Earned Certificates</h2>

                <?php if ($certificates->num_rows > 0): ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php while ($certificate = $certificates->fetch_assoc()):
                            $issue_date = date('F d, Y', strtotime($certificate['issued_date']));
                            $expiry_date = $certificate['expiry_date'] ? date('F d, Y', strtotime($certificate['expiry_date'])) : 'No Expiry';
                            $is_expired = $certificate['expiry_date'] && strtotime($certificate['expiry_date']) < time();
                            $progress_percent = $certificate['avg_score'] ?? 0;
                        ?>
                            <div class="certificate-card p-6">
                                <div class="relative">
                                    <!-- Certificate Preview -->
                                    <div class="certificate-preview mb-6">
                                        <div class="certificate-border">
                                            <div class="relative z-10">
                                                <div class="text-xs opacity-80 mb-2">CERTIFICATE OF ACHIEVEMENT</div>
                                                <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($certificate['skill_name']); ?></h3>
                                                <div class="text-sm opacity-90 mb-4"><?php echo htmlspecialchars($certificate['batch_name']); ?></div>

                                                <div class="flex items-center justify-center space-x-4 mb-4">
                                                    <div class="text-center">
                                                        <div class="text-xs opacity-80">Issued On</div>
                                                        <div class="text-sm font-medium"><?php echo $issue_date; ?></div>
                                                    </div>
                                                    <div class="h-8 w-px bg-white opacity-30"></div>
                                                    <div class="text-center">
                                                        <div class="text-xs opacity-80">Progress</div>
                                                        <div class="text-sm font-medium"><?php echo round($progress_percent, 1); ?>%</div>
                                                    </div>
                                                    <div class="h-8 w-px bg-white opacity-30"></div>
                                                    <div class="text-center">
                                                        <div class="text-xs opacity-80">Certificate ID</div>
                                                        <div class="text-sm font-mono"><?php echo $certificate['certificate_number']; ?></div>
                                                    </div>
                                                </div>

                                                <div class="text-xs opacity-80">Presented to</div>
                                                <div class="text-lg font-bold"><?php echo htmlspecialchars($student['name']); ?></div>
                                            </div>

                                            <!-- Seal -->
                                            <div class="certificate-seal">
                                                <i class="fas fa-award text-2xl text-white"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Certificate Details -->
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span class="badge <?php echo $is_expired ? 'badge-warning' : 'badge-success'; ?>">
                                                    <?php if ($is_expired): ?>
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> Expired
                                                    <?php else: ?>
                                                        <i class="fas fa-check-circle mr-1"></i> Valid
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm text-gray-500">Skill Progress</div>
                                                <div class="text-lg font-bold text-green-600"><?php echo round($progress_percent, 1); ?>%</div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <div class="text-sm text-gray-500">Issued Date</div>
                                                <div class="font-medium"><?php echo $issue_date; ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Expiry Date</div>
                                                <div class="font-medium"><?php echo $expiry_date; ?></div>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-sm text-gray-600 mb-1">Verification Code</div>
                                            <div class="font-mono text-gray-800 text-lg"><?php echo $certificate['verification_code']; ?></div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex space-x-3 pt-4 border-t border-gray-200">
                                            <a href="view_certificate.php?id=<?php echo $certificate['id']; ?>"
                                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center font-medium">
                                                <i class="fas fa-eye mr-2"></i> View
                                            </a>
                                            <a href="download_certificate.php?id=<?php echo $certificate['id']; ?>"
                                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-center font-medium">
                                                <i class="fas fa-download mr-2"></i> Download PDF
                                            </a>
                                            <a href="../verify_certificate.php?code=<?php echo $certificate['verification_code']; ?>"
                                                target="_blank"
                                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-center font-medium">
                                                <i class="fas fa-shield-alt mr-2"></i> Verify
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <!-- No Certificates -->
                    <div class="text-center py-12">
                        <div class="mb-6">
                            <i class="fas fa-certificate text-6xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-700 mb-2">No Certificates Yet</h3>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">
                            Achieve 85% or more progress in any skill with at least 80% overall performance to earn your first certificate.
                        </p>
                        <div class="space-x-4">
                            <a href="student_progress.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                <i class="fas fa-chart-line mr-2"></i> Check Progress
                            </a>
                            <a href="student_dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-medium">
                                <i class="fas fa-home mr-2"></i> Go to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Certificate Requirements -->
            <div class="mt-8 certificate-card p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Certificate Requirements</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                        </div>
                        <h4 class="font-medium text-gray-800 mb-2">85% Progress</h4>
                        <p class="text-sm text-gray-600">Minimum skill completion progress</p>
                    </div>
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-star text-2xl text-green-600"></i>
                        </div>
                        <h4 class="font-medium text-gray-800 mb-2">80% Performance</h4>
                        <p class="text-sm text-gray-600">Minimum overall performance score</p>
                    </div>
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-check-circle text-2xl text-purple-600"></i>
                        </div>
                        <h4 class="font-medium text-gray-800 mb-2">Topic Completion</h4>
                        <p class="text-sm text-gray-600">Complete required topics</p>
                    </div>
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-award text-2xl text-yellow-600"></i>
                        </div>
                        <h4 class="font-medium text-gray-800 mb-2">One Year Validity</h4>
                        <p class="text-sm text-gray-600">Certificate valid for 1 year</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Function to show certificate preview in modal
        function previewCertificate(certificateId) {
            fetch(`preview_certificate.php?id=${certificateId}`)
                .then(response => response.text())
                .then(html => {
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-auto">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h3 class="text-lg font-bold">Certificate Preview</h3>
                                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="p-6">
                                ${html}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load certificate preview');
                });
        }

        // Function to share certificate
        function shareCertificate(certificateId, title) {
            if (navigator.share) {
                navigator.share({
                    title: `My Certificate: ${title}`,
                    text: `I earned a certificate for ${title}!`,
                    url: `${window.location.origin}/student/verify_certificate.php?id=${certificateId}`
                });
            } else {
                // Fallback: Copy verification link
                const link = `${window.location.origin}/verify_certificate.php?code=${certificateId}`;
                navigator.clipboard.writeText(link)
                    .then(() => alert('Verification link copied to clipboard!'))
                    .catch(() => alert('Please copy the verification link manually.'));
            }
        }
    </script>
</body>

</html>
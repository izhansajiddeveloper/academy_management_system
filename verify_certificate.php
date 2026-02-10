<?php
// verify_certificate.php
require_once 'config/db.php';

$verification_code = isset($_GET['code']) ? $_GET['code'] : '';
$certificate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($verification_code) && !$certificate_id) {
    header("Location: index.php");
    exit;
}

// Fetch certificate details with progress information
$query = "
SELECT 
    c.*,
    s.skill_name,
    s.description as skill_description,
    b.batch_name,
    st.name as student_name,

    sp.progress_percent,
    sp.overall_performance,
    sp.performance_level,
    sp.topics_completed,
    sp.total_topics,
    sp.last_updated as progress_updated
FROM certificates c
JOIN skills s ON c.skill_id = s.id
JOIN batches b ON c.batch_id = b.id
JOIN students st ON c.student_id = st.id
LEFT JOIN skill_progress sp ON sp.student_id = c.student_id 
    AND sp.skill_id = c.skill_id 
    AND sp.batch_id = c.batch_id
WHERE c.verification_code = ? OR c.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $verification_code, $certificate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Certificate not found or invalid verification code.";
} else {
    $certificate = $result->fetch_assoc();
    $is_expired = $certificate['expiry_date'] && strtotime($certificate['expiry_date']) < time();
    $is_valid = !$is_expired;

    // Calculate progress details
    $progress_percent = $certificate['progress_percent'] ?? $certificate['avg_score'];
    $performance_level = $certificate['performance_level'] ?? 'Excellent';
    $topics_completed = $certificate['topics_completed'] ?? 0;
    $total_topics = $certificate['total_topics'] ?? 0;
    $completion_ratio = $total_topics > 0 ? round(($topics_completed / $total_topics) * 100, 1) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Certificate | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .progress-ring {
            width: 120px;
            height: 120px;
        }

        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .performance-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
        }

        .badge-excellent {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .badge-advanced {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .badge-intermediate {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .badge-beginner {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <i class="fas fa-shield-check text-blue-600 text-3xl mr-3"></i>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Certificate Verification System</h1>
                        <p class="text-gray-600 text-sm">Academy of Excellence</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800 font-medium">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="contact.php" class="text-gray-600 hover:text-gray-800 font-medium">
                        <i class="fas fa-envelope mr-2"></i> Contact
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <?php if (isset($error)): ?>
                <!-- Error State -->
                <div class="bg-white rounded-xl shadow-xl p-8 text-center">
                    <div class="mb-6">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-500"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Certificate Not Found</h2>
                    <p class="text-gray-600 text-lg mb-6"><?php echo htmlspecialchars($error); ?></p>
                    <div class="space-x-4">
                        <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium text-lg shadow-lg hover:shadow-xl transition-all duration-200">
                            <i class="fas fa-home mr-2"></i> Return Home
                        </a>
                        <button onclick="window.history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-medium text-lg">
                            <i class="fas fa-arrow-left mr-2"></i> Go Back
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Certificate Found -->
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-8">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 p-8 text-white">
                        <div class="flex flex-col md:flex-row justify-between items-center">
                            <div class="mb-6 md:mb-0">
                                <h2 class="text-3xl font-bold mb-2">Certificate Verified Successfully</h2>
                                <p class="opacity-90 text-lg">Official verification result - Issued by Academy of Excellence</p>
                            </div>
                            <div class="text-center md:text-right">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-bold <?php echo $is_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> shadow-lg">
                                    <?php if ($is_valid): ?>
                                        <i class="fas fa-check-circle mr-2"></i> VALID CERTIFICATE
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle mr-2"></i> EXPIRED CERTIFICATE
                                    <?php endif; ?>
                                </span>
                                <div class="mt-2 text-sm opacity-80">
                                    Verified on <?php echo date('F d, Y'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate Details -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                            <!-- Left Column -->
                            <div class="lg:col-span-2">
                                <h3 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">Certificate Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="stat-card">
                                        <div class="text-sm text-gray-500 mb-1">Certificate Number</div>
                                        <div class="font-mono font-bold text-gray-800 text-xl"><?php echo $certificate['certificate_number']; ?></div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="text-sm text-gray-500 mb-1">Verification Code</div>
                                        <div class="font-mono text-gray-800 text-lg"><?php echo $certificate['verification_code']; ?></div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="text-sm text-gray-500 mb-1">Course Name</div>
                                        <div class="font-bold text-gray-800 text-xl"><?php echo htmlspecialchars($certificate['skill_name']); ?></div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="text-sm text-gray-500 mb-1">Batch</div>
                                        <div class="text-gray-800 text-lg"><?php echo htmlspecialchars($certificate['batch_name']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Progress Circle -->
                            <div class="flex flex-col items-center justify-center">
                                <div class="relative w-40 h-40"> <!-- Added explicit size here -->
                                    <svg class="progress-ring w-40 h-40 transform -rotate-90"> <!-- Added rotation to start from top -->
                                        <circle
                                            class="text-gray-800"
                                            stroke-width="12"
                                            stroke="currentColor"
                                            fill="transparent"
                                            r="70"
                                            cx="80"
                                            cy="80" />
                                        <circle
                                            class="progress-ring-circle text-blue-500"
                                            stroke-width="12"
                                            stroke-linecap="round"
                                            stroke="currentColor"
                                            fill="transparent"
                                            r="70"
                                            cx="80"
                                            cy="80"
                                            style="stroke-dasharray: 439.8; stroke-dashoffset: <?php echo 439.8 - (439.8 * $progress_percent / 100); ?>;" />
                                    </svg>

                                    <!-- Absolute Center Overlay -->
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <div class="text-3xl font-bold text-blue-600"><?php echo round($progress_percent, 0); ?>%</div>
                                        <div class="text-sm text-gray-600">Progress</div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <span class="performance-badge badge-<?php echo strtolower($performance_level); ?> px-3 py-1 rounded">
                                        <?php echo $performance_level; ?> Level
                                    </span>
                                </div>
                            </div>

                        </div>

                        <!-- Recipient Information -->
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">Recipient Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="stat-card">
                                    <div class="text-sm text-gray-500 mb-1">Recipient Name</div>
                                    <div class="font-bold text-gray-800 text-xl"><?php echo htmlspecialchars($certificate['student_name']); ?></div>
                                </div>
                                <div class="stat-card">
                                    <div class="text-sm text-gray-500 mb-1">Issued On</div>
                                    <div class="text-gray-800 text-lg"><?php echo date('F d, Y', strtotime($certificate['issued_date'])); ?></div>
                                </div>
                                <div class="stat-card">
                                    <div class="text-sm text-gray-500 mb-1">Expiry Date</div>
                                    <div class="text-gray-800 text-lg <?php echo $is_expired ? 'text-red-600 font-bold' : 'text-green-600'; ?>">
                                        <?php echo $certificate['expiry_date'] ? date('F d, Y', strtotime($certificate['expiry_date'])) : 'No Expiry'; ?>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="text-sm text-gray-500 mb-1">Topics Completed</div>
                                    <div class="font-bold text-green-600 text-xl">
                                        <?php echo $topics_completed; ?>/<?php echo $total_topics; ?> (<?php echo $completion_ratio; ?>%)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="mb-8 p-6 rounded-xl <?php echo $is_valid ? 'bg-green-50 border-2 border-green-200' : 'bg-red-50 border-2 border-red-200'; ?>">
                            <div class="flex items-start">
                                <i class="fas <?php echo $is_valid ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500'; ?> text-3xl mt-1 mr-4"></i>
                                <div>
                                    <h4 class="font-bold text-xl <?php echo $is_valid ? 'text-green-800' : 'text-red-800'; ?> mb-2">
                                        <?php echo $is_valid ? 'Certificate is Valid and Authentic' : 'Certificate is Expired'; ?>
                                    </h4>
                                    <p class="<?php echo $is_valid ? 'text-green-700' : 'text-red-700'; ?>">
                                        <?php if ($is_valid): ?>
                                            This certificate has been verified as authentic and is currently valid for verification purposes.
                                            The recipient demonstrated <?php echo round($progress_percent, 1); ?>% progress with
                                            <?php echo round($certificate['overall_performance'] ?? 0, 1); ?>% overall performance,
                                            earning a <?php echo $performance_level; ?> level rating.
                                        <?php else: ?>
                                            This certificate has expired and is no longer valid for official verification purposes.
                                            Please contact the issuing authority for more information.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Metrics -->
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6">Performance Metrics</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="stat-card text-center">
                                    <div class="text-4xl font-bold text-blue-600 mb-2">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-1">Progress Achievement</div>
                                    <div class="text-2xl font-bold"><?php echo round($progress_percent, 1); ?>%</div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        <?php echo $progress_percent >= 85 ? 'Excellent Progress' : ($progress_percent >= 70 ? 'Good Progress' : 'Satisfactory'); ?>
                                    </div>
                                </div>
                                <div class="stat-card text-center">
                                    <div class="text-4xl font-bold text-purple-600 mb-2">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-1">Overall Performance</div>
                                    <div class="text-2xl font-bold"><?php echo round($certificate['overall_performance'] ?? 0, 1); ?>%</div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        Level: <?php echo $performance_level; ?>
                                    </div>
                                </div>
                                <div class="stat-card text-center">
                                    <div class="text-4xl font-bold text-green-600 mb-2">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-1">Completion Status</div>
                                    <div class="text-2xl font-bold"><?php echo $completion_ratio; ?>%</div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        <?php echo $topics_completed; ?> of <?php echo $total_topics; ?> topics
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                            <a href="../student/download_certificate.php?id=<?php echo $certificate['id']; ?>&verify=true"
                                class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-200 text-center">
                                <i class="fas fa-download mr-3"></i> Download Official PDF
                            </a>
                            <button onclick="printVerification()"
                                class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-200 text-center">
                                <i class="fas fa-print mr-3"></i> Print Verification
                            </button>
                            <button onclick="shareVerification()"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-200 text-center">
                                <i class="fas fa-share-alt mr-3"></i> Share Result
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Verification Instructions -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">About This Verification</h3>
                    <div class="text-gray-600 space-y-4 text-lg">
                        <p>✅ <strong>Authenticity Confirmed:</strong> This certificate has been digitally verified and recorded in our official database.</p>
                        <p>📊 <strong>Progress-Based Achievement:</strong> This certificate was awarded based on demonstrated skill progress and performance metrics tracked through our academic system.</p>
                        <p>🔒 <strong>Secure Verification:</strong> Each certificate has a unique verification code that can be used to confirm its authenticity at any time.</p>
                        <p>📞 <strong>Need Help?</strong> If you have any questions about this verification, please contact our verification support team at <strong>verify@academy.edu</strong>.</p>
                    </div>

                    <div class="mt-8 p-6 bg-blue-50 rounded-xl border border-blue-200">
                        <h4 class="text-xl font-bold text-blue-800 mb-3">Quick Verification Reference</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-600">Certificate ID</div>
                                <div class="font-mono text-gray-800"><?php echo $certificate['certificate_number']; ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Verification Code</div>
                                <div class="font-mono text-gray-800"><?php echo $certificate['verification_code']; ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Issue Date</div>
                                <div class="text-gray-800"><?php echo date('Y-m-d', strtotime($certificate['issued_date'])); ?></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Verification URL</div>
                                <div class="text-sm text-blue-600 break-all">
                                    <?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?code=' . $certificate['verification_code']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center">
                <h3 class="text-xl font-bold mb-4">Academy of Excellence</h3>
                <p class="text-gray-300 mb-4">Official Certificate Verification System</p>
                <div class="flex justify-center space-x-6 mb-4">
                    <a href="#" class="text-gray-300 hover:text-white">
                        <i class="fab fa-twitter text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white">
                        <i class="fab fa-facebook text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white">
                        <i class="fab fa-linkedin text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white">
                        <i class="fab fa-instagram text-2xl"></i>
                    </a>
                </div>
                <p class="text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> Academy of Excellence. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        function printVerification() {
            window.print();
        }

        function shareVerification() {
            const shareData = {
                title: 'Certificate Verification: <?php echo isset($certificate) ? htmlspecialchars($certificate["student_name"]) : ""; ?>',
                text: 'Verified certificate for <?php echo isset($certificate) ? htmlspecialchars($certificate["skill_name"]) : ""; ?> - Progress: <?php echo isset($certificate) ? round($progress_percent, 1) : ""; ?>%',
                url: window.location.href
            };

            if (navigator.share) {
                navigator.share(shareData)
                    .then(() => console.log('Shared successfully'))
                    .catch((error) => console.log('Error sharing:', error));
            } else {
                // Fallback: Copy URL to clipboard
                navigator.clipboard.writeText(window.location.href)
                    .then(() => {
                        alert('Verification URL copied to clipboard!\n\nYou can share this link: ' + window.location.href);
                    })
                    .catch(() => {
                        alert('Please copy the URL manually:\n\n' + window.location.href);
                    });
            }
        }

        // Animate progress circle on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressCircle = document.querySelector('.progress-ring-circle');
            if (progressCircle) {
                const progress = <?php echo isset($progress_percent) ? $progress_percent : 0; ?>;
                const circumference = 2 * Math.PI * 54;
                const offset = circumference - (progress / 100) * circumference;
                progressCircle.style.strokeDashoffset = offset;
            }
        });
    </script>
</body>

</html>
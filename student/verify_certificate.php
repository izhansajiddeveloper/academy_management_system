<?php
// verify_certificate.php
require_once 'config/db.php';

$verification_code = isset($_GET['code']) ? $_GET['code'] : '';
$certificate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($verification_code) && !$certificate_id) {
    header("Location: index.php");
    exit;
}

// Fetch certificate details
$query = "
SELECT 
    c.*,
    s.skill_name,
    s.description as skill_description,
    b.batch_name,
    st.name as student_name,
    st.email as student_email
FROM certificates c
JOIN skills s ON c.skill_id = s.id
JOIN batches b ON c.batch_id = b.id
JOIN students st ON c.student_id = st.id
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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Verify Certificate | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl font-bold text-gray-800">Certificate Verification</h1>
                </div>
                <div>
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <?php if (isset($error)): ?>
                <!-- Error State -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="mb-6">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-500"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Certificate Not Found</h2>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error); ?></p>
                    <div class="space-x-4">
                        <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-home mr-2"></i> Return Home
                        </a>
                        <button onclick="window.history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Go Back
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Certificate Found -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold">Certificate Verified</h2>
                                <p class="opacity-90">Official verification result</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $is_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php if ($is_valid): ?>
                                        <i class="fas fa-check-circle mr-1"></i> VALID CERTIFICATE
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle mr-1"></i> EXPIRED CERTIFICATE
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate Details -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-4">Certificate Details</h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="text-sm text-gray-500">Certificate Number</div>
                                        <div class="font-mono font-bold text-gray-800"><?php echo $certificate['certificate_number']; ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Verification Code</div>
                                        <div class="font-mono text-gray-800"><?php echo $certificate['verification_code']; ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Course Name</div>
                                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($certificate['skill_name']); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Batch</div>
                                        <div class="text-gray-800"><?php echo htmlspecialchars($certificate['batch_name']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-4">Recipient Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="text-sm text-gray-500">Recipient Name</div>
                                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($certificate['student_name']); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Issued On</div>
                                        <div class="text-gray-800"><?php echo date('F d, Y', strtotime($certificate['issued_date'])); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Expiry Date</div>
                                        <div class="text-gray-800 <?php echo $is_expired ? 'text-red-600' : 'text-green-600'; ?>">
                                            <?php echo $certificate['expiry_date'] ? date('F d, Y', strtotime($certificate['expiry_date'])) : 'No Expiry'; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Average Score</div>
                                        <div class="font-bold text-green-600"><?php echo round($certificate['avg_score'], 1); ?>%</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="mt-8 p-4 rounded-lg <?php echo $is_valid ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                            <div class="flex items-start">
                                <i class="fas <?php echo $is_valid ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500'; ?> text-xl mt-1 mr-3"></i>
                                <div>
                                    <h4 class="font-bold <?php echo $is_valid ? 'text-green-800' : 'text-red-800'; ?>">
                                        <?php echo $is_valid ? 'Certificate is Valid' : 'Certificate is Expired'; ?>
                                    </h4>
                                    <p class="<?php echo $is_valid ? 'text-green-700' : 'text-red-700'; ?> mt-1">
                                        <?php if ($is_valid): ?>
                                            This certificate has been verified and is currently valid.
                                            It was issued by Academy of Excellence and confirms the recipient's completion of the course.
                                        <?php else: ?>
                                            This certificate has expired and is no longer valid for verification purposes.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Summary -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center p-4 border rounded-lg">
                                <div class="text-2xl font-bold text-blue-600 mb-2">
                                    <i class="fas fa-shield-check"></i>
                                </div>
                                <div class="text-sm text-gray-600">Verification Status</div>
                                <div class="font-bold <?php echo $is_valid ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $is_valid ? 'Verified' : 'Expired'; ?>
                                </div>
                            </div>
                            <div class="text-center p-4 border rounded-lg">
                                <div class="text-2xl font-bold text-purple-600 mb-2">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="text-sm text-gray-600">Issued Date</div>
                                <div class="font-bold"><?php echo date('M d, Y', strtotime($certificate['issued_date'])); ?></div>
                            </div>
                            <div class="text-center p-4 border rounded-lg">
                                <div class="text-2xl font-bold text-green-600 mb-2">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="text-sm text-gray-600">Average Score</div>
                                <div class="font-bold"><?php echo round($certificate['avg_score'], 1); ?>%</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex justify-center space-x-4">
                            <a href="download_certificate.php?id=<?php echo $certificate['id']; ?>&verify=true"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                                <i class="fas fa-download mr-2"></i> Download PDF
                            </a>
                            <button onclick="printVerification()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium">
                                <i class="fas fa-print mr-2"></i> Print Verification
                            </button>
                            <button onclick="shareVerification()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                                <i class="fas fa-share-alt mr-2"></i> Share Result
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Verification Instructions -->
                <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">About This Verification</h3>
                    <div class="text-gray-600 space-y-3">
                        <p>This verification confirms the authenticity of the certificate issued by Academy of Excellence.</p>
                        <p>The certificate has been digitally recorded in our system and can be verified at any time using the verification code.</p>
                        <p>If you have any questions about this verification, please contact our support team.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function printVerification() {
            window.print();
        }

        function shareVerification() {
            const shareData = {
                title: 'Certificate Verification Result',
                text: 'Verified certificate for <?php echo isset($certificate) ? htmlspecialchars($certificate["student_name"]) : ""; ?> - <?php echo isset($certificate) ? htmlspecialchars($certificate["skill_name"]) : ""; ?>',
                url: window.location.href
            };

            if (navigator.share) {
                navigator.share(shareData);
            } else {
                // Fallback: Copy URL to clipboard
                navigator.clipboard.writeText(window.location.href)
                    .then(() => alert('Verification URL copied to clipboard!'))
                    .catch(() => alert('Please copy the URL manually.'));
            }
        }
    </script>
</body>

</html>
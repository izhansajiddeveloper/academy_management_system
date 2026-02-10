<?php
// receipt.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Validate payment_id
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    header("Location: fees.php?error=no_payment_id");
    exit;
}

$payment_id = (int) $_GET['payment_id'];
$user_id    = $_SESSION['user_id'];

// Fetch logged-in student
$student_query = "SELECT id FROM students WHERE user_id = ? AND status = 'active'";
$stmt_student = $conn->prepare($student_query);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();

if ($student_result->num_rows === 0) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit;
}

$student = $student_result->fetch_assoc();
$student_id = $student['id'];

// Fetch payment + student + course info
$payment_query = "
SELECT 
    fc.*,
    stu.student_code,
    stu.name AS student_name,
    s.skill_name,
    b.batch_name,
    ses.session_name,
    fs.total_fee
FROM fee_collections fc
JOIN students stu ON stu.id = fc.student_id
JOIN skills s ON s.id = fc.skill_id
JOIN batches b ON b.id = fc.batch_id
JOIN sessions ses ON ses.id = fc.session_id
JOIN fee_structures fs 
    ON fs.skill_id = fc.skill_id 
   AND fs.session_id = fc.session_id
WHERE fc.id = ? 
  AND fc.student_id = ? 
  AND fc.status = 'active'
LIMIT 1
";

$stmt_payment = $conn->prepare($payment_query);
$stmt_payment->bind_param("ii", $payment_id, $student_id);
$stmt_payment->execute();
$payment_result = $stmt_payment->get_result();

if ($payment_result->num_rows === 0) {
    header("Location: fees.php?error=payment_not_found");
    exit;
}

$payment = $payment_result->fetch_assoc();

// Total fee
$total_fee = $payment['total_fee'] ?? 0;

// Calculate total paid
$total_paid_query = "
SELECT COALESCE(SUM(amount_paid), 0) AS total_paid
FROM fee_collections
WHERE student_id = ?
  AND skill_id = ?
  AND session_id = ?
  AND status = 'active'
";

$stmt_total_paid = $conn->prepare($total_paid_query);
$stmt_total_paid->bind_param(
    "iii",
    $student_id,
    $payment['skill_id'],
    $payment['session_id']
);
$stmt_total_paid->execute();
$total_paid_result = $stmt_total_paid->get_result();
$total_paid_data = $total_paid_result->fetch_assoc();

$total_paid = $total_paid_data['total_paid'] ?? 0;

// Pending amount
$pending_amount = max(0, $total_fee - $total_paid);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Receipt | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
            }
            .receipt-header {
                border-bottom: 2px solid #000 !important;
            }
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 120px;
            font-weight: bold;
            color: #3b82f6;
            transform: rotate(-45deg);
            z-index: 0;
            pointer-events: none;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .receipt-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            transform: rotate(45deg);
            z-index: 0;
        }
        
        .receipt-body {
            padding: 40px;
            position: relative;
            z-index: 1;
        }
        
        .receipt-footer {
            padding: 20px 40px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .amount-box {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin: 30px 0;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #047857;
        }
        
        .signature-area {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .institution-info {
            background: #f0f9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #0ea5e9;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 p-4 md:p-6">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto mt-6">
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-8 no-print">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Payment Receipt</h1>
                <p class="text-gray-600 mt-2">
                    <i class="fas fa-receipt text-blue-500 mr-2"></i>
                    Payment confirmation and receipt
                </p>
            </div>
            <div class="flex gap-3">
                <a href="fees.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg inline-flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    Back to Fees
                </a>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                    <i class="fas fa-print"></i>
                    Print Receipt
                </button>
                <a href="make_payment.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                    <i class="fas fa-credit-card"></i>
                    Make New Payment
                </a>
            </div>
        </div>

        <!-- Receipt Container -->
        <div class="receipt-container">
            <!-- Watermark -->
            <div class="watermark" style="top: 200px; left: 50px;">PAID</div>
            
            <!-- Receipt Header -->
            <div class="receipt-header">
                <div style="position: relative; z-index: 1;">
                    <h2 class="text-3xl font-bold mb-2">PAYMENT RECEIPT</h2>
                    <p class="text-blue-100">Official Payment Confirmation</p>
                    <div class="mt-4">
                        <span class="status-badge status-paid">
                            <i class="fas fa-check-circle mr-2"></i>
                            PAYMENT SUCCESSFUL
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Body -->
            <div class="receipt-body">
                <!-- Institution Info -->
                <div class="institution-info">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">EduSkill Pro Academy</h3>
                            <p class="text-gray-600">123 Education Street, Knowledge City</p>
                            <p class="text-gray-600">Phone: +91 1234567890 | Email: info@eduskillpro.com</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600">GSTIN: 27ABCDE1234F1Z5</p>
                            <p class="text-gray-600">PAN: ABCDE1234F</p>
                        </div>
                    </div>
                </div>
                
                <!-- Receipt Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left Column - Payment Info -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Payment Information</h3>
                        
                        <div class="space-y-4">
                            <div class="info-row">
                                <span class="text-gray-600">Receipt Number:</span>
                                <span class="font-bold text-gray-800">RCPT-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-gray-600">Payment Date:</span>
                                <span class="font-bold text-gray-800"><?php echo date('d/m/Y, h:i A', strtotime($payment['payment_date'])); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="font-bold text-gray-800 text-capitalize"><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></span>
                            </div>
                            
                            <?php if (!empty($payment['reference_no'])): ?>
                            <div class="info-row">
                                <span class="text-gray-600">Reference No:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['reference_no']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($payment['remarks'])): ?>
                            <div class="info-row">
                                <span class="text-gray-600">Remarks:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['remarks']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Right Column - Student Info -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Student Information</h3>
                        
                        <div class="space-y-4">
                            <div class="info-row">
                                <span class="text-gray-600">Student Name:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['student_name']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-gray-600">Admission No:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['student_code']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-gray-600">Course:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['skill_name']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-gray-600">Batch:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['batch_name']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-gray-600">Session:</span>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($payment['session_name']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Amount Box -->
                <div class="amount-box">
                    <p class="text-blue-100 text-lg mb-2">Amount Paid</p>
                    <h2 class="text-4xl font-bold mb-2">₹<?php echo number_format($payment['amount_paid'], 2); ?></h2>
                    <p class="text-blue-100"><?php echo ucfirst(strtolower($payment['payment_method'])); ?> Payment</p>
                </div>
                
                <!-- Fee Summary -->
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Fee Summary</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Course Fee:</span>
                            <span class="font-bold text-gray-800">₹<?php echo number_format($payment['total_fee'], 2); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Previously Paid:</span>
                            <span class="font-bold text-green-600">₹<?php echo number_format($total_paid - $payment['amount_paid'], 2); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">This Payment:</span>
                            <span class="font-bold text-blue-600">₹<?php echo number_format($payment['amount_paid'], 2); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center pt-3 border-t border-gray-300">
                            <span class="text-gray-600">Total Paid:</span>
                            <span class="font-bold text-green-600">₹<?php echo number_format($total_paid, 2); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Pending Amount:</span>
                            <span class="font-bold text-red-600">₹<?php echo number_format($pending_amount, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Signature Area -->
                <div class="signature-area">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="border-t border-gray-400 mt-12 pt-2">
                                <p class="text-center text-gray-600">Student Signature</p>
                            </div>
                        </div>
                        <div>
                            <div class="border-t border-gray-400 mt-12 pt-2">
                                <p class="text-center text-gray-600">Authorized Signatory</p>
                                <p class="text-center text-sm text-gray-500">EduSkill Pro Academy</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Important Notes -->
                <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h4 class="font-bold text-gray-800 mb-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        Important Notes
                    </h4>
                    <ul class="text-sm text-gray-600 list-disc list-inside space-y-1">
                        <li>This is a computer-generated receipt and does not require a physical signature.</li>
                        <li>Please keep this receipt for your records and future reference.</li>
                        <li>For any discrepancies, contact accounts@eduskillpro.com within 7 days.</li>
                        <li>Receipt number must be quoted in all communications regarding this payment.</li>
                    </ul>
                </div>
            </div>
            
            <!-- Receipt Footer -->
            <div class="receipt-footer">
                <p class="text-gray-600 text-sm">
                    <i class="fas fa-lock text-green-500 mr-2"></i>
                    This receipt is securely generated and verified by EduSkill Pro Academy
                </p>
                <p class="text-gray-500 text-xs mt-2">
                    Generated on <?php echo date('d/m/Y h:i A'); ?> | Valid only with official stamp and signature
                </p>
            </div>
        </div>
        
        <!-- Action Buttons (Bottom) -->
        <div class="flex justify-center gap-4 mt-8 no-print">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2">
                <i class="fas fa-print"></i>
                Print Receipt
            </button>
            <button onclick="downloadReceipt()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2">
                <i class="fas fa-download"></i>
                Download as PDF
            </button>
            <a href="fees.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg inline-flex items-center gap-2">
                <i class="fas fa-list"></i>
                View Fee History
            </a>
        </div>
        
        <!-- QR Code for Verification -->
        <div class="mt-8 text-center no-print">
            <div class="inline-block p-4 bg-white rounded-lg shadow">
                <p class="text-sm text-gray-600 mb-2">Scan to verify receipt authenticity</p>
                <div class="bg-gray-200 w-32 h-32 mx-auto flex items-center justify-center">
                    <!-- In a real application, generate QR code here -->
                    <div class="text-center">
                        <i class="fas fa-qrcode text-4xl text-gray-400"></i>
                        <p class="text-xs text-gray-500 mt-2">QR Code</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Receipt ID: RCPT-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Function to simulate PDF download
        function downloadReceipt() {
            // In a real application, this would generate a PDF
            // For now, we'll show an alert and trigger print
            alert('PDF generation feature would be implemented here. For now, please use the print function.');
            window.print();
        }
        
        // Auto-print option (optional)
        <?php if (isset($_GET['print']) && $_GET['print'] == 'true'): ?>
        window.onload = function() {
            window.print();
        }
        <?php endif; ?>
        
        // Add print styles
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                @page {
                    margin: 20mm;
                }
                body * {
                    visibility: hidden;
                }
                .receipt-container, .receipt-container * {
                    visibility: visible;
                }
                .receipt-container {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    box-shadow: none;
                    border: none;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
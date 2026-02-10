<?php
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

// Get parameters
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_skill_id = intval($_POST['skill_id']);
    $payment_batch_id = intval($_POST['batch_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $transaction_id = trim($_POST['transaction_id']);
    $remarks = trim($_POST['remarks']);
    
    // Validate amount
    if ($amount <= 0) {
        $error = "Please enter a valid amount";
    } elseif (empty($payment_method)) {
        $error = "Please select a payment method";
    } else {
        // Get session_id from enrollment
        $session_query = "
        SELECT se.session_id, fs.total_fee
        FROM student_enrollments se
        JOIN fee_structures fs ON se.skill_id = fs.skill_id AND se.session_id = fs.session_id
        WHERE se.student_id = ? AND se.skill_id = ? AND se.batch_id = ?
        LIMIT 1
        ";
        
        $stmt_session = $conn->prepare($session_query);
        $stmt_session->bind_param("iii", $student_id, $payment_skill_id, $payment_batch_id);
        $stmt_session->execute();
        $session_result = $stmt_session->get_result();
        
        if ($session_result->num_rows > 0) {
            $session_data = $session_result->fetch_assoc();
            $session_id = $session_data['session_id'];
            $total_fee = $session_data['total_fee'];
            
            // Get total paid amount for this course
          // Get total paid amount for this course
$paid_query = "
    SELECT COALESCE(SUM(amount_paid), 0) AS total_paid
    FROM fee_collections 
    WHERE student_id = ? AND skill_id = ? AND session_id = ? AND status = 'active'
";

$stmt_paid = $conn->prepare($paid_query);
$stmt_paid->bind_param("iii", $student_id, $payment_skill_id, $session_id);
$stmt_paid->execute();
$paid_result = $stmt_paid->get_result();
$paid_data = $paid_result->fetch_assoc();
$total_paid = $paid_data['total_paid'] ?? 0;

$pending_amount = $total_fee - $total_paid;

// Validate payment amount doesn't exceed pending amount
if ($amount > $pending_amount) {
    $error = "Payment amount (₹" . number_format($amount) . ") exceeds pending amount (₹" . number_format($pending_amount) . ")";
} else {
    // Insert payment record
    $insert_query = "
        INSERT INTO fee_collections (
            student_id, skill_id, session_id, batch_id, 
            amount_paid, payment_date, payment_method, 
            remarks, status, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'active', NOW())
    ";

    $stmt_insert = $conn->prepare($insert_query);
    if ($stmt_insert === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters (8 placeholders)
    $stmt_insert->bind_param(
        "iiiisss", 
        $student_id, 
        $payment_skill_id, 
        $session_id, 
        $payment_batch_id,
        $amount, 
        $payment_method, 
        $remarks
    );

    if ($stmt_insert->execute()) {
        $payment_id = $stmt_insert->insert_id;
        // Redirect to receipt page
        header("Location: receipts.php?payment_id=" . $payment_id);
        exit;
    } else {
        $error = "Error processing payment. Please try again.";
    }
}
        }
    }
}

// Get enrolled courses with pending fees
$courses_query = "
SELECT 
    s.id as skill_id,
    s.skill_name,
    b.id as batch_id,
    b.batch_name,
    ses.session_name,
    fs.total_fee,
    COALESCE(fc.total_paid, 0) as paid_amount,
    (fs.total_fee - COALESCE(fc.total_paid, 0)) as pending_amount
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
LEFT JOIN fee_structures fs ON s.id = fs.skill_id AND se.session_id = fs.session_id
LEFT JOIN (
    SELECT skill_id, session_id, SUM(amount_paid) as total_paid
    FROM fee_collections 
    WHERE student_id = ? AND status = 'active'
    GROUP BY skill_id, session_id
) fc ON s.id = fc.skill_id AND se.session_id = fc.session_id
WHERE se.student_id = ? AND se.status = 'active'
AND (fs.total_fee - COALESCE(fc.total_paid, 0)) > 0
ORDER BY s.skill_name
";

$stmt_courses = $conn->prepare($courses_query);
$stmt_courses->bind_param("ii", $student_id, $student_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result();

// If skill_id is specified, get course details
$selected_course = null;
$pending_amount = 0;

if ($skill_id > 0 && $batch_id > 0) {
    $course_query = "
    SELECT 
        s.skill_name,
        b.batch_name,
        ses.session_name,
        fs.total_fee,
        COALESCE(fc.total_paid, 0) AS paid_amount,
        (fs.total_fee - COALESCE(fc.total_paid, 0)) AS pending_amount
    FROM student_enrollments se
    JOIN skills s ON se.skill_id = s.id
    JOIN batches b ON se.batch_id = b.id
    JOIN sessions ses ON se.session_id = ses.id
    JOIN fee_structures fs ON s.id = fs.skill_id AND se.session_id = fs.session_id
    LEFT JOIN (
        SELECT skill_id, session_id, SUM(amount_paid) AS total_paid
        FROM fee_collections 
        WHERE student_id = ? AND status = 'active'
        GROUP BY skill_id, session_id
    ) fc ON s.id = fc.skill_id AND se.session_id = fc.session_id
    WHERE se.student_id = ? AND se.skill_id = ? AND se.batch_id = ?
    LIMIT 1
    ";

    // 4 placeholders -> 4 types
    $stmt_course = $conn->prepare($course_query);
    $stmt_course->bind_param("iiii", $student_id, $student_id, $skill_id, $batch_id);
    $stmt_course->execute();
    $selected_course = $stmt_course->get_result()->fetch_assoc();

    if ($selected_course) {
        $pending_amount = max(0, $selected_course['pending_amount']);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Make Payment | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .payment-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-method:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .payment-method.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .payment-method-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .icon-cash { background: #d1fae5; color: #047857; }
        .icon-card { background: #dbeafe; color: #1d4ed8; }
        .icon-cheque { background: #fef3c7; color: #d97706; }
        .icon-online { background: #e0e7ff; color: #4f46e5; }

        .amount-button {
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .amount-button:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .amount-button.selected {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }

        .payment-form input,
        .payment-form select,
        .payment-form textarea {
            transition: all 0.2s;
        }

        .payment-form input:focus,
        .payment-form select:focus,
        .payment-form textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }

        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }

        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }

        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 0 8px 8px 0;
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
                        <h1 class="text-3xl font-bold text-gray-800">Make Payment</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-credit-card text-blue-500 mr-2"></i>
                            Submit your course fee payment
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <i class="fas fa-user-graduate mr-2"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($student['name']); ?></span>
                        </div>
                        <a href="fees.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Back to Fees
                        </a>
                    </div>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if (isset($error)): ?>
                <div class="error-box mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 mt-1"></i>
                        <div>
                            <h3 class="font-medium text-red-800">Error</h3>
                            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="success-box mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-600 mt-1"></i>
                        <div>
                            <h3 class="font-medium text-green-800">Success</h3>
                            <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Payment Form -->
                <div class="lg:col-span-2">
                    <div class="payment-card">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-800">Payment Details</h2>
                            <p class="text-sm text-gray-600 mt-1">Fill in the payment information below</p>
                        </div>

                        <form method="POST" class="p-6 payment-form" id="paymentForm" onsubmit="return validateForm()">
                            <!-- Course Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Course <span class="text-red-500">*</span>
                                </label>
                                
                                <?php if ($courses->num_rows > 0): ?>
                                    <select id="courseSelect" name="skill_id" required
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            onchange="updateCourseDetails()">
                                        <option value="">-- Select a Course --</option>
                                        <?php 
                                        $courses->data_seek(0); // Reset pointer
                                        while ($course = $courses->fetch_assoc()): ?>
                                            <option value="<?php echo $course['skill_id']; ?>"
                                                    data-batch-id="<?php echo $course['batch_id']; ?>"
                                                    data-pending="<?php echo $course['pending_amount']; ?>"
                                                    data-skill-name="<?php echo htmlspecialchars($course['skill_name']); ?>"
                                                    data-batch-name="<?php echo htmlspecialchars($course['batch_name']); ?>"
                                                    data-session="<?php echo htmlspecialchars($course['session_name']); ?>"
                                                    data-total="<?php echo $course['total_fee']; ?>"
                                                    data-paid="<?php echo $course['paid_amount']; ?>"
                                                    <?php echo ($skill_id == $course['skill_id'] && $batch_id == $course['batch_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['skill_name'] . ' - ' . $course['batch_name']); ?>
                                                (Pending: ₹<?php echo number_format($course['pending_amount']); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    
                                    <input type="hidden" id="batch_id" name="batch_id" value="<?php echo $batch_id; ?>">
                                <?php else: ?>
                                    <div class="text-center py-4 border border-gray-300 rounded-lg bg-gray-50">
                                        <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                                        <p class="text-gray-700">No pending fees found!</p>
                                        <p class="text-sm text-gray-500">All your course fees are paid.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Course Details -->
                            <div id="courseDetails" class="mb-6 <?php echo $selected_course ? '' : 'hidden'; ?>">
                                <?php if ($selected_course): ?>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <div class="text-sm text-gray-500">Course</div>
                                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($selected_course['skill_name']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Batch</div>
                                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($selected_course['batch_name']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Session</div>
                                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($selected_course['session_name']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Total Fee</div>
                                                <div class="font-medium text-gray-800">₹<?php echo number_format($selected_course['total_fee']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Paid Amount</div>
                                                <div class="font-medium text-green-600">₹<?php echo number_format($selected_course['paid_amount']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Pending Amount</div>
                                                <div class="font-medium text-red-600">₹<?php echo number_format($selected_course['pending_amount']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Amount Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Amount <span class="text-red-500">*</span>
                                </label>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                                    <button type="button" class="amount-button" onclick="setAmount(500)">₹500</button>
                                    <button type="button" class="amount-button" onclick="setAmount(1000)">₹1,000</button>
                                    <button type="button" class="amount-button" onclick="setAmount(2000)">₹2,000</button>
                                    <button type="button" class="amount-button" onclick="setAmount(5000)">₹5,000</button>
                                </div>
                                
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500">₹</span>
                                    </div>
                                    <input type="number" 
                                           id="amount" 
                                           name="amount" 
                                           required 
                                           min="1"
                                           step="1"
                                           value="<?php echo $pending_amount > 0 ? $pending_amount : ''; ?>"
                                           class="pl-8 w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Enter amount"
                                           onchange="validateAmount()"
                                           oninput="updateSummary()">
                                </div>
                                
                                <div id="amountSuggestions" class="mt-2">
                                    <?php if ($pending_amount > 0): ?>
                                        <button type="button" 
                                                onclick="setAmount(<?php echo $pending_amount; ?>)"
                                                class="text-sm bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded mr-2">
                                            Pay Full (₹<?php echo number_format($pending_amount); ?>)
                                        </button>
                                        <?php if ($pending_amount > 1000): ?>
                                            <button type="button" 
                                                    onclick="setAmount(<?php echo floor($pending_amount / 2); ?>)"
                                                    class="text-sm bg-green-100 text-green-800 hover:bg-green-200 px-3 py-1 rounded mr-2">
                                                50% (₹<?php echo number_format(floor($pending_amount / 2)); ?>)
                                            </button>
                                            <button type="button" 
                                                    onclick="setAmount(<?php echo floor($pending_amount / 4); ?>)"
                                                    class="text-sm bg-yellow-100 text-yellow-800 hover:bg-yellow-200 px-3 py-1 rounded">
                                                25% (₹<?php echo number_format(floor($pending_amount / 4)); ?>)
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="amountError" class="text-red-600 text-sm mt-2 hidden"></div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="payment-method" onclick="selectPaymentMethod('cash')">
                                        <div class="payment-method-icon icon-cash">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-medium text-gray-800">Cash</div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod('card')">
                                        <div class="payment-method-icon icon-card">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-medium text-gray-800">Card</div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod('cheque')">
                                        <div class="payment-method-icon icon-cheque">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-medium text-gray-800">Cheque</div>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-method" onclick="selectPaymentMethod('online')">
                                        <div class="payment-method-icon icon-online">
                                            <i class="fas fa-globe"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-medium text-gray-800">Online</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="payment_method" name="payment_method" required>
                                <div id="methodError" class="text-red-600 text-sm mt-2 hidden">Please select a payment method</div>
                            </div>

                            <!-- Transaction ID (for non-cash payments) -->
                            <div id="transactionField" class="mb-6 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Transaction/Reference ID <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="transaction_id" 
                                       name="transaction_id"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter transaction/reference number">
                                <div id="transactionError" class="text-red-600 text-sm mt-2 hidden">Transaction ID is required for this payment method</div>
                            </div>

                            <!-- Remarks -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Remarks (Optional)
                                </label>
                                <textarea 
                                    id="remarks" 
                                    name="remarks"
                                    rows="3"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Any additional information about this payment"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex gap-3">
                                <button type="button" onclick="window.location.href='fees.php'" 
                                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-medium">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        id="submitBtn"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Submit Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Summary & Instructions -->
                <div class="space-y-6">
                    <!-- Payment Summary -->
                    <div class="payment-card p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Payment Summary</h2>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Selected Course</span>
                                <span id="summaryCourse" class="font-medium text-right">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Amount</span>
                                <span id="summaryAmount" class="font-medium">₹0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method</span>
                                <span id="summaryMethod" class="font-medium">-</span>
                            </div>
                            <div class="pt-3 border-t border-gray-200">
                                <div class="flex justify-between">
                                    <span class="text-gray-800 font-bold">Total Payable</span>
                                    <span id="summaryTotal" class="text-xl font-bold text-blue-600">₹0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="payment-card p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Payment Instructions</h2>
                        
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info text-blue-600 text-xs"></i>
                                </div>
                                <p class="text-sm text-gray-600">Ensure you have sufficient balance before making payment.</p>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info text-blue-600 text-xs"></i>
                                </div>
                                <p class="text-sm text-gray-600">For online payments, keep your transaction ID ready.</p>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info text-blue-600 text-xs"></i>
                                </div>
                                <p class="text-sm text-gray-600">Receipt will be generated automatically after successful payment.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="payment-card p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Need Help?</h2>
                        
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-phone text-gray-400"></i>
                                <span class="text-sm text-gray-600">+91 1234567890</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-envelope text-gray-400"></i>
                                <span class="text-sm text-gray-600">accounts@eduskillpro.com</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-gray-400"></i>
                                <span class="text-sm text-gray-600">Mon-Fri: 9AM-6PM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="payment-card p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Important Notes</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="info-box">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-shield-alt text-blue-500 mt-1"></i>
                            <div>
                                <h3 class="font-medium text-gray-800 mb-1">Secure Payment</h3>
                                <p class="text-sm text-gray-600">All payments are processed securely and encrypted.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="warning-box">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1"></i>
                            <div>
                                <h3 class="font-medium text-gray-800 mb-1">Refund Policy</h3>
                                <p class="text-sm text-gray-600">Payments are non-refundable. Please review before submitting.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize course details if course is pre-selected
            const courseSelect = document.getElementById('courseSelect');
            if (courseSelect.value) {
                updateCourseDetails();
            }
            
            // Initialize amount validation
            validateAmount();
            
            // Initialize summary
            updateSummary();
            
            // Auto-select cash payment method
            selectPaymentMethod('cash');
        });

        // Update course details when selection changes
        function updateCourseDetails() {
            const courseSelect = document.getElementById('courseSelect');
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            const courseDetailsDiv = document.getElementById('courseDetails');
            const batchIdInput = document.getElementById('batch_id');
            
            if (selectedOption && selectedOption.value) {
                // Show course details
                courseDetailsDiv.classList.remove('hidden');
                
                // Update batch ID
                const batchId = selectedOption.dataset.batchId;
                batchIdInput.value = batchId;
                
                // Update course details display
                const detailsHTML = `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Course</div>
                                <div class="font-medium text-gray-800">${selectedOption.dataset.skillName}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Batch</div>
                                <div class="font-medium text-gray-800">${selectedOption.dataset.batchName}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Session</div>
                                <div class="font-medium text-gray-800">${selectedOption.dataset.session}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Total Fee</div>
                                <div class="font-medium text-gray-800">₹${parseInt(selectedOption.dataset.total).toLocaleString()}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Paid Amount</div>
                                <div class="font-medium text-green-600">₹${parseInt(selectedOption.dataset.paid).toLocaleString()}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Pending Amount</div>
                                <div class="font-medium text-red-600">₹${parseInt(selectedOption.dataset.pending).toLocaleString()}</div>
                            </div>
                        </div>
                    </div>
                `;
                
                courseDetailsDiv.innerHTML = detailsHTML;
                
                // Update amount input with pending amount
                const amountInput = document.getElementById('amount');
                const pendingAmount = parseFloat(selectedOption.dataset.pending) || 0;
                amountInput.value = pendingAmount > 0 ? pendingAmount : '';
                
                // Update amount suggestions
                const amountSuggestionsDiv = document.getElementById('amountSuggestions');
                if (pendingAmount > 0) {
                    let suggestionsHTML = `
                        <button type="button" 
                                onclick="setAmount(${pendingAmount})"
                                class="text-sm bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded mr-2">
                            Pay Full (₹${parseInt(pendingAmount).toLocaleString()})
                        </button>`;
                    
                    if (pendingAmount > 1000) {
                        const halfAmount = Math.floor(pendingAmount / 2);
                        const quarterAmount = Math.floor(pendingAmount / 4);
                        
                        suggestionsHTML += `
                            <button type="button" 
                                    onclick="setAmount(${halfAmount})"
                                    class="text-sm bg-green-100 text-green-800 hover:bg-green-200 px-3 py-1 rounded mr-2">
                                50% (₹${parseInt(halfAmount).toLocaleString()})
                            </button>
                            <button type="button" 
                                    onclick="setAmount(${quarterAmount})"
                                    class="text-sm bg-yellow-100 text-yellow-800 hover:bg-yellow-200 px-3 py-1 rounded">
                                25% (₹${parseInt(quarterAmount).toLocaleString()})
                            </button>`;
                    }
                    
                    amountSuggestionsDiv.innerHTML = suggestionsHTML;
                } else {
                    amountSuggestionsDiv.innerHTML = '';
                }
                
                // Validate amount
                validateAmount();
                
                // Update summary
                updateSummary();
            } else {
                // Hide course details
                courseDetailsDiv.classList.add('hidden');
                courseDetailsDiv.innerHTML = '';
                batchIdInput.value = '';
                amountInput.value = '';
                amountSuggestionsDiv.innerHTML = '';
                
                // Update summary
                updateSummary();
            }
        }

        // Set payment amount
        function setAmount(amount) {
            const amountInput = document.getElementById('amount');
            amountInput.value = amount;
            validateAmount();
            updateSummary();
        }

        // Select payment method
        function selectPaymentMethod(method) {
            // Remove selected class from all
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked
            const clickedMethod = event.currentTarget;
            clickedMethod.classList.add('selected');
            
            // Set value
            const paymentMethodInput = document.getElementById('payment_method');
            paymentMethodInput.value = method;
            
            // Hide method error
            document.getElementById('methodError').classList.add('hidden');
            
            // Show/hide transaction ID field
            const transactionField = document.getElementById('transactionField');
            const transactionInput = document.getElementById('transaction_id');
            const transactionError = document.getElementById('transactionError');
            
            if (method === 'cash') {
                transactionField.classList.add('hidden');
                transactionInput.required = false;
                transactionError.classList.add('hidden');
            } else {
                transactionField.classList.remove('hidden');
                transactionInput.required = true;
            }
            
            // Update summary
            updateSummary();
        }

        // Validate amount
        function validateAmount() {
            const amountInput = document.getElementById('amount');
            const amountError = document.getElementById('amountError');
            const submitBtn = document.getElementById('submitBtn');
            const courseSelect = document.getElementById('courseSelect');
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            
            let pendingAmount = 0;
            if (selectedOption && selectedOption.dataset.pending) {
                pendingAmount = parseFloat(selectedOption.dataset.pending);
            }
            
            const amount = parseFloat(amountInput.value) || 0;
            
            // Clear previous error
            amountError.classList.add('hidden');
            amountError.textContent = '';
            
            let isValid = true;
            
            if (amount <= 0) {
                amountError.textContent = "Please enter a valid amount";
                amountError.classList.remove('hidden');
                isValid = false;
            } else if (pendingAmount > 0 && amount > pendingAmount) {
                amountError.textContent = `Amount cannot exceed pending amount (₹${parseInt(pendingAmount).toLocaleString()})`;
                amountError.classList.remove('hidden');
                isValid = false;
            } else if (amount < 10) {
                amountError.textContent = "Minimum payment amount is ₹10";
                amountError.classList.remove('hidden');
                isValid = false;
            }
            
            // Enable/disable submit button
            submitBtn.disabled = !isValid;
            
            return isValid;
        }

        // Update payment summary
        function updateSummary() {
            const courseSelect = document.getElementById('courseSelect');
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            const amountInput = document.getElementById('amount');
            const paymentMethodInput = document.getElementById('payment_method');
            
            // Update course name
            const summaryCourse = document.getElementById('summaryCourse');
            if (selectedOption && selectedOption.value) {
                summaryCourse.textContent = selectedOption.dataset.skillName;
            } else {
                summaryCourse.textContent = '-';
            }
            
            // Update amount
            const amount = parseFloat(amountInput.value) || 0;
            const summaryAmount = document.getElementById('summaryAmount');
            summaryAmount.textContent = `₹${amount.toLocaleString()}`;
            
            // Update payment method
            const paymentMethod = paymentMethodInput.value;
            const summaryMethod = document.getElementById('summaryMethod');
            if (paymentMethod) {
                const methodNames = {
                    'cash': 'Cash',
                    'card': 'Card',
                    'cheque': 'Cheque',
                    'online': 'Online'
                };
                summaryMethod.textContent = methodNames[paymentMethod] || '-';
            } else {
                summaryMethod.textContent = '-';
            }
            
            // Update total
            const summaryTotal = document.getElementById('summaryTotal');
            summaryTotal.textContent = `₹${amount.toLocaleString()}`;
        }

        // Validate form before submission
        function validateForm() {
            let isValid = true;
            
            // Validate course selection
            const courseSelect = document.getElementById('courseSelect');
            if (!courseSelect.value) {
                alert("Please select a course");
                courseSelect.focus();
                return false;
            }
            
            // Validate amount
            if (!validateAmount()) {
                const amountInput = document.getElementById('amount');
                amountInput.focus();
                return false;
            }
            
            // Validate payment method
            const paymentMethod = document.getElementById('payment_method').value;
            if (!paymentMethod) {
                document.getElementById('methodError').classList.remove('hidden');
                isValid = false;
            }
            
            // Validate transaction ID for non-cash payments
            if (paymentMethod && paymentMethod !== 'cash') {
                const transactionId = document.getElementById('transaction_id').value.trim();
                if (!transactionId) {
                    document.getElementById('transactionError').classList.remove('hidden');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                return false;
            }
            
            // Confirm payment submission
            const amount = parseFloat(document.getElementById('amount').value);
            const courseName = courseSelect.options[courseSelect.selectedIndex].dataset.skillName;
            
            return confirm(`Are you sure you want to submit payment of ₹${amount.toLocaleString()} for ${courseName}?`);
        }

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
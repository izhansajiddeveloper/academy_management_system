<?php
require_once __DIR__ . '/../../config/db.php';

// Initialize variables
$success = $error = '';
$student_id = $skill_id = $session_id = $batch_id = $enrollment_id = $amount_paid = $payment_date = $payment_method = $remarks = '';
$is_edit = false;
$collection_id = 0;

// Check if editing existing fee collection
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $collection_id = intval($_GET['id']);
    $is_edit = true;

    $query = "SELECT * FROM fee_collections WHERE id = $collection_id AND status='active'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $collection = mysqli_fetch_assoc($result);
        $student_id = $collection['student_id'];
        $skill_id = $collection['skill_id'];
        $session_id = $collection['session_id'];
        $batch_id = $collection['batch_id'];
        $enrollment_id = $collection['enrollment_id'];
        $amount_paid = $collection['amount_paid'];
        $payment_date = $collection['payment_date'];
        $payment_method = $collection['payment_method'];
        $remarks = $collection['remarks'];
    } else {
        $error = "Fee collection not found or has been deleted.";
        $is_edit = false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $skill_id = intval($_POST['skill_id']);
    $session_id = intval($_POST['session_id']);
    $batch_id = intval($_POST['batch_id']);
    $enrollment_id = intval($_POST['enrollment_id']);
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $collection_id = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;

    // Validate required fields
    if (empty($student_id) || empty($skill_id) || empty($session_id) || empty($batch_id) || empty($amount_paid) || empty($payment_date)) {
        $error = "Please fill in all required fields.";
    } elseif ($amount_paid <= 0) {
        $error = "Amount must be greater than zero.";
    } else {
        if ($is_edit && $collection_id > 0) {
            // Update existing fee collection
            $query = "UPDATE fee_collections SET 
                student_id = '$student_id',
                skill_id = '$skill_id',
                session_id = '$session_id',
                batch_id = '$batch_id',
                enrollment_id = '$enrollment_id',
                amount_paid = '$amount_paid',
                payment_date = '$payment_date',
                payment_method = '$payment_method',
                remarks = '$remarks',
                updated_at = NOW()
                WHERE id = $collection_id";

            if (mysqli_query($conn, $query)) {
                $success = "Fee collection updated successfully!";
            } else {
                $error = "Error updating fee collection: " . mysqli_error($conn);
            }
        } else {
            // Insert new fee collection
            $query = "INSERT INTO fee_collections (
                student_id, skill_id, session_id, batch_id, enrollment_id,
                amount_paid, payment_date, payment_method, remarks, status, created_at
            ) VALUES (
                '$student_id', '$skill_id', '$session_id', '$batch_id', '$enrollment_id',
                '$amount_paid', '$payment_date', '$payment_method', '$remarks', 'active', NOW()
            )";

            if (mysqli_query($conn, $query)) {
                $success = "Fee collected successfully!";
                // Clear form
                $student_id = $skill_id = $session_id = $batch_id = $enrollment_id = $amount_paid = $payment_date = $payment_method = $remarks = '';
                $collection_id = 0;
                $is_edit = false;
            } else {
                $error = "Error collecting fee: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch active students
$students_query = "SELECT id, name, student_code FROM students WHERE status='active' ORDER BY name";
$students_result = mysqli_query($conn, $students_query);

// Fetch active skills
$skills_query = "SELECT id, skill_name FROM skills WHERE status='active' ORDER BY skill_name";
$skills_result = mysqli_query($conn, $skills_query);

// Fetch active sessions (assuming sessions table exists)
$sessions_query = "SELECT id, session_name FROM sessions WHERE status='active' ORDER BY session_name";
$sessions_result = mysqli_query($conn, $sessions_query);

// Fetch active batches (assuming batches table exists)
$batches_query = "SELECT id, batch_name FROM batches WHERE status='active' ORDER BY batch_name";
$batches_result = mysqli_query($conn, $batches_query);

// Fetch student enrollments for selected student (for enrollment dropdown)
$enrollments = [];
if ($student_id > 0) {
    $enrollments_query = "SELECT id, skill_id, session_id, batch_id FROM student_enrollments 
                          WHERE student_id = $student_id AND status='active'";
    $enrollments_result = mysqli_query($conn, $enrollments_query);
    while ($row = mysqli_fetch_assoc($enrollments_result)) {
        $enrollments[] = $row;
    }
}

// Calculate total paid for selected student and skill
$total_paid = 0;
if ($student_id > 0 && $skill_id > 0) {
    $total_paid_query = "SELECT SUM(amount_paid) as total FROM fee_collections 
                         WHERE student_id = $student_id AND skill_id = $skill_id AND status='active'";
    $total_paid_result = mysqli_query($conn, $total_paid_query);
    if ($total_paid_result) {
        $total_row = mysqli_fetch_assoc($total_paid_result);
        $total_paid = $total_row['total'] ?? 0;
    }
}

// Fetch fee structure for selected skill and session
$course_fee = 0;
if ($skill_id > 0 && $session_id > 0) {
    $fee_query = "SELECT total_fee FROM fee_structures 
                  WHERE skill_id = $skill_id AND session_id = $session_id AND status='active' 
                  LIMIT 1";
    $fee_result = mysqli_query($conn, $fee_query);
    if ($fee_result && mysqli_num_rows($fee_result) > 0) {
        $fee_row = mysqli_fetch_assoc($fee_result);
        $course_fee = $fee_row['total_fee'] ?? 0;
    }
}

// Calculate pending amount
$pending_amount = max(0, $course_fee - $total_paid);
$payment_percentage = $course_fee > 0 ? ($total_paid / $course_fee) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $is_edit ? 'Edit Fee Collection' : 'Collect Fee'; ?> | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
            --warning: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        }

        .input-field {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: white;
            border: 2px solid #e5e7eb;
            color: #4b5563;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #10b981, #34d399);
            transition: width 0.5s ease;
        }

        .amount-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 16px;
        }

        .success-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px;
        }

        .error-card {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fecaca;
            border-radius: 12px;
            padding: 16px;
        }

        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        .payment-method.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }

        .method-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .icon-cash {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .icon-bank {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
        }

        .icon-online {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7c3aed;
        }

        .icon-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-xl sticky top-0 h-screen">
            <div class="p-6 text-center border-b">
                <h2 class="text-2xl font-bold text-[var(--primary)]">
                    🎓 EduSkill Pro
                </h2>
                <p class="text-sm text-gray-500 mt-1">Admin Panel</p>
            </div>

            <nav class="p-4 space-y-2 text-gray-700">
                <!-- Dashboard -->
                <a href="../dashboard.php"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700">
                    <i class="fas fa-chart-line text-[var(--primary)]"></i> Dashboard
                </a>

                <!-- Skills -->
                <a href="../skills/skills.php"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700">
                    <i class="fas fa-book-open text-[var(--primary)]"></i> Skills / Courses
                </a>

                <!-- FEES (Active) -->
                <a href="../fees/fee_collection.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-[var(--primary)] font-semibold">
                    <i class="fas fa-money-bill-wave text-[var(--primary)]"></i> Fees
                </a>

                <!-- Fees Submenu -->
                <div class="ml-8 space-y-1">
                    <a href="../fees/fee_structures.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-calculator text-sm"></i>
                        <span>Fee Structures</span>
                    </a>

                    <a href="../fees/fee_collection.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-cash-register text-sm"></i>
                        <span>Fee Collection</span>
                    </a>

                    <a href="collect_fee.php"
                        class="flex items-center gap-2 p-2 rounded-lg bg-blue-100 text-[var(--primary)] font-semibold">
                        <i class="fas fa-plus-circle text-sm"></i>
                        <span>Collect Fee</span>
                    </a>

                    <a href="../fees/fee_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-chart-pie text-sm"></i>
                        <span>Fee Reports</span>
                    </a>
                </div>

                <!-- Other menu items -->
                <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                    <i class="fas fa-user-check"></i> Enrollments
                </a>
                <a href="../expenses/expenses.php" class="sidebar-link">
                    <i class="fas fa-wallet"></i> Expenses
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">
                            <?php echo $is_edit ? 'Edit Fee Collection' : 'Collect New Fee'; ?>
                        </h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-cash-register text-green-500 mr-2"></i>
                            <?php echo $is_edit ? 'Update existing fee payment' : 'Record new fee payment from students'; ?>
                        </p>
                    </div>
                    <a href="fee_collection.php"
                        class="flex items-center gap-2 text-gray-600 hover:text-gray-800 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Collections</span>
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="success-card mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-green-800">Success!</h4>
                            <p class="text-green-600"><?php echo $success; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-card mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-red-800">Error!</h4>
                            <p class="text-red-600"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Main Form -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Form -->
                <div class="lg:col-span-2">
                    <div class="form-container p-6">
                        <form method="POST" id="feeForm">
                            <input type="hidden" name="collection_id" value="<?php echo $collection_id; ?>">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Student Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Student <span class="text-red-500">*</span>
                                    </label>
                                    <select name="student_id" id="student_id" class="input-field" required onchange="loadEnrollments(this.value)">
                                        <option value="">Select Student</option>
                                        <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                            <option value="<?php echo $student['id']; ?>"
                                                <?php echo $student['id'] == $student_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($student['name']) . ' (' . $student['student_code'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Skill Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Skill/Course <span class="text-red-500">*</span>
                                    </label>
                                    <select name="skill_id" id="skill_id" class="input-field" required onchange="updateFeeInfo()">
                                        <option value="">Select Skill</option>
                                        <?php mysqli_data_seek($skills_result, 0); ?>
                                        <?php while ($skill = mysqli_fetch_assoc($skills_result)): ?>
                                            <option value="<?php echo $skill['id']; ?>"
                                                <?php echo $skill['id'] == $skill_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Session Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Session <span class="text-red-500">*</span>
                                    </label>
                                    <select name="session_id" id="session_id" class="input-field" required onchange="updateFeeInfo()">
                                        <option value="">Select Session</option>
                                        <?php mysqli_data_seek($sessions_result, 0); ?>
                                        <?php while ($session = mysqli_fetch_assoc($sessions_result)): ?>
                                            <option value="<?php echo $session['id']; ?>"
                                                <?php echo $session['id'] == $session_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($session['session_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Batch Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Batch <span class="text-red-500">*</span>
                                    </label>
                                    <select name="batch_id" id="batch_id" class="input-field" required>
                                        <option value="">Select Batch</option>
                                        <?php mysqli_data_seek($batches_result, 0); ?>
                                        <?php while ($batch = mysqli_fetch_assoc($batches_result)): ?>
                                            <option value="<?php echo $batch['id']; ?>"
                                                <?php echo $batch['id'] == $batch_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($batch['batch_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Enrollment ID (Optional) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Enrollment ID 
                                    </label>
                                    <select name="enrollment_id" id="enrollment_id" class="input-field">
                                        <option value="">Select Enrollment</option>
                                        <?php foreach ($enrollments as $enrollment): ?>
                                            <option value="<?php echo $enrollment['id']; ?>"
                                                <?php echo $enrollment['id'] == $enrollment_id ? 'selected' : ''; ?>>
                                                Enrollment #<?php echo $enrollment['id']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Payment Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Payment Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="payment_date" id="payment_date"
                                        class="input-field"
                                        value="<?php echo $payment_date ? $payment_date : date('Y-m-d'); ?>"
                                        required>
                                </div>

                                <!-- Amount -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Amount (PKR) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="amount_paid" id="amount_paid"
                                        class="input-field"
                                        value="<?php echo $amount_paid; ?>"
                                        step="0.01" min="0.01"
                                        placeholder="0.00" required>
                                    <p class="text-xs text-gray-500 mt-1">Enter the amount paid</p>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <?php
                                    $methods = [
                                        'cash' => ['icon' => 'fa-money-bill-wave', 'label' => 'Cash'],
                                        'bank transfer' => ['icon' => 'fa-university', 'label' => 'Bank Transfer'],
                                        'online' => ['icon' => 'fa-globe', 'label' => 'Online'],
                                        'card' => ['icon' => 'fa-credit-card', 'label' => 'Card']
                                    ];
                                    foreach ($methods as $method => $data):
                                        $is_selected = ($payment_method == $method);
                                    ?>
                                        <div class="payment-method <?php echo $is_selected ? 'selected' : ''; ?>"
                                            onclick="selectPaymentMethod('<?php echo $method; ?>')">
                                            <div class="method-icon icon-<?php echo str_replace(' ', '-', $method); ?>">
                                                <i class="fas <?php echo $data['icon']; ?>"></i>
                                            </div>
                                            <div class="text-center">
                                                <span class="text-sm font-medium text-gray-800">
                                                    <?php echo $data['label']; ?>
                                                </span>
                                            </div>
                                            <input type="radio" name="payment_method" value="<?php echo $method; ?>"
                                                class="hidden" <?php echo $is_selected ? 'checked' : ''; ?>>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Remarks (Optional)
                                </label>
                                <textarea name="remarks" id="remarks"
                                    class="input-field"
                                    rows="3"
                                    placeholder="Enter any remarks about this payment..."><?php echo htmlspecialchars($remarks); ?></textarea>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex gap-4 pt-6 border-t border-gray-200">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-save mr-2"></i>
                                    <?php echo $is_edit ? 'Update Fee Collection' : 'Collect Fee'; ?>
                                </button>
                                <a href="fee_collection.php" class="btn-secondary flex-1 text-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Fee Information -->
                <div class="space-y-6">
                    <!-- Fee Summary Card -->
                    <div class="form-container p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Fee Summary</h3>

                        <div class="space-y-4">
                            <!-- Course Fee -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Course Fee:</span>
                                <span class="font-semibold text-gray-800" id="courseFeeDisplay">
                                    Rs<?php echo number_format($course_fee, 2); ?>
                                </span>
                            </div>

                            <!-- Total Paid -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Paid:</span>
                                <span class="font-semibold text-green-600" id="totalPaidDisplay">
                                    Rs<?php echo number_format($total_paid, 2); ?>
                                </span>
                            </div>

                            <!-- Pending Amount -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Pending Amount:</span>
                                <span class="font-semibold text-orange-600" id="pendingAmountDisplay">
                                    Rs<?php echo number_format($pending_amount, 2); ?>
                                </span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Payment Progress</span>
                                    <span id="paymentPercentage"><?php echo number_format($payment_percentage, 1); ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progressFill"
                                        style="width: <?php echo min($payment_percentage, 100); ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-2">
                                    <span>Paid: <span id="paidAmount">Rs<?php echo number_format($total_paid, 2); ?></span></span>
                                    <span>Remaining: <span id="remainingAmount">Rs<?php echo number_format($pending_amount, 2); ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="form-container p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Stats</h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-users text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Active Students</p>
                                        <?php
                                        $active_students = mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE status='active'");
                                        $active_count = mysqli_fetch_assoc($active_students)['count'];
                                        ?>
                                        <h4 class="font-bold text-gray-800"><?php echo $active_count; ?></h4>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-book text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Active Courses</p>
                                        <?php
                                        $active_courses = mysqli_query($conn, "SELECT COUNT(*) as count FROM skills WHERE status='active'");
                                        $courses_count = mysqli_fetch_assoc($active_courses)['count'];
                                        ?>
                                        <h4 class="font-bold text-gray-800"><?php echo $courses_count; ?></h4>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-receipt text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Today's Collections</p>
                                        <?php
                                        $today_collections = mysqli_query(
                                            $conn,
                                            "SELECT COUNT(*) as count FROM fee_collections 
                                             WHERE status='active' AND payment_date = CURDATE()"
                                        );
                                        $today_count = mysqli_fetch_assoc($today_collections)['count'];
                                        ?>
                                        <h4 class="font-bold text-gray-800"><?php echo $today_count; ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="form-container p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Help & Guidelines</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>All fields marked with * are required</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Select student first to see their enrollments</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                <span>Fee structure will auto-load when skill and session are selected</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mt-1"></i>
                                <span>Edit option only updates the selected payment</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-trash text-red-500 mt-1"></i>
                                <span>Delete option soft-deletes (deactivates) the payment</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('payment_date').value) {
                document.getElementById('payment_date').valueAsDate = new Date();
            }

            // Format amount field on input
            const amountField = document.getElementById('amount_paid');
            amountField.addEventListener('input', function(e) {
                let value = parseFloat(e.target.value);
                if (!isNaN(value) && value > 0) {
                    e.target.value = value.toFixed(2);
                }
            });
        });

        // Select payment method
        function selectPaymentMethod(method) {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selected class to clicked method
            const selectedEl = document.querySelector(`.payment-method[onclick*="${method}"]`);
            selectedEl.classList.add('selected');

            // Set radio button as checked
            const radio = selectedEl.querySelector('input[type="radio"]');
            radio.checked = true;
        }

        // Load enrollments for selected student
        function loadEnrollments(studentId) {
            if (!studentId) {
                document.getElementById('enrollment_id').innerHTML = '<option value="">Select Enrollment</option>';
                return;
            }

            // Show loading
            const enrollmentSelect = document.getElementById('enrollment_id');
            enrollmentSelect.innerHTML = '<option value="">Loading enrollments...</option>';

            // Fetch enrollments via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_enrollments.php?student_id=${studentId}`, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    enrollmentSelect.innerHTML = xhr.responseText;
                    updateFeeInfo();
                } else {
                    enrollmentSelect.innerHTML = '<option value="">Error loading enrollments</option>';
                }
            };
            xhr.send();
        }

        // Update fee information based on selected skill and session
        function updateFeeInfo() {
            const skillId = document.getElementById('skill_id').value;
            const sessionId = document.getElementById('session_id').value;
            const studentId = document.getElementById('student_id').value;

            if (!skillId || !sessionId || !studentId) {
                return;
            }

            // Fetch fee information via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_fee_info.php?student_id=${studentId}&skill_id=${skillId}&session_id=${sessionId}`, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);

                        // Update displays
                        document.getElementById('courseFeeDisplay').textContent = 'Rs' + parseFloat(data.course_fee).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                        document.getElementById('totalPaidDisplay').textContent = 'Rs' + parseFloat(data.total_paid).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                        document.getElementById('pendingAmountDisplay').textContent = 'Rs' + parseFloat(data.pending_amount).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                        document.getElementById('paymentPercentage').textContent = data.payment_percentage.toFixed(1) + '%';
                        document.getElementById('progressFill').style.width = Math.min(data.payment_percentage, 100) + '%';
                        document.getElementById('paidAmount').textContent = 'Rs' + parseFloat(data.total_paid).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        document.getElementById('remainingAmount').textContent = 'Rs' + parseFloat(data.pending_amount).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                        // Suggest payment amount
                        const amountField = document.getElementById('amount_paid');
                        if (!amountField.value && data.pending_amount > 0) {
                            amountField.value = data.pending_amount.toFixed(2);
                        }
                    } catch (e) {
                        console.error('Error parsing fee info:', e);
                    }
                }
            };
            xhr.send();
        }

        // Form validation
        document.getElementById('feeForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount_paid').value);
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');

            if (amount <= 0) {
                e.preventDefault();
                alert('Amount must be greater than zero.');
                return;
            }

            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return;
            }

            // Optional: Confirm before submitting
            if (!<?php echo $is_edit ? 'true' : 'false'; ?>) {
                if (!confirm('Are you sure you want to collect this fee?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>

</html>
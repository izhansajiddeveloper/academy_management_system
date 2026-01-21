<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $donor_name = mysqli_real_escape_string($conn, trim($_POST['donor_name']));
    $donor_type = mysqli_real_escape_string($conn, trim($_POST['donor_type']));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $amount = floatval($_POST['amount']);
    $donation_date = mysqli_real_escape_string($conn, trim($_POST['donation_date']));
    $payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method']));
    $reference_no = mysqli_real_escape_string($conn, trim($_POST['reference_no']));
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));

    // Validate required fields
    if (empty($donor_name) || empty($amount) || empty($donation_date)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "
            INSERT INTO donations
            (donor_name, donor_type, contact_person, phone, email,
             amount, donation_date, payment_method, reference_no, remarks, status, created_at, updated_at)
            VALUES (
                '$donor_name',
                '$donor_type',
                '$contact_person',
                '$phone',
                '$email',
                $amount,
                '$donation_date',
                '$payment_method',
                '$reference_no',
                '$remarks',
                'active',
                NOW(),
                NOW()
            )
        ";

        if (mysqli_query($conn, $sql)) {
            header("Location: donations.php?added=1");
            exit;
        } else {
            $error_message = "Error adding donation: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Donation | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .required:after {
            content: " *";
            color: #ef4444;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-secondary {
            background: white;
            color: #374151;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
         <?php include __DIR__ . '/../includes/sidebar.php'; ?><aside class="w-64 sidebar h-screen sticky top-0">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-bold text-white">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1">Admin Panel</p>
            </div>

            <nav class="p-3 space-y-1">
                <a href="../dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Donations</p>
                    <a href="donations.php" class="sidebar-link">
                        <i class="fas fa-hand-holding-usd"></i> Donations
                    </a>
                    <a href="add_donation.php" class="sidebar-link active">
                        <i class="fas fa-plus"></i> Add Donation
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Financial</p>
                    <a href="../fees/fee_structures.php" class="sidebar-link">
                        <i class="fas fa-calculator"></i> Fee Structures
                    </a>
                    <a href="../fees/fee_collection.php" class="sidebar-link">
                        <i class="fas fa-cash-register"></i> Fee Collection
                    </a>
                    <a href="../fees/fee_history.php" class="sidebar-link">
                        <i class="fas fa-history"></i> Fee History
                    </a>
                    <a href="../expenses/expenses.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Expenses
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Student Management</p>
                    <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-user-check"></i> Enrollments
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../reports/student_report.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </div>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Add New Donation</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-plus-circle text-green-500 mr-1"></i>
                        Record a new donation for the academy
                    </p>
                </div>
                <div>
                    <a href="donations.php"
                        class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Donations
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p><?php echo $error_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="info-box mb-8">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Fill in all required fields to record a new donation. All donations will be logged with current date and time.
                </p>
            </div>

            <!-- Add Donation Form -->
            <div class="form-container max-w-3xl mx-auto">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Donor Information -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Donor Information</h3>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label required">Donor Name / Organization</label>
                            <input type="text"
                                name="donor_name"
                                class="form-input"
                                placeholder="Enter donor name or organization"
                                value="<?= isset($_POST['donor_name']) ? htmlspecialchars($_POST['donor_name']) : '' ?>"
                                required>
                        </div>

                        <div>
                            <label class="form-label required">Donor Type</label>
                            <select name="donor_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="organization" <?= (isset($_POST['donor_type']) && $_POST['donor_type'] == 'organization') ? 'selected' : '' ?>>Organization</option>
                                <option value="individual" <?= (isset($_POST['donor_type']) && $_POST['donor_type'] == 'individual') ? 'selected' : '' ?>>Individual</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Contact Person</label>
                            <input type="text"
                                name="contact_person"
                                class="form-input"
                                placeholder="Enter contact person name"
                                value="<?= isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : '' ?>">
                        </div>

                        <div>
                            <label class="form-label">Phone Number</label>
                            <input type="text"
                                name="phone"
                                class="form-input"
                                placeholder="Enter phone number"
                                value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                        </div>

                        <div>
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                name="email"
                                class="form-input"
                                placeholder="Enter email address"
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>

                        <!-- Donation Details -->
                        <div class="md:col-span-2 mt-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Donation Details</h3>
                        </div>

                        <div>
                            <label class="form-label required">Amount (PKR)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">PKR</span>
                                </div>
                                <input type="number"
                                    step="0.01"
                                    name="amount"
                                    class="form-input pl-16"
                                    placeholder="0.00"
                                    value="<?= isset($_POST['amount']) ? $_POST['amount'] : '' ?>"
                                    required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-money-bill-wave text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label required">Donation Date</label>
                            <input type="date"
                                name="donation_date"
                                class="form-input"
                                value="<?= isset($_POST['donation_date']) ? $_POST['donation_date'] : date('Y-m-d') ?>"
                                required>
                        </div>

                        <div>
                            <label class="form-label required">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select Method</option>
                                <option value="cash" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="bank" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'bank') ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="online" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'online') ? 'selected' : '' ?>>Online Payment</option>
                                <option value="cheque" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'cheque') ? 'selected' : '' ?>>Cheque</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Reference Number</label>
                            <input type="text"
                                name="reference_no"
                                class="form-input"
                                placeholder="Enter reference number"
                                value="<?= isset($_POST['reference_no']) ? htmlspecialchars($_POST['reference_no']) : '' ?>">
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Remarks / Purpose</label>
                            <textarea name="remarks"
                                class="form-input"
                                rows="3"
                                placeholder="Enter any remarks or purpose of donation"><?= isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks']) : '' ?></textarea>
                        </div>

                        <!-- Auto-generated Info -->
                        <div class="md:col-span-2 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-3">
                                <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                Auto-generated Information
                            </h4>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    <span>Recorded Date: <?php echo date('F j, Y'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-clock text-gray-400"></i>
                                    <span>Time: <?php echo date('h:i A'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-circle text-green-400"></i>
                                    <span>Status: Active</span>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="md:col-span-2 flex justify-end gap-4 pt-6 border-t border-gray-200">
                            <a href="donations.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Save Donation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-format amount input
            const amountInput = document.querySelector('input[name="amount"]');
            amountInput.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });

            // Real-time validation
            const requiredInputs = document.querySelectorAll('[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-300');
                    } else {
                        this.classList.remove('border-red-300');
                    }
                });

                input.addEventListener('input', function() {
                    this.classList.remove('border-red-300');
                });
            });
        });
    </script>

</body>

</html>
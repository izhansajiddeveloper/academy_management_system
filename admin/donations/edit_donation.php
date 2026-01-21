<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: donations.php");
    exit;
}

// Fetch the donation
$donation_query = "SELECT * FROM donations WHERE id=$id LIMIT 1";
$donation_result = mysqli_query($conn, $donation_query);
$donation = mysqli_fetch_assoc($donation_result);

if (!$donation) {
    header("Location: donations.php");
    exit;
}

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
            UPDATE donations SET
            donor_name = '$donor_name',
            donor_type = '$donor_type',
            contact_person = '$contact_person',
            phone = '$phone',
            email = '$email',
            amount = $amount,
            donation_date = '$donation_date',
            payment_method = '$payment_method',
            reference_no = '$reference_no',
            remarks = '$remarks',
            updated_at = NOW()
            WHERE id = $id
        ";

        if (mysqli_query($conn, $sql)) {
            $success_message = "Donation updated successfully!";
            // Refresh donation data
            $donation_result = mysqli_query($conn, $donation_query);
            $donation = mysqli_fetch_assoc($donation_result);
        } else {
            $error_message = "Error updating donation: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Donation | Academy Management System</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
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

        .donation-details {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .type-organization {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-individual {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
         <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Edit Donation</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-edit text-blue-500 mr-1"></i>
                        Update donation details
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
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Success</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p><?php echo $success_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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

            <!-- Donation Details -->
            <div class="donation-details mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold text-gray-800">Donation Information</h3>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Donation ID</p>
                                <p class="font-medium text-gray-800">#<?= $donation['id'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Created Date</p>
                                <p class="font-medium text-gray-800"><?= date('F j, Y', strtotime($donation['created_at'])) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Last Updated</p>
                                <p class="font-medium text-gray-800"><?= date('F j, Y', strtotime($donation['updated_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <?php
                        $type_class = $donation['donor_type'] == 'organization' ? 'type-organization' : 'type-individual';
                        ?>
                        <span class="<?= $type_class ?> type-badge">
                            <i class="fas fa-<?= $donation['donor_type'] == 'organization' ? 'building' : 'user' ?> text-xs"></i>
                            <?= ucfirst($donation['donor_type']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Donation Form -->
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
                                value="<?= htmlspecialchars($donation['donor_name']) ?>"
                                required>
                        </div>

                        <div>
                            <label class="form-label required">Donor Type</label>
                            <select name="donor_type" class="form-select" required>
                                <option value="organization" <?= $donation['donor_type'] == 'organization' ? 'selected' : '' ?>>Organization</option>
                                <option value="individual" <?= $donation['donor_type'] == 'individual' ? 'selected' : '' ?>>Individual</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Contact Person</label>
                            <input type="text"
                                name="contact_person"
                                class="form-input"
                                value="<?= htmlspecialchars($donation['contact_person']) ?>">
                        </div>

                        <div>
                            <label class="form-label">Phone Number</label>
                            <input type="text"
                                name="phone"
                                class="form-input"
                                value="<?= htmlspecialchars($donation['phone']) ?>">
                        </div>

                        <div>
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                name="email"
                                class="form-input"
                                value="<?= htmlspecialchars($donation['email']) ?>">
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
                                    value="<?= $donation['amount'] ?>"
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
                                value="<?= $donation['donation_date'] ?>"
                                required>
                        </div>

                        <div>
                            <label class="form-label required">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash" <?= $donation['payment_method'] == 'cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="bank" <?= $donation['payment_method'] == 'bank' ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="online" <?= $donation['payment_method'] == 'online' ? 'selected' : '' ?>>Online Payment</option>
                                <option value="cheque" <?= $donation['payment_method'] == 'cheque' ? 'selected' : '' ?>>Cheque</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Reference Number</label>
                            <input type="text"
                                name="reference_no"
                                class="form-input"
                                value="<?= htmlspecialchars($donation['reference_no']) ?>">
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Remarks / Purpose</label>
                            <textarea name="remarks"
                                class="form-input"
                                rows="3"><?= htmlspecialchars($donation['remarks']) ?></textarea>
                        </div>

                        <!-- Update Info -->
                        <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h4 class="font-medium text-blue-700 mb-2">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                Update Information
                            </h4>
                            <p class="text-sm text-blue-600">
                                This donation will be updated with the current date and time. The "Last Updated" timestamp will reflect when changes were saved.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="md:col-span-2 flex justify-end gap-4 pt-6 border-t border-gray-200">
                            <a href="donations.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Update Donation
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
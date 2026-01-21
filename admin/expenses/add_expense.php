<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

// Fetch active categories
$categories_result = mysqli_query($conn, "SELECT * FROM expense_categories WHERE status='active' ORDER BY category_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);

    if ($category_id <= 0 || empty($amount)) {
        $error_message = "Please select a category and enter a valid amount.";
    } else {
        $insert_query = "INSERT INTO expenses (category_id, description, amount, status, created_at, updated_at)
                         VALUES ($category_id, '" . mysqli_real_escape_string($conn, $description) . "', $amount, 'active', NOW(), NOW())";
        if (mysqli_query($conn, $insert_query)) {
            $success_message = "Expense added successfully!";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Expense | Academy Management System</title>
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
         <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Add New Expense</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-plus-circle text-blue-500 mr-1"></i>
                        Record a new expense for the academy
                    </p>
                </div>
                <div>
                    <a href="expenses.php"
                        class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Expenses
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

            <!-- Info Box -->
            <div class="info-box mb-8">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Fill in all required fields to record a new expense. All expenses will be logged with current date and time.
                </p>
            </div>

            <!-- Add Expense Form -->
            <div class="form-container max-w-2xl mx-auto">
                <form method="POST">
                    <div class="space-y-6">
                        <!-- Category -->
                        <div>
                            <label class="form-label required">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                class="form-input"
                                rows="4"
                                placeholder="Enter expense description (optional)"></textarea>
                        </div>

                        <!-- Amount -->
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
                                    required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-money-bill-wave text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-generated Info -->
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-3">
                                <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                Auto-generated Information
                            </h4>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    <span>Date: <?php echo date('F j, Y'); ?></span>
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
                        <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                            <a href="expenses.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Add Expense
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Initialize date and time
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-format amount input
            const amountInput = document.querySelector('input[name="amount"]');
            amountInput.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });

            // Real-time validation
            const inputs = document.querySelectorAll('.form-select, .form-input');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && this.value.trim() === '') {
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
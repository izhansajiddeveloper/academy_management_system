<?php
require_once __DIR__ . '/../../config/db.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (empty($category_name)) {
        $error_message = "Category name is required.";
    } else {
        // Check if category already exists
        $check_sql = "SELECT id FROM expense_categories WHERE category_name='" . mysqli_real_escape_string($conn, $category_name) . "' AND status='active'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Category already exists.";
        } else {
            $insert_sql = "INSERT INTO expense_categories (category_name, description, status, created_at, updated_at)
                           VALUES ('" . mysqli_real_escape_string($conn, $category_name) . "','" . mysqli_real_escape_string($conn, $description) . "','active', NOW(), NOW())";
            if (mysqli_query($conn, $insert_sql)) {
                $success_message = "Category added successfully!";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Expense Category | Academy Management System</title>
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

        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: #dbeafe;
            color: #1e40af;
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
                    <h1 class="text-2xl font-bold text-gray-800">Add New Expense Category</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-plus-circle text-blue-500 mr-1"></i>
                        Create a new category for expense tracking
                    </p>
                </div>
                <div>
                    <a href="expense_categories.php"
                        class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Categories
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
                    Create expense categories to organize your expenses. Categories help in better tracking and reporting of academy expenses.
                </p>
            </div>

            <!-- Add Category Form -->
            <div class="form-container max-w-2xl mx-auto">
                <form method="POST">
                    <div class="space-y-6">
                        <!-- Category Name -->
                        <div>
                            <label class="form-label required">Category Name</label>
                            <div class="relative">
                                <input type="text"
                                    name="category_name"
                                    class="form-input pl-10"
                                    placeholder="e.g., Transport, Utilities, Salaries"
                                    required>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-tag text-gray-400"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Enter a unique name for this expense category</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                class="form-input"
                                rows="4"
                                placeholder="Enter category description (optional)"></textarea>
                            <p class="text-xs text-gray-500 mt-1">Optional description for better understanding</p>
                        </div>

                        <!-- Examples -->
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-3">
                                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                Common Expense Categories
                            </h4>
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <span class="category-badge">
                                        <i class="fas fa-car mr-1"></i> Transport
                                    </span>
                                    <span class="category-badge">
                                        <i class="fas fa-home mr-1"></i> Rent
                                    </span>
                                    <span class="category-badge">
                                        <i class="fas fa-users mr-1"></i> Salaries
                                    </span>
                                    <span class="category-badge">
                                        <i class="fas fa-bolt mr-1"></i> Utilities
                                    </span>
                                    <span class="category-badge">
                                        <i class="fas fa-shopping-cart mr-1"></i> Supplies
                                    </span>
                                    <span class="category-badge">
                                        <i class="fas fa-gift mr-1"></i> Miscellaneous
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    These are common categories. You can create any category that fits your academy's needs.
                                </p>
                            </div>
                        </div>

                        <!-- Auto-generated Info -->
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h4 class="font-medium text-blue-700 mb-3">
                                <i class="fas fa-bolt text-blue-600 mr-2"></i>
                                Auto-generated Information
                            </h4>
                            <div class="space-y-2 text-sm text-blue-600">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar text-blue-400"></i>
                                    <span>Created Date: <?php echo date('F j, Y'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-circle text-green-400"></i>
                                    <span>Status: Active (by default)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check text-blue-400"></i>
                                    <span>Will be available immediately for expense tracking</span>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                            <a href="expense_categories.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Add Category
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
            // Real-time validation
            const categoryInput = document.querySelector('input[name="category_name"]');

            categoryInput.addEventListener('blur', function() {
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.classList.add('border-red-300');
                } else {
                    this.classList.remove('border-red-300');
                }
            });

            categoryInput.addEventListener('input', function() {
                this.classList.remove('border-red-300');
            });

            // Auto-suggest common categories
            const commonCategories = ['Transport', 'Salaries', 'Rent', 'Utilities', 'Supplies', 'Miscellaneous', 'Marketing', 'Maintenance'];

            categoryInput.addEventListener('focus', function() {
                if (!this.value) {
                    this.setAttribute('list', 'category-suggestions');

                    // Create datalist if it doesn't exist
                    if (!document.getElementById('category-suggestions')) {
                        const datalist = document.createElement('datalist');
                        datalist.id = 'category-suggestions';

                        commonCategories.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category;
                            datalist.appendChild(option);
                        });

                        document.body.appendChild(datalist);
                    }
                }
            });
        });
    </script>

</body>

</html>
<?php
require_once __DIR__ . '/../../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Category ID");

// Fetch the category
$category_query = "SELECT * FROM expense_categories WHERE id=$id LIMIT 1";
$category_result = mysqli_query($conn, $category_query);
$category = mysqli_fetch_assoc($category_result);
if (!$category) die("Category not found!");

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (empty($category_name)) {
        $error_message = "Category name is required.";
    } else {
        // Check if another category with same name exists
        $check_sql = "SELECT id FROM expense_categories WHERE category_name='" . mysqli_real_escape_string($conn, $category_name) . "' AND id != $id AND status='active'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Another category with this name already exists.";
        } else {
            $update_sql = "UPDATE expense_categories 
                           SET category_name='" . mysqli_real_escape_string($conn, $category_name) . "',
                               description='" . mysqli_real_escape_string($conn, $description) . "',
                               updated_at=NOW()
                           WHERE id=$id";
            if (mysqli_query($conn, $update_sql)) {
                $success_message = "Category updated successfully!";
                // Refresh category data
                $category_result = mysqli_query($conn, $category_query);
                $category = mysqli_fetch_assoc($category_result);
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
    <title>Edit Expense Category | Academy Management System</title>
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

        .category-details {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
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
                    <h1 class="text-2xl font-bold text-gray-800">Edit Expense Category</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-edit text-blue-500 mr-1"></i>
                        Update category details
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

            <!-- Category Details -->
            <div class="category-details mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold text-gray-800">Category Information</h3>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Category ID</p>
                                <p class="font-medium text-gray-800">#<?= $category['id'] ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Created Date</p>
                                <p class="font-medium text-gray-800"><?= date('F j, Y', strtotime($category['created_at'])) ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Last Updated</p>
                                <p class="font-medium text-gray-800"><?= date('F j, Y', strtotime($category['updated_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="status-badge status-active">
                            <i class="fas fa-check-circle text-xs"></i>
                            <?= ucfirst($category['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Category Form -->
            <div class="form-container max-w-2xl mx-auto">
                <form method="POST">
                    <div class="space-y-6">
                        <!-- Category Name -->
                        <div>
                            <label class="form-label required">Category Name</label>
                            <input type="text"
                                name="category_name"
                                class="form-input"
                                value="<?php echo htmlspecialchars($category['category_name']); ?>"
                                placeholder="Enter category name"
                                required>
                            <p class="text-xs text-gray-500 mt-1">This name will be used in expense records</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                class="form-input"
                                rows="4"
                                placeholder="Enter category description (optional)"><?php echo htmlspecialchars($category['description']); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Optional description for better understanding</p>
                        </div>

                        <!-- Important Note -->
                        <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                            <h4 class="font-medium text-yellow-800 mb-2">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                Important Note
                            </h4>
                            <p class="text-sm text-yellow-700">
                                Updating this category name will affect all existing expenses linked to this category.
                                Make sure the new name accurately represents all related expenses.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                            <a href="expense_categories.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Update Category
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
            const inputs = document.querySelectorAll('.form-input');
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
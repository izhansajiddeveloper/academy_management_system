<?php
require_once __DIR__ . '/../../config/db.php';

// Check if enrollment ID is provided
$enrollment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$enrollment_id) {
    header("Location: enrollment_list.php");
    exit;
}

// Fetch enrollment details for confirmation
$enrollment_query = "
    SELECT se.*, s.name AS student_name, s.student_code, sk.skill_name, ss.session_name, b.batch_name
    FROM student_enrollments se
    JOIN students s ON se.student_id = s.id
    JOIN skills sk ON se.skill_id = sk.id
    JOIN sessions ss ON se.session_id = ss.id
    JOIN batches b ON se.batch_id = b.id
    WHERE se.id = $enrollment_id AND se.status = 'active'
";
$enrollment_result = mysqli_query($conn, $enrollment_query);
$enrollment = mysqli_fetch_assoc($enrollment_result);

if (!$enrollment) {
    header("Location: enrollment_list.php");
    exit;
}

// Handle form submission for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Soft delete - change status to inactive
        $update_query = "UPDATE student_enrollments SET status='inactive', updated_at = NOW() WHERE id = $enrollment_id";

        if (mysqli_query($conn, $update_query)) {
            header("Location: enrollment_list.php?deleted=1");
            exit;
        } else {
            $error_message = "Error deleting enrollment: " . mysqli_error($conn);
        }
    } else {
        // Cancel deletion
        header("Location: enrollment_list.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Delete Enrollment | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            background: #111827;
            color: white;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
            color: #d1d5db;
            text-decoration: none;
        }

        .sidebar-link:hover {
            background: #374151;
            color: white;
        }

        .sidebar-link.active {
            background: #3b82f6;
            color: white;
        }

        .confirmation-container {
            background: white;
            border-radius: 6px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            max-width: 600px;
            margin: 0 auto;
        }

        .warning-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .enrollment-details {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 sidebar h-screen sticky top-0">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-bold text-white">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1">Admin Panel</p>
            </div>

            <nav class="p-3 space-y-1">
                <a href="../dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Skills & Courses</p>
                    <a href="../skills/skills.php" class="sidebar-link">
                        <i class="fas fa-book-open"></i> Skills
                    </a>
                    <a href="../sessions/sessions.php" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Sessions
                    </a>
                    <a href="../batches/batches.php" class="sidebar-link">
                        <i class="fas fa-layer-group"></i> Batches
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Enrollments</p>
                    <a href="enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-list"></i> All Enrollments
                    </a>
                    <a href="enroll_student.php" class="sidebar-link">
                        <i class="fas fa-user-plus"></i> New Enrollment
                    </a>
                    <a href="delete_enrollment.php?id=<?= $enrollment_id ?>" class="sidebar-link active">
                        <i class="fas fa-trash"></i> Delete Enrollment
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
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../expenses/expenses.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Expenses
                    </a>
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
                    <h1 class="text-2xl font-bold text-gray-800">Delete Enrollment</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-trash-alt text-red-500 mr-1"></i>
                        Deactivate enrollment record
                    </p>
                </div>
                <div>
                    <a href="enrollment_list.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Enrollments
                    </a>
                </div>
            </div>

            <!-- Confirmation Container -->
            <div class="confirmation-container">
                <!-- Warning Message -->
                <div class="warning-box mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-red-800">Warning: Deactivating Enrollment</h3>
                            <div class="mt-2 text-red-700">
                                <p>You are about to deactivate this enrollment. This action will:</p>
                                <ul class="list-disc pl-5 mt-2 space-y-1">
                                    <li>Change the enrollment status to "inactive"</li>
                                    <li>Remove this enrollment from active listings</li>
                                    <li>Not delete any student records or payment history</li>
                                    <li>Allow reactivation if needed</li>
                                </ul>
                                <p class="mt-3 font-semibold">This action cannot be undone through the interface.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Details -->
                <div class="enrollment-details">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Enrollment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Enrollment ID</p>
                            <p class="font-medium text-gray-800">#<?= $enrollment['id'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Student</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($enrollment['student_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= $enrollment['student_code'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Skill/Course</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($enrollment['skill_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Session & Batch</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($enrollment['session_name']) ?> - <?= htmlspecialchars($enrollment['batch_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Admission Date</p>
                            <p class="font-medium text-gray-800"><?= date('d M, Y', strtotime($enrollment['admission_date'])) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Created Date</p>
                            <p class="font-medium text-gray-800"><?= date('d M, Y', strtotime($enrollment['created_at'])) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <?php if (isset($error_message)): ?>
                    <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-lg">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <!-- Confirmation Form -->
                <form method="POST">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Please confirm by typing "DELETE" to proceed
                        </label>
                        <input type="text"
                            name="confirm_text"
                            placeholder="Type DELETE to confirm"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                            required
                            pattern="DELETE">
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="enrollment_list.php"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit" name="confirm_delete"
                            class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded text-sm font-medium"
                            onclick="return confirm('Are you absolutely sure you want to deactivate this enrollment?')">
                            <i class="fas fa-trash-alt mr-2"></i> Deactivate Enrollment
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Validate DELETE confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmInput = document.querySelector('input[name="confirm_text"]');
            if (confirmInput.value !== 'DELETE') {
                e.preventDefault();
                alert('Please type "DELETE" in the confirmation field to proceed.');
                confirmInput.focus();
                confirmInput.classList.add('border-red-500');
            }
        });

        // Remove red border when user starts typing
        document.querySelector('input[name="confirm_text"]').addEventListener('input', function() {
            this.classList.remove('border-red-500');
        });
    </script>
</body>

</html>
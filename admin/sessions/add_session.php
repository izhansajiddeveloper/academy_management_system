<?php
require_once __DIR__ . '/../../config/db.php';


if (isset($_POST['submit'])) {
    $session_name = mysqli_real_escape_string($conn, $_POST['session_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Add new session with default status 'active'
    mysqli_query($conn, "INSERT INTO sessions (session_name) VALUES ('$session_name')");

    header("Location: sessions.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New Session | Academy Management System</title>
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

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-link:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .form-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
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

                <!-- SESSIONS (Active) -->
                <a href="sessions.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-[var(--primary)] font-semibold">
                    <i class="fas fa-calendar-alt text-[var(--primary)]"></i> Sessions
                </a>

                <!-- Sessions Submenu -->
                <div class="ml-8 space-y-1">
                    <a href="sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-play-circle text-sm"></i>
                        <span>Active Sessions</span>
                    </a>

                    <a href="completed_sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-check-circle text-sm"></i>
                        <span>Completed Sessions</span>
                    </a>

                    <a href="add_session.php"
                        class="flex items-center gap-2 p-2 rounded-lg bg-blue-100 text-[var(--primary)] font-semibold">
                        <i class="fas fa-plus-circle text-sm"></i>
                        <span>Add New Session</span>
                    </a>
                </div>

                <!-- Other menu items -->
                <a href="../batches/batches.php" class="sidebar-link">
                    <i class="fas fa-layer-group"></i> Batches
                </a>
                <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                    <i class="fas fa-user-check"></i> Enrollments
                </a>
                <a href="../fees/fee_collection.php" class="sidebar-link">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>
                <a href="../expenses/expenses.php" class="sidebar-link">
                    <i class="fas fa-wallet"></i> Expenses
                </a>
                <a href="../reports/student_report.php" class="sidebar-link">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Add New Session</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                        Create a new training session
                    </p>
                </div>
                <div>
                    <a href="sessions.php"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-3 rounded-lg font-medium hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Sessions
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class="max-w-2xl mx-auto">
                <div class="form-card p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-plus text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Session Details</h2>
                            <p class="text-sm text-gray-500">Fill in the session information below</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-6">
                        <!-- Session Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-heading text-blue-500 mr-2"></i>
                                Session Name
                            </label>
                            <input type="text"
                                name="session_name"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter session name"
                                required>
                            <p class="text-xs text-gray-500 mt-1">
                                Give a descriptive name for the session (e.g., "Spring 2024 Training")
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>
                                Description (Optional)
                            </label>
                            <textarea
                                name="description"
                                rows="4"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Describe the session purpose, goals, or any additional details"></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Add a brief description about what this session covers
                            </p>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-blue-800 mb-1">Quick Information</h4>
                                    <ul class="text-sm text-blue-600 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>Sessions are set to "Active" by default</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>You can edit sessions anytime</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>Mark as completed when session ends</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4 border-t border-gray-100">
                            <button type="submit"
                                name="submit"
                                class="flex-1 btn-primary px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i>
                                Create Session
                            </button>
                            <a href="sessions.php"
                                class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to form inputs
            const formInputs = document.querySelectorAll('.form-input');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-200', 'ring-opacity-50');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-200', 'ring-opacity-50');
                });
            });
        });
    </script>
</body>

</html>
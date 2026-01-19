<?php
require_once __DIR__ . '/../../config/db.php';


// Fetch skills and sessions for dropdown
$skills = mysqli_query($conn, "SELECT * FROM skills WHERE status='active' ORDER BY skill_name");
$sessions = mysqli_query($conn, "SELECT * FROM sessions WHERE status='active' ORDER BY session_name");

if (isset($_POST['submit'])) {
    $skill_id = intval($_POST['skill_id']);
    $session_id = intval($_POST['session_id']);
    $batch_name = mysqli_real_escape_string($conn, $_POST['batch_name']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_students = intval($_POST['max_students']);

    mysqli_query($conn, "
        INSERT INTO batches (skill_id, session_id, batch_name, start_time, end_time, max_students, status)
        VALUES ($skill_id, $session_id, '$batch_name', '$start_time', '$end_time', $max_students, 'active')
    ");

    header("Location: batches.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New Batch | Academy Management System</title>
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

        .btn-success {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
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

                <!-- Sessions -->
                <a href="../sessions/sessions.php"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700">
                    <i class="fas fa-calendar-alt text-[var(--primary)]"></i> Sessions
                </a>

                <!-- BATCHES -->
                <a href="batches.php"
                    class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-[var(--primary)] font-semibold">
                    <i class="fas fa-layer-group text-[var(--primary)]"></i> Batches
                </a>

                <!-- Batches Submenu -->
                <div class="ml-8 space-y-1">
                    <a href="batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-play-circle text-sm"></i>
                        <span>Active Batches</span>
                    </a>

                    <a href="completed_batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-check-circle text-sm"></i>
                        <span>Completed Batches</span>
                    </a>

                    <a href="add_batch.php"
                        class="flex items-center gap-2 p-2 rounded-lg bg-blue-100 text-[var(--primary)] font-semibold">
                        <i class="fas fa-plus-circle text-sm"></i>
                        <span>Add New Batch</span>
                    </a>
                </div>

                <!-- Other menu items -->
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
                    <h1 class="text-3xl font-bold text-gray-800">Add New Batch</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                        Create a new training batch
                    </p>
                </div>
                <div>
                    <a href="batches.php"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-3 rounded-lg font-medium hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Batches
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class="max-w-2xl mx-auto">
                <div class="form-card p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Batch Details</h2>
                            <p class="text-sm text-gray-500">Fill in the batch information below</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-6">
                        <!-- Batch Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-heading text-blue-500 mr-2"></i>
                                Batch Name *
                            </label>
                            <input type="text"
                                name="batch_name"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., Web Development Batch 1"
                                required>
                            <p class="text-xs text-gray-500 mt-1">
                                Give a descriptive name for the batch
                            </p>
                        </div>

                        <!-- Skill Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-book text-purple-500 mr-2"></i>
                                Select Skill *
                            </label>
                            <select name="skill_id"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="">-- Select Skill --</option>
                                <?php while ($s = mysqli_fetch_assoc($skills)) { ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['skill_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Session Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                Select Session *
                            </label>
                            <select name="session_id"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="">-- Select Session --</option>
                                <?php while ($se = mysqli_fetch_assoc($sessions)) { ?>
                                    <option value="<?= $se['id'] ?>"><?= htmlspecialchars($se['session_name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Time Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-clock text-yellow-500 mr-2"></i>
                                    Start Time *
                                </label>
                                <input type="time"
                                    name="start_time"
                                    class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-clock text-red-500 mr-2"></i>
                                    End Time *
                                </label>
                                <input type="time"
                                    name="end_time"
                                    class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                            </div>
                        </div>

                        <!-- Max Students -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-users text-green-500 mr-2"></i>
                                Maximum Students *
                            </label>
                            <input type="number"
                                name="max_students"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., 30"
                                min="1"
                                max="100"
                                required>
                            <p class="text-xs text-gray-500 mt-1">
                                Maximum number of students allowed in this batch
                            </p>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-blue-800 mb-1">Important Information</h4>
                                    <ul class="text-sm text-blue-600 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>Batches are set to "Active" by default</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>You can assign a teacher after creating the batch</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check-circle text-xs"></i>
                                            <span>Mark as completed when batch ends</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4 border-t border-gray-100">
                            <button type="submit"
                                name="submit"
                                class="flex-1 btn-success px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i>
                                Create Batch
                            </button>
                            <a href="batches.php"
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

            // Time validation
            const startTime = document.querySelector('input[name="start_time"]');
            const endTime = document.querySelector('input[name="end_time"]');

            if (startTime && endTime) {
                startTime.addEventListener('change', function() {
                    if (endTime.value && this.value >= endTime.value) {
                        alert('Start time must be before end time');
                        this.value = '';
                    }
                });

                endTime.addEventListener('change', function() {
                    if (startTime.value && this.value <= startTime.value) {
                        alert('End time must be after start time');
                        this.value = '';
                    }
                });
            }
        });
    </script>
</body>

</html>
<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch all active skills
$skills = mysqli_query($conn, "SELECT id, skill_name FROM skills WHERE status='active' ORDER BY skill_name");

// Fetch all active sessions
$sessions = mysqli_query($conn, "SELECT id, session_name FROM sessions WHERE status='active' ORDER BY id DESC");

// Fetch all active batches
$batches = mysqli_query($conn, "SELECT id, batch_name FROM batches WHERE status='active' ORDER BY batch_name");

// Handle form submission
if (isset($_POST['enroll_student'])) {
    // User details
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // consider hashing in production
    $name = trim($_POST['name']);
    $father_name = trim($_POST['father_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Enrollment details
    $skill_id = intval($_POST['skill_id']);
    $session_id = intval($_POST['session_id']);
    $batch_id = intval($_POST['batch_id']);
    $admission_date = $_POST['admission_date'];

    // 1️⃣ Insert into users table
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, user_type_id, status, created_at) VALUES (?, ?, ?, 3, 'active', NOW())");
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);
    mysqli_stmt_execute($stmt);
    $user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // 2️⃣ Insert into students table
    $student_code = 'STD-' . str_pad($user_id, 3, '0', STR_PAD_LEFT); // example: STD-016
    $stmt = mysqli_prepare($conn, "INSERT INTO students (user_id, student_code, name, father_name, gender, dob, phone, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
    mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $student_code, $name, $father_name, $gender, $dob, $phone, $address);
    mysqli_stmt_execute($stmt);
    $student_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // 3️⃣ Insert into student_enrollments
    $stmt = mysqli_prepare($conn, "INSERT INTO student_enrollments (student_id, skill_id, session_id, batch_id, admission_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    mysqli_stmt_bind_param($stmt, "iiiis", $student_id, $skill_id, $session_id, $batch_id, $admission_date);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: enrollment_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Enroll New Student | Academy Management System</title>
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

        .form-container {
            background: white;
            border-radius: 6px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
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
                    <a href="enroll_student.php" class="sidebar-link active">
                        <i class="fas fa-user-plus"></i> New Enrollment
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
                    <h1 class="text-2xl font-bold text-gray-800">Enroll New Student</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-plus text-blue-500 mr-1"></i>
                        Register a new student and enroll them in a course
                    </p>
                </div>
                <div>
                    <a href="enrollment_list.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Enrollments
                    </a>
                </div>
            </div>

            <!-- Enrollment Form -->
            <div class="form-container">
                <form method="POST">
                    <!-- Student Details Section -->
                    <div class="mb-8">
                        <h3 class="section-title">Student Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-input" placeholder="Enter username" required>
                            </div>
                            <div>
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-input" placeholder="Enter email" required>
                            </div>
                            <div>
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-input" placeholder="Enter password" required>
                            </div>
                            <div>
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-input" placeholder="Enter full name" required>
                            </div>
                            <div>
                                <label class="form-label">Father's Name *</label>
                                <input type="text" name="father_name" class="form-input" placeholder="Enter father's name" required>
                            </div>
                            <div>
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name="dob" class="form-input" required>
                            </div>
                            <div>
                                <label class="form-label">Phone Number *</label>
                                <input type="text" name="phone" class="form-input" placeholder="Enter phone number" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Address *</label>
                                <input type="text" name="address" class="form-input" placeholder="Enter complete address" required>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Details Section -->
                    <div class="mb-8">
                        <h3 class="section-title">Enrollment Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Select Skill *</label>
                                <select name="skill_id" class="form-select" required>
                                    <option value="">Select Skill/Course</option>
                                    <?php while ($sk = mysqli_fetch_assoc($skills)) { ?>
                                        <option value="<?= $sk['id'] ?>"><?= htmlspecialchars($sk['skill_name']) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Select Session *</label>
                                <select name="session_id" class="form-select" required>
                                    <option value="">Select Session</option>
                                    <?php
                                    mysqli_data_seek($sessions, 0); // Reset pointer
                                    while ($se = mysqli_fetch_assoc($sessions)) { ?>
                                        <option value="<?= $se['id'] ?>"><?= htmlspecialchars($se['session_name']) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Select Batch *</label>
                                <select name="batch_id" class="form-select" required>
                                    <option value="">Select Batch</option>
                                    <?php
                                    mysqli_data_seek($batches, 0); // Reset pointer
                                    while ($b = mysqli_fetch_assoc($batches)) { ?>
                                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['batch_name']) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Admission Date *</label>
                                <input type="date" name="admission_date" value="<?= date('Y-m-d') ?>" class="form-input" required>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="enrollment_list.php"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit" name="enroll_student"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium">
                            <i class="fas fa-user-plus mr-2"></i> Enroll Student
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>
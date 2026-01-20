<?php
require_once __DIR__ . '/../../config/db.php';

$success_message = '';
$error_message = '';

// Get enrollment ID from URL
$enrollment_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;

if (!$enrollment_id) {
    header("Location: enrollment_list.php");
    exit;
}

// Fetch enrollment details
$enrollment_query = "
    SELECT se.*, s.name AS student_name, s.student_code
    FROM student_enrollments se
    JOIN students s ON se.student_id = s.id
    WHERE se.id = $enrollment_id AND se.status = 'active'
";
$enrollment_result = mysqli_query($conn, $enrollment_query);
$enrollment = mysqli_fetch_assoc($enrollment_result);

if (!$enrollment) {
    header("Location: enrollment_list.php");
    exit;
}

// Fetch all active skills
$skills = mysqli_query($conn, "SELECT id, skill_name FROM skills WHERE status='active' ORDER BY skill_name");

// Fetch all active sessions
$sessions = mysqli_query($conn, "SELECT id, session_name FROM sessions WHERE status='active' ORDER BY id DESC");

// Fetch all active batches
$batches = mysqli_query($conn, "SELECT id, batch_name FROM batches WHERE status='active' ORDER BY batch_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_enrollment'])) {
    $skill_id = intval($_POST['skill_id']);
    $session_id = intval($_POST['session_id']);
    $batch_id = intval($_POST['batch_id']);
    $admission_date = $_POST['admission_date'];

    // Validate required fields
    if ($skill_id > 0 && $session_id > 0 && $batch_id > 0 && !empty($admission_date)) {
        // Update enrollment
        $update_query = "
            UPDATE student_enrollments 
            SET skill_id = $skill_id,
                session_id = $session_id,
                batch_id = $batch_id,
                admission_date = '$admission_date',
                updated_at = NOW()
            WHERE id = $enrollment_id
        ";

        if (mysqli_query($conn, $update_query)) {
            $success_message = "Enrollment updated successfully!";
            // Refresh enrollment data
            $enrollment_result = mysqli_query($conn, $enrollment_query);
            $enrollment = mysqli_fetch_assoc($enrollment_result);
        } else {
            $error_message = "Error updating enrollment: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Enrollment | Academy Management System</title>
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

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
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
                    <a href="enroll_student.php" class="sidebar-link">
                        <i class="fas fa-user-plus"></i> New Enrollment
                    </a>
                    <a href="edit_enrollment.php?id=<?= $enrollment_id ?>" class="sidebar-link active">
                        <i class="fas fa-edit"></i> Edit Enrollment
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
                    <h1 class="text-2xl font-bold text-gray-800">Edit Enrollment</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-edit text-blue-500 mr-1"></i>
                        Update enrollment details for student
                    </p>
                </div>
                <div>
                    <a href="enrollment_list.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Enrollments
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <!-- Student Information Box -->
            <div class="info-box mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">Student Information</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <span class="font-medium">Name:</span> <?= htmlspecialchars($enrollment['student_name']) ?> |
                            <span class="font-medium">Code:</span> <?= $enrollment['student_code'] ?> |
                            <span class="font-medium">Enrollment ID:</span> #<?= $enrollment['id'] ?>
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-calendar mr-1"></i>
                        Created: <?= date('M d, Y', strtotime($enrollment['created_at'])) ?>
                    </div>
                </div>
            </div>

            <!-- Edit Enrollment Form -->
            <div class="form-container">
                <form method="POST">
                    <div class="mb-8">
                        <h3 class="section-title">Update Enrollment Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Skill -->
                            <div>
                                <label class="form-label">Select Skill *</label>
                                <select name="skill_id" class="form-select" required>
                                    <option value="">Select Skill/Course</option>
                                    <?php
                                    mysqli_data_seek($skills, 0); // Reset pointer
                                    while ($sk = mysqli_fetch_assoc($skills)): ?>
                                        <option value="<?= $sk['id'] ?>" <?= $enrollment['skill_id'] == $sk['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sk['skill_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Session -->
                            <div>
                                <label class="form-label">Select Session *</label>
                                <select name="session_id" class="form-select" required>
                                    <option value="">Select Session</option>
                                    <?php
                                    mysqli_data_seek($sessions, 0); // Reset pointer
                                    while ($se = mysqli_fetch_assoc($sessions)): ?>
                                        <option value="<?= $se['id'] ?>" <?= $enrollment['session_id'] == $se['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($se['session_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Batch -->
                            <div>
                                <label class="form-label">Select Batch *</label>
                                <select name="batch_id" class="form-select" required>
                                    <option value="">Select Batch</option>
                                    <?php
                                    mysqli_data_seek($batches, 0); // Reset pointer
                                    while ($b = mysqli_fetch_assoc($batches)): ?>
                                        <option value="<?= $b['id'] ?>" <?= $enrollment['batch_id'] == $b['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['batch_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Admission Date -->
                            <div>
                                <label class="form-label">Admission Date *</label>
                                <input type="date"
                                    name="admission_date"
                                    value="<?= $enrollment['admission_date'] ?>"
                                    class="form-input"
                                    required>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="enrollment_list.php"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit" name="update_enrollment"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium">
                            <i class="fas fa-save mr-2"></i> Update Enrollment
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>
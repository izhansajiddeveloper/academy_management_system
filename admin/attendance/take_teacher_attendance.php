<?php
require_once __DIR__ . '/../../config/db.php';

// Get today's date
$today = date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'];

    // Get marked_by (admin user id from session)
    session_start();
    $marked_by = $_SESSION['user_id'] ?? 1; // Default to admin id 1

    foreach ($attendance_data as $teacher_id => $status) {
        $teacher_id = intval($teacher_id);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks'][$teacher_id] ?? '');

        // Check if attendance already exists for today
        $check_query = "SELECT id FROM teacher_attendance WHERE teacher_id = $teacher_id AND attendance_date = '$attendance_date' AND status='active'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $update_query = "UPDATE teacher_attendance SET attendance_status = '$status', remarks = '$remarks', updated_at = NOW() WHERE teacher_id = $teacher_id AND attendance_date = '$attendance_date'";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO teacher_attendance (teacher_id, attendance_date, attendance_status, remarks, marked_by, status, created_at) VALUES ($teacher_id, '$attendance_date', '$status', '$remarks', $marked_by, 'active', NOW())";
            mysqli_query($conn, $insert_query);
        }
    }

    header("Location: take_teacher_attendance.php?success=1");
    exit;
}

// Fetch all active teachers with their details from both tables
$teachers_query = "
    SELECT 
        t.id as teacher_id,
        t.teacher_code,
        t.name as teacher_name,
        t.phone,
        t.qualification,
        u.username,
        u.email,
        u.status
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE u.user_type_id = 2 
    AND u.status = 'active'
    AND t.status = 'active'
    ORDER BY t.name
";
$teachers_result = mysqli_query($conn, $teachers_query);

// Get teacher count
$teacher_count_query = "
    SELECT COUNT(*) as count 
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE u.user_type_id = 2 
    AND u.status = 'active'
    AND t.status = 'active'
";
$teacher_count_result = mysqli_query($conn, $teacher_count_query);
$teacher_count_data = mysqli_fetch_assoc($teacher_count_result);
$teacher_count = $teacher_count_data['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Take Teacher Attendance | Academy Management System</title>
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
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-box {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
        }

        .search-box:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .teacher-name-cell {
            min-width: 200px;
        }

        .teacher-info {
            display: flex;
            flex-direction: column;
        }

        .teacher-code {
            font-size: 12px;
            color: #6b7280;
            font-family: monospace;
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
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Attendance</p>
                    <a href="attendance.php" class="sidebar-link">
                        <i class="fas fa-clipboard-check"></i> Attendance Home
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Teacher Attendance</p>
                    <a href="take_teacher_attendance.php" class="sidebar-link active">
                        <i class="fas fa-plus"></i> Take Teacher Attendance
                    </a>
                    <a href="edit_teacher_attendance.php" class="sidebar-link">
                        <i class="fas fa-edit"></i> Edit Teacher Attendance
                    </a>
                    <a href="view_teacher_attendance.php" class="sidebar-link">
                        <i class="fas fa-eye"></i> View Teacher Attendance
                    </a>
                </div>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Take Teacher Attendance</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-chalkboard-teacher text-blue-500 mr-1"></i>
                        Mark attendance for teachers
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search teachers..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    Teacher attendance saved successfully!
                </div>
            <?php endif; ?>

            <!-- Attendance Form -->
            <div class="form-container">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Teacher Attendance for <?= date('F j, Y') ?></h3>
                        <p class="text-sm text-gray-500"><?= $teacher_count ?> active teachers</p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="attendance_date" value="<?= $today ?>">

                    <div class="overflow-x-auto">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th class="teacher-name-cell">Teacher</th>
                                    <th>Login Details</th>
                                    <th>Phone</th>
                                    <th>Attendance Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $index = 1;
                                while ($teacher = mysqli_fetch_assoc($teachers_result)):
                                    // Get existing attendance for today
                                    $existing_query = "SELECT * FROM teacher_attendance WHERE teacher_id = {$teacher['teacher_id']} AND attendance_date = '$today' AND status='active'";
                                    $existing_result = mysqli_query($conn, $existing_query);
                                    $existing = mysqli_fetch_assoc($existing_result);
                                ?>
                                    <tr class="teacher-row">
                                        <td><?= $index++ ?></td>
                                        <td class="teacher-name-cell">
                                            <div class="teacher-info">
                                                <span class="font-medium text-gray-900"><?= htmlspecialchars($teacher['teacher_name']) ?></span>
                                                <span class="teacher-code"><?= $teacher['teacher_code'] ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-sm">
                                                <div class="font-medium text-gray-700"><?= htmlspecialchars($teacher['username']) ?></div>
                                                <div class="text-gray-500 text-xs"><?= htmlspecialchars($teacher['email']) ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm text-gray-600"><?= htmlspecialchars($teacher['phone']) ?></span>
                                        </td>
                                        <td>
                                            <select name="attendance[<?= $teacher['teacher_id'] ?>]" class="search-box" style="width: auto;">
                                                <option value="present" <?= ($existing['attendance_status'] ?? 'present') == 'present' ? 'selected' : '' ?>>Present</option>
                                                <option value="absent" <?= ($existing['attendance_status'] ?? '') == 'absent' ? 'selected' : '' ?>>Absent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text"
                                                name="remarks[<?= $teacher['teacher_id'] ?>]"
                                                class="search-box"
                                                placeholder="Optional remarks"
                                                value="<?= htmlspecialchars($existing['remarks'] ?? '') ?>">
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                        <a href="attendance.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium">
                            <i class="fas fa-save mr-2"></i> Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.teacher-row');

            rows.forEach(row => {
                const nameCell = row.querySelector('.teacher-info');
                const name = nameCell.textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>
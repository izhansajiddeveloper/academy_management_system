<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch student attendance data (limit to 10 for initial view)
$student_attendance_query = "
    SELECT sa.*, s.name as student_name, s.student_code, sk.skill_name, se.session_name, b.batch_name
    FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    JOIN skills sk ON sa.skill_id = sk.id
    JOIN sessions se ON sa.session_id = se.id
    JOIN batches b ON sa.batch_id = b.id
    WHERE sa.status='active'
    ORDER BY sa.attendance_date DESC, s.name
    LIMIT 10
";
$student_attendance_result = mysqli_query($conn, $student_attendance_query);

// Fetch teacher attendance data (limit to 10 for initial view)
$teacher_attendance_query = "
    SELECT ta.*, t.name as teacher_name, t.teacher_code, t.phone
    FROM teacher_attendance ta
    JOIN teachers t ON ta.teacher_id = t.id
    WHERE ta.status='active'
    ORDER BY ta.attendance_date DESC, t.name
    LIMIT 10
";
$teacher_attendance_result = mysqli_query($conn, $teacher_attendance_query);

// Get today's date
$today = date('Y-m-d');

// Student attendance stats
$student_today_query = "SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = '$today' AND status='active'";
$student_today_result = mysqli_query($conn, $student_today_query);
$student_today = mysqli_fetch_assoc($student_today_result)['count'];

$student_total_query = "SELECT COUNT(*) as count FROM student_attendance WHERE status='active'";
$student_total_result = mysqli_query($conn, $student_total_query);
$student_total = mysqli_fetch_assoc($student_total_result)['count'];

// Teacher attendance stats - updated to count only active teachers
$teacher_today_query = "
    SELECT COUNT(*) as count 
    FROM teacher_attendance ta
    JOIN teachers t ON ta.teacher_id = t.id
    WHERE ta.attendance_date = '$today' 
    AND ta.status='active' 
    AND t.status='active'
";
$teacher_today_result = mysqli_query($conn, $teacher_today_query);
$teacher_today = mysqli_fetch_assoc($teacher_today_result)['count'];

$teacher_total_query = "
    SELECT COUNT(*) as count 
    FROM teacher_attendance ta
    JOIN teachers t ON ta.teacher_id = t.id
    WHERE ta.status='active' 
    AND t.status='active'
";
$teacher_total_result = mysqli_query($conn, $teacher_total_query);
$teacher_total = mysqli_fetch_assoc($teacher_total_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Management | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

      
        .toggle-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .toggle-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .toggle-btn:not(.active) {
            background: #f3f4f6;
            color: #6b7280;
        }

        .toggle-btn:not(.active):hover {
            background: #e5e7eb;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb !important;
        }

        .attendance-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .badge-present {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-late {
            background: #fef3c7;
            color: #92400e;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .search-box {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-box:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
                    <h1 class="text-2xl font-bold text-gray-800">Attendance Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-clipboard-check text-blue-500 mr-1"></i>
                        Manage student and teacher attendance records
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search records..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500 mb-1">Students Today</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $student_today; ?></h3>
                </div>
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500 mb-1">Student Records</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $student_total; ?></h3>
                </div>
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500 mb-1">Teachers Today</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $teacher_today; ?></h3>
                </div>
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500 mb-1">Teacher Records</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $teacher_total; ?></h3>
                </div>
            </div>

            <!-- Toggle Section -->
            <div class="mb-6">
                <div class="flex justify-center mb-6">
                    <div class="flex bg-gray-100 p-1 rounded-lg">
                        <button id="studentToggle" class="toggle-btn active" onclick="showStudentSection()">
                            <i class="fas fa-user-graduate mr-2"></i> Student Attendance
                        </button>
                        <button id="teacherToggle" class="toggle-btn" onclick="showTeacherSection()">
                            <i class="fas fa-chalkboard-teacher mr-2"></i> Teacher Attendance
                        </button>
                    </div>
                </div>

                <!-- Student Section -->
                <div id="studentSection">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Student Attendance Records</h3>
                        <a href="take_student_attendance.php"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-plus mr-1"></i> Take New Attendance
                        </a>
                    </div>

                    <div class="table-container mb-6">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="font-medium text-gray-800">Recent Student Attendance</h3>
                        </div>

                        <?php if (mysqli_num_rows($student_attendance_result) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch & Skill</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php while ($record = mysqli_fetch_assoc($student_attendance_result)):
                                            $badge_class = '';
                                            switch ($record['attendance_status']) {
                                                case 'present':
                                                    $badge_class = 'badge-present';
                                                    break;
                                                case 'absent':
                                                    $badge_class = 'badge-absent';
                                                    break;
                                                case 'late':
                                                    $badge_class = 'badge-late';
                                                    break;
                                            }
                                        ?>
                                            <tr class="student-row">
                                                <td class="py-3 px-4 student-name">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($record['student_name']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= $record['student_code'] ?></div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($record['batch_name']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($record['skill_name']) ?></div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="text-sm text-gray-600"><?= date('d M, Y', strtotime($record['attendance_date'])) ?></div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <span class="<?= $badge_class ?> attendance-badge">
                                                        <?= ucfirst($record['attendance_status']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex gap-2">
                                                        <a href="take_student_attendance.php?batch_id=<?= $record['batch_id'] ?>"
                                                            class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                            title="Take Attendance">
                                                            <i class="fas fa-plus text-xs"></i> Take
                                                        </a>
                                                        <a href="edit_student_attendance.php"
                                                            class="action-btn bg-yellow-50 text-yellow-700 hover:bg-yellow-100"
                                                            title="Edit Attendance">
                                                            <i class="fas fa-edit text-xs"></i> Edit
                                                        </a>
                                                        <a href="view_student_attendance.php"
                                                            class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                            title="View Records">
                                                            <i class="fas fa-eye text-xs"></i> View
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 border-t bg-gray-50">
                                <a href="view_student_attendance.php"
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    View all student attendance records →
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="p-8 text-center">
                                <i class="fas fa-user-graduate text-gray-300 text-4xl mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-700 mb-2">No Student Attendance Records</h3>
                                <p class="text-gray-500 text-sm mb-4">Start by taking attendance for students</p>
                                <a href="take_student_attendance.php"
                                    class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                    <i class="fas fa-plus"></i> Take First Attendance
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teacher Section (Hidden by default) -->
                <div id="teacherSection" class="hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Teacher Attendance Records</h3>
                        <a href="take_teacher_attendance.php"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-plus mr-1"></i> Take New Attendance
                        </a>
                    </div>

                    <div class="table-container">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="font-medium text-gray-800">Recent Teacher Attendance</h3>
                        </div>

                        <?php if (mysqli_num_rows($teacher_attendance_result) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Remarks</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php while ($record = mysqli_fetch_assoc($teacher_attendance_result)):
                                            $badge_class = $record['attendance_status'] == 'present' ? 'badge-present' : 'badge-absent';
                                        ?>
                                            <tr class="teacher-row">
                                                <td class="py-3 px-4 teacher-name">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($record['teacher_name']) ?></div>
                                                    <div class="text-xs text-gray-500"><?= $record['teacher_code'] ?></div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="text-sm text-gray-600"><?= date('d M, Y', strtotime($record['attendance_date'])) ?></div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <span class="<?= $badge_class ?> attendance-badge">
                                                        <?= ucfirst($record['attendance_status']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="text-sm text-gray-600 max-w-xs truncate">
                                                        <?= htmlspecialchars($record['remarks'] ?: 'No remarks') ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <div class="flex gap-2">
                                                        <a href="take_teacher_attendance.php"
                                                            class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                            title="Take Attendance">
                                                            <i class="fas fa-plus text-xs"></i> Take
                                                        </a>
                                                        <a href="edit_teacher_attendance.php"
                                                            class="action-btn bg-yellow-50 text-yellow-700 hover:bg-yellow-100"
                                                            title="Edit Attendance">
                                                            <i class="fas fa-edit text-xs"></i> Edit
                                                        </a>
                                                        <a href="view_teacher_attendance.php"
                                                            class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                            title="View Records">
                                                            <i class="fas fa-eye text-xs"></i> View
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 border-t bg-gray-50">
                                <a href="view_teacher_attendance.php"
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    View all teacher attendance records →
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="p-8 text-center">
                                <i class="fas fa-chalkboard-teacher text-gray-300 text-4xl mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-700 mb-2">No Teacher Attendance Records</h3>
                                <p class="text-gray-500 text-sm mb-4">Start by taking attendance for teachers</p>
                                <a href="take_teacher_attendance.php"
                                    class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                    <i class="fas fa-plus"></i> Take First Attendance
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showStudentSection() {
            document.getElementById('studentSection').classList.remove('hidden');
            document.getElementById('teacherSection').classList.add('hidden');
            document.getElementById('studentToggle').classList.add('active');
            document.getElementById('teacherToggle').classList.remove('active');
        }

        function showTeacherSection() {
            document.getElementById('studentSection').classList.add('hidden');
            document.getElementById('teacherSection').classList.remove('hidden');
            document.getElementById('studentToggle').classList.remove('active');
            document.getElementById('teacherToggle').classList.add('active');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const isStudentView = !document.getElementById('studentSection').classList.contains('hidden');

            if (isStudentView) {
                const rows = document.querySelectorAll('.student-row');
                rows.forEach(row => {
                    const name = row.querySelector('.student-name').textContent.toLowerCase();
                    if (name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            } else {
                const rows = document.querySelectorAll('.teacher-row');
                rows.forEach(row => {
                    const name = row.querySelector('.teacher-name').textContent.toLowerCase();
                    if (name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    </script>

</body>

</html>
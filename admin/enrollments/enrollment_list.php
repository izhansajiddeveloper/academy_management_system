<?php
require_once __DIR__ . '/../../config/db.php';

// Handle soft delete (deactivate enrollment)
if (isset($_GET['deactivate_id'])) {
    $id = intval($_GET['deactivate_id']);
    mysqli_query($conn, "UPDATE student_enrollments SET status='inactive' WHERE id=$id");
    header("Location: enrollment_list.php");
    exit;
}

// Fetch all enrollments with student, skill, session, batch info
$enrollments = mysqli_query($conn, "
    SELECT se.*, s.name AS student_name, s.student_code, sk.skill_name, ss.session_name, b.batch_name
    FROM student_enrollments se
    JOIN students s ON se.student_id = s.id
    JOIN skills sk ON se.skill_id = sk.id
    JOIN sessions ss ON se.session_id = ss.id
    JOIN batches b ON se.batch_id = b.id
    WHERE se.status='active'
    ORDER BY se.id DESC
");

// Count statistics
$total_enrollments = mysqli_num_rows($enrollments);
mysqli_data_seek($enrollments, 0); // Reset pointer

// Count active vs inactive
$active_query = "SELECT COUNT(*) as count FROM student_enrollments WHERE status='active'";
$active_result = mysqli_query($conn, $active_query);
$active_count = mysqli_fetch_assoc($active_result)['count'];

$inactive_query = "SELECT COUNT(*) as count FROM student_enrollments WHERE status='inactive'";
$inactive_result = mysqli_query($conn, $inactive_query);
$inactive_count = mysqli_fetch_assoc($inactive_result)['count'];

$today_query = "SELECT COUNT(*) as count FROM student_enrollments WHERE DATE(created_at) = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$today_count = mysqli_fetch_assoc($today_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Enrollments | Academy Management System</title>
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

        .table-container {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb !important;
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

        .status-inactive {
            background: #f3f4f6;
            color: #6b7280;
            text-decoration: line-through;
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
                    <a href="enrollment_list.php" class="sidebar-link active">
                        <i class="fas fa-list"></i> All Enrollments
                    </a>
                    <a href="enroll_student.php" class="sidebar-link">
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

            <div class="p-3 border-t border-gray-700 mt-auto">
                <a href="enroll_student.php" class="w-full flex items-center justify-center gap-2 text-sm p-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    <span>Enroll Student</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Student Enrollments</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-check text-blue-500 mr-1"></i>
                        Manage all student enrollments and registrations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search enrollments..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="enroll_student.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Enroll Student
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-4 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Enrollments</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $active_count; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Active</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $active_count; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Inactive</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $inactive_count; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Today's</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $today_count; ?></h3>
                </div>
            </div>

            <!-- Enrollments Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">All Active Enrollments (<?php echo $total_enrollments; ?>)</h3>
                </div>

                <?php if ($total_enrollments > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Admission Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($enrollments)): ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">#<?= $row['id'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= $row['student_code'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($row['skill_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($row['session_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($row['batch_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= date('d M, Y', strtotime($row['admission_date'])) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle text-xs"></i>
                                                Active
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="enroll_student.php?edit_id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <a href="enrollment_list.php?deactivate_id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to deactivate this enrollment for <?= htmlspecialchars($row['student_name']) ?>?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Deactivate">
                                                    <i class="fas fa-times text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Enrollments Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No active enrollments found. Start by enrolling your first student.</p>
                        <a href="enroll_student.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Enroll First Student
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const tableRows = document.querySelectorAll('tbody tr');

                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = 'table-row';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

</body>

</html>
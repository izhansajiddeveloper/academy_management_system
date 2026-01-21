<?php
require_once __DIR__ . '/../../config/db.php';

// Handle attendance update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $attendance_id = intval($_POST['attendance_id']);
    $attendance_status = mysqli_real_escape_string($conn, $_POST['attendance_status']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $update_query = "UPDATE teacher_attendance SET attendance_status = '$attendance_status', remarks = '$remarks', updated_at = NOW() WHERE id = $attendance_id";
    mysqli_query($conn, $update_query);

    header("Location: edit_teacher_attendance.php?success=1&id=" . $attendance_id);
    exit;
}

// Handle attendance delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_query = "UPDATE teacher_attendance SET status='inactive' WHERE id = $delete_id";
    mysqli_query($conn, $delete_query);

    header("Location: edit_teacher_attendance.php?deleted=1");
    exit;
}

// Fetch attendance records with filters
$where_conditions = ["ta.status='active'"];

if (isset($_GET['teacher_name']) && !empty($_GET['teacher_name'])) {
    $teacher_name = mysqli_real_escape_string($conn, $_GET['teacher_name']);
    $where_conditions[] = "u.username LIKE '%$teacher_name%'";
}

if (isset($_GET['attendance_date']) && !empty($_GET['attendance_date'])) {
    $attendance_date = mysqli_real_escape_string($conn, $_GET['attendance_date']);
    $where_conditions[] = "ta.attendance_date = '$attendance_date'";
}

if (isset($_GET['attendance_status']) && !empty($_GET['attendance_status'])) {
    $attendance_status = mysqli_real_escape_string($conn, $_GET['attendance_status']);
    $where_conditions[] = "ta.attendance_status = '$attendance_status'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$attendance_query = "
    SELECT ta.*, u.username, u.email
    FROM teacher_attendance ta
    JOIN users u ON ta.teacher_id = u.id
    $where_clause
    ORDER BY ta.attendance_date DESC, u.username
    LIMIT 100
";

$attendance_result = mysqli_query($conn, $attendance_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Teacher Attendance | Academy Management System</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Edit Teacher Attendance</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-edit text-blue-500 mr-1"></i>
                        Modify and update teacher attendance records
                    </p>
                </div>
            </div>

            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    Teacher attendance updated successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    Teacher attendance record deleted successfully!
                </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="form-container mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Records</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teacher Name</label>
                        <input type="text"
                            name="teacher_name"
                            class="search-box w-full"
                            placeholder="Search by username"
                            value="<?= htmlspecialchars($_GET['teacher_name'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date"
                            name="attendance_date"
                            class="search-box w-full"
                            value="<?= htmlspecialchars($_GET['attendance_date'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="attendance_status" class="search-box w-full">
                            <option value="">All Status</option>
                            <option value="present" <?= (isset($_GET['attendance_status']) && $_GET['attendance_status'] == 'present') ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= (isset($_GET['attendance_status']) && $_GET['attendance_status'] == 'absent') ? 'selected' : '' ?>>Absent</option>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex justify-end gap-2">
                        <a href="edit_teacher_attendance.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium">
                            Clear Filters
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Attendance Records Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Teacher Attendance Records</h3>
                </div>

                <?php if (mysqli_num_rows($attendance_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Remarks</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($record = mysqli_fetch_assoc($attendance_result)):
                                    $badge_class = $record['attendance_status'] == 'present' ? 'badge-present' : 'badge-absent';
                                ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">#<?= $record['id'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($record['username']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($record['email']) ?></div>
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
                                                <button onclick="openEditModal(<?= $record['id'] ?>, '<?= htmlspecialchars($record['username']) ?>', '<?= $record['attendance_status'] ?>', '<?= htmlspecialchars($record['remarks']) ?>')"
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="edit_teacher_attendance.php?delete_id=<?= $record['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to delete this teacher attendance record?')"
                                                    class="text-red-600 hover:text-red-800 text-sm">
                                                    <i class="fas fa-trash"></i> Delete
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
                        <i class="fas fa-chalkboard-teacher text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Teacher Attendance Records Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No teacher attendance records match your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Teacher Attendance</h3>
                <form id="editForm" method="POST">
                    <input type="hidden" name="attendance_id" id="modalAttendanceId">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teacher</label>
                        <input type="text" id="modalTeacherName" class="search-box w-full" disabled>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="attendance_status" id="modalAttendanceStatus" class="search-box w-full" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                        <textarea name="remarks" id="modalRemarks" class="search-box w-full" rows="3" placeholder="Optional remarks"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="update_attendance" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-save mr-1"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(id, teacherName, status, remarks) {
            document.getElementById('modalAttendanceId').value = id;
            document.getElementById('modalTeacherName').value = teacherName;
            document.getElementById('modalAttendanceStatus').value = status;
            document.getElementById('modalRemarks').value = remarks;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>

</body>

</html>
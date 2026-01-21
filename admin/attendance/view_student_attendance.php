<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch attendance records with filters
$where_conditions = ["sa.status='active'"];

if (isset($_GET['student_name']) && !empty($_GET['student_name'])) {
    $student_name = mysqli_real_escape_string($conn, $_GET['student_name']);
    $where_conditions[] = "s.name LIKE '%$student_name%'";
}

if (isset($_GET['attendance_date']) && !empty($_GET['attendance_date'])) {
    $attendance_date = mysqli_real_escape_string($conn, $_GET['attendance_date']);
    $where_conditions[] = "sa.attendance_date = '$attendance_date'";
}

if (isset($_GET['batch_id']) && !empty($_GET['batch_id'])) {
    $batch_id = intval($_GET['batch_id']);
    $where_conditions[] = "sa.batch_id = $batch_id";
}

if (isset($_GET['attendance_status']) && !empty($_GET['attendance_status'])) {
    $attendance_status = mysqli_real_escape_string($conn, $_GET['attendance_status']);
    $where_conditions[] = "sa.attendance_status = '$attendance_status'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$attendance_query = "
    SELECT sa.*, s.name as student_name, s.student_code, sk.skill_name, se.session_name, b.batch_name
    FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    JOIN skills sk ON sa.skill_id = sk.id
    JOIN sessions se ON sa.session_id = se.id
    JOIN batches b ON sa.batch_id = b.id
    $where_clause
    ORDER BY sa.attendance_date DESC, s.name
    LIMIT 200
";

$attendance_result = mysqli_query($conn, $attendance_query);

// Calculate attendance summary
$summary_query = "
    SELECT 
        sa.attendance_status,
        COUNT(*) as count
    FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    $where_clause
    GROUP BY sa.attendance_status
";

$summary_result = mysqli_query($conn, $summary_query);
$attendance_summary = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'total' => 0
];

while ($row = mysqli_fetch_assoc($summary_result)) {
    $attendance_summary[$row['attendance_status']] = $row['count'];
    $attendance_summary['total'] += $row['count'];
}

// Fetch batches for filter
$batches_query = "SELECT * FROM batches WHERE status='active' ORDER BY batch_name";
$batches_result = mysqli_query($conn, $batches_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Student Attendance | Academy Management System</title>
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

        .badge-late {
            background: #fef3c7;
            color: #92400e;
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

        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e5e7eb;
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
                    <h1 class="text-2xl font-bold text-gray-800">View Student Attendance</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-eye text-blue-500 mr-1"></i>
                        View and analyze student attendance records
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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="summary-card">
                    <div class="text-center">
                        <p class="text-sm text-gray-500 mb-1">Total Records</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $attendance_summary['total'] ?></h3>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="text-center">
                        <p class="text-sm text-gray-500 mb-1">Present</p>
                        <h3 class="text-2xl font-bold text-green-600"><?= $attendance_summary['present'] ?></h3>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="text-center">
                        <p class="text-sm text-gray-500 mb-1">Absent</p>
                        <h3 class="text-2xl font-bold text-red-600"><?= $attendance_summary['absent'] ?></h3>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="text-center">
                        <p class="text-sm text-gray-500 mb-1">Late</p>
                        <h3 class="text-2xl font-bold text-yellow-600"><?= $attendance_summary['late'] ?></h3>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="form-container mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Records</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Student Name</label>
                        <input type="text"
                            name="student_name"
                            class="search-box w-full"
                            placeholder="Search by name"
                            value="<?= htmlspecialchars($_GET['student_name'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date"
                            name="attendance_date"
                            class="search-box w-full"
                            value="<?= htmlspecialchars($_GET['attendance_date'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch</label>
                        <select name="batch_id" class="search-box w-full">
                            <option value="">All Batches</option>
                            <?php
                            mysqli_data_seek($batches_result, 0);
                            while ($batch = mysqli_fetch_assoc($batches_result)):
                            ?>
                                <option value="<?= $batch['id'] ?>" <?= (isset($_GET['batch_id']) && $_GET['batch_id'] == $batch['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($batch['batch_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="attendance_status" class="search-box w-full">
                            <option value="">All Status</option>
                            <option value="present" <?= (isset($_GET['attendance_status']) && $_GET['attendance_status'] == 'present') ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= (isset($_GET['attendance_status']) && $_GET['attendance_status'] == 'absent') ? 'selected' : '' ?>>Absent</option>
                            <option value="late" <?= (isset($_GET['attendance_status']) && $_GET['attendance_status'] == 'late') ? 'selected' : '' ?>>Late</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-2">
                        <a href="view_student_attendance.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium">
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
                <div class="px-4 py-3 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-medium text-gray-800">Attendance Records</h3>
                    <span class="text-sm text-gray-500">
                        Showing <?= mysqli_num_rows($attendance_result) ?> records
                    </span>
                </div>

                <?php if (mysqli_num_rows($attendance_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch & Skill</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Remarks</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Marked By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($record = mysqli_fetch_assoc($attendance_result)):
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

                                    // Get marked by user name
                                    $marked_by_query = "SELECT username FROM users WHERE id = {$record['marked_by']}";
                                    $marked_by_result = mysqli_query($conn, $marked_by_query);
                                    $marked_by = mysqli_fetch_assoc($marked_by_result);
                                ?>
                                    <tr class="attendance-row">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">#<?= $record['id'] ?></div>
                                        </td>
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
                                            <div class="text-sm text-gray-600 max-w-xs">
                                                <?= htmlspecialchars($record['remarks'] ?: 'No remarks') ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600">
                                                <?= $marked_by['username'] ?? 'Admin' ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-clipboard-list text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Attendance Records Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No attendance records match your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.attendance-row');

            rows.forEach(row => {
                const name = row.querySelector('.student-name').textContent.toLowerCase();
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
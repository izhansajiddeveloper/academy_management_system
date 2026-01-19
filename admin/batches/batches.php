<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch all active batches with skill & session names
$batches = mysqli_query($conn, "
    SELECT b.*, s.skill_name, se.session_name 
    FROM batches b
    JOIN skills s ON b.skill_id = s.id
    JOIN sessions se ON b.session_id = se.id
    WHERE b.status='active'
    ORDER BY b.id DESC
");

$total_batches = mysqli_num_rows($batches);

// Handle mark as completed (soft delete)
if (isset($_GET['complete_id'])) {
    $id = intval($_GET['complete_id']);
    mysqli_query($conn, "UPDATE batches SET status='completed' WHERE id=$id");
    header("Location: batches.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Batches Management | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            background: #111827;
            /* Dark sidebar */
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
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #e5e7eb;
            color: #4b5563;
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
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Academic</p>
                    <a href="../sessions/sessions.php" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Sessions
                    </a>
                    <a href="batches.php" class="sidebar-link active">
                        <i class="fas fa-layer-group"></i> Batches
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-user-check"></i> Enrollments
                    </a>
                    <a href="../fees/fee_collection.php" class="sidebar-link">
                        <i class="fas fa-money-bill-wave"></i> Fees
                    </a>
                </div>
            </nav>

            <div class="p-3 border-t border-gray-700 mt-auto">
                <a href="add_batch.php" class="w-full flex items-center justify-center gap-2 text-sm p-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    <span>Add Batch</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Batches Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-layer-group text-blue-500 mr-1"></i>
                        Manage all training batches
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search batches..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="add_batch.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Batch
                    </a>
                    <a href="completed_batches.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-history mr-1"></i> Completed
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Active Batches</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_batches; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Actions</p>
                    <div class="mt-2">
                        <a href="add_batch.php" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i> Add Batch
                        </a>
                    </div>
                </div>
            </div>

            <!-- Batches Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Active Batches (<?php echo $total_batches; ?>)</h3>
                </div>

                <?php if ($total_batches > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Time</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Max Students</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($batches)): ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['batch_name']); ?></div>
                                            <span class="status-badge status-active">Active</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['skill_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['session_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700">
                                                <?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo $row['max_students']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="edit_batch.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <a href="assign_teacher.php?batch_id=<?= $row['id'] ?>"
                                                    class="action-btn bg-purple-50 text-purple-700 hover:bg-purple-100"
                                                    title="Assign Teacher">
                                                    <i class="fas fa-user-tie text-xs"></i>
                                                </a>
                                                <a href="batches.php?complete_id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Mark batch as completed?')"
                                                    class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                    title="Complete">
                                                    <i class="fas fa-check text-xs"></i>
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
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Active Batches</h3>
                        <p class="text-gray-500 text-sm mb-4">Create your first training batch</p>
                        <a href="add_batch.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Add First Batch
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
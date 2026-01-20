<?php
require_once __DIR__ . '/../../config/db.php';

// Soft delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "UPDATE fee_structures SET status='inactive' WHERE id=$id");
    header("Location: fee_structures.php");
    exit;
}

// Count statistics
$total_query = "SELECT COUNT(*) as count FROM fee_structures WHERE status='active'";
$total_result = mysqli_query($conn, $total_query);
$total_structures = mysqli_fetch_assoc($total_result)['count'];

// Calculate average fee
$avg_query = "SELECT AVG(total_fee) as avg_fee FROM fee_structures WHERE status='active'";
$avg_result = mysqli_query($conn, $avg_query);
$avg_fee = mysqli_fetch_assoc($avg_result)['avg_fee'];
$avg_fee_formatted = $avg_fee ? number_format($avg_fee, 2) : '0.00';

// Calculate total revenue potential
$revenue_query = "SELECT SUM(total_fee) as total_revenue FROM fee_structures WHERE status='active'";
$revenue_result = mysqli_query($conn, $revenue_query);
$total_revenue = mysqli_fetch_assoc($revenue_result)['total_revenue'];
$total_revenue_formatted = $total_revenue ? number_format($total_revenue, 2) : '0.00';

// Fetch active fee structures with details
$fee_structures = mysqli_query($conn, "
    SELECT fs.*, sk.skill_name, sk.level as skill_level, se.session_name
    FROM fee_structures fs
    JOIN skills sk ON fs.skill_id = sk.id
    JOIN sessions se ON fs.session_id = se.id
    WHERE fs.status='active'
    ORDER BY fs.total_fee DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Structures | Academy Management System</title>
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

        .level-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .level-beginner {
            background: #d1fae5;
            color: #065f46;
        }

        .level-intermediate {
            background: #dbeafe;
            color: #1e40af;
        }

        .level-advanced {
            background: #ede9fe;
            color: #5b21b6;
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
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Financial</p>
                    <a href="fee_structures.php" class="sidebar-link active">
                        <i class="fas fa-calculator"></i> Fee Structures
                    </a>
                    <a href="fee_collection.php" class="sidebar-link">
                        <i class="fas fa-cash-register"></i> Fee Collection
                    </a>
                    <a href="fee_history.php" class="sidebar-link">
                        <i class="fas fa-chart-pie"></i> Fee Reports
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-user-check"></i> Enrollments
                    </a>
                    <a href="../expenses/expenses.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Expenses
                    </a>
                    <a href="../reports/student_report.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </div>
            </nav>

            <div class="p-3 border-t border-gray-700 mt-auto">
                <a href="add_fee_structure.php" class="w-full flex items-center justify-center gap-2 text-sm p-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    <span>Add Fee Structure</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Fee Structures Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-calculator text-blue-500 mr-1"></i>
                        Manage all fee structures and pricing
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search fee structures..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="add_fee_structure.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Fee Structure
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Structures</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_structures; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Average Fee</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $avg_fee_formatted; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Revenue Potential</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $total_revenue_formatted; ?></h3>
                </div>
            </div>

            <!-- Fee Structures Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">All Fee Structures (<?php echo $total_structures; ?>)</h3>
                </div>

                <?php if ($total_structures > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">ID</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill Name</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Level</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Fee</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($fee_structures)):
                                    $level_class = '';
                                    switch ($row['skill_level']) {
                                        case 'Beginner':
                                            $level_class = 'level-beginner';
                                            break;
                                        case 'Intermediate':
                                            $level_class = 'level-intermediate';
                                            break;
                                        case 'Advanced':
                                            $level_class = 'level-advanced';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">#<?= $row['id'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['skill_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?= $level_class ?> level-badge">
                                                <?= $row['skill_level'] ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($row['session_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-lg font-bold text-green-600">
                                                Rs<?= number_format($row['total_fee'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="add_fee_structure.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <a href="fee_structures.php?delete_id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Delete this fee structure for <?= htmlspecialchars($row['skill_name']) ?>?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
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
                        <i class="fas fa-calculator text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Fee Structures Found</h3>
                        <p class="text-gray-500 text-sm mb-4">Add your first fee structure to get started</p>
                        <a href="add_fee_structure.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Add First Fee Structure
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
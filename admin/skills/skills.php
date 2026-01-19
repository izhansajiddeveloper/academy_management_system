<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Soft delete a skill
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "UPDATE skills SET status='inactive', updated_at=NOW() WHERE id=$id");
    $_SESSION['success_message'] = "Skill deleted successfully!";
    header("Location: skills.php");
    exit;
}

// Count statistics
$total_query = "SELECT COUNT(*) as count FROM skills WHERE status='active'";
$beginner_query = "SELECT COUNT(*) as count FROM skills WHERE status='active' AND level='Beginner'";
$intermediate_query = "SELECT COUNT(*) as count FROM skills WHERE status='active' AND level='Intermediate'";
$advanced_query = "SELECT COUNT(*) as count FROM skills WHERE status='active' AND level='Advanced'";

$total_result = mysqli_query($conn, $total_query);
$beginner_result = mysqli_query($conn, $beginner_query);
$intermediate_result = mysqli_query($conn, $intermediate_query);
$advanced_result = mysqli_query($conn, $advanced_query);

$total_skills = mysqli_fetch_assoc($total_result)['count'];
$beginner_count = mysqli_fetch_assoc($beginner_result)['count'];
$intermediate_count = mysqli_fetch_assoc($intermediate_result)['count'];
$advanced_count = mysqli_fetch_assoc($advanced_result)['count'];

// Fetch active skills with syllabus count
$result = mysqli_query($conn, "
    SELECT s.*, 
           (SELECT COUNT(*) FROM skill_syllabus ss WHERE ss.skill_id = s.id) AS syllabus_count,
           (SELECT COUNT(*) FROM batches WHERE skill_id = s.id AND status='active') AS active_batches
    FROM skills s
    WHERE s.status='active'
    ORDER BY s.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Skills Management | Academy Management System</title>
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
                    <a href="skills.php" class="sidebar-link active">
                        <i class="fas fa-book-open"></i> Skills
                    </a>
                    <a href="syllabus.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Syllabus
                    </a>
                    <a href="progress.php" class="sidebar-link">
                        <i class="fas fa-chart-line"></i> Progress
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Academic</p>
                    <a href="../sessions/sessions.php" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Sessions
                    </a>
                    <a href="../batches/batches.php" class="sidebar-link">
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
                    <a href="../reports/student_report.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </div>
            </nav>

            <div class="p-3 border-t border-gray-700 mt-auto">
                <a href="add_skill.php" class="w-full flex items-center justify-center gap-2 text-sm p-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    <span>Add Skill</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Skills Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-book text-blue-500 mr-1"></i>
                        Manage all courses and skills
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search skills..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="add_skill.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Skill
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-4 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Skills</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_skills; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Beginner</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $beginner_count; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Intermediate</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $intermediate_count; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Advanced</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $advanced_count; ?></h3>
                </div>
            </div>

            <!-- Skills Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">All Skills (<?php echo $total_skills; ?>)</h3>
                </div>

                <?php if ($total_skills > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill Name</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Duration</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Level</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Syllabus</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batches</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($skill = mysqli_fetch_assoc($result)) : ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($skill['skill_name']); ?></div>
                                            <?php if (!empty($skill['description'])): ?>
                                                <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs"><?php echo htmlspecialchars($skill['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-sm text-gray-700"><?php echo $skill['duration_months']; ?> months</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $level_badge_class = '';
                                            switch ($skill['level']) {
                                                case 'Beginner':
                                                    $level_badge_class = 'level-beginner';
                                                    break;
                                                case 'Intermediate':
                                                    $level_badge_class = 'level-intermediate';
                                                    break;
                                                case 'Advanced':
                                                    $level_badge_class = 'level-advanced';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $level_badge_class; ?> level-badge">
                                                <?php echo $skill['level']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($skill['syllabus_count'] > 0): ?>
                                                <span class="text-green-600 text-sm font-medium">
                                                    <i class="fas fa-check-circle mr-1"></i> <?php echo $skill['syllabus_count']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm">
                                                    <i class="fas fa-times-circle mr-1"></i> None
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-blue-600 text-sm font-medium">
                                                <i class="fas fa-users mr-1"></i> <?php echo $skill['active_batches']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="edit_skill.php?id=<?= $skill['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <a href="skills.php?delete=<?= $skill['id'] ?>"
                                                    onclick="return confirm('Delete <?php echo htmlspecialchars($skill['skill_name']); ?>?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </a>
                                                <a href="syllabus.php?skill_id=<?= $skill['id'] ?>"
                                                    class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                    title="View Syllabus">
                                                    <i class="fas fa-eye text-xs"></i>
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
                        <i class="fas fa-book text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Skills Found</h3>
                        <p class="text-gray-500 text-sm mb-4">Add your first skill to get started</p>
                        <a href="add_skill.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Add First Skill
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
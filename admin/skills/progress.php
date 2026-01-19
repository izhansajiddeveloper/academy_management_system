<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle Delete Progress
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM skill_progress WHERE id=$id");
    $_SESSION['success_message'] = "Progress record deleted successfully!";
    header("Location: progress.php");
    exit;
}

// Handle Add Progress
if (isset($_POST['add_progress'])) {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $session_id = (int)$_POST['session_id'];
    $topics_completed = (int)$_POST['topics_completed'];
    $total_topics = (int)$_POST['total_topics'];
    $completion_percent = $total_topics ? round(($topics_completed / $total_topics) * 100, 2) : 0;
    $last_updated = date('Y-m-d H:i:s');

    if ($topics_completed > $total_topics) {
        $_SESSION['error_message'] = "Topics completed cannot be greater than total topics.";
    } else {
        mysqli_query($conn, "
            INSERT INTO skill_progress 
            (enrollment_id, session_id, topics_completed, total_topics, completion_percent, last_updated)
            VALUES ($enrollment_id, $session_id, $topics_completed, $total_topics, $completion_percent, '$last_updated')
        ");
        $_SESSION['success_message'] = "Progress added successfully!";
        header("Location: progress.php");
        exit;
    }
}

// Handle Edit Progress
if (isset($_POST['edit_progress'])) {
    $id = (int)$_POST['progress_id'];
    $topics_completed = (int)$_POST['topics_completed'];
    $total_topics = (int)$_POST['total_topics'];
    $completion_percent = $total_topics ? round(($topics_completed / $total_topics) * 100, 2) : 0;
    $last_updated = date('Y-m-d H:i:s');

    if ($topics_completed > $total_topics) {
        $_SESSION['error_message'] = "Topics completed cannot be greater than total topics.";
    } else {
        mysqli_query($conn, "
            UPDATE skill_progress
            SET topics_completed=$topics_completed,
                total_topics=$total_topics,
                completion_percent=$completion_percent,
                last_updated='$last_updated'
            WHERE id=$id
        ");
        $_SESSION['success_message'] = "Progress updated successfully!";
        header("Location: progress.php");
        exit;
    }
}

// Get messages from session
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch progress statistics
$stats_query = "SELECT 
                COUNT(*) as total_progress,
                AVG(completion_percent) as avg_completion,
                SUM(topics_completed) as total_completed,
                SUM(total_topics) as total_topics
                FROM skill_progress";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Fetch progress list with details
$result = mysqli_query($conn, "
    SELECT sp.*, 
           s.skill_name, 
           s.level,
           se.student_id, 
           st.name AS student_name, 
           st.student_code,
           se.session_id
    FROM skill_progress sp
    JOIN student_enrollments se ON sp.enrollment_id = se.id
    JOIN skills s ON se.skill_id = s.id
    JOIN students st ON se.student_id = st.id
    ORDER BY sp.last_updated DESC
");

// Fetch enrollments for add form
$enrollments = mysqli_query($conn, "
    SELECT se.id, 
           st.name AS student_name, 
           st.student_code,
           s.skill_name,
           s.duration_months
    FROM student_enrollments se
    JOIN students st ON se.student_id = st.id
    JOIN skills s ON se.skill_id = s.id
    WHERE se.status = 'active'
    ORDER BY st.name, s.skill_name
");

$progress_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Skill Progress | Academy Management System</title>
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

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
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
                    <a href="skills.php" class="sidebar-link">
                        <i class="fas fa-book-open"></i> Skills
                    </a>
                    <a href="syllabus.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Syllabus
                    </a>
                    <a href="progress.php" class="sidebar-link active">
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
                </div>
            </nav>

            <div class="p-3 border-t border-gray-700 mt-auto">
                <button onclick="document.getElementById('addProgressModal').style.display = 'flex'"
                    class="w-full flex items-center justify-center gap-2 text-sm p-2 rounded bg-green-600 text-white hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    <span>Add Progress</span>
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Skill Progress Tracking</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-chart-line text-green-500 mr-1"></i>
                        Monitor student learning progress
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search progress..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <button onclick="document.getElementById('addProgressModal').style.display = 'flex'"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Progress
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded flex items-center gap-2 text-sm">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <span class="text-green-700"><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded flex items-center gap-2 text-sm">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                    <span class="text-red-700"><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="grid grid-cols-4 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Records</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $stats['total_progress']; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Avg. Completion</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo round($stats['avg_completion'], 1); ?>%</h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Topics Done</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $stats['total_completed'] ?: 0; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Completion Rate</p>
                    <h3 class="text-xl font-bold text-gray-800">
                        <?php echo $stats['total_topics'] > 0 ? round(($stats['total_completed'] / $stats['total_topics']) * 100, 1) : 0; ?>%
                    </h3>
                </div>
            </div>

            <!-- Progress Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Progress Records (<?php echo $progress_count; ?>)</h3>
                </div>

                <?php if ($progress_count > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Progress</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Topics</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Last Updated</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $row['student_code']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['skill_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $row['level']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <div class="progress-bar flex-1">
                                                    <?php
                                                    $percent = $row['completion_percent'];
                                                    $color = $percent >= 70 ? '#10b981' : ($percent >= 40 ? '#f59e0b' : '#ef4444');
                                                    ?>
                                                    <div class="progress-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $color; ?>"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700"><?php echo $percent; ?>%</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700">
                                                <?php echo $row['topics_completed']; ?> / <?php echo $row['total_topics']; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo date('M d, Y', strtotime($row['last_updated'])); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <button onclick="editProgress(<?php echo $row['id']; ?>, <?php echo $row['topics_completed']; ?>, <?php echo $row['total_topics']; ?>, <?php echo $row['session_id']; ?>)"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>
                                                <a href="progress.php?delete=<?php echo $row['id']; ?>"
                                                    onclick="return confirm('Delete this progress record?');"
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
                        <i class="fas fa-chart-line text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Progress Records</h3>
                        <p class="text-gray-500 text-sm mb-4">Start tracking student progress</p>
                        <button onclick="document.getElementById('addProgressModal').style.display = 'flex'"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Add Progress Record
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Progress Modal -->
    <div class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden" id="addProgressModal">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Add Progress Record</h3>
                <button onclick="document.getElementById('addProgressModal').style.display = 'none'" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student & Course</label>
                    <select name="enrollment_id" required class="w-full p-2 border border-gray-300 rounded text-sm">
                        <option value="">Select Student & Course</option>
                        <?php mysqli_data_seek($enrollments, 0);
                        while ($row = mysqli_fetch_assoc($enrollments)): ?>
                            <option value="<?= $row['id'] ?>">
                                <?= htmlspecialchars($row['student_name']) ?> - <?= htmlspecialchars($row['skill_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Session ID</label>
                        <input type="number" name="session_id" required min="1" class="w-full p-2 border border-gray-300 rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Topics</label>
                        <input type="number" name="total_topics" required min="1" class="w-full p-2 border border-gray-300 rounded text-sm" id="totalTopics">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topics Completed</label>
                    <input type="number" name="topics_completed" required min="0" class="w-full p-2 border border-gray-300 rounded text-sm" id="topicsCompleted" oninput="calculatePercent()">
                    <div class="mt-2">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressBar" style="width: 0%; background: #10b981"></div>
                        </div>
                        <div class="text-sm text-gray-600 mt-1 text-center" id="percentText">0%</div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('addProgressModal').style.display = 'none'"
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded text-sm hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="add_progress"
                        class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        Add Progress
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Progress Modal -->
    <div class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden" id="editProgressModal">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Edit Progress Record</h3>
                <button onclick="document.getElementById('editProgressModal').style.display = 'none'" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="post" class="space-y-4">
                <input type="hidden" name="progress_id" id="editProgressId">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Session ID</label>
                        <input type="number" name="session_id" required min="1" class="w-full p-2 border border-gray-300 rounded text-sm" id="editSessionId">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Topics</label>
                        <input type="number" name="total_topics" required min="1" class="w-full p-2 border border-gray-300 rounded text-sm" id="editTotalTopics" oninput="calculateEditPercent()">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topics Completed</label>
                    <input type="number" name="topics_completed" required min="0" class="w-full p-2 border border-gray-300 rounded text-sm" id="editTopicsCompleted" oninput="calculateEditPercent()">
                    <div class="mt-2">
                        <div class="progress-bar">
                            <div class="progress-fill" id="editProgressBar" style="width: 0%; background: #10b981"></div>
                        </div>
                        <div class="text-sm text-gray-600 mt-1 text-center" id="editPercentText">0%</div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('editProgressModal').style.display = 'none'"
                        class="px-4 py-2 text-gray-700 border border-gray-300 rounded text-sm hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" name="edit_progress"
                        class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Calculate percentage for add form
        function calculatePercent() {
            const completed = parseInt(document.getElementById('topicsCompleted').value) || 0;
            const total = parseInt(document.getElementById('totalTopics').value) || 0;
            const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('percentText').textContent = percent + '%';

            // Change color based on percentage
            const progressBar = document.getElementById('progressBar');
            if (percent >= 70) {
                progressBar.style.background = '#10b981';
            } else if (percent >= 40) {
                progressBar.style.background = '#f59e0b';
            } else {
                progressBar.style.background = '#ef4444';
            }
        }

        // Calculate percentage for edit form
        function calculateEditPercent() {
            const completed = parseInt(document.getElementById('editTopicsCompleted').value) || 0;
            const total = parseInt(document.getElementById('editTotalTopics').value) || 0;
            const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

            document.getElementById('editProgressBar').style.width = percent + '%';
            document.getElementById('editPercentText').textContent = percent + '%';

            // Change color based on percentage
            const progressBar = document.getElementById('editProgressBar');
            if (percent >= 70) {
                progressBar.style.background = '#10b981';
            } else if (percent >= 40) {
                progressBar.style.background = '#f59e0b';
            } else {
                progressBar.style.background = '#ef4444';
            }
        }

        // Edit progress function
        function editProgress(id, completed, total, sessionId) {
            document.getElementById('editProgressId').value = id;
            document.getElementById('editTopicsCompleted').value = completed;
            document.getElementById('editTotalTopics').value = total;
            document.getElementById('editSessionId').value = sessionId;

            calculateEditPercent();
            document.getElementById('editProgressModal').style.display = 'flex';
        }

        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Search functionality
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
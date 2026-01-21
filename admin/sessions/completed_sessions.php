<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch completed sessions
$sessions = mysqli_query($conn, "SELECT * FROM sessions WHERE status='completed' ORDER BY id DESC");
$total_completed = mysqli_num_rows($sessions);

// Optionally, allow marking back as active
if (isset($_GET['activate_id'])) {
    $id = intval($_GET['activate_id']);
    mysqli_query($conn, "UPDATE sessions SET status='active' WHERE id=$id");
    header("Location: completed_sessions.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Completed Sessions | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
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
         <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Completed Sessions</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-history text-gray-500 mr-1"></i>
                        View and manage completed sessions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search completed sessions..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="sessions.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Active Sessions
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Completed Sessions</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_completed; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Actions</p>
                    <div class="mt-2">
                        <a href="sessions.php" class="text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye mr-1"></i> View Active
                        </a>
                    </div>
                </div>
            </div>

            <!-- Completed Sessions Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Completed Sessions (<?php echo $total_completed; ?>)</h3>
                </div>

                <?php if ($total_completed > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Start Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">End Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($sessions)): ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['session_name']); ?></div>
                                            <?php if (!empty($row['description'])): ?>
                                                <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs"><?php echo htmlspecialchars($row['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700">
                                                <?php if (!empty($row['start_date'])): ?>
                                                    <?php echo date('M d, Y', strtotime($row['start_date'])); ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Not set</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700">
                                                <?php if (!empty($row['end_date'])): ?>
                                                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Not set</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="status-badge status-completed">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="completed_sessions.php?activate_id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Reactivate this session?')"
                                                    class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                    title="Reactivate">
                                                    <i class="fas fa-redo text-xs"></i>
                                                </a>
                                                <a href="edit_session.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
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
                        <i class="fas fa-history text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Completed Sessions</h3>
                        <p class="text-gray-500 text-sm mb-4">No sessions have been marked as completed yet</p>
                        <a href="sessions.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-arrow-left"></i> View Active Sessions
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
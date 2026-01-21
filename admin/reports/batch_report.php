<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Batch summary
$total_batches = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM batches
"))['total'];

$active_batches = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM batches WHERE status='active'
"))['total'];

$inactive_batches = $total_batches - $active_batches;

// Batch details with more information
$batch_data = mysqli_query($conn, "
    SELECT 
        b.id,
        b.batch_name,
        b.start_time,
        b.end_time,
        b.status,
        s.skill_name,
        COUNT(DISTINCT se.student_id) AS students,
        GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') AS teachers
    FROM batches b
    LEFT JOIN student_enrollments se 
        ON se.batch_id = b.id AND se.status='active'
    LEFT JOIN batch_teachers bt 
        ON bt.batch_id = b.id
    LEFT JOIN teachers t 
        ON t.id = bt.teacher_id AND t.status='active'
    LEFT JOIN skills s 
        ON s.id = b.skill_id
    GROUP BY b.id
    ORDER BY b.start_time ASC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Batch Report | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
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

        .progress-bar {
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-layer-group mr-2"></i> Batch Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Overview of all batches in the academy</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" placeholder="Search batches..." class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box" id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Batches</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= $total_batches ?></h3>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-layer-group text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Active Batches</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= $active_batches ?></h3>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-green-400" style="width: <?= $total_batches > 0 ? ($active_batches / $total_batches) * 100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Inactive Batches</p>
                            <h3 class="text-2xl font-bold text-gray-600"><?= $inactive_batches ?></h3>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-times-circle text-gray-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Batch Distribution Chart -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Batch Distribution</h3>
                <div class="h-64">
                    <canvas id="batchChart"></canvas>
                </div>
            </div>

            <!-- Batch Details Table -->
            <div class="card">
                <h3 class="font-semibold text-gray-800 mb-4">Batch Details</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch Name</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Duration</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teachers</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Students</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = mysqli_fetch_assoc($batch_data)): ?>
                                <tr class="batch-row">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['batch_name']) ?></div>
                                        <div class="text-xs text-gray-500">ID: #<?= $row['id'] ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $row['skill_name'] ?: 'N/A' ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-700">
                                            <?= $row['start_time'] ? date('M d, Y', strtotime($row['start_time'])) : 'N/A' ?>
                                            <?php if ($row['end_time']): ?>
                                                <br><span class="text-xs text-gray-500">to <?= date('M d, Y', strtotime($row['end_time'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm text-gray-700 max-w-xs truncate"><?= $row['teachers'] ?: '—' ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-blue-600"><?= $row['students'] ?></span>
                                        <span class="text-sm text-gray-500"> students</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary -->
            <div class="mt-6 p-4 bg-white rounded-lg border">
                <div class="text-sm text-gray-600">
                    <p><strong>Report Summary:</strong> Total <?= $total_batches ?> batches, <?= $active_batches ?> active, <?= $inactive_batches ?> inactive.</p>
                    <p class="mt-1">Report generated on <?= date('F d, Y h:i A') ?></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.batch-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Initialize chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('batchChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Active Batches', 'Inactive Batches'],
                    datasets: [{
                        data: [<?= $active_batches ?>, <?= $inactive_batches ?>],
                        backgroundColor: ['#34d399', '#9ca3af'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>
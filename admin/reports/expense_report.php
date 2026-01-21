<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Total expenses
$total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM expenses 
    WHERE status='active'
    AND MONTH(created_at)='$month'
    AND YEAR(created_at)='$year'
"))['total'];

// Expenses by category
$cats = mysqli_query($conn, "
    SELECT 
        ec.category_name,
        COUNT(e.id) as count,
        SUM(e.amount) as total,
        ROUND((SUM(e.amount) / (SELECT SUM(amount) FROM expenses WHERE status='active' AND MONTH(created_at)='$month' AND YEAR(created_at)='$year')) * 100, 2) as percentage
    FROM expenses e
    JOIN expense_categories ec ON e.category_id=ec.id
    WHERE e.status='active'
    AND MONTH(e.created_at)='$month'
    AND YEAR(e.created_at)='$year'
    GROUP BY e.category_id
    ORDER BY total DESC
");

// Daily expenses
$daily_expenses = mysqli_query($conn, "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        SUM(amount) as total
    FROM expenses
    WHERE status='active'
    AND MONTH(created_at)='$month'
    AND YEAR(created_at)='$year'
    GROUP BY DATE(created_at)
    ORDER BY date
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expense Report | Academy Management System</title>
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

        .search-box {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
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
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Reports</p>
                    <a href="batch_report.php" class="sidebar-link">
                        <i class="fas fa-layer-group"></i> Batch Report
                    </a>
                    <a href="student_report.php" class="sidebar-link">
                        <i class="fas fa-user-graduate"></i> Student Report
                    </a>
                    <a href="teacher_report.php" class="sidebar-link">
                        <i class="fas fa-chalkboard-teacher"></i> Teacher Report
                    </a>
                    <a href="attendance_report.php" class="sidebar-link">
                        <i class="fas fa-calendar-check"></i> Attendance Report
                    </a>
                    <a href="fee_report.php" class="sidebar-link">
                        <i class="fas fa-cash-register"></i> Fee Report
                    </a>
                    <a href="donation_report.php" class="sidebar-link">
                        <i class="fas fa-hand-holding-usd"></i> Donation Report
                    </a>
                    <a href="expense_report.php" class="sidebar-link active">
                        <i class="fas fa-wallet"></i> Expense Report
                    </a>
                    <a href="profit_report.php" class="sidebar-link">
                        <i class="fas fa-chart-line"></i> Profit Report
                    </a>
                </div>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-wallet mr-2"></i> Expense Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Expense overview for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Month/Year Filter -->
                    <div class="flex gap-2">
                        <select name="month" class="search-box" onchange="updateMonthYear()" id="monthSelect">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <input type="number" name="year" value="<?= $year ?>" min="2020" max="2030"
                            class="search-box w-24" onchange="updateMonthYear()" id="yearInput">
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Expenses</p>
                            <h3 class="text-2xl font-bold text-red-600"><?= number_format($total, 2) ?> PKR</h3>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg">
                            <i class="fas fa-money-bill-wave text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Average Daily Expense</p>
                            <?php
                            $daily_avg = 0;
                            if (mysqli_num_rows($daily_expenses) > 0) {
                                $total_days = 0;
                                $total_amount = 0;
                                mysqli_data_seek($daily_expenses, 0);
                                while ($day = mysqli_fetch_assoc($daily_expenses)) {
                                    $total_days++;
                                    $total_amount += $day['total'];
                                }
                                $daily_avg = $total_days > 0 ? $total_amount / $total_days : 0;
                                mysqli_data_seek($daily_expenses, 0);
                            }
                            ?>
                            <h3 class="text-2xl font-bold text-orange-600"><?= number_format($daily_avg, 2) ?> PKR</h3>
                        </div>
                        <div class="p-3 bg-orange-50 rounded-lg">
                            <i class="fas fa-calendar-day text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Number of Expenses</p>
                            <?php
                            $expense_count = 0;
                            while ($cat = mysqli_fetch_assoc($cats)) {
                                $expense_count += $cat['count'];
                            }
                            mysqli_data_seek($cats, 0);
                            ?>
                            <h3 class="text-2xl font-bold text-blue-600"><?= $expense_count ?></h3>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Expenses by Category -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Expenses by Category</h3>
                    <div class="h-64">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Daily Expenses -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Daily Expense Trend</h3>
                    <div class="h-64">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Category Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Category</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Number of Expenses</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Amount</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Distribution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($cat = mysqli_fetch_assoc($cats)): ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($cat['category_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $cat['count'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600"><?= number_format($cat['total'], 2) ?> PKR</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $cat['percentage'] ?>%</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="progress-bar">
                                            <div class="progress-fill bg-red-400" style="width: <?= $cat['percentage'] ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily Expenses Table -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Daily Expenses</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Day</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Number of Expenses</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($day = mysqli_fetch_assoc($daily_expenses)): ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= date('d M, Y', strtotime($day['date'])) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= date('l', strtotime($day['date'])) ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $day['count'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600"><?= number_format($day['total'], 2) ?> PKR</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary -->
            <div class="p-4 bg-white rounded-lg border">
                <div class="text-sm text-gray-600">
                    <p><strong>Report Summary:</strong> Total expenses of <?= number_format($total, 2) ?> PKR in <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?> with an average daily expense of <?= number_format($daily_avg, 2) ?> PKR.</p>
                    <p class="mt-1">Report generated on <?= date('F d, Y h:i A') ?></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateMonthYear() {
            const month = document.getElementById('monthSelect').value;
            const year = document.getElementById('yearInput').value;
            window.location.href = `?month=${month}&year=${year}`;
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Category Chart
            <?php
            $catLabels = [];
            $catData = [];
            $catColors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'];
            mysqli_data_seek($cats, 0);
            $colorIndex = 0;
            while ($cat = mysqli_fetch_assoc($cats)) {
                $catLabels[] = $cat['category_name'];
                $catData[] = $cat['total'];
                $colorIndex++;
            }
            mysqli_data_seek($cats, 0);
            ?>

            const catCtx = document.getElementById('categoryChart').getContext('2d');
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($catLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($catData) ?>,
                        backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#3b82f6'],
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

            // Daily Chart
            <?php
            $dailyLabels = [];
            $dailyData = [];
            mysqli_data_seek($daily_expenses, 0);
            while ($day = mysqli_fetch_assoc($daily_expenses)) {
                $dailyLabels[] = date('d', strtotime($day['date']));
                $dailyData[] = $day['total'];
            }
            mysqli_data_seek($daily_expenses, 0);
            ?>

            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($dailyLabels) ?>,
                    datasets: [{
                        label: 'Daily Expenses (PKR)',
                        data: <?= json_encode($dailyData) ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.3)',
                        borderColor: '#ef4444',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [5, 5]
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>
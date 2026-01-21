<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Calculate totals
$fees = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount_paid), 0) as t FROM fee_collections
    WHERE status='active'
    AND MONTH(payment_date)='$month'
    AND YEAR(payment_date)='$year'
"))['t'] ?? 0;

$donations = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount), 0) as t FROM donations
    WHERE status='active'
    AND MONTH(donation_date)='$month'
    AND YEAR(donation_date)='$year'
"))['t'] ?? 0;

$expenses = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount), 0) as t FROM expenses
    WHERE status='active'
    AND MONTH(created_at)='$month'
    AND YEAR(created_at)='$year'
"))['t'] ?? 0;

$profit = ($fees + $donations) - $expenses;
$revenue = $fees + $donations;

// Monthly trend
$monthly_trend = mysqli_query($conn, "
    SELECT 
        MONTH(payment_date) as month_num,
        DATE_FORMAT(payment_date, '%b') as month_name,
        COALESCE(SUM(amount_paid), 0) as fees,
        (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status='active' AND MONTH(donation_date)=month_num AND YEAR(donation_date)='$year') as donations,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status='active' AND MONTH(created_at)=month_num AND YEAR(created_at)='$year') as expenses
    FROM fee_collections
    WHERE status='active'
    AND YEAR(payment_date)='$year'
    GROUP BY MONTH(payment_date), DATE_FORMAT(payment_date, '%b')
    ORDER BY month_num
");

// Calculate percentages
$fee_percentage = $revenue > 0 ? ($fees / $revenue) * 100 : 0;
$donation_percentage = $revenue > 0 ? ($donations / $revenue) * 100 : 0;
$expense_percentage = $revenue > 0 ? ($expenses / $revenue) * 100 : 0;
$profit_margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profit Report | Academy Management System</title>
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
                        <i class="fas fa-chart-line mr-2"></i> Profit & Loss Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Financial overview for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></p>
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

            <!-- Profit/Loss Summary -->
            <div class="stats-card mb-6" style="border-left: 4px solid <?= $profit >= 0 ? '#10b981' : '#ef4444' ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Net <?= $profit >= 0 ? 'Profit' : 'Loss' ?> for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></p>
                        <h1 class="text-3xl font-bold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= number_format($profit, 2) ?> PKR
                        </h1>
                        <p class="text-sm text-gray-600 mt-2">
                            Revenue: <?= number_format($revenue, 2) ?> PKR |
                            Expenses: <?= number_format($expenses, 2) ?> PKR |
                            Margin: <?= number_format($profit_margin, 1) ?>%
                        </p>
                    </div>
                    <div class="p-4 <?= $profit >= 0 ? 'bg-green-50' : 'bg-red-50' ?> rounded-lg">
                        <i class="fas fa-<?= $profit >= 0 ? 'chart-line' : 'chart-bar' ?> <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?> text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Financial Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Revenue</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= number_format($revenue, 2) ?> PKR</h3>
                            <p class="text-xs text-gray-500 mt-1">Fees + Donations</p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-blue-400" style="width: 100%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Fees Collected</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= number_format($fees, 2) ?> PKR</h3>
                            <p class="text-xs text-gray-500 mt-1"><?= number_format($fee_percentage, 1) ?>% of revenue</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-cash-register text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-green-400" style="width: <?= $fee_percentage ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Donations Received</p>
                            <h3 class="text-2xl font-bold text-purple-600"><?= number_format($donations, 2) ?> PKR</h3>
                            <p class="text-xs text-gray-500 mt-1"><?= number_format($donation_percentage, 1) ?>% of revenue</p>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-lg">
                            <i class="fas fa-hand-holding-usd text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-purple-400" style="width: <?= $donation_percentage ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Expenses Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Expenses</p>
                            <h3 class="text-2xl font-bold text-red-600"><?= number_format($expenses, 2) ?> PKR</h3>
                            <p class="text-xs text-gray-500 mt-1"><?= number_format($expense_percentage, 1) ?>% of revenue</p>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg">
                            <i class="fas fa-wallet text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-red-400" style="width: <?= $expense_percentage ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Profit Margin</p>
                            <h3 class="text-2xl font-bold <?= $profit_margin >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= number_format($profit_margin, 1) ?>%
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">(Profit / Revenue) × 100</p>
                        </div>
                        <div class="p-3 <?= $profit_margin >= 0 ? 'bg-green-50' : 'bg-red-50' ?> rounded-lg">
                            <i class="fas fa-percentage <?= $profit_margin >= 0 ? 'text-green-600' : 'text-red-600' ?> text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill <?= $profit_margin >= 0 ? 'bg-green-400' : 'bg-red-400' ?>"
                            style="width: <?= min(abs($profit_margin), 100) ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Revenue vs Expenses -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Revenue vs Expenses</h3>
                    <div class="h-64">
                        <canvas id="revenueExpenseChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Monthly Trend (<?= $year ?>)</h3>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Financial Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Component</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Amount (PKR)</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                        <span class="font-medium text-gray-900">Fees Collected</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-green-600">+ <?= number_format($fees, 2) ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm text-gray-700"><?= number_format($fee_percentage, 1) ?>%</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Revenue
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
                                        <span class="font-medium text-gray-900">Donations Received</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-purple-600">+ <?= number_format($donations, 2) ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm text-gray-700"><?= number_format($donation_percentage, 1) ?>%</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Revenue
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                                        <span class="font-medium text-gray-900">Total Expenses</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-red-600">- <?= number_format($expenses, 2) ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm text-gray-700"><?= number_format($expense_percentage, 1) ?>%</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Expense
                                    </span>
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full <?= $profit >= 0 ? 'bg-green-500' : 'bg-red-500' ?> mr-2"></div>
                                        <span class="font-bold text-gray-900">Net <?= $profit >= 0 ? 'Profit' : 'Loss' ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold <?= $profit >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $profit >= 0 ? '+ ' : '- ' ?><?= number_format(abs($profit), 2) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm font-medium <?= $profit_margin >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= number_format($profit_margin, 1) ?>%
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $profit >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $profit >= 0 ? 'PROFIT' : 'LOSS' ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary -->
            <div class="p-4 bg-white rounded-lg border">
                <div class="text-sm text-gray-600">
                    <p><strong>Report Summary:</strong>
                        <?= $profit >= 0 ? 'Profit of ' : 'Loss of ' ?><?= number_format(abs($profit), 2) ?> PKR
                        with a <?= $profit_margin >= 0 ? 'profit' : 'loss' ?> margin of <?= number_format(abs($profit_margin), 1) ?>%
                        for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>.
                    </p>
                    <p class="mt-1">Revenue: <?= number_format($revenue, 2) ?> PKR | Expenses: <?= number_format($expenses, 2) ?> PKR</p>
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
            // Revenue vs Expenses Chart
            const revCtx = document.getElementById('revenueExpenseChart').getContext('2d');
            new Chart(revCtx, {
                type: 'bar',
                data: {
                    labels: ['Fees', 'Donations', 'Expenses'],
                    datasets: [{
                        label: 'Amount (PKR)',
                        data: [<?= $fees ?>, <?= $donations ?>, <?= -$expenses ?>],
                        backgroundColor: ['#22c55e', '#a855f7', '#ef4444'],
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

            // Monthly Trend Chart
            <?php
            $monthLabels = [];
            $profitData = [];
            while ($trend = mysqli_fetch_assoc($monthly_trend)) {
                $monthLabels[] = $trend['month_name'];
                $monthProfit = ($trend['fees'] + $trend['donations']) - $trend['expenses'];
                $profitData[] = $monthProfit;
            }
            ?>

            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($monthLabels) ?>,
                    datasets: [{
                        label: 'Monthly Profit/Loss (PKR)',
                        data: <?= json_encode($profitData) ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderColor: '#22c55e',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
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
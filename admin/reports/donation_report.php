<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Total donations
$total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM donations 
    WHERE status='active'
    AND MONTH(donation_date)='$month'
    AND YEAR(donation_date)='$year'
"))['total'];

// Donations by type
$types = mysqli_query($conn, "
    SELECT donor_type, COUNT(*) as count, SUM(amount) as total
    FROM donations
    WHERE status='active'
    AND MONTH(donation_date)='$month'
    AND YEAR(donation_date)='$year'
    GROUP BY donor_type
");

// Top donors
$top_donors = mysqli_query($conn, "
    SELECT donor_name, SUM(amount) as total, COUNT(*) as donations
    FROM donations
    WHERE status='active'
    AND MONTH(donation_date)='$month'
    AND YEAR(donation_date)='$year'
    GROUP BY donor_name
    ORDER BY total DESC
    LIMIT 10
");

// Monthly trend
$monthly_trend = mysqli_query($conn, "
    SELECT 
        MONTH(donation_date) as month_num,
        DATE_FORMAT(donation_date, '%b') as month_name,
        SUM(amount) as total
    FROM donations
    WHERE status='active'
    AND YEAR(donation_date)='$year'
    GROUP BY MONTH(donation_date), DATE_FORMAT(donation_date, '%b')
    ORDER BY month_num
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Donation Report | Academy Management System</title>
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
                        <i class="fas fa-hand-holding-usd mr-2"></i> Donation Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Donation overview for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></p>
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
                            <p class="text-sm text-gray-500 mb-1">Total Donations</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= number_format($total, 2) ?> PKR</h3>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <?php
                $type_counts = [];
                while ($type = mysqli_fetch_assoc($types)) {
                    $type_counts[] = $type;
                }
                mysqli_data_seek($types, 0);

                foreach ($type_counts as $index => $type):
                    $colors = ['#60a5fa', '#34d399', '#fbbf24'];
                ?>
                    <div class="stats-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1"><?= ucfirst($type['donor_type']) ?> Donations</p>
                                <h3 class="text-2xl font-bold" style="color: <?= $colors[$index] ?>">
                                    <?= number_format($type['total'], 2) ?> PKR
                                </h3>
                                <p class="text-xs text-gray-500 mt-1"><?= $type['count'] ?> donations</p>
                            </div>
                            <div class="p-3 rounded-lg" style="background: <?= $colors[$index] ?>20">
                                <i class="fas fa-users" style="color: <?= $colors[$index] ?>"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Donation by Type -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Donations by Donor Type</h3>
                    <div class="h-64">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Monthly Donation Trend (<?= $year ?>)</h3>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Donors -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Top Donors - <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Donor Name</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Amount</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Number of Donations</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Average Donation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($donor = mysqli_fetch_assoc($top_donors)): ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($donor['donor_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-green-600"><?= number_format($donor['total'], 2) ?> PKR</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $donor['donations'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= number_format($donor['total'] / $donor['donations'], 2) ?> PKR</span>
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
                    <p><strong>Report Summary:</strong> Total donations of <?= number_format($total, 2) ?> PKR received in <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>.</p>
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
            // Donation by Type Chart
            <?php
            $typeData = [];
            $typeLabels = [];
            while ($type = mysqli_fetch_assoc($types)) {
                $typeLabels[] = ucfirst($type['donor_type']);
                $typeData[] = $type['total'];
            }
            ?>

            const typeCtx = document.getElementById('typeChart').getContext('2d');
            new Chart(typeCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($typeLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($typeData) ?>,
                        backgroundColor: ['#60a5fa', '#34d399', '#fbbf24'],
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

            // Monthly Trend Chart
            <?php
            $trendLabels = [];
            $trendData = [];
            while ($trend = mysqli_fetch_assoc($monthly_trend)) {
                $trendLabels[] = $trend['month_name'];
                $trendData[] = $trend['total'];
            }
            ?>

            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trendLabels) ?>,
                    datasets: [{
                        label: 'Donations (PKR)',
                        data: <?= json_encode($trendData) ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderColor: '#34d399',
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
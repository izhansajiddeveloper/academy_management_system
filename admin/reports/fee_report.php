<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

// Total fees
$total_fees = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount_paid), 0) AS total
    FROM fee_collections
    WHERE status='active'
    AND MONTH(payment_date)='$month'
    AND YEAR(payment_date)='$year'
"))['total'];

// Batch-wise fees
$batch_fees = mysqli_query($conn, "
    SELECT 
        b.batch_name,
        s.skill_name,
        COUNT(DISTINCT fc.student_id) as students,
        COUNT(fc.id) as payments,
        SUM(fc.amount_paid) AS total
    FROM fee_collections fc
    JOIN batches b ON fc.batch_id = b.id
    JOIN skills s ON b.skill_id = s.id
    WHERE fc.status='active'
    AND MONTH(fc.payment_date)='$month'
    AND YEAR(fc.payment_date)='$year'
    GROUP BY fc.batch_id
    ORDER BY total DESC
");

// Student-wise fees
$student_fees = mysqli_query($conn, "
    SELECT 
        s.name AS student_name,
       
        b.batch_name,
        COUNT(fc.id) as payments,
        SUM(fc.amount_paid) AS total
    FROM fee_collections fc
    JOIN students s ON fc.student_id = s.id
    JOIN batches b ON fc.batch_id = b.id
    WHERE fc.status='active'
    AND MONTH(fc.payment_date)='$month'
    AND YEAR(fc.payment_date)='$year'
    GROUP BY fc.student_id
    ORDER BY total DESC
    LIMIT 20
");

// Payment method breakdown
$payment_methods = mysqli_query($conn, "
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount_paid) as total
    FROM fee_collections
    WHERE status='active'
    AND MONTH(payment_date)='$month'
    AND YEAR(payment_date)='$year'
    GROUP BY payment_method
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Report | Academy Management System</title>
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
                        <i class="fas fa-cash-register mr-2"></i> Fee Collection Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Fee overview for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></p>
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Fees Collected</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= number_format($total_fees, 2) ?> PKR</h3>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <?php
                // Calculate statistics
                $total_students = 0;
                $total_payments = 0;
                $average_per_student = 0;

                if (mysqli_num_rows($batch_fees) > 0) {
                    mysqli_data_seek($batch_fees, 0);
                    while ($batch = mysqli_fetch_assoc($batch_fees)) {
                        $total_students += $batch['students'];
                        $total_payments += $batch['payments'];
                    }
                    mysqli_data_seek($batch_fees, 0);
                    $average_per_student = $total_students > 0 ? $total_fees / $total_students : 0;
                }
                ?>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Students Paid</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= $total_students ?></h3>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Payments</p>
                            <h3 class="text-2xl font-bold text-purple-600"><?= $total_payments ?></h3>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-lg">
                            <i class="fas fa-credit-card text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Avg. per Student</p>
                            <h3 class="text-2xl font-bold text-orange-600"><?= number_format($average_per_student, 2) ?> PKR</h3>
                        </div>
                        <div class="p-3 bg-orange-50 rounded-lg">
                            <i class="fas fa-calculator text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Fees by Batch -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Fees by Batch</h3>
                    <div class="h-64">
                        <canvas id="batchChart"></canvas>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Payment Methods</h3>
                    <div class="h-64">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Batch-wise Fees -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Batch-wise Fee Collection</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Students Paid</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Payments</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Amount Collected</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($batch = mysqli_fetch_assoc($batch_fees)):
                                $percentage = $total_fees > 0 ? ($batch['total'] / $total_fees) * 100 : 0;
                            ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($batch['batch_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $batch['skill_name'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $batch['students'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $batch['payments'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-green-600"><?= number_format($batch['total'], 2) ?> PKR</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-700"><?= number_format($percentage, 1) ?>%</span>
                                            <div class="progress-bar flex-1">
                                                <div class="progress-fill bg-green-400" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Paying Students -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Top Paying Students</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Roll Number</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Payments</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($student = mysqli_fetch_assoc($student_fees)): ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($student['student_name']) ?></div>
                                    </td>

                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $student['batch_name'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $student['payments'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-green-600"><?= number_format($student['total'], 2) ?> PKR</span>
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
                    <p><strong>Report Summary:</strong> Collected <?= number_format($total_fees, 2) ?> PKR from <?= $total_students ?> students through <?= $total_payments ?> payments in <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>.</p>
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
            // Batch Fees Chart
            <?php
            $batchLabels = [];
            $batchData = [];
            mysqli_data_seek($batch_fees, 0);
            while ($batch = mysqli_fetch_assoc($batch_fees)) {
                $batchLabels[] = $batch['batch_name'];
                $batchData[] = $batch['total'];
            }
            mysqli_data_seek($batch_fees, 0);
            ?>

            const batchCtx = document.getElementById('batchChart').getContext('2d');
            new Chart(batchCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($batchLabels) ?>,
                    datasets: [{
                        label: 'Fee Collection (PKR)',
                        data: <?= json_encode($batchData) ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.3)',
                        borderColor: '#22c55e',
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

            // Payment Methods Chart
            <?php
            $methodLabels = [];
            $methodData = [];
            while ($method = mysqli_fetch_assoc($payment_methods)) {
                $methodLabels[] = ucfirst($method['payment_method']);
                $methodData[] = $method['total'];
            }
            ?>

            const paymentCtx = document.getElementById('paymentChart').getContext('2d');
            new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($methodLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($methodData) ?>,
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
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
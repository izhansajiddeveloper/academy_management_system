<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Total teachers
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM teachers
"))['total'];

// Active / Inactive
$active_teachers = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM teachers WHERE status='active'
"))['total'];

$inactive_teachers = $total_teachers - $active_teachers;

// Teacher workload
$teacher_data = mysqli_query($conn, "
    SELECT 
        t.id,
        t.name,
       
        t.phone,
        t.qualification,
        t.status,
        COUNT(DISTINCT bt.batch_id) AS batches,
        COUNT(DISTINCT se.student_id) AS students,
        GROUP_CONCAT(DISTINCT b.batch_name SEPARATOR ', ') as batch_names,
        GROUP_CONCAT(DISTINCT s.skill_name SEPARATOR ', ') as skills_taught
    FROM teachers t
    LEFT JOIN batch_teachers bt ON bt.teacher_id = t.id
    LEFT JOIN batches b ON b.id = bt.batch_id AND b.status='active'
    LEFT JOIN skills s ON s.id = b.skill_id
    LEFT JOIN student_enrollments se ON se.batch_id = bt.batch_id AND se.status='active'
    GROUP BY t.id
    ORDER BY t.name
");

// Gender distribution
$gender_distribution = mysqli_query($conn, "
    SELECT gender, COUNT(*) as count
    FROM teachers
    WHERE status='active'
    GROUP BY gender
");

// Qualification distribution
$qualification_distribution = mysqli_query($conn, "
    SELECT qualification, COUNT(*) as count
    FROM teachers
    WHERE status='active' AND qualification IS NOT NULL AND qualification != ''
    GROUP BY qualification
    ORDER BY count DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Report | Academy Management System</title>
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
                        <i class="fas fa-chalkboard-teacher mr-2"></i> Teacher Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Overview of teaching staff and their assignments</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" placeholder="Search teachers..." class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box" id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Teachers</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= $total_teachers ?></h3>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-chalkboard-teacher text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Active Teachers</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= $active_teachers ?></h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $total_teachers > 0 ? round(($active_teachers / $total_teachers) * 100, 1) : 0 ?>% of total</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-user-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-green-400" style="width: <?= $total_teachers > 0 ? ($active_teachers / $total_teachers) * 100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Inactive Teachers</p>
                            <h3 class="text-2xl font-bold text-gray-600"><?= $inactive_teachers ?></h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $total_teachers > 0 ? round(($inactive_teachers / $total_teachers) * 100, 1) : 0 ?>% of total</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-user-times text-gray-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teacher Workload Stats -->
            <?php
            // Calculate workload statistics
            $total_batches = 0;
            $total_students = 0;
            $avg_batches_per_teacher = 0;
            $avg_students_per_teacher = 0;

            if (mysqli_num_rows($teacher_data) > 0) {
                mysqli_data_seek($teacher_data, 0);
                while ($teacher = mysqli_fetch_assoc($teacher_data)) {
                    $total_batches += $teacher['batches'];
                    $total_students += $teacher['students'];
                }
                mysqli_data_seek($teacher_data, 0);

                $avg_batches_per_teacher = $total_teachers > 0 ? round($total_batches / $total_teachers, 1) : 0;
                $avg_students_per_teacher = $total_teachers > 0 ? round($total_students / $total_teachers, 1) : 0;
            }
            ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Batches Assigned</p>
                            <h3 class="text-2xl font-bold text-purple-600"><?= $total_batches ?></h3>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-lg">
                            <i class="fas fa-layer-group text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Students Taught</p>
                            <h3 class="text-2xl font-bold text-orange-600"><?= $total_students ?></h3>
                        </div>
                        <div class="p-3 bg-orange-50 rounded-lg">
                            <i class="fas fa-users text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Avg. Workload per Teacher</p>
                            <h3 class="text-lg font-bold text-green-600"><?= $avg_batches_per_teacher ?> batches</h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $avg_students_per_teacher ?> students average</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-chart-pie text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Gender Distribution -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Gender Distribution</h3>
                    <div class="h-64">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>

                <!-- Top Qualifications -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Top Qualifications</h3>
                    <div class="h-64">
                        <canvas id="qualificationChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Teacher Details -->
            <div class="card">
                <h3 class="font-semibold text-gray-800 mb-4">Teacher Details</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Contact</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Qualification</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batches</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Students</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skills Taught</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = mysqli_fetch_assoc($teacher_data)): ?>
                                <tr class="teacher-row">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="text-xs text-gray-500">ID: #<?= $row['id'] ?></div>
                                    </td>
                                    <td class="py-3 px-4">

                                        <div class="text-xs text-gray-500"><?= $row['phone'] ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $row['qualification'] ?: '—' ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-purple-600"><?= $row['batches'] ?></span>
                                        <div class="text-xs text-gray-500 max-w-xs truncate"><?= $row['batch_names'] ?: '—' ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-green-600"><?= $row['students'] ?></span>
                                        <span class="text-sm text-gray-500"> students</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $row['skills_taught'] ?: '—' ?></span>
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
                    <p><strong>Report Summary:</strong> <?= $total_teachers ?> teachers in total, <?= $active_teachers ?> active and <?= $inactive_teachers ?> inactive.
                        Average workload: <?= $avg_batches_per_teacher ?> batches and <?= $avg_students_per_teacher ?> students per teacher.</p>
                    <p class="mt-1">Report generated on <?= date('F d, Y h:i A') ?></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.teacher-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Gender Chart
            <?php
            $genderLabels = [];
            $genderData = [];
            mysqli_data_seek($gender_distribution, 0);
            while ($gender = mysqli_fetch_assoc($gender_distribution)) {
                $genderLabels[] = ucfirst($gender['gender']);
                $genderData[] = $gender['count'];
            }
            ?>

            const genderCtx = document.getElementById('genderChart').getContext('2d');
            new Chart(genderCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($genderLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($genderData) ?>,
                        backgroundColor: ['#3b82f6', '#ec4899'],
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

            // Qualification Chart
            <?php
            $qualLabels = [];
            $qualData = [];
            mysqli_data_seek($qualification_distribution, 0);
            while ($qual = mysqli_fetch_assoc($qualification_distribution)) {
                $qualLabels[] = $qual['qualification'];
                $qualData[] = $qual['count'];
            }
            ?>

            const qualCtx = document.getElementById('qualificationChart').getContext('2d');
            new Chart(qualCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($qualLabels) ?>,
                    datasets: [{
                        label: 'Number of Teachers',
                        data: <?= json_encode($qualData) ?>,
                        backgroundColor: 'rgba(139, 92, 246, 0.3)',
                        borderColor: '#8b5cf6',
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
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

/* ============================
   BASIC COUNTS
============================ */

// Total students
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students"))['total'];

// Active students
$active_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE status='active'"))['total'];

// Inactive students
$inactive_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE status='inactive'"))['total'];

// Total enrollments
$total_enrollments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student_enrollments WHERE status='active'"))['total'];

// Gender distribution
$gender_distribution = mysqli_query($conn, "
    SELECT gender, COUNT(*) as count
    FROM students
    WHERE status='active'
    GROUP BY gender
");

/* ============================
   STUDENTS PER SKILL
============================ */
$students_per_skill = mysqli_query($conn, "
    SELECT 
        s.skill_name,
        COUNT(DISTINCT e.student_id) AS total_students,
        ROUND((COUNT(DISTINCT e.student_id) / $total_enrollments * 100), 1) as percentage
    FROM student_enrollments e
    JOIN skills s ON e.skill_id = s.id
    WHERE e.status='active'
    GROUP BY e.skill_id
    ORDER BY total_students DESC
");

/* ============================
   STUDENTS PER BATCH
============================ */
$students_per_batch = mysqli_query($conn, "
    SELECT 
        b.batch_name,
        s.skill_name,
        COUNT(DISTINCT e.student_id) AS total_students
    FROM student_enrollments e
    JOIN batches b ON e.batch_id = b.id
    JOIN skills s ON b.skill_id = s.id
    WHERE e.status='active'
    GROUP BY e.batch_id
    ORDER BY total_students DESC
");

// Recent enrollments
$recent_enrollments = mysqli_query($conn, "
    SELECT 
        s.name AS student_name,
        s.gender,
        s.phone,
        b.batch_name,
        sk.skill_name,
        e.created_at AS enrollment_date
    FROM student_enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN batches b ON e.batch_id = b.id
    JOIN skills sk ON e.skill_id = sk.id
    WHERE e.status='active'
    ORDER BY e.created_at DESC
    LIMIT 10
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Report | Academy Management System</title>
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
                        <i class="fas fa-user-graduate mr-2"></i> Student Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Comprehensive overview of student data and enrollments</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" placeholder="Search students..." class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box" id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Students</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= number_format($total_students) ?></h3>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Active Students</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= number_format($active_students) ?></h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $total_students > 0 ? round(($active_students / $total_students) * 100, 1) : 0 ?>% of total</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i class="fas fa-user-check text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-green-400" style="width: <?= $total_students > 0 ? ($active_students / $total_students) * 100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Inactive Students</p>
                            <h3 class="text-2xl font-bold text-gray-600"><?= number_format($inactive_students) ?></h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $total_students > 0 ? round(($inactive_students / $total_students) * 100, 1) : 0 ?>% of total</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-user-times text-gray-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Enrollments</p>
                            <h3 class="text-2xl font-bold text-purple-600"><?= number_format($total_enrollments) ?></h3>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-lg">
                            <i class="fas fa-graduation-cap text-purple-600 text-xl"></i>
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

                <!-- Students by Skill -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Students by Skill</h3>
                    <div class="h-64">
                        <canvas id="skillChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Students by Skill -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Students by Skill</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Students</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Distribution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = mysqli_fetch_assoc($students_per_skill)): ?>
                                <tr class="skill-row">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['skill_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-blue-600"><?= $row['total_students'] ?></span>
                                        <span class="text-sm text-gray-500"> students</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $row['percentage'] ?>%</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="progress-bar">
                                            <div class="progress-fill bg-blue-400" style="width: <?= $row['percentage'] ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Students by Batch -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Students by Batch</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Students</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = mysqli_fetch_assoc($students_per_batch)): ?>
                                <tr class="batch-row">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['batch_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= htmlspecialchars($row['skill_name']) ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-green-600"><?= $row['total_students'] ?></span>
                                        <span class="text-sm text-gray-500"> students</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Enrollments -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Recent Enrollments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                               
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Gender</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Contact</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = mysqli_fetch_assoc($recent_enrollments)): ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                    </td>
                                   
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?= $row['gender'] == 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' ?>">
                                            <?= ucfirst($row['gender']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= $row['phone'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= htmlspecialchars($row['batch_name']) ?></span>
                                        <div class="text-xs text-gray-500"><?= $row['skill_name'] ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= date('d M, Y', strtotime($row['enrollment_date'])) ?></span>
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
                    <p><strong>Report Summary:</strong> Total <?= $total_students ?> students registered, with <?= $active_students ?> active and <?= $inactive_students ?> inactive. <?= $total_enrollments ?> total enrollments across all batches.</p>
                    <p class="mt-1">Report generated on <?= date('F d, Y h:i A') ?></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const skillRows = document.querySelectorAll('.skill-row');
            const batchRows = document.querySelectorAll('.batch-row');

            skillRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? 'table-row' : 'none';
            });

            batchRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? 'table-row' : 'none';
            });
        });

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Gender Chart
            <?php
            $genderLabels = [];
            $genderData = [];
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

            // Skill Chart
            <?php
            $skillLabels = [];
            $skillData = [];
            mysqli_data_seek($students_per_skill, 0);
            while ($skill = mysqli_fetch_assoc($students_per_skill)) {
                $skillLabels[] = $skill['skill_name'];
                $skillData[] = $skill['total_students'];
            }
            mysqli_data_seek($students_per_skill, 0);
            ?>

            const skillCtx = document.getElementById('skillChart').getContext('2d');
            new Chart(skillCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($skillLabels) ?>,
                    datasets: [{
                        label: 'Number of Students',
                        data: <?= json_encode($skillData) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.3)',
                        borderColor: '#3b82f6',
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
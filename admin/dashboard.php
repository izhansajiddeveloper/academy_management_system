<?php
require_once "../includes/auth_check.php";
require_once "../config/db.php";
include "../includes/navbar.php";

// Fetch statistics from database
$stats = [
    'total_students' => 156,
    'total_teachers' => 24,
    'active_batches' => 18,
    'pending_fees' => 12450,
    'monthly_revenue' => 85600,
    'completion_rate' => 78,
    'total_sessions' => 45,
    'upcoming_sessions' => 8,
    'new_enrollments' => 32,
    'attendance_rate' => 92
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3b82f6;
            --secondary: #10b981;
            --accent: #8b5cf6;
            --dark: #1f2937;
            --light: #f9fafb;
            --card-bg: #ffffff;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }

        .main-container {
            min-height: calc(100vh - 60px);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            stroke-dasharray: 283;
            stroke-dashoffset: 283;
            transition: stroke-dashoffset 1s ease-in-out;
            stroke-linecap: round;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }

        .sidebar-link {
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }
    </style>
</head>

<body class="bg-gray-50">

    <div class="flex main-container">

        <!-- SIDEBAR -->

        <?php require_once './includes/sidebar.php'; ?>


        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 overflow-y-auto custom-scrollbar">

            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 mb-1">Dashboard Overview</h1>
                        <p class="text-gray-600 text-sm">
                            <i class="fas fa-calendar-day text-gray-400 mr-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <button class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors border">
                                <i class="fas fa-bell"></i>
                            </button>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </div>
                        <div class="flex items-center gap-3 bg-white p-3 rounded-lg border">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 text-sm">Administrator</p>
                                <p class="text-xs text-gray-500">Super Admin</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP STATS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <!-- Student Card -->
                <div class="stat-card p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fas fa-user-graduate text-xl"></i>
                        </div>
                        <span class="badge bg-blue-50 text-blue-700">
                            +12.5%
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Students</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo number_format($stats['total_students']); ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: 85%"></div>
                            </div>
                            <span class="ml-2">85% Active</span>
                        </div>
                    </div>
                </div>

                <!-- Revenue Card -->
                <div class="stat-card p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center text-green-600">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <span class="badge bg-green-50 text-green-700">
                            +8.2%
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Monthly Revenue</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Rs<?php echo number_format($stats['monthly_revenue']); ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: 78%"></div>
                            </div>
                            <span class="ml-2">78% Target</span>
                        </div>
                    </div>
                </div>

                <!-- Batches Card -->
                <div class="stat-card p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center text-purple-600">
                            <i class="fas fa-layer-group text-xl"></i>
                        </div>
                        <span class="badge bg-purple-50 text-purple-700">
                            3 Ending
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Active Batches</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $stats['active_batches']; ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: 60%"></div>
                            </div>
                            <span class="ml-2">Avg 60% Full</span>
                        </div>
                    </div>
                </div>

                <!-- Completion Card -->
                <div class="stat-card p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center text-orange-600">
                            <i class="fas fa-trophy text-xl"></i>
                        </div>
                        <span class="badge bg-orange-50 text-orange-700">
                            78%
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Completion Rate</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $stats['completion_rate']; ?>%</h3>
                        <div class="relative w-12 h-12 ml-auto">
                            <svg width="48" height="48" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f3f4f6" stroke-width="8" />
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f59e0b" stroke-width="8" stroke-linecap="round"
                                    stroke-dasharray="283" stroke-dashoffset="<?php echo 283 - (283 * $stats['completion_rate'] / 100); ?>" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- New Enrollments -->
                <div class="stat-card p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div class="w-12 h-12 bg-pink-50 rounded-lg flex items-center justify-center text-pink-600">
                            <i class="fas fa-user-plus text-xl"></i>
                        </div>
                        <span class="badge bg-pink-50 text-pink-700">
                            +15
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">New Enrollments</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $stats['new_enrollments']; ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <span class="text-green-600 font-medium">This week</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS SECTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Revenue Chart -->
                <div class="chart-container lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Revenue Overview</h3>
                            <p class="text-sm text-gray-500">Last 6 months performance</p>
                        </div>
                        <select class="text-sm bg-gray-50 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option>Monthly</option>
                            <option>Quarterly</option>
                            <option>Yearly</option>
                        </select>
                    </div>
                    <div style="height: 220px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Student Distribution -->
                <div class="chart-container">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Student Distribution</h3>
                            <p class="text-sm text-gray-500">By course category</p>
                        </div>
                    </div>
                    <div style="height: 220px;">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- BOTTOM SECTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Today's Sessions -->
                <div class="chart-container">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Today's Sessions</h3>
                        <span class="text-sm px-3 py-1 bg-blue-50 text-blue-700 rounded-full">
                            <?php echo $stats['upcoming_sessions']; ?> Total
                        </span>
                    </div>
                    <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                        <?php
                        $sessions = [
                            ['time' => '10:00 AM', 'course' => 'Web Dev Basics', 'batch' => 'WD-101', 'room' => 'Room 12'],
                            ['time' => '11:30 AM', 'course' => 'Data Science', 'batch' => 'DS-202', 'room' => 'Lab 3'],
                            ['time' => '02:00 PM', 'course' => 'UI/UX Design', 'batch' => 'UX-303', 'room' => 'Room 8'],
                            ['time' => '04:30 PM', 'course' => 'Digital Marketing', 'batch' => 'DM-404', 'room' => 'Room 5'],
                        ];
                        foreach ($sessions as $session):
                        ?>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="text-center bg-white p-2 rounded-lg border min-w-[60px]">
                                    <p class="text-xs text-gray-500">Starts</p>
                                    <p class="text-sm font-semibold text-gray-800"><?php echo $session['time']; ?></p>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="font-medium text-gray-800 text-sm"><?php echo $session['course']; ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo $session['batch']; ?></p>
                                </div>
                                <div class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                    <?php echo $session['room']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Attendance Stats -->
                <div class="chart-container">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Attendance Overview</h3>
                        <div class="text-right">
                            <p class="text-xl font-bold text-gray-800"><?php echo $stats['attendance_rate']; ?>%</p>
                            <p class="text-xs text-gray-500">Overall Rate</p>
                        </div>
                    </div>
                    <div style="height: 250px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Present Today</p>
                            <p class="text-lg font-semibold text-gray-800">142</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Absent Today</p>
                            <p class="text-lg font-semibold text-gray-800">14</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="chart-container">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="sessions/add_session.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-blue-50 transition-colors">
                                <i class="fas fa-plus text-gray-600 group-hover:text-blue-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">New Session</p>
                            <p class="text-xs text-gray-500 mt-1">Schedule class</p>
                        </a>

                        <a href="users/students.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-green-50 transition-colors">
                                <i class="fas fa-user-plus text-gray-600 group-hover:text-green-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Add Student</p>
                            <p class="text-xs text-gray-500 mt-1">New admission</p>
                        </a>

                        <a href="fees/fee_collection.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-purple-50 transition-colors">
                                <i class="fas fa-money-bill-wave text-gray-600 group-hover:text-purple-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Collect Fees</p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo number_format($stats['pending_fees']); ?> pending</p>
                        </a>

                        <a href="reports/student_report.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-orange-50 transition-colors">
                                <i class="fas fa-chart-bar text-gray-600 group-hover:text-orange-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Generate Report</p>
                            <p class="text-xs text-gray-500 mt-1">Monthly analysis</p>
                        </a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Footer -->
    <footer class="py-4 px-6 bg-white border-t">
        <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
            <div class="text-sm text-gray-600 mb-2 md:mb-0">
                <span class="font-medium text-gray-800">© <?php echo date('Y'); ?> EduSkill Pro.</span>
                All rights reserved.
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">
                    <i class="fas fa-database mr-1"></i>
                    Updated: <?php echo date('g:i A'); ?>
                </span>
                <span class="text-sm text-gray-500">
                    <i class="fas fa-users mr-1"></i>
                    Online: 42
                </span>
            </div>
        </div>
    </footer>

    <script>
        // Sidebar functions
        function toggleUsersMenu() {
            const menu = document.getElementById('usersMenu');
            const chevron = document.getElementById('usersChevron');
            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        function toggleSkillsMenu() {
            const menu = document.getElementById('skillsMenu');
            const chevron = document.getElementById('skillsChevron');
            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        function toggleSessionsMenu() {
            const menu = document.getElementById('sessionsMenu');
            const chevron = document.getElementById('sessionsChevron');
            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        function toggleBatchesMenu() {
            const menu = document.getElementById('batchesMenu');
            const chevron = document.getElementById('batchesChevron');
            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [72000, 80000, 85000, 82000, 85600, 90000],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rs' + (value / 1000).toFixed(0) + 'k';
                                }
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

            // Distribution Chart
            const distributionCtx = document.getElementById('distributionChart').getContext('2d');
            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Web Dev', 'Data Science', 'UI/UX', 'Marketing'],
                    datasets: [{
                        data: [65, 42, 28, 22],
                        backgroundColor: [
                            '#3b82f6', // Blue
                            '#10b981', // Green
                            '#8b5cf6', // Purple
                            '#f59e0b' // Orange
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Attendance Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    datasets: [{
                        label: 'Attendance %',
                        data: [88, 92, 90, 95, 89, 85],
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 80,
                            max: 100,
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
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

            // Animate progress circles
            document.querySelectorAll('.progress-ring-circle').forEach(circle => {
                const radius = circle.r.baseVal.value;
                const circumference = radius * 2 * Math.PI;
                const percent = parseInt(circle.parentElement.nextElementSibling.textContent);
                const offset = circumference - (percent / 100 * circumference);

                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                circle.style.strokeDashoffset = circumference;

                setTimeout(() => {
                    circle.style.strokeDashoffset = offset;
                }, 500);
            });
        });
    </script>

</body>

</html>
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

/* ============================
   CONFIGURATION
============================ */
$report_type = $_GET['type'] ?? 'student'; // 'student' or 'teacher'
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');
$batch_id = $_GET['batch'] ?? 'all';
$teacher_id = $_GET['teacher'] ?? 'all';

// Get all batches for filter
$batches_query = mysqli_query($conn, "SELECT id, batch_name FROM batches WHERE status='active' ORDER BY batch_name");

// Get all teachers for filter
$teachers_query = mysqli_query($conn, "SELECT id, name FROM teachers WHERE status='active' ORDER BY name");

/* ============================
   STUDENT ATTENDANCE DATA
============================ */
if ($report_type == 'student') {
    // Base query for student attendance
    $student_where = "sa.status='active' 
                     AND MONTH(sa.attendance_date) = '$month' 
                     AND YEAR(sa.attendance_date) = '$year'";

    if ($batch_id != 'all') {
        $student_where .= " AND sa.batch_id = $batch_id";
    }

    // Total counts (students)
    $student_totals = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            COUNT(*) AS total,
            SUM(sa.attendance_status='present') AS present_count,
            SUM(sa.attendance_status='absent') AS absent_count
        FROM student_attendance sa
        WHERE $student_where
    "));

    $total_attendance = $student_totals['total'] ?? 0;
    $present_count = $student_totals['present_count'] ?? 0;
    $absent_count = $student_totals['absent_count'] ?? 0;

    // Attendance by batch
    $attendance_by_batch = mysqli_query($conn, "
        SELECT 
            b.batch_name,
            COUNT(sa.id) AS total_attendance,
            SUM(sa.attendance_status='present') AS present_count,
            SUM(sa.attendance_status='absent') AS absent_count,
            ROUND((SUM(sa.attendance_status='present') / COUNT(sa.id)) * 100, 2) AS attendance_percentage
        FROM student_attendance sa
        JOIN batches b ON sa.batch_id = b.id
        WHERE sa.status='active'
        AND MONTH(sa.attendance_date) = '$month'
        AND YEAR(sa.attendance_date) = '$year'
        GROUP BY sa.batch_id
        ORDER BY attendance_percentage DESC
    ");

    // Attendance by student
    $attendance_by_student = mysqli_query($conn, "
        SELECT 
            s.name AS student_name,
           
            b.batch_name,
            sk.skill_name,
            COUNT(sa.id) AS total_days,
            SUM(sa.attendance_status='present') AS present_days,
            SUM(sa.attendance_status='absent') AS absent_days,
            ROUND((SUM(sa.attendance_status='present') / COUNT(sa.id)) * 100, 2) AS attendance_percentage
        FROM student_attendance sa
        JOIN students s ON sa.student_id = s.id
        JOIN batches b ON sa.batch_id = b.id
        JOIN skills sk ON sa.skill_id = sk.id
        WHERE sa.status='active'
        AND MONTH(sa.attendance_date) = '$month'
        AND YEAR(sa.attendance_date) = '$year'
        " . ($batch_id != 'all' ? " AND sa.batch_id = $batch_id" : "") . "
        GROUP BY sa.student_id
        ORDER BY attendance_percentage DESC
    ");

    // Daily attendance summary
    $daily_summary = mysqli_query($conn, "
        SELECT 
            attendance_date,
            COUNT(*) AS total_students,
            SUM(attendance_status='present') AS present_count,
            SUM(attendance_status='absent') AS absent_count,
            ROUND((SUM(attendance_status='present') / COUNT(*)) * 100, 2) AS daily_percentage
        FROM student_attendance
        WHERE status='active'
        AND MONTH(attendance_date) = '$month'
        AND YEAR(attendance_date) = '$year'
        GROUP BY attendance_date
        ORDER BY attendance_date
    ");
}

/* ============================
   TEACHER ATTENDANCE DATA
============================ */
if ($report_type == 'teacher') {
    // Base query for teacher attendance
    $teacher_where = "ta.status='active' 
                     AND MONTH(ta.attendance_date) = '$month' 
                     AND YEAR(ta.attendance_date) = '$year'";

    if ($teacher_id != 'all') {
        $teacher_where .= " AND ta.teacher_id = $teacher_id";
    }

    // Total counts (teachers)
    $teacher_totals = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            COUNT(*) AS total,
            SUM(ta.attendance_status='present') AS present_count,
            SUM(ta.attendance_status='absent') AS absent_count
        FROM teacher_attendance ta
        WHERE $teacher_where
    "));

    $total_attendance = $teacher_totals['total'] ?? 0;
    $present_count = $teacher_totals['present_count'] ?? 0;
    $absent_count = $teacher_totals['absent_count'] ?? 0;

    // Attendance by teacher
    $attendance_by_teacher = mysqli_query($conn, "
        SELECT 
            t.name AS teacher_name,
          
            t.phone,
            COUNT(ta.id) AS total_days,
            SUM(ta.attendance_status='present') AS present_days,
            SUM(ta.attendance_status='absent') AS absent_days,
            ROUND((SUM(ta.attendance_status='present') / COUNT(ta.id)) * 100, 2) AS attendance_percentage
        FROM teacher_attendance ta
        JOIN teachers t ON ta.teacher_id = t.id
        WHERE ta.status='active'
        AND MONTH(ta.attendance_date) = '$month'
        AND YEAR(ta.attendance_date) = '$year'
        " . ($teacher_id != 'all' ? " AND ta.teacher_id = $teacher_id" : "") . "
        GROUP BY ta.teacher_id
        ORDER BY attendance_percentage DESC
    ");

    // Daily attendance summary for teachers
    $daily_summary = mysqli_query($conn, "
        SELECT 
            attendance_date,
            COUNT(*) AS total_teachers,
            SUM(attendance_status='present') AS present_count,
            SUM(attendance_status='absent') AS absent_count,
            ROUND((SUM(attendance_status='present') / COUNT(*)) * 100, 2) AS daily_percentage
        FROM teacher_attendance
        WHERE status='active'
        AND MONTH(attendance_date) = '$month'
        AND YEAR(attendance_date) = '$year'
        GROUP BY attendance_date
        ORDER BY attendance_date
    ");

    // Get teacher details for selected filter
    $selected_teacher = null;
    if ($teacher_id != 'all') {
        $teacher_query = mysqli_query($conn, "
            SELECT name, email, phone, qualification 
            FROM teachers 
            WHERE id = $teacher_id AND status='active'
        ");
        $selected_teacher = mysqli_fetch_assoc($teacher_query);
    }
}

// Calculate percentages
$present_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 2) : 0;
$absent_percentage = $total_attendance > 0 ? round(($absent_count / $total_attendance) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($report_type); ?> Attendance Report | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

      
         

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .tab-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .tab-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .tab-btn:not(.active) {
            background: #f3f4f6;
            color: #6b7280;
        }

        .tab-btn:not(.active):hover {
            background: #e5e7eb;
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .attendance-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .badge-excellent {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-good {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-poor {
            background: #fee2e2;
            color: #991b1b;
        }

        .filter-box {
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
            width: 100%;
        }

        .search-box:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
                        <i class="fas fa-chart-bar mr-2"></i>
                        <?php echo ucfirst($report_type); ?> Attendance Report
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search reports..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                    <button onclick="printReport()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button onclick="exportToPDF()"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>

            <!-- Report Type Toggle -->
            <div class="mb-6">
                <div class="flex gap-2">
                    <a href="?type=student&month=<?= $month ?>&year=<?= $year ?>&batch=<?= $batch_id ?>"
                        class="tab-btn <?= $report_type == 'student' ? 'active' : '' ?>">
                        <i class="fas fa-users mr-2"></i> Student Attendance
                    </a>
                    <a href="?type=teacher&month=<?= $month ?>&year=<?= $year ?>&teacher=<?= $teacher_id ?>"
                        class="tab-btn <?= $report_type == 'teacher' ? 'active' : '' ?>">
                        <i class="fas fa-chalkboard-teacher mr-2"></i> Teacher Attendance
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-box mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <select name="month" class="search-box" onchange="updateFilters()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?= ($m == $month) ? 'selected' : '' ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <input type="number" name="year" min="2020" max="2030"
                            value="<?php echo $year; ?>" class="search-box" onchange="updateFilters()">
                    </div>

                    <?php if ($report_type == 'student'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Batch</label>
                            <select name="batch" class="search-box" onchange="updateFilters()">
                                <option value="all">All Batches</option>
                                <?php while ($batch = mysqli_fetch_assoc($batches_query)): ?>
                                    <option value="<?= $batch['id'] ?>" <?= ($batch_id == $batch['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($batch['batch_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Teacher</label>
                            <select name="teacher" class="search-box" onchange="updateFilters()">
                                <option value="all">All Teachers</option>
                                <?php while ($teacher = mysqli_fetch_assoc($teachers_query)): ?>
                                    <option value="<?= $teacher['id'] ?>" <?= ($teacher_id == $teacher['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($teacher['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-end">
                        <button type="button" onclick="updateFilters()"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-filter mr-1"></i> Apply Filters
                        </button>
                    </div>
                </div>

                <!-- Selected Teacher Info -->
                <?php if ($report_type == 'teacher' && $selected_teacher): ?>
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="font-medium text-blue-800 mb-1">Teacher Details</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                            <div>
                                <span class="text-gray-600">Name:</span>
                                <p class="font-medium"><?= $selected_teacher['name'] ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600">Email:</span>
                                <p class="font-medium"><?= $selected_teacher['email'] ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600">Phone:</span>
                                <p class="font-medium"><?= $selected_teacher['phone'] ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600">Qualification:</span>
                                <p class="font-medium"><?= $selected_teacher['qualification'] ?? 'N/A' ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Records</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_attendance) ?></h3>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="fas fa-database text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Present</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= number_format($present_count) ?></h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $present_percentage ?>% of total</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-green-500" style="width: <?= $present_percentage ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Absent</p>
                            <h3 class="text-2xl font-bold text-red-600"><?= number_format($absent_count) ?></h3>
                            <p class="text-xs text-gray-500 mt-1"><?= $absent_percentage ?>% of total</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 progress-bar">
                        <div class="progress-fill bg-red-500" style="width: <?= $absent_percentage ?>%"></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Overall Attendance</p>
                            <h3 class="text-2xl font-bold <?= $present_percentage >= 80 ? 'text-green-600' : ($present_percentage >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= $present_percentage ?>%
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php
                                if ($present_percentage >= 80) echo 'Excellent';
                                elseif ($present_percentage >= 60) echo 'Good';
                                else echo 'Needs Improvement';
                                ?>
                            </p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Attendance Distribution Chart -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Attendance Distribution</h3>
                    <div class="h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <!-- Daily Trend Chart -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">Daily Attendance Trend</h3>
                    <div class="h-64">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Reports -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <?php if ($report_type == 'student'): ?>
                    <!-- Student Attendance by Batch -->
                    <div class="card">
                        <h3 class="font-semibold text-gray-800 mb-4">Attendance by Batch</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Absent</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($batch = mysqli_fetch_assoc($attendance_by_batch)):
                                        $percentage = $batch['attendance_percentage'];
                                        $badge_class = $percentage >= 80 ? 'badge-excellent' : ($percentage >= 60 ? 'badge-good' : 'badge-poor');
                                    ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($batch['batch_name']) ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-green-600"><?= $batch['present_count'] ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-red-600"><?= $batch['absent_count'] ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="<?= $badge_class ?> attendance-badge">
                                                    <i class="fas fa-percentage text-xs"></i>
                                                    <?= $percentage ?>%
                                                </span>
                                                <div class="mt-1 progress-bar">
                                                    <div class="progress-fill <?= $percentage >= 80 ? 'bg-green-500' : ($percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                                                        style="width: <?= min($percentage, 100) ?>%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Student Attendance by Student -->
                    <div class="card">
                        <h3 class="font-semibold text-gray-800 mb-4">Attendance by Student</h3>
                        <div class="overflow-x-auto max-h-96">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 sticky top-0">
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($student = mysqli_fetch_assoc($attendance_by_student)):
                                        $percentage = $student['attendance_percentage'];
                                        $badge_class = $percentage >= 80 ? 'badge-excellent' : ($percentage >= 60 ? 'badge-good' : 'badge-poor');
                                    ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($student['student_name']) ?></div>

                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="text-sm text-gray-700"><?= htmlspecialchars($student['batch_name']) ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-green-600"><?= $student['present_days'] ?></span>
                                                <span class="text-sm text-gray-500">/<?= $student['total_days'] ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="<?= $badge_class ?> attendance-badge">
                                                    <?= $percentage ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Teacher Attendance Summary -->
                    <div class="card">
                        <h3 class="font-semibold text-gray-800 mb-4">Teacher Attendance Summary</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Contact</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($teacher = mysqli_fetch_assoc($attendance_by_teacher)):
                                        $percentage = $teacher['attendance_percentage'];
                                        $badge_class = $percentage >= 80 ? 'badge-excellent' : ($percentage >= 60 ? 'badge-good' : 'badge-poor');
                                    ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($teacher['teacher_name']) ?></div>
                                            </td>
                                            <td class="py-3 px-4">

                                                <div class="text-xs text-gray-500"><?= $teacher['phone'] ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-green-600"><?= $teacher['present_days'] ?></span>
                                                <span class="text-sm text-gray-500">/<?= $teacher['total_days'] ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="<?= $badge_class ?> attendance-badge">
                                                    <?= $percentage ?>%
                                                </span>
                                                <div class="mt-1 progress-bar">
                                                    <div class="progress-fill <?= $percentage >= 80 ? 'bg-green-500' : ($percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                                                        style="width: <?= min($percentage, 100) ?>%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Daily Attendance for Teachers -->
                    <div class="card">
                        <h3 class="font-semibold text-gray-800 mb-4">Daily Attendance Records</h3>
                        <div class="overflow-x-auto max-h-96">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 sticky top-0">
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Absent</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($day = mysqli_fetch_assoc($daily_summary)):
                                        $percentage = $day['daily_percentage'];
                                        $badge_class = $percentage >= 80 ? 'badge-excellent' : ($percentage >= 60 ? 'badge-good' : 'badge-poor');
                                    ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?= date('d M, Y', strtotime($day['attendance_date'])) ?></div>
                                                <div class="text-xs text-gray-500"><?= date('l', strtotime($day['attendance_date'])) ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-green-600"><?= $day['present_count'] ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-red-600"><?= $day['absent_count'] ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="<?= $badge_class ?> attendance-badge">
                                                    <?= $percentage ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Daily Summary -->
            <div class="card mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Daily Attendance Summary</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Day</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Present</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Absent</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            // Reset pointer
                            mysqli_data_seek($daily_summary, 0);
                            while ($day = mysqli_fetch_assoc($daily_summary)):
                                $percentage = $day['daily_percentage'];
                                $badge_class = $percentage >= 80 ? 'badge-excellent' : ($percentage >= 60 ? 'badge-good' : 'badge-poor');
                            ?>
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900"><?= date('d M, Y', strtotime($day['attendance_date'])) ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-700"><?= date('l', strtotime($day['attendance_date'])) ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-green-600"><?= $day['present_count'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-red-600"><?= $day['absent_count'] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-gray-800"><?= $day['total_' . ($report_type == 'student' ? 'students' : 'teachers')] ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <span class="<?= $badge_class ?> attendance-badge">
                                                <?= $percentage ?>%
                                            </span>
                                            <div class="progress-bar flex-1">
                                                <div class="progress-fill <?= $percentage >= 80 ? 'bg-green-500' : ($percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                                                    style="width: <?= min($percentage, 100) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report Footer -->
            <div class="bg-white rounded-lg border p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-600">Report generated on: <?= date('F d, Y h:i A') ?></p>
                        <p class="text-sm text-gray-600">Report Type: <?= ucfirst($report_type) ?> Attendance</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-800">Summary</p>
                        <p class="text-xs text-gray-600">
                            Total: <?= $total_attendance ?> |
                            Present: <?= $present_count ?> (<?= $present_percentage ?>%) |
                            Absent: <?= $absent_count ?> (<?= $absent_percentage ?>%)
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update filters
        function updateFilters() {
            const month = document.querySelector('select[name="month"]').value;
            const year = document.querySelector('input[name="year"]').value;
            const type = '<?= $report_type ?>';

            let url = `?type=${type}&month=${month}&year=${year}`;

            if (type === 'student') {
                const batch = document.querySelector('select[name="batch"]').value;
                url += `&batch=${batch}`;
            } else {
                const teacher = document.querySelector('select[name="teacher"]').value;
                url += `&teacher=${teacher}`;
            }

            window.location.href = url;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tables = document.querySelectorAll('table tbody');

            tables.forEach(table => {
                const rows = table.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Print report
        function printReport() {
            window.print();
        }

        // Export to PDF (mock function)
        function exportToPDF() {
            alert('PDF export feature would be implemented with a PDF library like jsPDF or by generating a server-side PDF.');
            // In production, you would implement actual PDF generation here
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Pie Chart - Attendance Distribution
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent'],
                    datasets: [{
                        data: [<?= $present_count ?>, <?= $absent_count ?>],
                        backgroundColor: ['#10b981', '#ef4444'],
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Bar Chart - Daily Trend
            <?php
            // Prepare daily data for chart
            mysqli_data_seek($daily_summary, 0);
            $dailyDates = [];
            $dailyPresent = [];
            $dailyAbsent = [];

            while ($day = mysqli_fetch_assoc($daily_summary)) {
                $dailyDates[] = date('d M', strtotime($day['attendance_date']));
                $dailyPresent[] = $day['present_count'];
                $dailyAbsent[] = $day['absent_count'];
            }
            ?>

            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($dailyDates) ?>,
                    datasets: [{
                            label: 'Present',
                            data: <?= json_encode($dailyPresent) ?>,
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            borderWidth: 1
                        },
                        {
                            label: 'Absent',
                            data: <?= json_encode($dailyAbsent) ?>,
                            backgroundColor: '#ef4444',
                            borderColor: '#dc2626',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [5, 5]
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>
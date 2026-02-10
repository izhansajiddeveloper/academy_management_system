<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

// Get teacher ID
$user_id = $_SESSION['user_id'];
$teacher_query = "SELECT * FROM teachers WHERE user_id = ?";
$stmt_teacher = $conn->prepare($teacher_query);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    header("Location: ../auth/login.php?error=teacher_not_found");
    exit;
}

$teacher_id = $teacher['id'];

// Get filter parameters
$batch_filter = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$student_filter = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Get teacher's assigned batches
$batches_query = "
SELECT 
    b.id AS batch_id,
    b.batch_name,
    s.skill_name
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE ta.teacher_id = ?
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$assigned_batches = $stmt_batches->get_result();

// Get students for selected batch
$students = [];
if ($batch_filter > 0) {
    $students_query = "
    SELECT 
        s.id,
        s.name,
        s.student_code
    FROM student_enrollments se
    JOIN students s ON se.student_id = s.id
    WHERE se.batch_id = ? AND se.status = 'active'
    ORDER BY s.name
    ";

    $stmt_students = $conn->prepare($students_query);
    $stmt_students->bind_param("i", $batch_filter);
    $stmt_students->execute();
    $students = $stmt_students->get_result();
}

// Build attendance query
$where_conditions = ["ta.teacher_id = ?"];
$params = [$teacher_id];
$param_types = "i";

if ($batch_filter > 0) {
    $where_conditions[] = "sa.batch_id = ?";
    $params[] = $batch_filter;
    $param_types .= "i";
}

if (!empty($date_from)) {
    $where_conditions[] = "sa.attendance_date >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "sa.attendance_date <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

if ($student_filter > 0) {
    $where_conditions[] = "sa.student_id = ?";
    $params[] = $student_filter;
    $param_types .= "i";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get attendance records
$attendance_query = "
SELECT 
    sa.*,
    s.name as student_name,
    s.student_code,
    b.batch_name,
    sk.skill_name,
    se.session_name,
    t.name as teacher_name
FROM student_attendance sa
JOIN students s ON sa.student_id = s.id
JOIN batches b ON sa.batch_id = b.id
JOIN skills sk ON sa.skill_id = sk.id
JOIN sessions se ON sa.session_id = se.id
JOIN teachers t ON sa.marked_by = t.id
JOIN teacher_assignments ta ON sa.batch_id = ta.batch_id AND ta.teacher_id = t.id
$where_clause
ORDER BY sa.attendance_date DESC, s.name
LIMIT 500
";

$stmt_attendance = $conn->prepare($attendance_query);
if (!empty($params)) {
    $stmt_attendance->bind_param($param_types, ...$params);
}
$stmt_attendance->execute();
$attendance_records = $stmt_attendance->get_result();
$total_records = $attendance_records->num_rows;

// Calculate statistics
$stats = [
    'total' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'leave' => 0,
    'attendance_rate' => 0
];

if ($total_records > 0) {
    mysqli_data_seek($attendance_records, 0);
    while ($record = $attendance_records->fetch_assoc()) {
        $stats['total']++;
        $status = $record['attendance_status'];
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
    mysqli_data_seek($attendance_records, 0); // Reset pointer

    if ($stats['total'] > 0) {
        $stats['attendance_rate'] = round(($stats['present'] / $stats['total']) * 100, 1);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Attendance | Teacher Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
            --warning: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-present {
            background: #d1fae5;
            color: #065f46;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-late {
            background: #fef3c7;
            color: #92400e;
        }

        .status-leave {
            background: #dbeafe;
            color: #1e40af;
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .attendance-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .calendar-day.present {
            background: #d1fae5;
            color: #065f46;
        }

        .calendar-day.absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .calendar-day.late {
            background: #fef3c7;
            color: #92400e;
        }

        .calendar-day.leave {
            background: #dbeafe;
            color: #1e40af;
        }

        .calendar-day.empty {
            background: #f3f4f6;
            color: #9ca3af;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">View Attendance</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-history text-blue-500 mr-2"></i>
                            View and analyze attendance records
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="attendance.php" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-clipboard-check mr-2"></i> Mark Attendance
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Filter Attendance Records</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-users mr-1"></i> Batch
                        </label>
                        <select name="batch_id" class="w-full form-input px-4 py-2.5 rounded-lg">
                            <option value="0">All Batches</option>
                            <?php while ($batch = $assigned_batches->fetch_assoc()): ?>
                                <option value="<?php echo $batch['batch_id']; ?>"
                                    <?php echo $batch_filter == $batch['batch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch['batch_name']); ?>
                                    (<?php echo htmlspecialchars($batch['skill_name']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <?php if ($batch_filter > 0 && $students->num_rows > 0): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-graduate mr-1"></i> Student
                            </label>
                            <select name="student_id" class="w-full form-input px-4 py-2.5 rounded-lg">
                                <option value="0">All Students</option>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>"
                                        <?php echo $student_filter == $student['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-graduate mr-1"></i> Student
                            </label>
                            <select name="student_id" class="w-full form-input px-4 py-2.5 rounded-lg" disabled>
                                <option value="0">Select batch first</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i> From Date
                        </label>
                        <input type="date"
                            name="date_from"
                            value="<?php echo $date_from; ?>"
                            class="w-full form-input px-4 py-2.5 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i> To Date
                        </label>
                        <input type="date"
                            name="date_to"
                            value="<?php echo $date_to; ?>"
                            class="w-full form-input px-4 py-2.5 rounded-lg">
                    </div>

                    <div class="md:col-span-4 flex gap-3">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <a href="view_attendance.php"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-medium">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </a>
                        <?php if ($total_records > 0): ?>
                            <button type="button"
                                onclick="exportToExcel()"
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-medium ml-auto">
                                <i class="fas fa-file-excel mr-2"></i> Export to Excel
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <?php if ($total_records > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card card p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Records</h3>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Attendance records found
                        </p>
                    </div>

                    <div class="stat-card card p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Present</h3>
                                <p class="text-3xl font-bold text-green-600"><?php echo $stats['present']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php echo $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0; ?>% of total
                        </p>
                    </div>

                    <div class="stat-card card p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Absent</h3>
                                <p class="text-3xl font-bold text-red-600"><?php echo $stats['absent']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php echo $stats['total'] > 0 ? round(($stats['absent'] / $stats['total']) * 100, 1) : 0; ?>% of total
                        </p>
                    </div>

                    <div class="stat-card card p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Attendance Rate</h3>
                                <p class="text-3xl font-bold text-purple-600"><?php echo $stats['attendance_rate']; ?>%</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Overall attendance percentage
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Attendance Records -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="font-medium text-gray-800">
                        <i class="fas fa-list mr-2"></i>
                        Attendance Records (<?php echo $total_records; ?>)
                    </h3>
                    <?php if ($total_records > 0): ?>
                        <div class="text-sm text-gray-500">
                            Showing <?php echo min($total_records, 500); ?> records
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_records > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Date</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Student</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Batch</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Status</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Percentage</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Remarks</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Marked By</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($record = $attendance_records->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <!-- In the table actions cell -->

                                        <td class="font-medium text-gray-900 p-2">
                                            <?php echo date('M j, Y', strtotime($record['attendance_date'])); ?>
                                        </td>

                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($record['student_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['student_code']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($record['batch_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['skill_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $status_classes = [
                                                'present' => 'status-present',
                                                'absent' => 'status-absent',
                                                'late' => 'status-late',
                                                'leave' => 'status-leave'
                                            ];
                                            $status_icons = [
                                                'present' => 'fa-check',
                                                'absent' => 'fa-times',
                                                'late' => 'fa-clock',
                                                'leave' => 'fa-umbrella-beach'
                                            ];
                                            ?>
                                            <span class="status-badge <?php echo $status_classes[$record['attendance_status']] ?? 'status-absent'; ?>">
                                                <i class="fas <?php echo $status_icons[$record['attendance_status']] ?? 'fa-times'; ?>"></i>
                                                <?php echo ucfirst($record['attendance_status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-green-500 h-2 rounded-full"
                                                        style="width: <?php echo min($record['attendance_percentage'], 100); ?>%">
                                                    </div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php echo $record['attendance_percentage']; ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($record['remarks']); ?>">
                                                <?php echo htmlspecialchars($record['remarks']) ?: '—'; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($record['teacher_name']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo date('h:i A', strtotime($record['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <button onclick="viewAttendanceDetails(<?php echo $record['id']; ?>)"
                                                    class="text-blue-600 hover:text-blue-800 text-sm"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($record['marked_by'] == $teacher_id): ?>
                                                    <button onclick="editAttendance(<?php echo $record['id']; ?>)"
                                                        class="text-green-600 hover:text-green-800 text-sm"
                                                        title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteAttendance(<?php echo $record['id']; ?>)"
                                                        class="text-red-600 hover:text-red-800 text-sm"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-clipboard-list text-gray-300 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Attendance Records Found</h3>
                        <p class="text-gray-500 mb-6">
                            <?php if ($batch_filter > 0 || !empty($date_from) || !empty($date_to)): ?>
                                No attendance records match your current filters.
                            <?php else: ?>
                                No attendance records found for your batches.
                            <?php endif; ?>
                        </p>
                        <?php if ($batch_filter > 0 || !empty($date_from) || !empty($date_to)): ?>
                            <a href="view_attendance.php"
                                class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-redo mr-2"></i> Clear Filters
                            </a>
                        <?php else: ?>
                            <a href="attendance.php"
                                class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-clipboard-check mr-2"></i> Mark Attendance
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Calendar View (Optional) -->
            <?php if ($batch_filter > 0 && $student_filter > 0): ?>
                <?php
                // Get student attendance for calendar view
                $calendar_query = "
                SELECT attendance_date, attendance_status 
                FROM student_attendance 
                WHERE batch_id = ? AND student_id = ? 
                AND attendance_date BETWEEN ? AND ?
                ORDER BY attendance_date
                ";

                $stmt_calendar = $conn->prepare($calendar_query);
                $stmt_calendar->bind_param("iiss", $batch_filter, $student_filter, $date_from, $date_to);
                $stmt_calendar->execute();
                $calendar_data = $stmt_calendar->get_result();

                $attendance_by_date = [];
                while ($row = $calendar_data->fetch_assoc()) {
                    $attendance_by_date[$row['attendance_date']] = $row['attendance_status'];
                }

                // Get student name
                $student_name = '';
                if ($student_filter > 0) {
                    mysqli_data_seek($students, 0);
                    while ($student = $students->fetch_assoc()) {
                        if ($student['id'] == $student_filter) {
                            $student_name = $student['name'];
                            break;
                        }
                    }
                }
                ?>

                <div class="card p-6 mt-8">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Monthly Calendar for <?php echo htmlspecialchars($student_name); ?>
                    </h3>

                    <div class="attendance-calendar mb-4">
                        <!-- Day headers -->
                        <?php $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
                        <?php foreach ($days as $day): ?>
                            <div class="calendar-day empty font-medium">
                                <?php echo $day; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- Calendar days -->
                        <?php
                        $start_date = new DateTime($date_from);
                        $end_date = new DateTime($date_to);
                        $interval = new DateInterval('P1D');
                        $period = new DatePeriod($start_date, $interval, $end_date);

                        // Add empty cells for days before the start date
                        $first_day = (int)$start_date->format('w');
                        for ($i = 0; $i < $first_day; $i++): ?>
                            <div class="calendar-day empty"></div>
                        <?php endfor; ?>

                        <?php foreach ($period as $date):
                            $date_str = $date->format('Y-m-d');
                            $day = $date->format('j');
                            $status = $attendance_by_date[$date_str] ?? null;
                            $class = $status ? "calendar-day $status" : "calendar-day empty";
                        ?>
                            <div class="<?php echo $class; ?>" title="<?php echo $date_str . ($status ? " - " . ucfirst($status) : ''); ?>">
                                <?php echo $day; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-100 rounded"></div>
                            <span>Present</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-red-100 rounded"></div>
                            <span>Absent</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-yellow-100 rounded"></div>
                            <span>Late</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-blue-100 rounded"></div>
                            <span>Leave</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gray-100 rounded"></div>
                            <span>No Record</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Attendance Details Modal -->
    <div id="attendanceDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Attendance Details</h3>
                    <button onclick="hideAttendanceDetails()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                <div id="attendanceDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Export to Excel
        function exportToExcel() {
            // Get filter parameters
            const params = new URLSearchParams(window.location.search);

            // Redirect to export script
            window.location.href = 'export_attendance.php?' + params.toString();
        }

        // View attendance details
        function viewAttendanceDetails(attendanceId) {
            // Show loading
            document.getElementById('attendanceDetailsContent').innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-3xl mb-4"></i>
                    <p class="text-gray-600">Loading attendance details...</p>
                </div>
            `;

            // Show modal
            document.getElementById('attendanceDetailsModal').classList.remove('hidden');
            document.getElementById('attendanceDetailsModal').classList.add('flex');

            // Load details via AJAX
            fetch(`get_attendance_details.php?id=${attendanceId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    document.getElementById('attendanceDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('attendanceDetailsContent').innerHTML = `
                        <div class="text-center py-12">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                            <p class="text-gray-600">Failed to load attendance details.</p>
                        </div>
                    `;
                });
        }

        // Hide details modal
        function hideAttendanceDetails() {
            document.getElementById('attendanceDetailsModal').classList.add('hidden');
            document.getElementById('attendanceDetailsModal').classList.remove('flex');
        }

        // Edit attendance
        function editAttendance(attendanceId) {
            if (confirm('Edit this attendance record?')) {
                window.location.href = `attendance.php?edit=${attendanceId}`;
            }
        }

        // Delete attendance
        function deleteAttendance(attendanceId) {
            if (confirm('Are you sure you want to delete this attendance record? This action cannot be undone.')) {
                fetch(`delete_attendance.php?id=${attendanceId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Attendance record deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the record.');
                    });
            }
        }

        // Close modal when clicking outside
        document.getElementById('attendanceDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideAttendanceDetails();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideAttendanceDetails();
            }
        });

        // Update student dropdown when batch changes
        document.querySelector('select[name="batch_id"]')?.addEventListener('change', function() {
            if (this.value > 0) {
                // Reload page with new batch
                const form = this.closest('form');
                form.querySelector('select[name="student_id"]').value = '0';
                form.submit();
            }
        });
    </script>
</body>

</html>
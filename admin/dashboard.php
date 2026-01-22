<?php
require_once "../includes/auth_check.php";
require_once "../config/db.php";
include "../includes/navbar.php";

// Add helper function for time display
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if (isset($diff->$k) && $diff->$k > 0) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }


    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Fetch real statistics from database

// Total Students (active status)
$student_query = "SELECT COUNT(*) as count FROM students WHERE status='active'";
$student_result = mysqli_query($conn, $student_query);
$total_students = mysqli_fetch_assoc($student_result)['count'];

// Total Teachers (user_type_id = 2 and active)
$teacher_query = "SELECT COUNT(*) as count FROM users WHERE user_type_id=2 AND status='active'";
$teacher_result = mysqli_query($conn, $teacher_query);
$total_teachers = mysqli_fetch_assoc($teacher_result)['count'];

// Active Batches
$batch_query = "SELECT COUNT(*) as count FROM batches WHERE status='active'";
$batch_result = mysqli_query($conn, $batch_query);
$active_batches = mysqli_fetch_assoc($batch_result)['count'];

// Calculate Pending Fees (Total fees - Total paid)
$total_fees_query = "
    SELECT SUM(fs.total_fee) as total_fees 
    FROM student_enrollments e
    JOIN fee_structures fs ON e.skill_id = fs.skill_id AND e.session_id = fs.session_id
    WHERE e.status='active' AND fs.status='active'
";
$total_fees_result = mysqli_query($conn, $total_fees_query);
$total_fees = mysqli_fetch_assoc($total_fees_result)['total_fees'] ?? 0;

$total_paid_query = "SELECT SUM(amount_paid) as total_paid FROM fee_collections WHERE status='active'";
$total_paid_result = mysqli_query($conn, $total_paid_query);
$total_paid = mysqli_fetch_assoc($total_paid_result)['total_paid'] ?? 0;

$pending_fees = $total_fees - $total_paid;

// Monthly Revenue (current month)
$current_month = date('Y-m');
$monthly_revenue_query = "
    SELECT SUM(amount_paid) as monthly_revenue 
    FROM fee_collections 
    WHERE status='active' AND DATE_FORMAT(payment_date, '%Y-%m') = '$current_month'
";
$monthly_revenue_result = mysqli_query($conn, $monthly_revenue_query);
$monthly_revenue = mysqli_fetch_assoc($monthly_revenue_result)['monthly_revenue'] ?? 0;

// Completion Rate (estimate based on payments)
$completion_query = "
    SELECT 
        COUNT(DISTINCT e.student_id, e.skill_id) as total_enrollments,
        COUNT(DISTINCT fc.student_id, fc.skill_id) as completed_enrollments
    FROM student_enrollments e
    LEFT JOIN (
        SELECT DISTINCT student_id, skill_id 
        FROM fee_collections 
        GROUP BY student_id, skill_id 
        HAVING SUM(amount_paid) >= (SELECT total_fee FROM fee_structures fs WHERE fs.skill_id = skill_id AND fs.session_id = session_id LIMIT 1)
    ) fc ON e.student_id = fc.student_id AND e.skill_id = fc.skill_id
    WHERE e.status='active'
";
$completion_result = mysqli_query($conn, $completion_query);
$completion_data = mysqli_fetch_assoc($completion_result);
$completion_rate = $completion_data['total_enrollments'] > 0 ?
    round(($completion_data['completed_enrollments'] / $completion_data['total_enrollments']) * 100) : 0;

// Total Sessions
$session_query = "SELECT COUNT(*) as count FROM sessions";
$session_result = mysqli_query($conn, $session_query);
$total_sessions = mysqli_fetch_assoc($session_result)['count'];

// Upcoming Sessions (sessions with active status)
$upcoming_query = "SELECT COUNT(*) as count FROM sessions WHERE status='active'";
$upcoming_result = mysqli_query($conn, $upcoming_query);
$upcoming_sessions = mysqli_fetch_assoc($upcoming_result)['count'];

// SYSTEM NOTIFICATIONS - Only for activities
// New Enrollments (last 7 days)
$new_enrollments_query = "
    SELECT COUNT(*) as count 
    FROM student_enrollments 
    WHERE status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$new_enrollments_result = mysqli_query($conn, $new_enrollments_query);
$new_enrollments = mysqli_fetch_assoc($new_enrollments_result)['count'];

// New Teachers (last 7 days)
$new_teachers_query = "
    SELECT COUNT(*) as count 
    FROM users 
    WHERE user_type_id=2 AND status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$new_teachers_result = mysqli_query($conn, $new_teachers_query);
$new_teachers = mysqli_fetch_assoc($new_teachers_result)['count'];

// New Batches (last 7 days)
$new_batches_query = "
    SELECT COUNT(*) as count 
    FROM batches 
    WHERE status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$new_batches_result = mysqli_query($conn, $new_batches_query);
$new_batches = mysqli_fetch_assoc($new_batches_result)['count'];

// New Skills (last 7 days)
$new_skills_query = "
    SELECT COUNT(*) as count 
    FROM skills 
    WHERE status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$new_skills_result = mysqli_query($conn, $new_skills_query);
$new_skills = mysqli_fetch_assoc($new_skills_result)['count'];

// New Sessions (last 7 days)
$new_sessions_query = "
    SELECT COUNT(*) as count 
    FROM sessions 
    WHERE status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$new_sessions_result = mysqli_query($conn, $new_sessions_query);
$new_sessions = mysqli_fetch_assoc($new_sessions_result)['count'];

// Pending Fee Payments (unpaid fees)
$pending_payments_query = "
    SELECT COUNT(*) as count
    FROM (
        SELECT e.student_id, e.skill_id, COALESCE(SUM(fc.amount_paid),0) as total_paid
        FROM student_enrollments e
        LEFT JOIN fee_collections fc 
            ON e.student_id = fc.student_id 
            AND e.skill_id = fc.skill_id
        WHERE e.status = 'active'
        GROUP BY e.student_id, e.skill_id
        HAVING total_paid < (
            SELECT fs.total_fee 
            FROM fee_structures fs 
            WHERE fs.skill_id = e.skill_id 
            LIMIT 1
        )
    ) AS pending
";

$pending_payments_result = mysqli_query($conn, $pending_payments_query);
$pending_payments = mysqli_fetch_assoc($pending_payments_result)['count'] ?? 0;

// Calculate total system notifications
$system_notifications = $new_enrollments + $new_teachers + $new_batches + $new_skills + $new_sessions + $pending_payments;

// Get system activities for dropdown
$system_activities_query = "
    (SELECT 'student_enrollment' as type, 'New Student Enrollment' as title, 
            CONCAT(s.name, ' enrolled in ', sk.skill_name) as description,
            e.created_at
     FROM student_enrollments e
     JOIN students s ON e.student_id = s.id
     JOIN skills sk ON e.skill_id = sk.id
     WHERE e.status='active' 
     ORDER BY e.created_at DESC LIMIT 3)
    
    UNION ALL
    
    (SELECT 'teacher' as type, 'New Teacher Added' as title,
            CONCAT('Teacher: ', u.username) as description,
            u.created_at
     FROM users u
     WHERE u.user_type_id = 2 AND u.status = 'active'
     ORDER BY u.created_at DESC LIMIT 2)
    
    UNION ALL
    
    (SELECT 'batch' as type, 'New Batch Created' as title,
            CONCAT('Batch: ', b.batch_name, ' for ', sk.skill_name) as description,
            b.created_at
     FROM batches b
     JOIN skills sk ON b.skill_id = sk.id
     WHERE b.status='active'
     ORDER BY b.created_at DESC LIMIT 2)
    
    UNION ALL
    
    (SELECT 'payment' as type, 'Fee Payment Pending' as title,
            CONCAT(s.name, ' - ', sk.skill_name) as description,
            MAX(fc.created_at) as created_at
     FROM student_enrollments e
     JOIN students s ON e.student_id = s.id
     JOIN skills sk ON e.skill_id = sk.id
     LEFT JOIN fee_collections fc 
        ON e.student_id = fc.student_id 
        AND e.skill_id = fc.skill_id
     WHERE e.status='active'
     GROUP BY e.student_id, e.skill_id
     HAVING COALESCE(SUM(fc.amount_paid),0) < (
        SELECT total_fee 
        FROM fee_structures fs 
        WHERE fs.skill_id = e.skill_id 
        LIMIT 1
     )
     ORDER BY created_at DESC LIMIT 2)
    
    ORDER BY created_at DESC LIMIT 8
";

$system_activities_result = mysqli_query($conn, $system_activities_query);

// ANNOUNCEMENTS - Separate system
// Get announcements count for the current user (admin)
$user_role = 'admin'; // Since this is admin dashboard
$announcements_query = "SELECT COUNT(*) as count FROM announcements 
                       WHERE status = 'active' 
                       AND (target_role = 'all' OR target_role = ?)
                       AND (start_date IS NULL OR start_date <= NOW())
                       AND (end_date IS NULL OR end_date >= NOW())";

$stmt = mysqli_prepare($conn, $announcements_query);
mysqli_stmt_bind_param($stmt, "s", $user_role);
mysqli_stmt_execute($stmt);
$ann_result = mysqli_stmt_get_result($stmt);
$announcements_count = mysqli_fetch_assoc($ann_result)['count'];

// Get announcements for dropdown (last 5)
$announcements_list_query = "SELECT * FROM announcements 
                           WHERE status = 'active' 
                           AND (target_role = 'all' OR target_role = ?)
                           AND (start_date IS NULL OR start_date <= NOW())
                           AND (end_date IS NULL OR end_date >= NOW())
                           ORDER BY created_at DESC LIMIT 5";

$stmt2 = mysqli_prepare($conn, $announcements_list_query);
mysqli_stmt_bind_param($stmt2, "s", $user_role);
mysqli_stmt_execute($stmt2);
$announcements_list = mysqli_stmt_get_result($stmt2);

// Attendance Rate (placeholder - you would need an attendance table)
$attendance_rate = 92; // Default value

// Get monthly revenue data for chart (last 6 months)
$revenue_chart_query = "
    SELECT 
        DATE_FORMAT(payment_date, '%b') as month,
        SUM(amount_paid) as revenue
    FROM fee_collections 
    WHERE status='active' AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m'), DATE_FORMAT(payment_date, '%b')
    ORDER BY DATE_FORMAT(payment_date, '%Y-%m')
    LIMIT 6
";
$revenue_chart_result = mysqli_query($conn, $revenue_chart_query);
$revenue_data = [];
while ($row = mysqli_fetch_assoc($revenue_chart_result)) {
    $revenue_data[] = $row;
}

// Get student distribution by skills for chart
$distribution_query = "
    SELECT 
        s.skill_name,
        COUNT(DISTINCT e.student_id) as student_count
    FROM student_enrollments e
    JOIN skills s ON e.skill_id = s.id
    WHERE e.status='active' AND s.status='active'
    GROUP BY s.id, s.skill_name
    ORDER BY student_count DESC
    LIMIT 4
";
$distribution_result = mysqli_query($conn, $distribution_query);
$distribution_data = [];
while ($row = mysqli_fetch_assoc($distribution_result)) {
    $distribution_data[] = $row;
}

// Get today's sessions from batches
$today_sessions_query = "
    SELECT 
        b.start_time,
        s.skill_name as course,
        b.batch_name,
        CONCAT('Room ', FLOOR(RAND() * 20) + 1) as room
    FROM batches b
    JOIN skills s ON b.skill_id = s.id
    WHERE b.status='active'
    ORDER BY b.start_time
    LIMIT 4
";
$today_sessions_result = mysqli_query($conn, $today_sessions_query);
$today_sessions = [];
while ($row = mysqli_fetch_assoc($today_sessions_result)) {
    $today_sessions[] = [
        'time' => date('h:i A', strtotime($row['start_time'])),
        'course' => $row['course'],
        'batch' => $row['batch_name'],
        'room' => $row['room']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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

        .notification-dropdown {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .announcement-dropdown {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .priority-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-urgent {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .priority-high {
            background-color: #ffedd5;
            color: #ea580c;
        }

        .priority-medium {
            background-color: #fef3c7;
            color: #d97706;
        }

        .priority-low {
            background-color: #d1fae5;
            color: #059669;
        }

        .activity-item {
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background-color: #f8fafc;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .activity-student {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .activity-teacher {
            background-color: #f3e8ff;
            color: #7c3aed;
        }

        .activity-batch {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .activity-payment {
            background-color: #fef3c7;
            color: #d97706;
        }

        .activity-skill {
            background-color: #fce7f3;
            color: #be185d;
        }

        .activity-session {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
                    <div class="flex items-center gap-3">
                        <!-- Announcements Icon - SEPARATE -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors border relative">
                                <i class="fas fa-bullhorn"></i>

                                <?php if ($announcements_count > 0): ?>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-blue-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                        <?php echo $announcements_count > 9 ? '9+' : $announcements_count; ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Announcements Dropdown -->
                            <div x-show="open" x-transition
                                class="absolute right-0 mt-2 w-96 bg-white rounded-lg announcement-dropdown z-50 overflow-hidden">
                                <div class="p-4 border-b border-gray-100">
                                    <div class="flex justify-between items-center">
                                        <h3 class="font-semibold text-gray-800">
                                            <i class="fas fa-bullhorn mr-2 text-blue-500"></i> Announcements
                                        </h3>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                            <?php echo $announcements_count; ?> active
                                        </span>
                                    </div>
                                </div>

                                <div class="overflow-y-auto max-h-96">
                                    <?php if ($announcements_count > 0): ?>
                                        <?php mysqli_data_seek($announcements_list, 0); // Reset pointer 
                                        ?>
                                        <?php while ($announcement = mysqli_fetch_assoc($announcements_list)):
                                            $time_ago = time_elapsed_string($announcement['created_at']);
                                            $priority_class = $announcement['priority'];
                                        ?>
                                            <div class="activity-item p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                                                onclick="window.location.href='announcements.php'">
                                                <div class="flex justify-between items-start mb-2">
                                                    <h5 class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                                    <span class="priority-badge priority-<?php echo $priority_class; ?>">
                                                        <?php echo $priority_class; ?>
                                                    </span>
                                                </div>
                                                <p class="text-xs text-gray-600 mb-3 line-clamp-2">
                                                    <?php echo htmlspecialchars($announcement['message']); ?>
                                                </p>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-xs text-gray-500">
                                                        <i class="far fa-clock mr-1"></i><?php echo $time_ago; ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        <?php echo ucfirst($announcement['target_role']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="p-8 text-center">
                                            <i class="fas fa-bullhorn text-gray-300 text-4xl mb-3"></i>
                                            <p class="text-sm font-medium text-gray-500 mb-2">No announcements yet</p>
                                            <p class="text-xs text-gray-400 mb-4">Create your first announcement</p>
                                            <a href="announcements.php" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors inline-block">
                                                <i class="fas fa-plus mr-1"></i> Create Announcement
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- View All Button -->
                                    <div class="p-4 bg-gray-50 border-t border-gray-100">
                                        <a href="announcements.php"
                                            class="block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                            <i class="fas fa-external-link-alt mr-2"></i> View All Announcements
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Notification Bell - SEPARATE -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors border relative">
                                <i class="fas fa-bell"></i>

                                <?php if ($system_notifications > 0): ?>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                        <?php echo $system_notifications > 9 ? '9+' : $system_notifications; ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- System Notifications Dropdown -->
                            <div x-show="open" x-transition
                                class="absolute right-0 mt-2 w-96 bg-white rounded-lg notification-dropdown z-50 overflow-hidden">
                                <div class="p-4 border-b border-gray-100">
                                    <div class="flex justify-between items-center">
                                        <h3 class="font-semibold text-gray-800">
                                            <i class="fas fa-bell mr-2 text-red-500"></i> System Activities
                                        </h3>
                                        <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
                                            <?php echo $system_notifications; ?> new
                                        </span>
                                    </div>
                                </div>

                                <!-- Button to view all activities -->
                                <div class="p-4">
                                    <a href="/academy_management_system/admin/activity_logs.php"
                                        class="block w-full text-center bg-blue-500 hover:bg-blue-600 text-white font-medium px-4 py-2 rounded-lg transition-colors">
                                        View All Activities
                                    </a>
                                </div>


                                <div class="overflow-y-auto max-h-96">
                                    <?php if (mysqli_num_rows($system_activities_result) > 0): ?>
                                        <?php while ($activity = mysqli_fetch_assoc($system_activities_result)):
                                            $time_ago = time_elapsed_string($activity['created_at']);
                                            $type_class = 'activity-' . $activity['type'];
                                            $icon = '';

                                            switch ($activity['type']) {
                                                case 'student_enrollment':
                                                    $icon = 'fas fa-user-graduate';
                                                    break;
                                                case 'teacher':
                                                    $icon = 'fas fa-chalkboard-teacher';
                                                    break;
                                                case 'batch':
                                                    $icon = 'fas fa-layer-group';
                                                    break;
                                                case 'payment':
                                                    $icon = 'fas fa-money-bill-wave';
                                                    break;
                                                case 'skill':
                                                    $icon = 'fas fa-graduation-cap';
                                                    break;
                                                case 'session':
                                                    $icon = 'fas fa-calendar-alt';
                                                    break;
                                            }
                                        ?>
                                            <div class="activity-item p-4 border-b border-gray-100 hover:bg-gray-50">
                                                <div class="flex items-start">
                                                    <div class="activity-icon <?php echo $type_class; ?> mr-3">
                                                        <i class="<?php echo $icon; ?>"></i>
                                                    </div>
                                                    <div class="flex-1">
                                                        <h5 class="font-medium text-gray-800 text-sm mb-1"><?php echo htmlspecialchars($activity['title']); ?></h5>
                                                        <p class="text-xs text-gray-600 mb-2"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                        <span class="text-xs text-gray-500">
                                                            <i class="far fa-clock mr-1"></i><?php echo $time_ago; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>

                                        <!-- System Statistics Summary -->
                                        <div class="p-4 bg-gray-50 border-t border-gray-100">
                                            <h4 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wider">Recent Activities Summary</h4>
                                            <div class="grid grid-cols-2 gap-2">
                                                <?php if ($new_enrollments > 0): ?>
                                                    <div class="flex items-center text-xs">
                                                        <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-user-graduate text-blue-600 text-xs"></i>
                                                        </div>
                                                        <span class="text-gray-700"><?php echo $new_enrollments; ?> new students</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($new_teachers > 0): ?>
                                                    <div class="flex items-center text-xs">
                                                        <div class="w-6 h-6 bg-purple-100 rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-chalkboard-teacher text-purple-600 text-xs"></i>
                                                        </div>
                                                        <span class="text-gray-700"><?php echo $new_teachers; ?> new teachers</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($new_batches > 0): ?>
                                                    <div class="flex items-center text-xs">
                                                        <div class="w-6 h-6 bg-green-100 rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-layer-group text-green-600 text-xs"></i>
                                                        </div>
                                                        <span class="text-gray-700"><?php echo $new_batches; ?> new batches</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($new_skills > 0): ?>
                                                    <div class="flex items-center text-xs">
                                                        <div class="w-6 h-6 bg-pink-100 rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-graduation-cap text-pink-600 text-xs"></i>
                                                        </div>
                                                        <span class="text-gray-700"><?php echo $new_skills; ?> new skills</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($new_sessions > 0): ?>
                                                    <div class="flex items-center text-xs">
                                                        <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-calendar-alt text-blue-600 text-xs"></i>
                                                        </div>
                                                        <span class="text-gray-700"><?php echo $new_sessions; ?> new sessions</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($pending_payments > 0): ?>
                                                    <div class="flex items-center text-xs">
                                                        <div class="w-6 h-6 bg-yellow-100 rounded flex items-center justify-center mr-2">
                                                            <i class="fas fa-money-bill-wave text-yellow-600 text-xs"></i>
                                                        </div>
                                                        <span class="text-gray-700"><?php echo $pending_payments; ?> pending payments</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-8 text-center">
                                            <i class="fas fa-bell-slash text-gray-300 text-4xl mb-3"></i>
                                            <p class="text-sm font-medium text-gray-500 mb-1">No recent activities</p>
                                            <p class="text-xs text-gray-400">System activities will appear here</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile -->
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
                            +<?php echo $new_enrollments; ?> new
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Students</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo number_format($total_students); ?></h3>
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
                            Rs<?php echo number_format($monthly_revenue); ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Monthly Revenue</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Rs<?php echo number_format($monthly_revenue); ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: 78%"></div>
                            </div>
                            <span class="ml-2">Current Month</span>
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
                            <?php echo $active_batches; ?> Active
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Active Batches</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $active_batches; ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: 60%"></div>
                            </div>
                            <span class="ml-2">Running</span>
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
                            <?php echo $completion_rate; ?>%
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Completion Rate</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $completion_rate; ?>%</h3>
                        <div class="relative w-12 h-12 ml-auto">
                            <svg width="48" height="48" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f3f4f6" stroke-width="8" />
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f59e0b" stroke-width="8" stroke-linecap="round"
                                    stroke-dasharray="283" stroke-dashoffset="<?php echo 283 - (283 * $completion_rate / 100); ?>" />
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
                            +<?php echo $new_enrollments; ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">New Enrollments</p>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $new_enrollments; ?></h3>
                        <div class="flex items-center text-xs text-gray-500">
                            <span class="text-green-600 font-medium">Last 7 days</span>
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
                            <?php echo count($today_sessions); ?> Active
                        </span>
                    </div>
                    <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                        <?php if (count($today_sessions) > 0): ?>
                            <?php foreach ($today_sessions as $session): ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="text-center bg-white p-2 rounded-lg border min-w-[60px]">
                                        <p class="text-xs text-gray-500">Starts</p>
                                        <p class="text-sm font-semibold text-gray-800"><?php echo $session['time']; ?></p>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h4 class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($session['course']); ?></h4>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($session['batch']); ?></p>
                                    </div>
                                    <div class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                        <?php echo $session['room']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-center bg-white p-2 rounded-lg border min-w-[60px]">
                                    <p class="text-xs text-gray-500">-</p>
                                    <p class="text-sm font-semibold text-gray-800">-</p>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h4 class="font-medium text-gray-800 text-sm">No sessions today</h4>
                                    <p class="text-xs text-gray-500">Check schedule</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Activities Panel -->
                <div class="chart-container">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recent System Activities</h3>
                        <span class="text-sm px-3 py-1 bg-red-50 text-red-700 rounded-full">
                            <?php echo $system_notifications ?? 0; ?> new
                        </span>
                    </div>

                    <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                        <?php
                        // Re-execute system activities query for the panel
                        $panel_activities_result = mysqli_query($conn, $system_activities_query);

                        if ($panel_activities_result && mysqli_num_rows($panel_activities_result) > 0):
                            $count = 0;

                            while (($activity = mysqli_fetch_assoc($panel_activities_result)) && $count < 4):

                                if (!$activity) continue;

                                $time_ago = time_elapsed_string($activity['created_at'] ?? null);
                                $type_class = 'activity-' . ($activity['type'] ?? 'default');
                                $icon = 'fas fa-info-circle';

                                switch ($activity['type'] ?? '') {
                                    case 'student_enrollment':
                                        $icon = 'fas fa-user-graduate';
                                        break;
                                    case 'teacher':
                                        $icon = 'fas fa-chalkboard-teacher';
                                        break;
                                    case 'batch':
                                        $icon = 'fas fa-layer-group';
                                        break;
                                    case 'payment':
                                        $icon = 'fas fa-money-bill-wave';
                                        break;
                                }

                                $count++;
                        ?>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="activity-icon <?php echo $type_class; ?> mr-3">
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>

                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800 text-sm mb-1">
                                            <?php echo htmlspecialchars($activity['title'] ?? 'Activity'); ?>
                                        </h4>
                                        <p class="text-xs text-gray-600 line-clamp-1">
                                            <?php echo htmlspecialchars($activity['description'] ?? 'No description'); ?>
                                        </p>
                                    </div>

                                    <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                        <?php echo $time_ago; ?>
                                    </span>
                                </div>
                            <?php endwhile; ?>

                            <!-- View More -->
                            <a href="/academy_management_system/admin/activity_logs.php"
                                class="block text-center text-sm text-blue-600 hover:text-blue-800 mt-2 p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="fas fa-external-link-alt mr-1"></i> View All Activities
                            </a>

                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg">
                                <i class="fas fa-bell-slash text-gray-300 text-3xl mb-3"></i>
                                <p class="text-sm font-medium text-gray-500 mb-1">No recent activities</p>
                                <p class="text-xs text-gray-400">System activities will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Quick Actions -->
                <div class="chart-container">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="announcements.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-blue-50 transition-colors">
                                <i class="fas fa-bullhorn text-gray-600 group-hover:text-blue-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Create Announcement</p>
                            <p class="text-xs text-gray-500 mt-1">Broadcast message</p>
                        </a>

                        <a href="/academy_management_system/admin/users/add_student.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-green-50 transition-colors">
                                <i class="fas fa-user-plus text-gray-600 group-hover:text-green-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Add Student</p>
                            <p class="text-xs text-gray-500 mt-1">New admission</p>
                        </a>

                        <a href="/academy_management_system/admin/fees/fee_collection.php"
                            class="group p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-center">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-purple-50 transition-colors">
                                <i class="fas fa-money-bill-wave text-gray-600 group-hover:text-purple-600"></i>
                            </div>
                            <p class="font-medium text-gray-800 text-sm">Collect Fees</p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo number_format($pending_fees); ?> pending</p>
                        </a>

                        <a href="/academy_management_system/admin/reports/student_report.php"
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
                    Students: <?php echo $total_students; ?>
                </span>
                <span class="text-sm text-gray-500">
                    <i class="fas fa-bell mr-1"></i>
                    Activities: <?php echo $system_notifications; ?>
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
            // Revenue Chart with real data
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');

            // Prepare revenue chart data from PHP
            const revenueMonths = <?php echo json_encode(array_column($revenue_data, 'month') ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']); ?>;
            const revenueAmounts = <?php echo json_encode(array_column($revenue_data, 'revenue') ?: [0, 0, 0, 0, 0, 0]); ?>;

            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: revenueMonths,
                    datasets: [{
                        label: 'Revenue',
                        data: revenueAmounts,
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
                            beginAtZero: true,
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

            // Distribution Chart with real data
            const distributionCtx = document.getElementById('distributionChart').getContext('2d');

            // Prepare distribution chart data from PHP
            const skillNames = <?php echo json_encode(array_column($distribution_data, 'skill_name') ?: ['Web Dev', 'Data Science', 'UI/UX', 'Marketing']); ?>;
            const studentCounts = <?php echo json_encode(array_column($distribution_data, 'student_count') ?: [1, 1, 1, 1]); ?>;

            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: skillNames,
                    datasets: [{
                        data: studentCounts,
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

            // Attendance Chart (placeholder data - needs attendance table)
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

            // Clear notification badges when clicked
            const systemNotificationButton = document.querySelectorAll('[x-data] button')[1];
            const systemNotificationBadge = systemNotificationButton.querySelector('.absolute');

            if (systemNotificationBadge) {
                systemNotificationButton.addEventListener('click', function() {
                    // Fade out animation
                    systemNotificationBadge.style.transition = 'opacity 0.3s ease';
                    systemNotificationBadge.style.opacity = '0';

                    setTimeout(() => {
                        // You can add AJAX call here to mark notifications as read in database
                        // fetch('mark_activities_read.php', { method: 'POST' });

                        // Remove badge after animation
                        systemNotificationBadge.remove();
                    }, 300);
                });
            }

            const announcementButton = document.querySelectorAll('[x-data] button')[0];
            const announcementBadge = announcementButton.querySelector('.absolute');

            if (announcementBadge) {
                announcementButton.addEventListener('click', function() {
                    // Fade out animation
                    announcementBadge.style.transition = 'opacity 0.3s ease';
                    announcementBadge.style.opacity = '0';

                    setTimeout(() => {
                        // You can add AJAX call here to mark announcements as read
                        // fetch('mark_announcements_read.php', { method: 'POST' });

                        // Remove badge after animation
                        announcementBadge.remove();
                    }, 300);
                });
            }
        });
    </script>

</body>

</html>
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// =============== REMOVE AJAX HANDLING FROM HERE ===============
// All AJAX is now handled in the separate file

// Check and update expired announcements
$expiry_check_query = "
    UPDATE announcements 
    SET status = 'inactive', 
        is_expired = 1 
    WHERE status = 'active' 
        AND (
            (end_date IS NOT NULL AND end_date < NOW()) OR
            (end_date IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
        )";
mysqli_query($conn, $expiry_check_query);

// Handle form submission for new announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $target_role = $_POST['target_role'];
    $session_id = $_POST['session_id'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $created_by = $_SESSION['user_id'];

    // Handle duration
    $duration = $_POST['duration'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d H:i:s');

    // Calculate end_date based on duration
    $end_date = null;
    if ($duration == 'custom') {
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d H:i:s', strtotime('+7 days'));
    } else if ($duration != 'permanent') {
        $end_date = date('Y-m-d H:i:s', strtotime("+$duration days", strtotime($start_date)));
    }

    if (empty($title) || empty($message)) {
        $error_message = "Title and message are required.";
    } else {
        $sql = "INSERT INTO announcements (title, message, target_role, session_id, priority, start_date, end_date, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssissssi", $title, $message, $target_role, $session_id, $priority, $start_date, $end_date, $status, $created_by);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Announcement added successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error_message = "Error adding announcement: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle announcement deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM announcements WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Announcement deleted successfully!";
    } else {
        $error_message = "Error deleting announcement: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $sql = "UPDATE announcements SET status = IF(status='active', 'inactive', 'active') WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Fetch all announcements with expiration info
$announcements_query = "
    SELECT 
        a.*, 
        s.session_name, 
        u.username AS created_by_name,
        CASE 
            WHEN a.is_expired = 1 THEN 'expired'
            WHEN a.end_date IS NOT NULL AND a.end_date < NOW() THEN 'expired'
            WHEN a.end_date IS NULL AND a.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'expired'
            ELSE a.status
        END as display_status,
        CASE 
            WHEN a.end_date IS NOT NULL THEN TIMESTAMPDIFF(HOUR, NOW(), a.end_date)
            ELSE NULL 
        END as hours_remaining
    FROM announcements a
    LEFT JOIN sessions s ON a.session_id = s.id
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY 
        CASE WHEN a.priority = 'urgent' THEN 1 
             WHEN a.priority = 'high' THEN 2 
             WHEN a.priority = 'medium' THEN 3 
             ELSE 4 END,
        a.created_at DESC
";

$announcements_result = mysqli_query($conn, $announcements_query);

// Fetch active sessions for dropdown
$sessions_query = "SELECT id, session_name FROM sessions WHERE status='active' ORDER BY session_name DESC";
$sessions_result = mysqli_query($conn, $sessions_query);

// Get statistics
$total_announcements = mysqli_num_rows($announcements_result);

$active_stats = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM announcements 
    WHERE status='active' 
    AND (end_date IS NULL OR end_date > NOW())
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$active_announcements = mysqli_fetch_assoc($active_stats)['count'];

$expired_stats = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM announcements 
    WHERE (status='inactive' AND is_expired = 1) 
    OR (end_date IS NOT NULL AND end_date < NOW())
    OR (end_date IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
");
$expired_announcements = mysqli_fetch_assoc($expired_stats)['count'];

// Get recent announcements (last 7 days)
$recent_announcements = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM announcements 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (end_date IS NULL OR end_date > NOW())
");
$recent_announcements = mysqli_fetch_assoc($recent_announcements)['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Announcements | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .priority-low {
            border-left: 4px solid #10b981;
        }

        .priority-medium {
            border-left: 4px solid #f59e0b;
        }

        .priority-high {
            border-left: 4px solid #ef4444;
        }

        .priority-urgent {
            border-left: 4px solid #dc2626;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .status-expired {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 24px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .announcement-card {
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
        }

        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .preview-area {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            min-height: 100px;
            max-height: 200px;
            overflow-y: auto;
        }

        .expiring-soon {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #fbbf24;
        }

        .expired-card {
            opacity: 0.7;
            background-color: #fef2f2;
        }

        .badge-green {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-yellow {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-orange {
            background-color: #ffedd5;
            color: #9a3412;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-red {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-gray {
            background-color: #f3f4f6;
            color: #6b7280;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .time-remaining {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
            display: inline-block;
            margin-top: 4px;
        }

        .blink {
            animation: blink 2s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .ajax-error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex">
        <?php require_once './includes/sidebar.php'; ?>

        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manage Announcements</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-bullhorn text-blue-500 mr-1"></i>
                        Create and manage system announcements (Auto-expires after 7 days)
                    </p>
                </div>
                <div>
                    <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i> New Announcement
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-bullhorn text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Announcements</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_announcements; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Active</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $active_announcements; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-100 p-3 rounded-lg">
                            <i class="fas fa-clock text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Expired</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $expired_announcements; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-calendar-week text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Last 7 Days</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $recent_announcements; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Success</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p><?php echo $success_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p><?php echo $error_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Announcements List -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title & Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Remaining</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                                <?php mysqli_data_seek($announcements_result, 0); ?>
                                <?php while ($announcement = mysqli_fetch_assoc($announcements_result)):
                                    $priority_class = 'priority-' . $announcement['priority'];
                                    $created_date = date('M d, Y', strtotime($announcement['created_at']));
                                    $created_time = date('h:i A', strtotime($announcement['created_at']));

                                    // Determine status and styling
                                    $is_expiring = false;
                                    $is_expired = false;
                                    $remaining_text = '';
                                    $status_class = 'status-' . $announcement['display_status'];

                                    if ($announcement['display_status'] == 'expired') {
                                        $is_expired = true;
                                        $remaining_text = 'Expired';
                                    } elseif ($announcement['hours_remaining'] !== null) {
                                        if ($announcement['hours_remaining'] <= 24 && $announcement['hours_remaining'] > 0) {
                                            $is_expiring = true;
                                            $remaining_text = $announcement['hours_remaining'] . 'h remaining';
                                        } elseif ($announcement['hours_remaining'] > 24) {
                                            $days = floor($announcement['hours_remaining'] / 24);
                                            $remaining_text = $days . 'd remaining';
                                        } elseif ($announcement['hours_remaining'] <= 0) {
                                            $is_expired = true;
                                            $remaining_text = 'Expired';
                                        }
                                    } else {
                                        // No end date, check if older than 7 days
                                        $days_old = floor((time() - strtotime($announcement['created_at'])) / (60 * 60 * 24));
                                        if ($days_old >= 7) {
                                            $is_expired = true;
                                            $remaining_text = 'Expired (7+ days)';
                                        } else {
                                            $remaining_text = (7 - $days_old) . 'd left';
                                        }
                                    }
                                ?>
                                    <tr class="announcement-card <?php echo $priority_class; ?> <?php echo $is_expired ? 'expired-card' : ''; ?> <?php echo $is_expiring ? 'expiring-soon' : ''; ?> hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1 truncate max-w-xs"><?php echo htmlspecialchars(substr($announcement['message'], 0, 100)) . (strlen($announcement['message']) > 100 ? '...' : ''); ?></div>
                                            <div class="text-xs text-gray-400 mt-1">
                                                <i class="far fa-calendar mr-1"></i> Created: <?php echo $created_date; ?>
                                            </div>
                                            <?php if ($announcement['end_date']): ?>
                                                <div class="text-xs text-gray-400">
                                                    <i class="far fa-clock mr-1"></i> Ends: <?php echo date('M d, Y', strtotime($announcement['end_date'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-400">
                                                    <i class="fas fa-infinity mr-1"></i> Auto-expires in 7 days
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900"><?php echo ucfirst($announcement['target_role']); ?></span>
                                            <?php if ($announcement['session_name']): ?>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($announcement['session_name']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                <?php echo $announcement['priority'] == 'urgent' ? 'bg-red-100 text-red-800' : ($announcement['priority'] == 'high' ? 'bg-orange-100 text-orange-800' : ($announcement['priority'] == 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-green-100 text-green-800')); ?>">
                                                <?php echo ucfirst($announcement['priority']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($announcement['display_status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm <?php echo $is_expiring ? 'text-amber-600 font-medium' : ($is_expired ? 'text-red-600' : 'text-gray-700'); ?>">
                                                <?php if ($is_expiring): ?>
                                                    <i class="fas fa-exclamation-triangle mr-1 blink"></i>
                                                <?php endif; ?>
                                                <?php echo $remaining_text; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewAnnouncement(<?php echo $announcement['id']; ?>)"
                                                class="text-blue-600 hover:text-blue-900 mr-3" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!$is_expired): ?>
                                                <a href="?toggle_status=<?php echo $announcement['id']; ?>"
                                                    class="text-yellow-600 hover:text-yellow-900 mr-3"
                                                    title="<?php echo $announcement['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo $announcement['id']; ?>"
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Are you sure you want to delete this announcement?')"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-400">
                                            <i class="fas fa-bullhorn text-4xl mb-3"></i>
                                            <p class="text-lg font-medium">No announcements found</p>
                                            <p class="text-sm mt-1">Click "New Announcement" to create your first announcement</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Create New Announcement</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="announcementForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" required maxlength="200"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter announcement title"
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea name="message" rows="5" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter announcement message (max 1000 characters)"
                                maxlength="1000"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            <div class="text-xs text-gray-500 mt-1" id="charCount">0/1000 characters</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Target Role *</label>
                                <select name="target_role" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all" selected>All Users</option>
                                    <option value="admin">Admin Only</option>
                                    <option value="teacher">Teachers</option>
                                    <option value="student">Students</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority *</label>
                                <select name="priority" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Session (Optional)</label>
                                <select name="session_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Sessions</option>
                                    <?php mysqli_data_seek($sessions_result, 0); ?>
                                    <?php while ($session = mysqli_fetch_assoc($sessions_result)): ?>
                                        <option value="<?php echo $session['id']; ?>"><?php echo htmlspecialchars($session['session_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Duration *</label>
                                <select name="duration" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="toggleCustomDate()">
                                    <option value="1">1 Day</option>
                                    <option value="2">2 Days</option>
                                    <option value="3">3 Days</option>
                                    <option value="5" selected>5 Days</option>
                                    <option value="7">7 Days (Max)</option>
                                    <option value="custom">Custom Date Range</option>
                                    <option value="permanent">Permanent (Auto-expires in 7 days)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4" id="customDateRange" style="display: none;">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="datetime-local" name="start_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="datetime-local" name="end_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    min="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- Preview Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Preview</label>
                            <div id="previewArea" class="preview-area">
                                <div id="previewTitle" class="font-bold text-lg mb-2 text-gray-800"></div>
                                <div id="previewMessage" class="text-gray-700 mb-3"></div>
                                <div id="previewMeta" class="text-xs text-gray-500"></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Announcements auto-expire after 7 days if no end date is set
                                </div>
                                <button type="submit" name="add_announcement"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-md transition-colors">
                                    <i class="fas fa-paper-plane mr-2"></i> Publish Announcement
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Announcement Modal -->
    <div id="viewModal" class="modal bg-black/50 backdrop-blur-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="viewTitle" class="text-lg font-semibold text-gray-800">
                    <span id="viewTitleText">Announcement Details</span>
                    <span id="viewLoading" class="loading ml-2" style="display: none;"></span>
                </h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="viewContent">
                    <div class="text-center py-8">
                        <div class="loading mx-auto"></div>
                        <p class="text-gray-500 mt-3">Loading announcement details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal() {
            document.getElementById('announcementModal').style.display = 'block';
            updatePreview(); // Initial preview
        }

        function closeModal() {
            document.getElementById('announcementModal').style.display = 'none';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModal();
                closeViewModal();
            }
        }

        // Character count for message
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.querySelector('textarea[name="message"]');
            const charCount = document.getElementById('charCount');

            if (messageInput && charCount) {
                messageInput.addEventListener('input', function() {
                    const length = this.value.length;
                    charCount.textContent = `${length}/1000 characters`;

                    if (length > 900) {
                        charCount.className = 'text-xs text-red-500 mt-1';
                    } else if (length > 700) {
                        charCount.className = 'text-xs text-yellow-500 mt-1';
                    } else {
                        charCount.className = 'text-xs text-gray-500 mt-1';
                    }

                    updatePreview();
                });

                // Initial count
                charCount.textContent = `${messageInput.value.length}/1000 characters`;
            }

            // Initialize preview
            updatePreview();
        });

        // Toggle custom date range
        function toggleCustomDate() {
            const durationSelect = document.querySelector('select[name="duration"]');
            const customDateRange = document.getElementById('customDateRange');

            if (durationSelect.value === 'custom') {
                customDateRange.style.display = 'grid';

                // Set default end date to 7 days from now
                const startDateInput = document.querySelector('input[name="start_date"]');
                const endDateInput = document.querySelector('input[name="end_date"]');

                if (startDateInput && endDateInput) {
                    const startDate = startDateInput.value ? new Date(startDateInput.value) : new Date();
                    const endDate = new Date(startDate);
                    endDate.setDate(endDate.getDate() + 7);

                    // Format to YYYY-MM-DDTHH:MM
                    const endDateStr = endDate.toISOString().slice(0, 16);
                    endDateInput.value = endDateStr;
                    endDateInput.min = startDateInput.value || new Date().toISOString().slice(0, 16);
                }
            } else {
                customDateRange.style.display = 'none';
            }
            updatePreview();
        }

        // Preview functionality
        function updatePreview() {
            const titleInput = document.querySelector('input[name="title"]');
            const messageInput = document.querySelector('textarea[name="message"]');
            const targetSelect = document.querySelector('select[name="target_role"]');
            const prioritySelect = document.querySelector('select[name="priority"]');
            const durationSelect = document.querySelector('select[name="duration"]');

            if (!titleInput || !messageInput || !targetSelect || !prioritySelect) return;

            const title = titleInput.value || 'Title will appear here';
            const message = messageInput.value || 'Message will appear here...';
            const target = targetSelect.value || 'all';
            const priority = prioritySelect.value || 'medium';
            const duration = durationSelect.value || '5';

            let durationText = '';
            if (duration === 'custom') {
                durationText = 'Custom date range';
            } else if (duration === 'permanent') {
                durationText = 'Permanent (auto-expires in 7 days)';
            } else {
                durationText = `${duration} day${duration != '1' ? 's' : ''}`;
            }

            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewMessage').textContent = message;

            let meta = '';
            meta += `📢 For: ${target.charAt(0).toUpperCase() + target.slice(1)}`;
            meta += ` | ⚡ Priority: ${priority.charAt(0).toUpperCase() + priority.slice(1)}`;
            meta += ` | ⏱️ Duration: ${durationText}`;
            meta += ` | 📅 Created: ${new Date().toLocaleDateString()}`;

            document.getElementById('previewMeta').textContent = meta;
        }

        // Add event listeners for preview updates
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = [
                'input[name="title"]',
                'textarea[name="message"]',
                'select[name="target_role"]',
                'select[name="priority"]',
                'select[name="duration"]',
                'select[name="status"]'
            ];

            inputs.forEach(selector => {
                const element = document.querySelector(selector);
                if (element) {
                    element.addEventListener('input', updatePreview);
                    element.addEventListener('change', updatePreview);
                }
            });
        });

        // View announcement details using separate AJAX file
        // View announcement details using separate AJAX file
        async function viewAnnouncement(id) {
            try {
                // Show modal with loading state
                document.getElementById('viewTitleText').textContent = 'Loading...';
                document.getElementById('viewLoading').style.display = 'inline-block';
                document.getElementById('viewContent').innerHTML = `
            <div class="text-center py-8">
                <div class="loading mx-auto"></div>
                <p class="text-gray-500 mt-3">Loading announcement details...</p>
            </div>
        `;

                document.getElementById('viewModal').style.display = 'block';

                // Use the correct path - check if ajax folder exists in admin directory
                const response = await fetch(`./ajax/view_announcement.php?id=${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // First check if we got HTML instead of JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // Try to get the response as text to see what we got
                    const text = await response.text();
                    console.error('Received non-JSON response:', text.substring(0, 200));
                    throw new Error(`Server returned HTML instead of JSON. Check the AJAX file path and PHP errors.`);
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    const ann = result.announcement;

                    // Prepare HTML content
                    let html = `
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="${ann.priority_class} px-3 py-1 rounded-full text-xs font-medium">${ann.priority}</span>
                        ${ann.status_badge}
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-gray-800 mb-2">Announcement Message:</h4>
                        <div class="text-gray-700">${ann.message}</div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-3">
                            <div>
                                <span class="block text-sm font-medium text-gray-600">Target Audience:</span>
                                <span class="text-sm text-gray-900 font-medium">${ann.target_role}</span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-600">Session:</span>
                                <span class="text-sm text-gray-900">${ann.session_name}</span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-600">Created By:</span>
                                <span class="text-sm text-gray-900">${ann.created_by}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="block text-sm font-medium text-gray-600">Created:</span>
                                <span class="text-sm text-gray-900">${ann.created_at}</span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-600">Start Date:</span>
                                <span class="text-sm text-gray-900">${ann.start_date}</span>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-600">End Date:</span>
                                <span class="text-sm text-gray-900">${ann.end_date}</span>
                            </div>
                        </div>
                    </div>
            `;

                    if (ann.remaining) {
                        const remainingClass = ann.is_expired ? 'bg-red-50 border-red-200 text-red-600' : 'bg-blue-50 border-blue-200 text-blue-600';
                        html += `
                    <div class="p-3 rounded-lg mb-4 border ${remainingClass}">
                        <span class="block text-sm font-medium mb-1">Time Remaining:</span>
                        <span class="text-sm font-medium">${ann.remaining}</span>
                    </div>
                `;
                    }

                    html += `
                    <div class="text-xs text-gray-500 mt-6 pt-4 border-t border-gray-200">
                        <i class="fas fa-info-circle mr-1"></i>
                        Note: Announcements auto-expire after 7 days if no specific end date is set.
                    </div>
                </div>
            `;

                    // Update modal content
                    document.getElementById('viewTitleText').textContent = ann.title;
                    document.getElementById('viewLoading').style.display = 'none';
                    document.getElementById('viewContent').innerHTML = html;

                } else {
                    document.getElementById('viewTitleText').textContent = 'Error';
                    document.getElementById('viewLoading').style.display = 'none';
                    document.getElementById('viewContent').innerHTML = `
                <div class="ajax-error">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                        <div>
                            <h4 class="font-medium text-red-800">Error Loading Announcement</h4>
                            <p class="text-sm text-red-700 mt-1">${result.message || 'Failed to load announcement details.'}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button onclick="viewAnnouncement(${id})" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                            <i class="fas fa-redo mr-1"></i> Try Again
                        </button>
                    </div>
                </div>
            `;
                }
            } catch (error) {
                console.error('Error fetching announcement:', error);
                document.getElementById('viewTitleText').textContent = 'Error';
                document.getElementById('viewLoading').style.display = 'none';
                document.getElementById('viewContent').innerHTML = `
            <div class="ajax-error">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    <div>
                        <h4 class="font-medium text-red-800">Network Error</h4>
                        <p class="text-sm text-red-700 mt-1">${error.message}</p>
                        <p class="text-xs text-gray-600 mt-2">URL: ./ajax/view_announcement.php?id=${id}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button onclick="viewAnnouncement(${id})" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        <i class="fas fa-redo mr-1"></i> Try Again
                    </button>
                    <button onclick="window.open('./ajax/view_announcement.php?id=${id}', '_blank')" 
                        class="ml-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                        <i class="fas fa-external-link-alt mr-1"></i> Test URL
                    </button>
                </div>
            </div>
        `;
            }
        }

        // Auto-refresh page for expiring announcements
        function checkExpiringAnnouncements() {
            const expiringElements = document.querySelectorAll('.expiring-soon');
            if (expiringElements.length > 0) {
                // Refresh page every 5 minutes if there are expiring announcements
                setTimeout(() => {
                    window.location.reload();
                }, 5 * 60 * 1000); // 5 minutes
            }
        }

        // Auto-close success message after 5 seconds
        <?php if ($success_message): ?>
            setTimeout(function() {
                const successMsg = document.querySelector('.bg-green-50');
                if (successMsg) {
                    successMsg.style.opacity = '0';
                    setTimeout(() => successMsg.remove(), 300);
                }
            }, 5000);
        <?php endif; ?>

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkExpiringAnnouncements();

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Escape key to close modals
                if (e.key === 'Escape') {
                    closeModal();
                    closeViewModal();
                }
                // Ctrl+N to open new announcement modal
                if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    openModal();
                }
            });

            // Update custom date range min date when start date changes
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');

            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function() {
                    endDateInput.min = this.value;
                });
            }
        });
    </script>
    </script>

</body>

</html>
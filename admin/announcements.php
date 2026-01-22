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

// Handle form submission for new announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $target_role = $_POST['target_role'];
    $session_id = $_POST['session_id'];
    $priority = $_POST['priority'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = $_POST['status'];
    $created_by = $_SESSION['user_id'];

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

// Fetch all announcements
$announcements_query = "
    SELECT 
        a.*, 
        s.session_name, 
        u.username AS created_by_name
    FROM announcements a
    LEFT JOIN sessions s ON a.session_id = s.id
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
";

$announcements_result = mysqli_query($conn, $announcements_query);


// Fetch active sessions for dropdown
$sessions_query = "SELECT id, session_name FROM sessions WHERE status='active' ORDER BY session_name DESC";
$sessions_result = mysqli_query($conn, $sessions_query);

// Get statistics
$total_announcements = mysqli_num_rows($announcements_result);
$active_announcements = mysqli_query($conn, "SELECT COUNT(*) as count FROM announcements WHERE status='active'");
$active_announcements = mysqli_fetch_assoc($active_announcements)['count'];

// Get recent announcements (last 7 days)
$recent_announcements = mysqli_query($conn, "SELECT COUNT(*) as count FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
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
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
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
            max-width: 500px;
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
                        Create and manage system announcements
                    </p>
                </div>
                <div>
                    <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i> New Announcement
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                            <h3 class="text-sm font-medium text-gray-500">Active Announcements</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo $active_announcements; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-clock text-purple-600 text-xl"></i>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                                <?php while ($announcement = mysqli_fetch_assoc($announcements_result)):
                                    $priority_class = 'priority-' . $announcement['priority'];
                                    $created_date = date('M d, Y', strtotime($announcement['created_at']));
                                    $created_time = date('h:i A', strtotime($announcement['created_at']));
                                ?>
                                    <tr class="announcement-card <?php echo $priority_class; ?> hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1 truncate max-w-xs"><?php echo htmlspecialchars(substr($announcement['message'], 0, 100)) . (strlen($announcement['message']) > 100 ? '...' : ''); ?></div>
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
                                            <span class="status-badge <?php echo $announcement['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($announcement['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $created_date; ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $created_time; ?></div>
                                            <?php if ($announcement['created_by_name']): ?>
                                                <div class="text-xs text-gray-500">By: <?php echo htmlspecialchars($announcement['created_by_name']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewAnnouncement(<?php echo $announcement['id']; ?>)"
                                                class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="?toggle_status=<?php echo $announcement['id']; ?>"
                                                class="text-yellow-600 hover:text-yellow-900 mr-3"
                                                title="<?php echo $announcement['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-power-off"></i>
                                            </a>
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
                            <input type="text" name="title" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter announcement title"
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea name="message" rows="4" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Enter announcement message"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Target Role</label>
                                <select name="target_role"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all">All Users</option>
                                    <option value="admin">Admin Only</option>
                                    <option value="teacher">Teachers</option>
                                    <option value="student">Students</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select name="priority"
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
                                    <?php while ($session = mysqli_fetch_assoc($sessions_result)): ?>
                                        <option value="<?php echo $session['id']; ?>"><?php echo htmlspecialchars($session['session_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date (Optional)</label>
                                <input type="datetime-local" name="start_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                                <input type="datetime-local" name="end_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Preview</label>
                            <div id="previewArea" class="preview-area">
                                <div id="previewTitle" class="font-bold text-lg mb-2"></div>
                                <div id="previewMessage" class="text-gray-700"></div>
                                <div id="previewMeta" class="text-xs text-gray-500 mt-3"></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit" name="add_announcement"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-md transition-colors">
                                <i class="fas fa-paper-plane mr-2"></i> Publish Announcement
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Announcement Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="viewTitle" class="text-lg font-semibold text-gray-800"></h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="viewMessage" class="text-gray-700 mb-6"></div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Target:</span>
                        <span id="viewTarget" class="ml-2"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Priority:</span>
                        <span id="viewPriority" class="ml-2"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Status:</span>
                        <span id="viewStatus" class="ml-2"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Session:</span>
                        <span id="viewSession" class="ml-2"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Created:</span>
                        <span id="viewCreated" class="ml-2"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Created By:</span>
                        <span id="viewCreatedBy" class="ml-2"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal() {
            document.getElementById('announcementModal').style.display = 'block';
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

        // Preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.querySelector('input[name="title"]');
            const messageInput = document.querySelector('textarea[name="message"]');
            const targetSelect = document.querySelector('select[name="target_role"]');
            const prioritySelect = document.querySelector('select[name="priority"]');

            function updatePreview() {
                const title = titleInput?.value || 'Title will appear here';
                const message = messageInput?.value || 'Message will appear here';
                const target = targetSelect?.value || 'all';
                const priority = prioritySelect?.value || 'medium';

                document.getElementById('previewTitle').textContent = title;
                document.getElementById('previewMessage').textContent = message;

                let meta = '';
                meta += `Target: ${target.charAt(0).toUpperCase() + target.slice(1)}`;
                meta += ` | Priority: ${priority.charAt(0).toUpperCase() + priority.slice(1)}`;
                meta += ` | Time: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;

                document.getElementById('previewMeta').textContent = meta;
            }

            if (titleInput) titleInput.addEventListener('input', updatePreview);
            if (messageInput) messageInput.addEventListener('input', updatePreview);
            if (targetSelect) targetSelect.addEventListener('change', updatePreview);
            if (prioritySelect) prioritySelect.addEventListener('change', updatePreview);

            // Initial preview
            updatePreview();
        });

        // View announcement details
        async function viewAnnouncement(id) {
            try {
                const response = await fetch(`get_announcement.php?id=${id}`);
                const announcement = await response.json();

                document.getElementById('viewTitle').textContent = announcement.title;
                document.getElementById('viewMessage').textContent = announcement.message;
                document.getElementById('viewTarget').textContent = announcement.target_role.charAt(0).toUpperCase() + announcement.target_role.slice(1);
                document.getElementById('viewPriority').textContent = announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1);
                document.getElementById('viewStatus').textContent = announcement.status.charAt(0).toUpperCase() + announcement.status.slice(1);
                document.getElementById('viewSession').textContent = announcement.session_name || 'All Sessions';
                document.getElementById('viewCreated').textContent = announcement.created_at;
                document.getElementById('viewCreatedBy').textContent = announcement.created_by_name || 'System';

                document.getElementById('viewModal').style.display = 'block';
            } catch (error) {
                console.error('Error fetching announcement:', error);
                alert('Error loading announcement details.');
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
    </script>

</body>

</html>
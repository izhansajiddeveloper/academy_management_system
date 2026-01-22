<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
require_once __DIR__ . '/../includes/auth_check.php';

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_date = $_GET['date'] ?? '';
$filter_user = $_GET['user'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build WHERE conditions
$conditions = ["1=1"];
$params = [];
$types = '';

if ($filter_type != 'all') {
    $conditions[] = "activity_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if ($filter_date) {
    $conditions[] = "DATE(created_at) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

if ($search) {
    $conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$where_clause = implode(" AND ", $conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM activities WHERE $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
if ($params) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_activities = mysqli_fetch_assoc($count_result)['total'];

// Pagination
$per_page = 20;
$total_pages = ceil($total_activities / $per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get activities with pagination
$query = "SELECT 
            a.*, 
            u.username,
            u.user_type_id
          FROM activities a
          LEFT JOIN users u ON a.user_id = u.id
          WHERE $where_clause
          ORDER BY a.created_at DESC
          LIMIT ? OFFSET ?";


// Add pagination parameters
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get activity statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        activity_type,
        COUNT(*) as count,
        MAX(created_at) as last_activity
    FROM activities
    GROUP BY activity_type
    ORDER BY count DESC
");
$activity_stats = [];
while ($stat = mysqli_fetch_assoc($stats_query)) {
    $activity_stats[] = $stat;
}

// Get unique activity types for filter
$types_query = mysqli_query($conn, "SELECT DISTINCT activity_type FROM activities ORDER BY activity_type");
$activity_types = [];
while ($type = mysqli_fetch_assoc($types_query)) {
    $activity_types[] = $type['activity_type'];
}

// Get recent users for filter
$users_query = mysqli_query($conn, "
    SELECT DISTINCT u.id, u.username
    FROM activities a
    JOIN users u ON a.user_id = u.id
    ORDER BY u.username
");

$users_list = [];
while ($user = mysqli_fetch_assoc($users_query)) {
    $users_list[] = $user;
}

// Get today's activity count
$today_query = mysqli_query($conn, "
    SELECT COUNT(*) as count 
    FROM activities 
    WHERE DATE(created_at) = CURDATE()
");
$today_count = mysqli_fetch_assoc($today_query)['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Activity Feed | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .activity-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .timeline-line {
            position: relative;
        }

        .timeline-line::before {
            content: '';
            position: absolute;
            left: 24px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #e5e7eb, #e5e7eb);
            z-index: 1;
        }

        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 3px solid white;
            position: absolute;
            left: 20px;
            top: 20px;
            z-index: 2;
        }

        .filter-btn {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .filter-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .filter-btn:not(.active) {
            background: white;
            color: #6b7280;
            border-color: #e5e7eb;
        }

        .filter-btn:not(.active):hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-new {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-update {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-system {
            background: #f3e8ff;
            color: #5b21b6;
        }

        .empty-state {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>


    <div class="flex">
        <!-- SIDEBAR -->
        <?php require_once './includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Activity Feed</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-history mr-1"></i>
                        Track all system activities and updates in real-time
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-blue-50 px-4 py-2 rounded-lg">
                        <div class="text-sm text-blue-600">Today's Activities</div>
                        <div class="text-2xl font-bold text-blue-700"><?= $today_count ?></div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Activities</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_activities) ?></h3>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Active Today</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= $today_count ?></h3>
                            <p class="text-xs text-gray-500 mt-1">+<?= rand(2, 10) ?>% from yesterday</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-bolt text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Most Active Type</p>
                            <h3 class="text-lg font-bold text-gray-800">
                                <?= !empty($activity_stats) ? ucfirst(str_replace('_', ' ', $activity_stats[0]['activity_type'])) : 'N/A' ?>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1"><?= !empty($activity_stats) ? $activity_stats[0]['count'] . ' activities' : '' ?></p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <i class="fas fa-star text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Last Updated</p>
                            <h3 class="text-xl font-bold text-gray-800">Just now</h3>
                            <p class="text-xs text-gray-500 mt-1">Auto-refresh every 30s</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Activities</h3>

                    <!-- Search Box -->
                    <div class="relative md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <form method="GET" class="flex gap-2">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search activities..."
                                class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="this.form.submit()">
                            <input type="hidden" name="type" value="<?= $filter_type ?>">
                            <input type="hidden" name="date" value="<?= $filter_date ?>">
                            <input type="hidden" name="user" value="<?= $filter_user ?>">
                        </form>
                    </div>
                </div>

                <!-- Filter Options -->
                <div class="space-y-4">
                    <!-- Activity Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                        <div class="flex flex-wrap gap-2">
                            <a href="?type=all&date=<?= $filter_date ?>&user=<?= $filter_user ?>&search=<?= $search ?>"
                                class="filter-btn <?= $filter_type == 'all' ? 'active' : '' ?>">
                                <i class="fas fa-layer-group mr-1"></i> All Activities
                            </a>
                            <?php foreach ($activity_types as $type):
                                $type_name = ucfirst(str_replace('_', ' ', $type));
                                $type_icon = getIconForType($type);
                            ?>
                                <a href="?type=<?= $type ?>&date=<?= $filter_date ?>&user=<?= $filter_user ?>&search=<?= $search ?>"
                                    class="filter-btn <?= $filter_type == $type ? 'active' : '' ?>">
                                    <i class="fas fa-<?= $type_icon ?> mr-1"></i> <?= $type_name ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Date</label>
                        <div class="flex flex-wrap gap-2">
                            <form method="GET" class="flex items-center gap-2">
                                <input type="hidden" name="type" value="<?= $filter_type ?>">
                                <input type="hidden" name="user" value="<?= $filter_user ?>">
                                <input type="hidden" name="search" value="<?= $search ?>">
                                <input type="date" name="date" value="<?= $filter_date ?>"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="this.form.submit()">
                                <?php if ($filter_date): ?>
                                    <a href="?type=<?= $filter_type ?>&user=<?= $filter_user ?>&search=<?= $search ?>"
                                        class="text-sm text-red-600 hover:text-red-800 flex items-center gap-1">
                                        <i class="fas fa-times"></i> Clear Date
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Recent Activities
                            <span class="text-sm font-normal text-gray-500 ml-2">
                                (<?= $total_activities ?> total)
                            </span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <button onclick="refreshActivities()"
                                class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <span class="text-xs text-gray-400">|</span>
                            <div class="text-xs text-gray-500">
                                Showing <?= min($per_page, $total_activities) ?> of <?= $total_activities ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activities List -->
                <div class="p-6">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="timeline-line">
                            <?php
                            $counter = 0;
                            $last_date = null;
                            while ($activity = mysqli_fetch_assoc($result)):
                                $counter++;
                                $current_date = date('Y-m-d', strtotime($activity['created_at']));
                                $time_ago = time_elapsed_string($activity['created_at']);
                                $activity_color = getColorForType($activity['activity_type']);
                                $activity_icon = getIconForType($activity['activity_type']);
                                $badge_class = getBadgeClass($activity['activity_type']);

                                // Show date header if date changed
                                if ($current_date != $last_date):
                                    $date_label = date('Y-m-d') == $current_date ? 'Today' : (date('Y-m-d', strtotime('-1 day')) == $current_date ? 'Yesterday' :
                                        date('F j, Y', strtotime($current_date)));
                            ?>
                                    <div class="mb-6">
                                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-800 text-sm font-medium">
                                            <i class="fas fa-calendar-day mr-2"></i>
                                            <?= $date_label ?>
                                        </div>
                                    </div>
                                <?php
                                endif;
                                $last_date = $current_date;
                                ?>

                                <!-- Activity Item -->
                                <div class="activity-card mb-6 bg-white rounded-lg border border-gray-200 p-5 shadow-sm fade-in relative">
                                    <!-- Timeline dot -->
                                    <div class="timeline-dot" style="background-color: <?= $activity_color ?>"></div>

                                    <div class="flex items-start">
                                        <!-- Icon -->
                                        <div class="activity-icon mr-4" style="background-color: <?= $activity_color ?>20; color: <?= $activity_color ?>">
                                            <i class="fas fa-<?= $activity_icon ?>"></i>
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-semibold text-gray-800 text-lg mb-1">
                                                        <?= htmlspecialchars($activity['title']) ?>
                                                    </h4>
                                                    <?php if ($activity['description']): ?>
                                                        <p class="text-gray-600 mb-2">
                                                            <?= htmlspecialchars($activity['description']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <!-- Metadata -->
                                                    <?php if ($activity['metadata']):
                                                        $metadata = json_decode($activity['metadata'], true);
                                                        if (is_array($metadata) && !empty($metadata)):
                                                    ?>
                                                            <div class="flex flex-wrap gap-2 mt-2">
                                                                <?php foreach ($metadata as $key => $value):
                                                                    if (!empty($value) && $key != 'id'):
                                                                ?>
                                                                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                                                            <?= htmlspecialchars($key) ?>: <?= htmlspecialchars($value) ?>
                                                                        </span>
                                                                <?php
                                                                    endif;
                                                                endforeach; ?>
                                                            </div>
                                                    <?php
                                                        endif;
                                                    endif; ?>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <!-- Badge -->
                                                    <span class="badge <?= $badge_class ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $activity['activity_type'])) ?>
                                                    </span>

                                                    <!-- Time -->
                                                    <div class="text-right">
                                                        <div class="text-sm text-gray-500" title="<?= date('M j, Y h:i A', strtotime($activity['created_at'])) ?>">
                                                            <i class="far fa-clock mr-1"></i> <?= $time_ago ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- User Info -->
                                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                                        <i class="fas fa-user text-gray-600 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-700">
                                                            <?= isset($activity['username']) ? htmlspecialchars($activity['username']) : 'System' ?>
                                                        </div>

                                                        <div class="text-xs text-gray-500">
                                                            <?= $activity['username'] ?? 'Automated' ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex items-center gap-2">
                                                    <?php if ($activity['related_id'] && $activity['related_type']): ?>
                                                        <a href="#"
                                                            class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                                            <i class="fas fa-external-link-alt"></i> View Details
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="empty-state rounded-xl p-12 text-center">
                            <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-history text-gray-400 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No activities found</h3>
                            <p class="text-gray-500 mb-6 max-w-md mx-auto">
                                <?php if ($filter_type != 'all' || $filter_date || $search): ?>
                                    Try changing your filters or search criteria
                                <?php else: ?>
                                    When activities occur in the system, they will appear here
                                <?php endif; ?>
                            </p>
                            <?php if ($filter_type != 'all' || $filter_date || $search): ?>
                                <a href="activities.php"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-times"></i> Clear All Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    Page <?= $current_page ?> of <?= $total_pages ?>
                                </div>
                                <div class="flex gap-2">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?page=<?= $current_page - 1 ?>&type=<?= $filter_type ?>&date=<?= $filter_date ?>&user=<?= $filter_user ?>&search=<?= $search ?>"
                                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                            <i class="fas fa-chevron-left mr-1"></i> Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $start_page + 4);

                                    for ($page = $start_page; $page <= $end_page; $page++):
                                    ?>
                                        <a href="?page=<?= $page ?>&type=<?= $filter_type ?>&date=<?= $filter_date ?>&user=<?= $filter_user ?>&search=<?= $search ?>"
                                            class="px-4 py-2 border rounded-lg text-sm <?= $page == $current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                                            <?= $page ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?page=<?= $current_page + 1 ?>&type=<?= $filter_type ?>&date=<?= $filter_date ?>&user=<?= $filter_user ?>&search=<?= $search ?>"
                                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                            Next <i class="fas fa-chevron-right ml-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Refresh activities
        function refreshActivities() {
            location.reload();
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshActivities, 30000);

        // Add animation to new activities
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to all activity cards
            const cards = document.querySelectorAll('.activity-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.05}s`;
            });

            // Highlight new activities
            const currentTime = new Date().getTime();
            cards.forEach(card => {
                const timeText = card.querySelector('.text-gray-500').textContent;
                if (timeText.includes('minute') || timeText.includes('just now') || timeText.includes('seconds')) {
                    card.style.borderLeftColor = '#10b981';
                    card.style.background = 'linear-gradient(to right, #f0fdf4 0%, white 100%)';
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + R to refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshActivities();
            }
            // / to focus search
            if (e.key === '/' && !e.ctrlKey) {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
        });
    </script>

</body>

</html>

<?php
// Helper functions
function getIconForType($type)
{
    $icons = [
        'teacher_added' => 'user-plus',
        'batch_created' => 'calendar-plus',
        'student_enrolled' => 'user-check',
        'login' => 'log-in',
        'payment_received' => 'credit-card',
        'assignment_submitted' => 'file-text',
        'course_created' => 'book-open',
        'exam_scheduled' => 'calendar',
        'student_promoted' => 'trending-up',
        'system' => 'cog',
        'update' => 'edit',
        'delete' => 'trash',
        'create' => 'plus-circle'
    ];

    return $icons[$type] ?? 'bell';
}

function getColorForType($type)
{
    $colors = [
        'teacher_added' => '#10b981',
        'batch_created' => '#3b82f6',
        'student_enrolled' => '#8b5cf6',
        'login' => '#059669',
        'payment_received' => '#16a34a',
        'assignment_submitted' => '#9333ea',
        'course_created' => '#ea580c',
        'exam_scheduled' => '#dc2626',
        'student_promoted' => '#7c3aed',
        'system' => '#6b7280',
        'update' => '#f59e0b',
        'delete' => '#ef4444',
        'create' => '#3b82f6'
    ];

    return $colors[$type] ?? '#6b7280';
}

function getBadgeClass($type)
{
    if (strpos($type, 'added') !== false || strpos($type, 'created') !== false || strpos($type, 'enrolled') !== false) {
        return 'badge-new';
    } elseif (strpos($type, 'update') !== false || strpos($type, 'edit') !== false) {
        return 'badge-update';
    } else {
        return 'badge-system';
    }
}

// Time ago function
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d - ($w * 7);

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
        $value = ($k === 'w') ? $w : (($k === 'd') ? $d : $diff->$k);
        if ($value) {
            $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

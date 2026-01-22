<?php
// AJAX endpoint for viewing announcement details
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin (for security)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if it's an AJAX request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid announcement ID']);
    exit;
}

$id = intval($_GET['id']);

try {
    // Fetch announcement details - FIXED: Only select columns that exist in users table
    $query = "
        SELECT 
            a.*, 
            s.session_name, 
            u.username AS created_by_name,
            u.email
        FROM announcements a
        LEFT JOIN sessions s ON a.session_id = s.id
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Database prepare statement failed: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Database query execution failed: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $announcement = mysqli_fetch_assoc($result);

    if (!$announcement) {
        echo json_encode(['success' => false, 'message' => 'Announcement not found']);
        exit;
    }

    // Format dates
    $created_at = date('M d, Y h:i A', strtotime($announcement['created_at']));
    $start_date = $announcement['start_date'] ? date('M d, Y h:i A', strtotime($announcement['start_date'])) : 'Immediately';
    $end_date = $announcement['end_date'] ? date('M d, Y h:i A', strtotime($announcement['end_date'])) : 'Never (Auto-expires in 7 days)';

    // Calculate remaining time
    $remaining = '';
    $is_expired = false;

    if ($announcement['end_date'] && $announcement['end_date'] != '0000-00-00 00:00:00') {
        $now = new DateTime();
        $end = new DateTime($announcement['end_date']);
        if ($end > $now) {
            $interval = $now->diff($end);
            if ($interval->days > 0) {
                $remaining = $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' remaining';
            } else if ($interval->h > 0) {
                $remaining = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' remaining';
            } else {
                $remaining = 'Less than 1 hour remaining';
            }
        } else {
            $remaining = 'Expired';
            $is_expired = true;
        }
    } else {
        // Check if older than 7 days
        $days_old = floor((time() - strtotime($announcement['created_at'])) / (60 * 60 * 24));
        if ($days_old >= 7) {
            $remaining = 'Expired (7+ days old)';
            $is_expired = true;
        } else {
            $remaining = (7 - $days_old) . ' day' . ((7 - $days_old) > 1 ? 's' : '') . ' left (auto-expire)';
        }
    }

    // Prepare badge class
    $priority_class = '';
    switch ($announcement['priority']) {
        case 'urgent':
            $priority_class = 'badge-red';
            break;
        case 'high':
            $priority_class = 'badge-orange';
            break;
        case 'medium':
            $priority_class = 'badge-yellow';
            break;
        default:
            $priority_class = 'badge-green';
    }

    // Prepare status badge
    $status_badge = '';
    if ($is_expired || $announcement['is_expired'] == 1) {
        $status_badge = '<span class="badge-red">Expired</span>';
    } else if ($announcement['status'] == 'active') {
        $status_badge = '<span class="badge-success">Active</span>';
    } else {
        $status_badge = '<span class="badge-gray">Inactive</span>';
    }

    // Get creator name - use username since we don't have name field
    $creator_display = htmlspecialchars($announcement['created_by_name'] ?: 'System');
    if ($announcement['email']) {
        $creator_display .= ' (' . htmlspecialchars($announcement['email']) . ')';
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'announcement' => [
            'id' => $announcement['id'],
            'title' => htmlspecialchars($announcement['title']),
            'message' => nl2br(htmlspecialchars($announcement['message'])),
            'target_role' => ucfirst($announcement['target_role']),
            'session_name' => $announcement['session_name'] ?: 'All Sessions',
            'priority' => ucfirst($announcement['priority']),
            'priority_class' => $priority_class,
            'status' => ucfirst($announcement['status']),
            'status_badge' => $status_badge,
            'created_at' => $created_at,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'remaining' => $remaining,
            'remaining_class' => $is_expired ? 'text-red-600' : 'text-blue-600',
            'created_by' => $creator_display,
            'is_expired' => $is_expired || $announcement['is_expired'] == 1
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

exit;

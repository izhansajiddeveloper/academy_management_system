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
$skill_filter = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Active';

// Get teacher's assigned batches
$batches_query = "
SELECT 
    b.id AS batch_id,
    b.batch_name,
    s.skill_name,
    s.id as skill_id,
    se.session_name,
    COUNT(DISTINCT ss.id) as syllabus_count
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
JOIN sessions se ON b.session_id = se.id
LEFT JOIN skill_syllabus ss ON b.id = ss.batch_id AND ss.created_by = ta.teacher_id
WHERE ta.teacher_id = ? AND b.status = 'active'
GROUP BY b.id, s.id, se.id
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$assigned_batches = $stmt_batches->get_result();

// Get syllabus for selected batch
$syllabus_data = [];
$batch_skill_id = 0;
$batch_skill_name = '';
$batch_info = null;

if ($batch_filter > 0) {
    // Get batch details
    $batch_query = "SELECT b.*, s.skill_name, s.id as skill_id FROM batches b 
                   JOIN skills s ON b.skill_id = s.id 
                   WHERE b.id = ?";
    $stmt_batch = $conn->prepare($batch_query);
    $stmt_batch->bind_param("i", $batch_filter);
    $stmt_batch->execute();
    $batch_info = $stmt_batch->get_result()->fetch_assoc();

    if ($batch_info) {
        $batch_skill_id = $batch_info['skill_id'];
        $batch_skill_name = $batch_info['skill_name'];
    }

    // Get syllabus items
    $syllabus_query = "
    SELECT 
        ss.*,
        t.name as created_by_name,
        CONCAT(FLOOR(ss.duration_hours / 60), 'h ', MOD(ss.duration_hours, 60), 'm') as duration_display
    FROM skill_syllabus ss
    LEFT JOIN teachers t ON ss.created_by = t.id
    WHERE ss.batch_id = ? AND ss.skill_id = ? AND ss.created_by = ?
    ";

    $params = [$batch_filter, $batch_skill_id, $teacher_id];
    $param_types = "iii";

    if ($status_filter !== 'all') {
        $syllabus_query .= " AND ss.status = ?";
        $params[] = $status_filter;
        $param_types .= "s";
    }

    $syllabus_query .= " ORDER BY ss.topic_order, ss.id";

    $stmt_syllabus = $conn->prepare($syllabus_query);
    if (!empty($params)) {
        $stmt_syllabus->bind_param($param_types, ...$params);
    }
    $stmt_syllabus->execute();
    $syllabus_data = $stmt_syllabus->get_result();
}

// Handle syllabus operations
$success_message = '';
$error_message = '';

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle syllabus upload/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $syllabus_id = isset($_POST['syllabus_id']) ? intval($_POST['syllabus_id']) : 0;
    $batch_id = intval($_POST['batch_id'] ?? $_GET['batch_id'] ?? 0);
    $skill_id = intval($_POST['skill_id'] ?? $_GET['skill_id'] ?? 0);

    if ($batch_id === 0 || $skill_id === 0) {
        $error_message = "Invalid batch or skill selection.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            if ($action === 'add' || $action === 'edit') {
                $topic_title = trim($_POST['topic_title']);
                $topic_description = trim($_POST['topic_description']);
                $topic_order = intval($_POST['topic_order']);
                $duration_hours = intval($_POST['duration_hours']);
                $learning_outcomes = trim($_POST['learning_outcomes']);
                $prerequisites = trim($_POST['prerequisites']);
                $resource_type = $_POST['resource_type'];
                $status = $_POST['status'];

                // Validate required fields
                if (empty($topic_title)) {
                    throw new Exception("Topic title is required.");
                }

                // Handle file upload
                $file_path = null;
                $file_name = null;
                $file_size = null;
                $external_link = null;
                $content_text = null;

                if ($resource_type === 'Link') {
                    $external_link = trim($_POST['external_link']);
                    if (empty($external_link) || !filter_var($external_link, FILTER_VALIDATE_URL)) {
                        throw new Exception("Please enter a valid URL for external link.");
                    }
                } elseif ($resource_type === 'Text') {
                    $content_text = trim($_POST['content_text']);
                    if (empty($content_text)) {
                        throw new Exception("Content text is required for text resources.");
                    }
                } else {
                    // Handle file upload for PDF, DOC, PPT, Video
                    if (isset($_FILES['syllabus_file']) && $_FILES['syllabus_file']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['syllabus_file'];

                        // Validate file extension based on resource type
                        $allowed_extensions = [
                            'PDF' => ['pdf'],
                            'DOC' => ['doc', 'docx'],
                            'PPT' => ['ppt', 'pptx'],
                            'Video' => ['mp4', 'avi', 'mkv', 'mov', 'wmv']
                        ];

                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                        if (!isset($allowed_extensions[$resource_type]) || !in_array($file_extension, $allowed_extensions[$resource_type])) {
                            $allowed_list = implode(', ', $allowed_extensions[$resource_type] ?? []);
                            throw new Exception("Invalid file type for $resource_type. Allowed extensions: $allowed_list");
                        }

                        // File size limit (10MB)
                        $max_size = 10 * 1024 * 1024;
                        if ($file['size'] > $max_size) {
                            throw new Exception("File size exceeds 10MB limit.");
                        }

                        // Create upload directory
                        $upload_dir = "../uploads/syllabus/" . date('Y') . '/' . date('m') . '/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        // Generate unique filename
                        $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $file['name']);
                        $file_path = $upload_dir . $file_name;
                        $file_size = formatFileSize($file['size']);

                        // Move uploaded file
                        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                            throw new Exception("Failed to upload file. Please try again.");
                        }
                    } elseif ($action === 'add') {
                        throw new Exception("Please upload a file for $resource_type resource.");
                    }
                }

                if ($action === 'edit' && $syllabus_id > 0) {
                    // Get existing syllabus data
                    $existing_query = "SELECT * FROM skill_syllabus WHERE id = ? AND created_by = ?";
                    $stmt_existing = $conn->prepare($existing_query);
                    $stmt_existing->bind_param("ii", $syllabus_id, $teacher_id);
                    $stmt_existing->execute();
                    $existing = $stmt_existing->get_result()->fetch_assoc();

                    if (!$existing) {
                        throw new Exception("Syllabus not found or you don't have permission to edit it.");
                    }

                    // Update existing syllabus
                    $update_query = "
                    UPDATE skill_syllabus 
                    SET 
                        topic_title = ?,
                        topic_description = ?,
                        topic_order = ?,
                        duration_hours = ?,
                        learning_outcomes = ?,
                        prerequisites = ?,
                        resource_type = ?,
                        status = ?,
                        updated_at = NOW()
                    ";

                    $params = [
                        $topic_title,
                        $topic_description,
                        $topic_order,
                        $duration_hours,
                        $learning_outcomes,
                        $prerequisites,
                        $resource_type,
                        $status
                    ];
                    $param_types = "ssiissss";

                    // Add file-related fields if file was uploaded
                    if ($file_path) {
                        $update_query .= ", file_path = ?, file_name = ?, file_size = ?";
                        $params[] = $file_path;
                        $params[] = $file_name;
                        $params[] = $file_size;
                        $param_types .= "sss";
                    } elseif ($resource_type === 'Link') {
                        $update_query .= ", external_link = ?, file_path = NULL, file_name = NULL, file_size = NULL, content_text = NULL";
                        $params[] = $external_link;
                        $param_types .= "s";
                    } elseif ($resource_type === 'Text') {
                        $update_query .= ", content_text = ?, file_path = NULL, file_name = NULL, file_size = NULL, external_link = NULL";
                        $params[] = $content_text;
                        $param_types .= "s";
                    }

                    $update_query .= " WHERE id = ? AND created_by = ?";
                    $params[] = $syllabus_id;
                    $params[] = $teacher_id;
                    $param_types .= "ii";

                    $stmt_update = $conn->prepare($update_query);
                    $stmt_update->bind_param($param_types, ...$params);

                    if ($stmt_update->execute()) {
                        // Add to history
                        $history_query = "
                        INSERT INTO syllabus_history (
                            syllabus_id,
                            skill_id,
                            batch_id,
                            topic_title,
                            changed_by,
                            change_type,
                            change_description
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ";

                        $stmt_history = $conn->prepare($history_query);
                        $change_desc = "Topic updated: {$topic_title}";
                        $change_type = "Updated";
                        $stmt_history->bind_param(
                            "iiiisss",
                            $syllabus_id,
                            $skill_id,
                            $batch_id,
                            $topic_title,
                            $teacher_id,
                            $change_type,
                            $change_desc
                        );
                        $stmt_history->execute();
                    }
                } else {
                    // Insert new syllabus
                    $insert_query = "
                    INSERT INTO skill_syllabus (
                        skill_id,
                        batch_id,
                        topic_title,
                        topic_description,
                        topic_order,
                        duration_hours,
                        learning_outcomes,
                        prerequisites,
                        resource_type,
                        file_path,
                        file_name,
                        file_size,
                        external_link,
                        content_text,
                        status,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";

                    $stmt_insert = $conn->prepare($insert_query);
                    $stmt_insert->bind_param(
                        "iissiisssssssssi",
                        $skill_id,
                        $batch_id,
                        $topic_title,
                        $topic_description,
                        $topic_order,
                        $duration_hours,
                        $learning_outcomes,
                        $prerequisites,
                        $resource_type,
                        $file_path,
                        $file_name,
                        $file_size,
                        $external_link,
                        $content_text,
                        $status,
                        $teacher_id
                    );

                    if ($stmt_insert->execute()) {
                        $syllabus_id = $conn->insert_id;

                        // Add to history
                        $history_query = "
                        INSERT INTO syllabus_history (
                            syllabus_id,
                            skill_id,
                            batch_id,
                            topic_title,
                            changed_by,
                            change_type,
                            change_description
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ";

                        $stmt_history = $conn->prepare($history_query);
                        $change_desc = "New topic created: {$topic_title}";
                        $change_type = "Created";
                        $stmt_history->bind_param(
                            "iiiisss",
                            $syllabus_id,
                            $skill_id,
                            $batch_id,
                            $topic_title,
                            $teacher_id,
                            $change_type,
                            $change_desc
                        );
                        $stmt_history->execute();
                    }
                }

                $success_message = "Syllabus " . ($action === 'edit' ? 'updated' : 'added') . " successfully!";
            } elseif ($action === 'archive' && $syllabus_id > 0) {
                // Archive syllabus
                $archive_query = "UPDATE skill_syllabus SET status = 'Archived' WHERE id = ? AND created_by = ?";
                $stmt_archive = $conn->prepare($archive_query);
                $stmt_archive->bind_param("ii", $syllabus_id, $teacher_id);

                if ($stmt_archive->execute()) {
                    // Get syllabus info
                    $syllabus_info = $conn->query("SELECT topic_title FROM skill_syllabus WHERE id = $syllabus_id")->fetch_assoc();
                    $topic_title = $syllabus_info['topic_title'] ?? 'Unknown';

                    // Add to history
                    $history_query = "
                    INSERT INTO syllabus_history (
                        syllabus_id,
                        skill_id,
                        batch_id,
                        topic_title,
                        changed_by,
                        change_type,
                        change_description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ";

                    $stmt_history = $conn->prepare($history_query);
                    $change_desc = "Topic archived: " . $topic_title;
                    $change_type = "Archived";
                    $stmt_history->bind_param(
                        "iiiisss",
                        $syllabus_id,
                        $skill_id,
                        $batch_id,
                        $topic_title,
                        $teacher_id,
                        $change_type,
                        $change_desc
                    );
                    $stmt_history->execute();

                    $success_message = "Syllabus topic archived successfully!";
                }
            } elseif ($action === 'restore' && $syllabus_id > 0) {
                // Restore syllabus
                $restore_query = "UPDATE skill_syllabus SET status = 'Active' WHERE id = ? AND created_by = ?";
                $stmt_restore = $conn->prepare($restore_query);
                $stmt_restore->bind_param("ii", $syllabus_id, $teacher_id);

                if ($stmt_restore->execute()) {
                    // Get syllabus info
                    $syllabus_info = $conn->query("SELECT topic_title FROM skill_syllabus WHERE id = $syllabus_id")->fetch_assoc();
                    $topic_title = $syllabus_info['topic_title'] ?? 'Unknown';

                    // Add to history
                    $history_query = "
                    INSERT INTO syllabus_history (
                        syllabus_id,
                        skill_id,
                        batch_id,
                        topic_title,
                        changed_by,
                        change_type,
                        change_description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ";

                    $stmt_history = $conn->prepare($history_query);
                    $change_desc = "Topic restored: " . $topic_title;
                    $change_type = "Restored";
                    $stmt_history->bind_param(
                        "iiiisss",
                        $syllabus_id,
                        $skill_id,
                        $batch_id,
                        $topic_title,
                        $teacher_id,
                        $change_type,
                        $change_desc
                    );
                    $stmt_history->execute();

                    $success_message = "Syllabus topic restored successfully!";
                }
            } elseif ($action === 'reorder') {
                // Handle topic reordering
                if (isset($_POST['order']) && is_array($_POST['order'])) {
                    foreach ($_POST['order'] as $position => $syllabus_id) {
                        $syllabus_id = intval($syllabus_id);
                        $position = intval($position) + 1;

                        $update_order_query = "UPDATE skill_syllabus SET topic_order = ? WHERE id = ? AND created_by = ?";
                        $stmt_order = $conn->prepare($update_order_query);
                        $stmt_order->bind_param("iii", $position, $syllabus_id, $teacher_id);
                        $stmt_order->execute();
                    }
                    $success_message = "Topic order updated successfully!";
                }
            }

            mysqli_commit($conn);
            $_SESSION['success_message'] = $success_message;

            // Redirect to prevent form resubmission
            header("Location: syllabus_management.php?batch_id={$batch_filter}&status={$status_filter}&success=1");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Helper function to format file size
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Syllabus Management | Teacher Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --accent: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-draft {
            background: #f3f4f6;
            color: #4b5563;
        }

        .status-archived {
            background: #fef3c7;
            color: #92400e;
        }

        .resource-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-pdf {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-doc {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-ppt {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .badge-video {
            background: #fce7f3;
            color: #9d174d;
        }

        .badge-link {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-text {
            background: #fef3c7;
            color: #92400e;
        }

        .sortable-ghost {
            opacity: 0.4;
            background: #f3f4f6;
        }

        .sortable-drag {
            opacity: 0.8;
            transform: rotate(5deg);
        }

        .tab-active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .tab-inactive {
            background: white;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .tab-inactive:hover {
            background: #f9fafb;
            border-color: var(--primary);
        }

        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
            border-left: 2px solid #e5e7eb;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
        }

        .file-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }

        .drag-handle {
            cursor: move;
            color: #9ca3af;
            transition: color 0.2s;
        }

        .drag-handle:hover {
            color: var(--primary);
        }

        .progress-ring {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
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
                        <h1 class="text-3xl font-bold text-gray-800">Syllabus Management</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-book-open text-primary mr-2"></i>
                            Create and manage course syllabus with topics, resources, and learning materials
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm bg-primary text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-user-tie mr-2"></i>
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 flex items-center justify-between animate-pulse">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">Success!</h4>
                            <p class="text-green-700">Operation completed successfully!</p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">Success!</h4>
                            <p class="text-green-700"><?php echo $success_message; ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-red-800">Error!</h4>
                            <p class="text-red-700"><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Filters & Batch Selection -->
            <div class="glass-card p-7 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <i class="fas fa-filter text-primary"></i>
                    Select Batch & Filter Syllabus
                </h3>
                <form method="GET" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-users text-primary"></i>
                            Select Batch
                        </label>
                        <select name="batch_id"
                            class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium"
                            onchange="this.form.submit()">
                            <option value="0">-- Select a Batch --</option>
                            <?php
                            $assigned_batches->data_seek(0);
                            while ($batch = $assigned_batches->fetch_assoc()): ?>
                                <option value="<?php echo $batch['batch_id']; ?>"
                                    <?php echo $batch_filter == $batch['batch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch['batch_name']); ?>
                                    (<?php echo htmlspecialchars($batch['skill_name']); ?>)
                                    - <?php echo $batch['syllabus_count']; ?> topics
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-flag text-primary"></i>
                            Status Filter
                        </label>
                        <select name="status"
                            class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Draft" <?php echo $status_filter === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Archived" <?php echo $status_filter === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>

                    <div class="lg:col-span-1 flex items-end gap-3">
                        <button type="submit"
                            class="w-full btn-primary px-6 py-1.5 rounded-xl font-bold text-base flex items-center justify-center gap-3">
                            <i class="fas fa-search"></i>
                            Load Syllabus
                        </button>
                        <a href="syllabus_management.php"
                            class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3.5 rounded-xl font-bold text-lg flex items-center justify-center gap-3">
                            <i class="fas fa-redo"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <?php if ($batch_filter > 0): ?>
                <!-- Batch Information -->
                <div class="card p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($batch_info['batch_name']); ?>
                            </h2>
                            <p class="text-gray-600 mt-2">
                                <i class="fas fa-book mr-2"></i>
                                Skill: <?php echo htmlspecialchars($batch_skill_name); ?>
                                |
                                <i class="fas fa-calendar ml-4 mr-2"></i>
                                Session: <?php echo htmlspecialchars($batch_info['session_name'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <button onclick="showAddSyllabusModal()"
                                class="btn-success px-6 py-3 rounded-xl font-bold text-lg flex items-center gap-3">
                                <i class="fas fa-plus"></i>
                                Add Topic
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex space-x-1 mb-6 border-b">
                        <button id="tab-active"
                            class="px-6 py-3 rounded-t-lg font-medium transition-all duration-300 <?php echo $status_filter === 'Active' || $status_filter === 'all' ? 'tab-active' : 'tab-inactive'; ?>"
                            onclick="changeTab('Active')">
                            <i class="fas fa-play-circle mr-2"></i>
                            Active Topics
                        </button>
                        <button id="tab-draft"
                            class="px-6 py-3 rounded-t-lg font-medium transition-all duration-300 <?php echo $status_filter === 'Draft' ? 'tab-active' : 'tab-inactive'; ?>"
                            onclick="changeTab('Draft')">
                            <i class="fas fa-edit mr-2"></i>
                            Drafts
                        </button>
                        <button id="tab-archived"
                            class="px-6 py-3 rounded-t-lg font-medium transition-all duration-300 <?php echo $status_filter === 'Archived' ? 'tab-active' : 'tab-inactive'; ?>"
                            onclick="changeTab('Archived')">
                            <i class="fas fa-archive mr-2"></i>
                            Archived
                        </button>
                        <button onclick="showTimeline()"
                            class="px-6 py-3 rounded-t-lg font-medium transition-all duration-300 tab-inactive">
                            <i class="fas fa-history mr-2"></i>
                            Timeline
                        </button>
                    </div>

                    <!-- Syllabus List -->
                    <?php if ($syllabus_data->num_rows > 0): ?>
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-700">
                                    <?php echo $syllabus_data->num_rows; ?> Topics Found
                                </h3>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Drag topics to reorder
                                    </span>
                                    <button onclick="saveOrder()"
                                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-save mr-1"></i> Save Order
                                    </button>
                                </div>
                            </div>

                            <div id="syllabusList" class="space-y-4">
                                <?php
                                $syllabus_data->data_seek(0);
                                $total_hours = 0;
                                while ($topic = $syllabus_data->fetch_assoc()):
                                    $total_hours += $topic['duration_hours'];
                                ?>
                                    <div class="card p-5 syllabus-item" data-id="<?php echo $topic['id']; ?>">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-4 flex-1">
                                                <!-- Drag Handle -->
                                                <div class="drag-handle pt-1">
                                                    <i class="fas fa-grip-vertical text-xl"></i>
                                                </div>

                                                <!-- Order Badge -->
                                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-gray-100 to-gray-200 rounded-lg flex items-center justify-center">
                                                    <span class="font-bold text-gray-700"><?php echo $topic['topic_order']; ?></span>
                                                </div>

                                                <!-- Topic Content -->
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <h4 class="text-lg font-bold text-gray-800">
                                                            <?php echo htmlspecialchars($topic['topic_title']); ?>
                                                        </h4>
                                                        <span class="resource-badge badge-<?php echo strtolower($topic['resource_type']); ?>">
                                                            <i class="fas 
                                                                <?php echo $topic['resource_type'] === 'PDF' ? 'fa-file-pdf' : ($topic['resource_type'] === 'DOC' ? 'fa-file-word' : ($topic['resource_type'] === 'PPT' ? 'fa-file-powerpoint' : ($topic['resource_type'] === 'Video' ? 'fa-video' : ($topic['resource_type'] === 'Link' ? 'fa-link' : 'fa-file-alt')))); ?> mr-1">
                                                            </i>
                                                            <?php echo $topic['resource_type']; ?>
                                                        </span>
                                                        <span class="status-badge status-<?php echo strtolower($topic['status']); ?>">
                                                            <?php echo $topic['status']; ?>
                                                        </span>
                                                    </div>

                                                    <?php if ($topic['topic_description']): ?>
                                                        <p class="text-gray-600 mb-3">
                                                            <?php echo htmlspecialchars(substr($topic['topic_description'], 0, 200)); ?>
                                                            <?php echo strlen($topic['topic_description']) > 200 ? '...' : ''; ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                                        <?php if ($topic['duration_hours'] > 0): ?>
                                                            <span class="flex items-center gap-1">
                                                                <i class="fas fa-clock"></i>
                                                                <?php echo $topic['duration_display']; ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($topic['file_name']): ?>
                                                            <span class="flex items-center gap-1">
                                                                <i class="fas fa-file"></i>
                                                                <?php echo htmlspecialchars($topic['file_name']); ?>
                                                                <?php if ($topic['file_size']): ?>
                                                                    (<?php echo $topic['file_size']; ?>)
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($topic['external_link']): ?>
                                                            <span class="flex items-center gap-1">
                                                                <i class="fas fa-link"></i>
                                                                <a href="<?php echo htmlspecialchars($topic['external_link']); ?>"
                                                                    target="_blank" class="text-primary hover:underline">
                                                                    External Link
                                                                </a>
                                                            </span>
                                                        <?php endif; ?>

                                                        <span class="flex items-center gap-1">
                                                            <i class="fas fa-user-tie"></i>
                                                            <?php echo htmlspecialchars($topic['created_by_name'] ?? 'You'); ?>
                                                        </span>

                                                        <span class="flex items-center gap-1">
                                                            <i class="fas fa-calendar"></i>
                                                            <?php echo date('M d, Y', strtotime($topic['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex flex-col gap-2 ml-4">
                                                <?php if ($topic['resource_type'] === 'PDF' && $topic['file_path']): ?>
                                                    <button onclick="previewFile('<?php echo htmlspecialchars($topic['file_path']); ?>', '<?php echo htmlspecialchars($topic['file_name']); ?>')"
                                                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium">
                                                        <i class="fas fa-eye mr-1"></i> Preview
                                                    </button>
                                                <?php elseif ($topic['external_link']): ?>
                                                    <a href="<?php echo htmlspecialchars($topic['external_link']); ?>"
                                                        target="_blank"
                                                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium text-center">
                                                        <i class="fas fa-external-link-alt mr-1"></i> Open Link
                                                    </a>
                                                <?php elseif ($topic['content_text']): ?>
                                                    <button onclick="showTextContent(<?php echo htmlspecialchars(json_encode($topic)); ?>)"
                                                        class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium">
                                                        <i class="fas fa-file-alt mr-1"></i> View Text
                                                    </button>
                                                <?php elseif ($topic['file_path']): ?>
                                                    <a href="<?php echo htmlspecialchars($topic['file_path']); ?>"
                                                        download="<?php echo htmlspecialchars($topic['file_name']); ?>"
                                                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium text-center">
                                                        <i class="fas fa-download mr-1"></i> Download
                                                    </a>
                                                <?php endif; ?>

                                                <button onclick="editSyllabus(<?php echo htmlspecialchars(json_encode($topic)); ?>)"
                                                    class="px-4 py-2 bg-gradient-to-r from-primary to-primary-dark text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium">
                                                    <i class="fas fa-edit mr-1"></i> Edit
                                                </button>

                                                <?php if ($topic['status'] === 'Active'): ?>
                                                    <button onclick="archiveSyllabus(<?php echo $topic['id']; ?>, '<?php echo htmlspecialchars($topic['topic_title']); ?>')"
                                                        class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-amber-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium">
                                                        <i class="fas fa-archive mr-1"></i> Archive
                                                    </button>
                                                <?php elseif ($topic['status'] === 'Archived'): ?>
                                                    <button onclick="restoreSyllabus(<?php echo $topic['id']; ?>, '<?php echo htmlspecialchars($topic['topic_title']); ?>')"
                                                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium">
                                                        <i class="fas fa-redo mr-1"></i> Restore
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($topic['status'] === 'Draft' || $topic['status'] === 'Archived'): ?>
                                                    <button onclick="deleteSyllabus(<?php echo $topic['id']; ?>, '<?php echo htmlspecialchars($topic['topic_title']); ?>')"
                                                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium">
                                                        <i class="fas fa-trash mr-1"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <!-- Summary -->
                            <div class="mt-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-800"><?php echo $syllabus_data->num_rows; ?></div>
                                        <div class="text-sm text-gray-600">Total Topics</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-primary"><?php echo floor($total_hours / 60); ?>h <?php echo $total_hours % 60; ?>m</div>
                                        <div class="text-sm text-gray-600">Total Duration</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600">
                                            <?php
                                            $active_count = 0;
                                            $syllabus_data->data_seek(0);
                                            while ($t = $syllabus_data->fetch_assoc()) {
                                                if ($t['status'] === 'Active') $active_count++;
                                            }
                                            echo $active_count;
                                            ?>
                                        </div>
                                        <div class="text-sm text-gray-600">Active Topics</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">
                                            <?php echo $active_count > 0 ? round(($active_count / $syllabus_data->num_rows) * 100) : 0; ?>%
                                        </div>
                                        <div class="text-sm text-gray-600">Active Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="mx-auto w-32 h-32 bg-gradient-to-r from-gray-200 to-gray-300 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-book-open text-gray-400 text-5xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-700 mb-3">No Syllabus Topics Found</h3>
                            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                                No syllabus topics found for the selected filters. Start by creating your first topic.
                            </p>
                            <button onclick="showAddSyllabusModal()"
                                class="btn-success px-8 py-3.5 rounded-xl font-bold text-lg flex items-center gap-3 mx-auto">
                                <i class="fas fa-plus"></i>
                                Create Your First Topic
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- No Batch Selected -->
                <div class="glass-card p-12 text-center">
                    <div class="mx-auto w-40 h-40 bg-gradient-to-r from-primary/10 to-primary/5 rounded-full flex items-center justify-center mb-8">
                        <i class="fas fa-book-open text-primary text-6xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Syllabus Management System</h2>
                    <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                        Select a batch from the dropdown above to view and manage syllabus topics.
                        Create structured learning paths with various resource types for your students.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10 max-w-4xl mx-auto">
                        <div class="card p-6">
                            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-upload text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Upload Resources</h4>
                            <p class="text-sm text-gray-600">Upload PDFs, Documents, Presentations, and Videos</p>
                        </div>
                        <div class="card p-6">
                            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-link text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">External Links</h4>
                            <p class="text-sm text-gray-600">Add external resources and online learning materials</p>
                        </div>
                        <div class="card p-6">
                            <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-text-height text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Text Content</h4>
                            <p class="text-sm text-gray-600">Create rich text content with learning outcomes</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add/Edit Syllabus Modal -->
    <div id="syllabusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">Add Syllabus Topic</h3>
                    <button onclick="closeSyllabusModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" id="syllabusForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="syllabus_id" id="syllabus_id" value="0">
                    <input type="hidden" name="batch_id" value="<?php echo $batch_filter; ?>">
                    <input type="hidden" name="skill_id" value="<?php echo $batch_skill_id; ?>">

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Topic Title *</label>
                                <input type="text" name="topic_title" id="topic_title"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    placeholder="Enter topic title" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Topic Order</label>
                                <input type="number" name="topic_order" id="topic_order"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="1" value="1">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Topic Description</label>
                            <textarea name="topic_description" id="topic_description"
                                class="w-full form-input px-4 py-3 rounded-lg"
                                rows="3" placeholder="Brief description of the topic..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Duration (minutes)</label>
                                <input type="number" name="duration_hours" id="duration_hours"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="0" value="0">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Resource Type *</label>
                                <select name="resource_type" id="resource_type"
                                    class="w-full form-input px-4 py-3 rounded-lg" required
                                    onchange="toggleResourceFields()">
                                    <option value="PDF">PDF Document</option>
                                    <option value="DOC">Word Document</option>
                                    <option value="PPT">PowerPoint</option>
                                    <option value="Video">Video File</option>
                                    <option value="Link">External Link</option>
                                    <option value="Text">Text Content</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                <select name="status" id="status" class="w-full form-input px-4 py-3 rounded-lg">
                                    <option value="Active">Active</option>
                                    <option value="Draft">Draft</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>

                        <!-- Resource Fields (Dynamic) -->
                        <div id="fileField" class="resource-field">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Upload File *</label>
                            <input type="file" name="syllabus_file" id="syllabus_file"
                                class="w-full form-input px-4 py-3 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.avi,.mkv,.mov,.wmv">
                            <p class="text-sm text-gray-500 mt-2" id="fileHelp">
                                Upload PDF, DOC, PPT, or Video files (Max 10MB)
                            </p>
                        </div>

                        <div id="linkField" class="resource-field hidden">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">External Link *</label>
                            <input type="url" name="external_link" id="external_link"
                                class="w-full form-input px-4 py-3 rounded-lg"
                                placeholder="https://example.com/resource">
                        </div>

                        <div id="textField" class="resource-field hidden">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Content Text *</label>
                            <textarea name="content_text" id="content_text"
                                class="w-full form-input px-4 py-3 rounded-lg"
                                rows="6" placeholder="Enter your content here..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Learning Outcomes</label>
                            <textarea name="learning_outcomes" id="learning_outcomes"
                                class="w-full form-input px-4 py-3 rounded-lg"
                                rows="3" placeholder="What students will learn from this topic..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Prerequisites</label>
                            <textarea name="prerequisites" id="prerequisites"
                                class="w-full form-input px-4 py-3 rounded-lg"
                                rows="2" placeholder="Required knowledge or skills..."></textarea>
                        </div>

                        <div class="flex justify-end gap-4 pt-6 border-t">
                            <button type="button" onclick="closeSyllabusModal()"
                                class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-6 py-3 btn-primary rounded-lg font-medium">
                                Save Topic
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800" id="previewTitle">Preview</h3>
                    <button onclick="closePreviewModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="previewContent">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline Modal -->
    <div id="timelineModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glasscard max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800">Syllabus History Timeline</h3>
                    <button onclick="closeTimelineModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="timelineContent" class="space-y-4">
                    <!-- Timeline will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        // Initialize Sortable for drag and drop
        document.addEventListener('DOMContentLoaded', function() {
            const syllabusList = document.getElementById('syllabusList');
            if (syllabusList) {
                new Sortable(syllabusList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    handle: '.drag-handle',
                    onEnd: function() {
                        // Update order numbers visually
                        const items = syllabusList.querySelectorAll('.syllabus-item');
                        items.forEach((item, index) => {
                            const orderBadge = item.querySelector('.flex-shrink-0 span');
                            if (orderBadge) {
                                orderBadge.textContent = index + 1;
                            }
                        });
                    }
                });
            }

            // Set initial resource fields
            toggleResourceFields();
        });

        // Tab Functions
        function changeTab(status) {
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            window.location.href = url.toString();
        }

        // Save order function
        function saveOrder() {
            const items = document.querySelectorAll('.syllabus-item');
            const order = Array.from(items).map(item => item.getAttribute('data-id'));

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reorder';
            form.appendChild(actionInput);

            order.forEach((id, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        // Modal Functions
        function showAddSyllabusModal() {
            if (<?php echo $batch_filter; ?> === 0) {
                alert('Please select a batch first.');
                return;
            }

            const modal = document.getElementById('syllabusModal');
            const form = document.getElementById('syllabusForm');

            // Reset form
            form.reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('syllabus_id').value = '0';
            document.getElementById('modalTitle').textContent = 'Add Syllabus Topic';
            document.getElementById('topic_order').value = <?php echo ($syllabus_data->num_rows ?? 0) + 1; ?>;
            document.getElementById('status').value = 'Active';

            // Reset resource fields
            toggleResourceFields();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function editSyllabus(topicData) {
            const modal = document.getElementById('syllabusModal');
            const form = document.getElementById('syllabusForm');

            // Fill form with existing data
            document.getElementById('formAction').value = 'edit';
            document.getElementById('syllabus_id').value = topicData.id;
            document.getElementById('modalTitle').textContent = 'Edit Syllabus Topic';
            document.getElementById('topic_title').value = topicData.topic_title;
            document.getElementById('topic_order').value = topicData.topic_order;
            document.getElementById('topic_description').value = topicData.topic_description || '';
            document.getElementById('duration_hours').value = topicData.duration_hours;
            document.getElementById('resource_type').value = topicData.resource_type;
            document.getElementById('status').value = topicData.status;
            document.getElementById('learning_outcomes').value = topicData.learning_outcomes || '';
            document.getElementById('prerequisites').value = topicData.prerequisites || '';
            document.getElementById('external_link').value = topicData.external_link || '';
            document.getElementById('content_text').value = topicData.content_text || '';

            // Set up resource fields
            toggleResourceFields();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSyllabusModal() {
            document.getElementById('syllabusModal').classList.add('hidden');
            document.getElementById('syllabusModal').classList.remove('flex');
        }

        // Toggle resource fields based on type
        function toggleResourceFields() {
            const resourceType = document.getElementById('resource_type').value;
            const fileField = document.getElementById('fileField');
            const linkField = document.getElementById('linkField');
            const textField = document.getElementById('textField');
            const fileInput = document.getElementById('syllabus_file');

            // Hide all fields first
            fileField.classList.add('hidden');
            linkField.classList.add('hidden');
            textField.classList.add('hidden');

            // Remove required attribute from all
            fileInput.required = false;
            document.getElementById('external_link').required = false;
            document.getElementById('content_text').required = false;

            // Show relevant field and set file accept
            if (resourceType === 'PDF' || resourceType === 'DOC' || resourceType === 'PPT' || resourceType === 'Video') {
                fileField.classList.remove('hidden');
                fileInput.required = true;

                // Set accept attribute based on resource type
                switch (resourceType) {
                    case 'PDF':
                        fileInput.accept = '.pdf';
                        fileInput.setAttribute('data-help', 'Upload PDF files (Max 10MB)');
                        break;
                    case 'DOC':
                        fileInput.accept = '.doc,.docx';
                        fileInput.setAttribute('data-help', 'Upload Word documents (Max 10MB)');
                        break;
                    case 'PPT':
                        fileInput.accept = '.ppt,.pptx';
                        fileInput.setAttribute('data-help', 'Upload PowerPoint files (Max 10MB)');
                        break;
                    case 'Video':
                        fileInput.accept = '.mp4,.avi,.mkv,.mov,.wmv';
                        fileInput.setAttribute('data-help', 'Upload video files (Max 10MB)');
                        break;
                }

                // Update help text
                const fileHelp = document.getElementById('fileHelp');
                if (fileHelp) {
                    fileHelp.textContent = fileInput.getAttribute('data-help');
                }
            } else if (resourceType === 'Link') {
                linkField.classList.remove('hidden');
                document.getElementById('external_link').required = true;
            } else if (resourceType === 'Text') {
                textField.classList.remove('hidden');
                document.getElementById('content_text').required = true;
            }
        }

        // Preview Functions
        function previewFile(filePath, fileName) {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            const title = document.getElementById('previewTitle');

            title.textContent = 'Preview: ' + fileName;
            content.innerHTML = `
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-file-pdf text-6xl text-red-500"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-800 mb-2">${fileName}</h4>
                    <p class="text-gray-600 mb-6">This is a PDF file preview. Click the button below to view/download.</p>
                    <div class="flex justify-center gap-4">
                        <a href="${filePath}" 
                           target="_blank"
                           class="px-6 py-3 bg-gradient-to-r from-primary to-primary-dark text-white rounded-lg font-medium">
                            <i class="fas fa-external-link-alt mr-2"></i> Open in New Tab
                        </a>
                        <a href="${filePath}" 
                           download="${fileName}"
                           class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg font-medium">
                            <i class="fas fa-download mr-2"></i> Download
                        </a>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function showTextContent(topicData) {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
            const title = document.getElementById('previewTitle');

            title.textContent = 'Content: ' + topicData.topic_title;
            content.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h4 class="font-bold text-gray-800 mb-4">Topic Content</h4>
                        <div class="prose max-w-none">
                            ${topicData.content_text.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    
                    ${topicData.learning_outcomes ? `
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-bullseye text-blue-500"></i>
                            Learning Outcomes
                        </h4>
                        <div class="prose max-w-none">
                            ${topicData.learning_outcomes.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    ` : ''}
                    
                    ${topicData.prerequisites ? `
                    <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-6 rounded-lg">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-exclamation-circle text-amber-500"></i>
                            Prerequisites
                        </h4>
                        <div class="prose max-w-none">
                            ${topicData.prerequisites.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.getElementById('previewModal').classList.remove('flex');
        }

        // Timeline Functions
        function showTimeline() {
            fetch(`get_syllabus_history.php?batch_id=<?php echo $batch_filter; ?>`)
                .then(response => response.json())
                .then(data => {
                    const modal = document.getElementById('timelineModal');
                    const content = document.getElementById('timelineContent');

                    if (data.length === 0) {
                        content.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                                <h4 class="text-lg font-semibold text-gray-700">No History Found</h4>
                                <p class="text-gray-500">No history records found for this syllabus.</p>
                            </div>
                        `;
                    } else {
                        let html = '';
                        data.forEach(record => {
                            const date = new Date(record.changed_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            let icon = 'fa-edit';
                            let color = 'text-blue-500';

                            switch (record.change_type) {
                                case 'Created':
                                    icon = 'fa-plus-circle';
                                    color = 'text-green-500';
                                    break;
                                case 'Updated':
                                    icon = 'fa-edit';
                                    color = 'text-blue-500';
                                    break;
                                case 'Archived':
                                    icon = 'fa-archive';
                                    color = 'text-yellow-500';
                                    break;
                                case 'Restored':
                                    icon = 'fa-redo';
                                    color = 'text-green-500';
                                    break;
                            }

                            html += `
                                <div class="timeline-item">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-10 h-10 bg-white rounded-full border-2 border-gray-200 flex items-center justify-center">
                                            <i class="fas ${icon} ${color}"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <h4 class="font-bold text-gray-800">${record.change_type}: ${record.topic_title}</h4>
                                                <span class="text-sm text-gray-500">${date}</span>
                                            </div>
                                            <p class="text-gray-600 mt-1">${record.change_description}</p>
                                            <p class="text-sm text-gray-500 mt-2">
                                                <i class="fas fa-user-tie mr-1"></i>
                                                Changed by: ${record.changed_by_name}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        content.innerHTML = html;
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                })
                .catch(error => {
                    console.error('Error fetching timeline:', error);
                    alert('Error loading timeline data.');
                });
        }

        function closeTimelineModal() {
            document.getElementById('timelineModal').classList.add('hidden');
            document.getElementById('timelineModal').classList.remove('flex');
        }

        // Action Functions
        function archiveSyllabus(id, title) {
            if (confirm(`Are you sure you want to archive "${title}"? Students will no longer see this topic.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'archive';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'syllabus_id';
                idInput.value = id;
                form.appendChild(idInput);

                const batchInput = document.createElement('input');
                batchInput.type = 'hidden';
                batchInput.name = 'batch_id';
                batchInput.value = <?php echo $batch_filter; ?>;
                form.appendChild(batchInput);

                const skillInput = document.createElement('input');
                skillInput.type = 'hidden';
                skillInput.name = 'skill_id';
                skillInput.value = <?php echo $batch_skill_id; ?>;
                form.appendChild(skillInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function restoreSyllabus(id, title) {
            if (confirm(`Are you sure you want to restore "${title}"? Students will be able to see this topic again.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'restore';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'syllabus_id';
                idInput.value = id;
                form.appendChild(idInput);

                const batchInput = document.createElement('input');
                batchInput.type = 'hidden';
                batchInput.name = 'batch_id';
                batchInput.value = <?php echo $batch_filter; ?>;
                form.appendChild(batchInput);

                const skillInput = document.createElement('input');
                skillInput.type = 'hidden';
                skillInput.name = 'skill_id';
                skillInput.value = <?php echo $batch_skill_id; ?>;
                form.appendChild(skillInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteSyllabus(id, title) {
            if (confirm(`Are you sure you want to permanently delete "${title}"? This action cannot be undone.`)) {
                window.location.href = `delete_syllabus.php?id=${id}&batch_id=<?php echo $batch_filter; ?>&confirm=1`;
            }
        }
    </script>
</body>
</html>
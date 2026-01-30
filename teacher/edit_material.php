<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$material_id) {
    $_SESSION['error'] = "Material ID is required.";
    header("Location: teaching_materials.php");
    exit;
}

// Fetch material details
$material_query = "
SELECT tm.*, b.batch_name, s.skill_name 
FROM teaching_materials tm
LEFT JOIN batches b ON tm.batch_id = b.id
LEFT JOIN skills s ON b.skill_id = s.id
WHERE tm.id = ? AND tm.teacher_id = ?
";

$stmt = $conn->prepare($material_query);
$stmt->bind_param("ii", $material_id, $teacher_id);
$stmt->execute();
$material_result = $stmt->get_result();

if ($material_result->num_rows === 0) {
    $_SESSION['error'] = "Material not found or unauthorized.";
    header("Location: teaching_materials.php");
    exit;
}

$material = $material_result->fetch_assoc();

// Fetch teacher's batches
$batches_query = "
SELECT b.id, b.batch_name, s.skill_name 
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE ta.teacher_id = ? AND b.status = 'active'
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$teacher_batches = $stmt_batches->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = intval($_POST['batch_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];

    // Validate inputs
    if (empty($title) || $batch_id <= 0) {
        $_SESSION['error'] = "Please fill all required fields.";
    } else {
        // Check if new file is uploaded
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['material_file'];

            // File validation
            $allowed_types = ['pdf', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'zip', 'txt'];
            $max_size = 50 * 1024 * 1024; // 50MB

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];

            if (!in_array($file_ext, $allowed_types)) {
                $_SESSION['error'] = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
            } elseif ($file_size > $max_size) {
                $_SESSION['error'] = "File size exceeds maximum limit of 50MB.";
            } else {
                // Delete old file
                if (!empty($material['file_path']) && file_exists($material['file_path'])) {
                    unlink($material['file_path']);
                }

                // Upload new file
                $upload_dir = '../uploads/materials/';
                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                $file_path = $upload_dir . $filename;

                // Determine file type
                $file_type_map = [
                    'pdf' => 'pdf',
                    'docx' => 'docx',
                    'doc' => 'docx',
                    'ppt' => 'ppt',
                    'pptx' => 'ppt',
                    'jpg' => 'image',
                    'jpeg' => 'image',
                    'png' => 'image',
                    'gif' => 'image',
                    'mp4' => 'video',
                    'avi' => 'video',
                    'mov' => 'video',
                    'zip' => 'other',
                    'txt' => 'other'
                ];

                $file_type = $file_type_map[$file_ext] ?? 'other';

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Format file size
                    $size_units = ['B', 'KB', 'MB', 'GB'];
                    $file_size_formatted = $file_size;
                    $unit_index = 0;
                    while ($file_size_formatted >= 1024 && $unit_index < count($size_units) - 1) {
                        $file_size_formatted /= 1024;
                        $unit_index++;
                    }
                    $file_size_display = round($file_size_formatted, 2) . ' ' . $size_units[$unit_index];

                    // Update with new file
                    $update_query = "
                    UPDATE teaching_materials SET 
                        batch_id = ?,
                        title = ?,
                        description = ?,
                        category = ?,
                        file_path = ?,
                        file_type = ?,
                        file_size = ?,
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND teacher_id = ?
                    ";

                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param(
                        "isssssssii",
                        $batch_id,
                        $title,
                        $description,
                        $category,
                        $file_path,
                        $file_type,
                        $file_size_display,
                        $status,
                        $material_id,
                        $teacher_id
                    );
                } else {
                    $_SESSION['error'] = "Failed to upload new file.";
                    header("Location: edit_material.php?id=" . $material_id);
                    exit;
                }
            }
        } else {
            // Update without changing file
            $update_query = "
            UPDATE teaching_materials SET 
                batch_id = ?,
                title = ?,
                description = ?,
                category = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND teacher_id = ?
            ";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(
                "isssii",
                $batch_id,
                $title,
                $description,
                $category,
                $status,
                $material_id,
                $teacher_id
            );
        }

        if (!isset($_SESSION['error']) && $stmt->execute()) {
            $_SESSION['success'] = "Material updated successfully!";
            header("Location: teaching_materials.php");
            exit;
        } elseif (!isset($_SESSION['error'])) {
            $_SESSION['error'] = "Failed to update material.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Edit Material | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .current-file {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            background: #f9fafb;
        }

        .file-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .icon-pdf {
            background: #fee2e2;
            color: #ef4444;
        }

        .icon-docx {
            background: #dbeafe;
            color: #3b82f6;
        }

        .icon-ppt {
            background: #fef3c7;
            color: #f59e0b;
        }

        .icon-video {
            background: #e0e7ff;
            color: #6366f1;
        }

        .icon-image {
            background: #dcfce7;
            color: #10b981;
        }

        .icon-other {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Edit Material</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-edit text-blue-500 mr-2"></i>
                            Update material details
                        </p>
                    </div>
                    <div>
                        <a href="teaching_materials.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Materials
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Edit Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Edit Material Details</h2>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Current File Display -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current File</label>
                                <div class="current-file">
                                    <div class="flex items-center gap-4">
                                        <div class="file-icon icon-<?php echo $material['file_type']; ?>">
                                            <?php
                                            $icons = [
                                                'pdf' => 'fas fa-file-pdf',
                                                'docx' => 'fas fa-file-word',
                                                'ppt' => 'fas fa-file-powerpoint',
                                                'video' => 'fas fa-file-video',
                                                'image' => 'fas fa-file-image',
                                                'other' => 'fas fa-file'
                                            ];
                                            $icon = $icons[$material['file_type']] ?? 'fas fa-file';
                                            ?>
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-800">
                                                <?php echo htmlspecialchars(basename($material['file_path'])); ?>
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                <?php echo strtoupper($material['file_type']); ?> •
                                                <?php echo $material['file_size']; ?> •
                                                Uploaded: <?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="../uploads/materials/<?php echo basename($material['file_path']); ?>"
                                                target="_blank"
                                                class="text-blue-600 hover:text-blue-800"
                                                title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../uploads/materials/<?php echo basename($material['file_path']); ?>"
                                                download
                                                class="text-green-600 hover:text-green-800"
                                                title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Replace File Option -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Replace File (Optional)</label>
                                <input type="file" name="material_file"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    accept=".pdf,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.zip,.txt">
                                <p class="text-xs text-gray-500 mt-1">
                                    Leave empty to keep current file. Max 50MB.
                                </p>
                            </div>

                            <!-- Material Details -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Batch *</label>
                                    <select name="batch_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="">-- Select Batch --</option>
                                        <?php while ($batch = $teacher_batches->fetch_assoc()): ?>
                                            <option value="<?php echo $batch['id']; ?>"
                                                <?php echo $material['batch_id'] == $batch['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($batch['skill_name'] . ' - ' . $batch['batch_name']); ?>
                                            </option>
                                        <?php endwhile;
                                        mysqli_data_seek($teacher_batches, 0); ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                    <input type="text" name="title" required
                                        value="<?php echo htmlspecialchars($material['title']); ?>"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <input type="text" name="category"
                                        value="<?php echo htmlspecialchars($material['category']); ?>"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., Lecture Notes, Assignments, Reference Material">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="description" rows="4"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Describe what this material contains..."><?php echo htmlspecialchars($material['description']); ?></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="active" <?php echo $material['status'] === 'active' ? 'selected' : ''; ?>>Active (Visible to students)</option>
                                        <option value="inactive" <?php echo $material['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden from students)</option>
                                        <option value="archived" <?php echo $material['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                </div>

                                <div class="pt-4 flex gap-3">
                                    <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                                        <i class="fas fa-save mr-2"></i> Save Changes
                                    </button>
                                    <a href="teaching_materials.php"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium">
                                        Cancel
                                    </a>
                                    <a href="?delete_id=<?php echo $material_id; ?>"
                                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium ml-auto"
                                        onclick="return confirm('Are you sure you want to delete this material?');">
                                        <i class="fas fa-trash mr-2"></i> Delete Material
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Stats & Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Material Statistics</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Uploaded:</span>
                                <span class="font-medium"><?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Last Updated:</span>
                                <span class="font-medium">
                                    <?php echo $material['updated_at'] ? date('M d, Y', strtotime($material['updated_at'])) : 'Never'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Downloads:</span>
                                <span class="font-medium"><?php echo $material['download_count']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">File Size:</span>
                                <span class="font-medium"><?php echo $material['file_size']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">File Type:</span>
                                <span class="font-medium"><?php echo strtoupper($material['file_type']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h2>
                        <div class="space-y-3">
                            <a href="../uploads/materials/<?php echo basename($material['file_path']); ?>"
                                target="_blank"
                                class="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-eye text-blue-600"></i>
                                    </div>
                                    <span class="font-medium text-gray-800">Preview File</span>
                                </div>
                                <i class="fas fa-chevron-right text-blue-400"></i>
                            </a>

                            <a href="../uploads/materials/<?php echo basename($material['file_path']); ?>"
                                download
                                class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-download text-green-600"></i>
                                    </div>
                                    <span class="font-medium text-gray-800">Download File</span>
                                </div>
                                <i class="fas fa-chevron-right text-green-400"></i>
                            </a>

                            <a href="teaching_materials.php"
                                class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-arrow-left text-gray-600"></i>
                                    </div>
                                    <span class="font-medium text-gray-800">Back to All Materials</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Handle delete action
        if (window.location.search.includes('delete_id')) {
            if (confirm('Are you sure you want to delete this material?')) {
                // Already handled by onclick, but as backup
                return true;
            } else {
                window.history.back();
            }
        }
    </script>
</body>

</html>
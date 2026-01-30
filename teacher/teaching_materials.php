<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Handle material deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Check if material belongs to this teacher
    $check_query = "SELECT id, file_path FROM teaching_materials WHERE id = ? AND teacher_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $delete_id, $teacher_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $material = $check_result->fetch_assoc();

        // Delete file from server
        if (!empty($material['file_path']) && file_exists($material['file_path'])) {
            unlink($material['file_path']);
        }

        // Delete from database
        $delete_query = "DELETE FROM teaching_materials WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_query);
        $stmt_delete->bind_param("i", $delete_id);

        if ($stmt_delete->execute()) {
            $_SESSION['success'] = "Material deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete material.";
        }
    } else {
        $_SESSION['error'] = "Material not found or unauthorized.";
    }
    header("Location: teaching_materials.php");
    exit;
}

// Handle status change
if (isset($_GET['change_status'])) {
    $material_id = intval($_GET['material_id']);
    $new_status = $_GET['new_status'];

    // Validate status
    $valid_statuses = ['active', 'inactive', 'archived'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status.";
        header("Location: teaching_materials.php");
        exit;
    }

    // Check ownership
    $check_query = "SELECT id FROM teaching_materials WHERE id = ? AND teacher_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $material_id, $teacher_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $update_query = "UPDATE teaching_materials SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("si", $new_status, $material_id);

        if ($stmt_update->execute()) {
            $_SESSION['success'] = "Material status updated!";
        } else {
            $_SESSION['error'] = "Failed to update status.";
        }
    } else {
        $_SESSION['error'] = "Material not found or unauthorized.";
    }
    header("Location: teaching_materials.php");
    exit;
}

// Fetch materials with batch information
$query = "
SELECT 
    tm.*,
    b.batch_name,
    s.skill_name,
    COUNT(DISTINCT d.student_id) as download_count
FROM teaching_materials tm
LEFT JOIN batches b ON tm.batch_id = b.id
LEFT JOIN skills s ON b.skill_id = s.id
LEFT JOIN material_downloads d ON tm.id = d.material_id
WHERE tm.teacher_id = ?
GROUP BY tm.id
ORDER BY tm.uploaded_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$materials = $stmt->get_result();

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

// Get file type counts for stats
$stats_query = "
SELECT 
    COUNT(*) as total_materials,
    SUM(download_count) as total_downloads,
    SUM(CASE WHEN file_type = 'pdf' THEN 1 ELSE 0 END) as pdf_count,
    SUM(CASE WHEN file_type = 'docx' THEN 1 ELSE 0 END) as docx_count,
    SUM(CASE WHEN file_type = 'ppt' THEN 1 ELSE 0 END) as ppt_count,
    SUM(CASE WHEN file_type = 'video' THEN 1 ELSE 0 END) as video_count,
    SUM(CASE WHEN file_type = 'image' THEN 1 ELSE 0 END) as image_count
FROM teaching_materials 
WHERE teacher_id = ?
";

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("i", $teacher_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Teaching Materials | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }

        .status-archived {
            background: #fee2e2;
            color: #991b1b;
        }

        .file-type-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .type-pdf {
            background: #fee2e2;
            color: #991b1b;
        }

        .type-docx {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-ppt {
            background: #fef3c7;
            color: #92400e;
        }

        .type-video {
            background: #e0e7ff;
            color: #3730a3;
        }

        .type-image {
            background: #dcfce7;
            color: #166534;
        }

        .type-other {
            background: #f3f4f6;
            color: #4b5563;
        }

        .material-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .material-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
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
                        <h1 class="text-3xl font-bold text-gray-800">Teaching Materials</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-book text-blue-500 mr-2"></i>
                            Share study materials with your students
                        </p>
                    </div>
                    <div>
                        <a href="upload_material.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-upload mr-2"></i> Upload Material
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Materials</h3>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo $stats_result['total_materials'] ?? 0; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Downloads</h3>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php echo $stats_result['total_downloads'] ?? 0; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-download text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Active Materials</h3>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php
                                $active_query = "SELECT COUNT(*) as active_count FROM teaching_materials WHERE teacher_id = ? AND status = 'active'";
                                $stmt_active = $conn->prepare($active_query);
                                $stmt_active->bind_param("i", $teacher_id);
                                $stmt_active->execute();
                                $active_result = $stmt_active->get_result()->fetch_assoc();
                                echo $active_result['active_count'] ?? 0;
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">File Types</h3>
                            <p class="text-xl font-bold text-gray-800">
                                <?php
                                $types = [];
                                if ($stats_result['pdf_count'] > 0) $types[] = 'PDF';
                                if ($stats_result['docx_count'] > 0) $types[] = 'DOCX';
                                if ($stats_result['ppt_count'] > 0) $types[] = 'PPT';
                                if ($stats_result['video_count'] > 0) $types[] = 'Video';
                                if ($stats_result['image_count'] > 0) $types[] = 'Image';
                                echo implode(', ', $types);
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-folder text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 mb-6">
                <div class="flex flex-wrap gap-4 items-center">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                        <select id="batchFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="all">All Batches</option>
                            <?php while ($batch = $teacher_batches->fetch_assoc()): ?>
                                <option value="<?php echo $batch['id']; ?>">
                                    <?php echo htmlspecialchars($batch['skill_name'] . ' - ' . $batch['batch_name']); ?>
                                </option>
                            <?php endwhile;
                            mysqli_data_seek($teacher_batches, 0); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                        <select id="typeFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="all">All Types</option>
                            <option value="pdf">PDF</option>
                            <option value="docx">DOCX</option>
                            <option value="ppt">PPT</option>
                            <option value="video">Video</option>
                            <option value="image">Image</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="ml-auto">
                        <button id="resetFilters" class="text-gray-600 hover:text-gray-800 text-sm">
                            <i class="fas fa-redo mr-1"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Materials Grid -->
            <div id="materialsGrid">
                <?php if ($materials->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($material = $materials->fetch_assoc()): ?>
                            <div class="material-card bg-white p-5"
                                data-batch="<?php echo $material['batch_id']; ?>"
                                data-type="<?php echo $material['file_type']; ?>"
                                data-status="<?php echo $material['status']; ?>">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-start gap-3">
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
                                        <div>
                                            <h3 class="font-bold text-gray-800 text-lg">
                                                <?php echo htmlspecialchars($material['title']); ?>
                                            </h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="file-type-badge type-<?php echo $material['file_type']; ?>">
                                                    <?php echo strtoupper($material['file_type']); ?>
                                                </span>
                                                <span class="status-badge status-<?php echo $material['status']; ?>">
                                                    <?php echo ucfirst($material['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <button class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                            onclick="toggleMaterialMenu('menu-<?php echo $material['id']; ?>')">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div id="menu-<?php echo $material['id']; ?>"
                                            class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden">
                                            <a href="edit_material.php?id=<?php echo $material['id']; ?>"
                                                class="block px-4 py-2 text-sm text-blue-600 hover:bg-blue-50">
                                                <i class="fas fa-edit mr-2"></i> Edit
                                            </a>
                                            <?php if ($material['status'] !== 'active'): ?>
                                                <a href="?change_status&material_id=<?php echo $material['id']; ?>&new_status=active"
                                                    class="block px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                                    <i class="fas fa-check mr-2"></i> Activate
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($material['status'] !== 'inactive'): ?>
                                                <a href="?change_status&material_id=<?php echo $material['id']; ?>&new_status=inactive"
                                                    class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                                    <i class="fas fa-pause mr-2"></i> Deactivate
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($material['status'] !== 'archived'): ?>
                                                <a href="?change_status&material_id=<?php echo $material['id']; ?>&new_status=archived"
                                                    class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                    <i class="fas fa-archive mr-2"></i> Archive
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete_id=<?php echo $material['id']; ?>"
                                                class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                                onclick="return confirm('Are you sure you want to delete this material?');">
                                                <i class="fas fa-trash mr-2"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($material['description'])): ?>
                                    <p class="text-gray-600 mb-4 text-sm">
                                        <?php echo htmlspecialchars(substr($material['description'], 0, 120)); ?>
                                        <?php if (strlen($material['description']) > 120): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="border-t border-gray-100 pt-4">
                                    <div class="flex justify-between items-center text-sm text-gray-500">
                                        <div class="flex items-center gap-4">
                                            <span>
                                                <i class="fas fa-users mr-1"></i>
                                                <?php echo htmlspecialchars($material['skill_name'] ?? 'N/A'); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-download mr-1"></i>
                                                <?php echo $material['download_count']; ?> downloads
                                            </span>
                                        </div>
                                        <span>
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php echo date('M d, Y', strtotime($material['uploaded_at'])); ?>
                                        </span>
                                    </div>

                                    <div class="mt-3 flex gap-2">
                                        <a href="../uploads/materials/<?php echo basename($material['file_path']); ?>"
                                            target="_blank"
                                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg text-sm font-medium">
                                            <i class="fas fa-eye mr-2"></i> Preview
                                        </a>
                                        <a href="../uploads/materials/<?php echo basename($material['file_path']); ?>"
                                            download
                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-2 px-4 rounded-lg text-sm font-medium">
                                            <i class="fas fa-download mr-2"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-book-open text-gray-300 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-700 mb-3">No Teaching Materials Yet</h3>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">
                            Start sharing study materials with your students. Upload PDFs, documents, presentations, videos, and more.
                        </p>
                        <a href="upload_material.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                            <i class="fas fa-upload mr-2"></i> Upload Your First Material
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleMaterialMenu(id) {
            const menu = document.getElementById(id);
            menu.classList.toggle('hidden');

            // Close other menus
            document.querySelectorAll('[id^="menu-"]').forEach(m => {
                if (m.id !== id) m.classList.add('hidden');
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[id^="menu-"]') && !e.target.closest('button')) {
                document.querySelectorAll('[id^="menu-"]').forEach(m => {
                    m.classList.add('hidden');
                });
            }
        });

        // Filter functionality
        document.getElementById('batchFilter').addEventListener('change', filterMaterials);
        document.getElementById('typeFilter').addEventListener('change', filterMaterials);
        document.getElementById('statusFilter').addEventListener('change', filterMaterials);
        document.getElementById('resetFilters').addEventListener('click', resetFilters);

        function filterMaterials() {
            const batchFilter = document.getElementById('batchFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const materials = document.querySelectorAll('.material-card');

            materials.forEach(material => {
                const batch = material.getAttribute('data-batch');
                const type = material.getAttribute('data-type');
                const status = material.getAttribute('data-status');
                let show = true;

                if (batchFilter !== 'all' && batch !== batchFilter) {
                    show = false;
                }

                if (typeFilter !== 'all' && type !== typeFilter) {
                    show = false;
                }

                if (statusFilter !== 'all' && status !== statusFilter) {
                    show = false;
                }

                material.style.display = show ? 'block' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('batchFilter').value = 'all';
            document.getElementById('typeFilter').value = 'all';
            document.getElementById('statusFilter').value = 'all';
            filterMaterials();
        }
    </script>
</body>

</html>
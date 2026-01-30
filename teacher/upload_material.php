<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = intval($_POST['batch_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];

    // Validate inputs
    if (empty($title) || $batch_id <= 0) {
        $_SESSION['error'] = "Please fill all required fields.";
    } elseif (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Please select a file to upload.";
    } else {
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
            // Create uploads directory if not exists
            $upload_dir = '../uploads/materials/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
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

                // Insert into database
                $insert_query = "
                INSERT INTO teaching_materials (batch_id, teacher_id, title, description, category, file_path, file_type, file_size, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";

                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param(
                    "iisssssss",
                    $batch_id,
                    $teacher_id,
                    $title,
                    $description,
                    $category,
                    $file_path,
                    $file_type,
                    $file_size_display,
                    $status
                );

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Material uploaded successfully!";
                    header("Location: teaching_materials.php");
                    exit;
                } else {
                    // Delete uploaded file if DB insert fails
                    unlink($file_path);
                    $_SESSION['error'] = "Failed to save material information.";
                }
            } else {
                $_SESSION['error'] = "Failed to upload file. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Upload Material | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .file-preview {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            background: #f9fafb;
        }

        .progress-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            border-radius: 3px;
            transition: width 0.3s ease;
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
                        <h1 class="text-3xl font-bold text-gray-800">Upload Teaching Material</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-upload text-blue-500 mr-2"></i>
                            Share study materials with your students
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
                <!-- Left Column: Upload Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Material Details</h2>

                        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                            <div class="space-y-6">
                                <!-- File Upload Area -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload File *</label>
                                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('materialFile').click()">
                                        <input type="file" name="material_file" id="materialFile"
                                            class="hidden" accept=".pdf,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.zip,.txt"
                                            onchange="previewFile()">

                                        <div id="uploadPlaceholder" class="text-center">
                                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-cloud-upload-alt text-blue-600 text-2xl"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-700 mb-2">Click to upload</h3>
                                            <p class="text-sm text-gray-500 mb-3">or drag and drop</p>
                                            <p class="text-xs text-gray-400">
                                                PDF, DOCX, PPT, JPG, PNG, GIF, MP4, ZIP, TXT (Max 50MB)
                                            </p>
                                        </div>

                                        <div id="filePreview" class="hidden">
                                            <!-- File preview will be inserted here -->
                                        </div>
                                    </div>
                                    <div id="uploadProgress" class="hidden">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Uploading...</span>
                                            <span id="progressPercent">0%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div id="progressFill" class="progress-fill" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Material Details -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Batch *</label>
                                    <select name="batch_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="">-- Select Batch --</option>
                                        <?php while ($batch = $teacher_batches->fetch_assoc()): ?>
                                            <option value="<?php echo $batch['id']; ?>">
                                                <?php echo htmlspecialchars($batch['skill_name'] . ' - ' . $batch['batch_name']); ?>
                                            </option>
                                        <?php endwhile;
                                        mysqli_data_seek($teacher_batches, 0); ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                    <input type="text" name="title" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., Chapter 1 Notes, Week 3 Slides">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <input type="text" name="category"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., Lecture Notes, Assignments, Reference Material">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="description" rows="4"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Describe what this material contains..."></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="active">Active (Visible to students)</option>
                                        <option value="inactive">Inactive (Hidden from students)</option>
                                    </select>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" id="submitBtn"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium w-full">
                                        <i class="fas fa-upload mr-2"></i> Upload Material
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Instructions & Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Upload Guidelines</h2>
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                <span>Maximum file size: <strong>50MB</strong></span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                <span>Supported formats: PDF, DOCX, PPT, Images, Videos, ZIP</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                <span>Use descriptive titles for easy searching</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                <span>Add categories to organize materials</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                <span>Set to "Active" for students to see immediately</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">File Type Icons</h2>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="file-icon icon-pdf">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">PDF Documents</p>
                                    <p class="text-xs text-gray-500">Study notes, research papers</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="file-icon icon-docx">
                                    <i class="fas fa-file-word"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Word Documents</p>
                                    <p class="text-xs text-gray-500">Assignments, reports</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="file-icon icon-ppt">
                                    <i class="fas fa-file-powerpoint"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Presentations</p>
                                    <p class="text-xs text-gray-500">Lecture slides</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="file-icon icon-video">
                                    <i class="fas fa-file-video"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Videos</p>
                                    <p class="text-xs text-gray-500">Lecture recordings, tutorials</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="file-icon icon-image">
                                    <i class="fas fa-file-image"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Images</p>
                                    <p class="text-xs text-gray-500">Diagrams, charts, photos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // File upload preview
        function previewFile() {
            const fileInput = document.getElementById('materialFile');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const filePreview = document.getElementById('filePreview');

            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileSize = formatFileSize(file.size);
                const fileType = file.name.split('.').pop().toLowerCase();

                // Get icon based on file type
                const icons = {
                    'pdf': 'fas fa-file-pdf text-red-500',
                    'docx': 'fas fa-file-word text-blue-500',
                    'doc': 'fas fa-file-word text-blue-500',
                    'ppt': 'fas fa-file-powerpoint text-orange-500',
                    'pptx': 'fas fa-file-powerpoint text-orange-500',
                    'jpg': 'fas fa-file-image text-green-500',
                    'jpeg': 'fas fa-file-image text-green-500',
                    'png': 'fas fa-file-image text-green-500',
                    'gif': 'fas fa-file-image text-green-500',
                    'mp4': 'fas fa-file-video text-purple-500',
                    'avi': 'fas fa-file-video text-purple-500',
                    'mov': 'fas fa-file-video text-purple-500',
                    'zip': 'fas fa-file-archive text-yellow-500',
                    'txt': 'fas fa-file-alt text-gray-500'
                };

                const iconClass = icons[fileType] || 'fas fa-file text-gray-500';

                filePreview.innerHTML = `
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="${iconClass} text-xl"></i>
                        </div>
                        <div class="text-left flex-1">
                            <h4 class="font-medium text-gray-800 truncate">${file.name}</h4>
                            <p class="text-sm text-gray-500">${fileSize} • ${fileType.toUpperCase()}</p>
                        </div>
                        <button type="button" onclick="removeFile()" 
                                class="text-gray-400 hover:text-red-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                uploadPlaceholder.classList.add('hidden');
                filePreview.classList.remove('hidden');
            }
        }

        function removeFile() {
            const fileInput = document.getElementById('materialFile');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            const filePreview = document.getElementById('filePreview');

            fileInput.value = '';
            uploadPlaceholder.classList.remove('hidden');
            filePreview.classList.add('hidden');
            filePreview.innerHTML = '';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('materialFile');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewFile();
            }
        });

        // Form submission with progress simulation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const fileInput = document.getElementById('materialFile');

            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return;
            }

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Uploading...';
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

            // Show progress bar (simulated for now)
            const progressBar = document.getElementById('uploadProgress');
            const progressFill = document.getElementById('progressFill');
            const progressPercent = document.getElementById('progressPercent');

            progressBar.classList.remove('hidden');

            // Simulate progress for large files
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                if (progress <= 90) {
                    progressFill.style.width = progress + '%';
                    progressPercent.textContent = progress + '%';
                }
            }, 200);

            // Clear interval when form submits
            setTimeout(() => {
                clearInterval(interval);
                progressFill.style.width = '100%';
                progressPercent.textContent = '100%';
            }, 2000);
        });
    </script>
</body>

</html>
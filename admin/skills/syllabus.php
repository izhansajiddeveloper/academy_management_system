<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$skill_id = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : 0;

// Redirect if no skill_id
if (!$skill_id) {
    header("Location: skills.php");
    exit;
}

// Uploads folder
$upload_dir = __DIR__ . '/uploads/'; // points to admin/skills/uploads/
$web_path = '/uploads/';  // relative path for browser links
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Add Topic
if (isset($_POST['add_topic'])) {
    $title = trim($_POST['topic_title']);
    $order = (int)$_POST['topic_order'];
    $pdf_path = null;

    if (isset($_FILES['topic_pdf']) && $_FILES['topic_pdf']['error'] == 0) {
        $filename = time() . '_' . basename($_FILES['topic_pdf']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['topic_pdf']['tmp_name'], $target)) {
            $pdf_path = $web_path . $filename;
        }
    }

    if ($title && $order) {
        mysqli_query($conn, "INSERT INTO skill_syllabus (skill_id, topic_title, topic_order, file_path) 
                             VALUES ($skill_id, '$title', $order, " . ($pdf_path ? "'$pdf_path'" : "NULL") . ")");
        // Update skill to has_syllabus = 1
        mysqli_query($conn, "UPDATE skills SET has_syllabus=1 WHERE id=$skill_id");
        header("Location: syllabus.php?skill_id=$skill_id");
        exit;
    }
}

// Edit Topic
if (isset($_POST['edit_topic'])) {
    $topic_id = (int)$_POST['topic_id'];
    $title = trim($_POST['topic_title']);
    $order = (int)$_POST['topic_order'];

    $pdf_path_sql = '';
    if (isset($_FILES['topic_pdf']) && $_FILES['topic_pdf']['error'] == 0) {
        $filename = time() . '_' . basename($_FILES['topic_pdf']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['topic_pdf']['tmp_name'], $target)) {
            $pdf_path = $web_path . $filename;
            $pdf_path_sql = ", file_path='$pdf_path'";
        }
    }

    if ($topic_id && $title && $order) {
        mysqli_query($conn, "UPDATE skill_syllabus SET topic_title='$title', topic_order=$order $pdf_path_sql WHERE id=$topic_id");
        header("Location: syllabus.php?skill_id=$skill_id");
        exit;
    }
}

// Delete Topic
if (isset($_GET['delete'])) {
    $topic_id = (int)$_GET['delete'];

    // 1. Fetch the file path from the database before deleting the record
    $result = mysqli_query($conn, "SELECT file_path FROM skill_syllabus WHERE id=$topic_id");
    if ($row = mysqli_fetch_assoc($result)) {
        $file_name = $row['file_path'];

        if (!empty($file_name)) {
            // 2. Construct the full server path
            $full_path = __DIR__ . $file_name;

            // 3. Delete the physical file from the server
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
    }

    // 4. Delete the record from the database
    mysqli_query($conn, "DELETE FROM skill_syllabus WHERE id=$topic_id");

    header("Location: syllabus.php?skill_id=$skill_id");
    exit;
}

// Fetch Skill info
$skill = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM skills WHERE id=$skill_id"));

// Fetch Syllabus
$syllabus_result = mysqli_query($conn, "SELECT * FROM skill_syllabus WHERE skill_id=$skill_id ORDER BY topic_order ASC");
$syllabus_count = mysqli_num_rows($syllabus_result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Syllabus Management | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            background: #111827;
            /* Dark sidebar */
            color: white;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
            color: #d1d5db;
            text-decoration: none;
        }

        .sidebar-link:hover {
            background: #374151;
            color: white;
        }

        .sidebar-link.active {
            background: #3b82f6;
            color: white;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .table-container {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb !important;
        }

        .course-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 sidebar h-screen sticky top-0">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-bold text-white">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1">Admin Panel</p>
            </div>

            <nav class="p-3 space-y-1">
                <a href="../dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Skills & Courses</p>
                    <a href="skills.php" class="sidebar-link">
                        <i class="fas fa-book-open"></i> Skills
                    </a>
                    <a href="syllabus.php?skill_id=<?php echo $skill_id; ?>" class="sidebar-link active">
                        <i class="fas fa-file-alt"></i> Syllabus
                        <?php if ($syllabus_count > 0): ?>
                            <span class="ml-auto text-xs bg-blue-500 text-white px-2 py-1 rounded-full"><?php echo $syllabus_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="progress.php" class="sidebar-link">
                        <i class="fas fa-chart-line"></i> Progress
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Academic</p>
                    <a href="../sessions/sessions.php" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Sessions
                    </a>
                    <a href="../batches/batches.php" class="sidebar-link">
                        <i class="fas fa-layer-group"></i> Batches
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-user-check"></i> Enrollments
                    </a>
                    <a href="../fees/fee_collection.php" class="sidebar-link">
                        <i class="fas fa-money-bill-wave"></i> Fees
                    </a>
                </div>
            </nav>

            <div class="p-3 border-t border-gray-700 mt-auto">
                <a href="skills.php" class="w-full flex items-center justify-center gap-2 text-sm p-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Skills</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Syllabus Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-file-alt text-blue-500 mr-1"></i>
                        Manage course syllabus and learning materials
                    </p>
                </div>
            </div>

            <!-- Course Header -->
            <div class="course-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($skill['skill_name']); ?></h2>
                        <div class="flex flex-wrap gap-4">
                            <div>
                                <div class="text-sm opacity-80">Skill Level</div>
                                <div class="font-medium"><?php echo htmlspecialchars($skill['level']); ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Duration</div>
                                <div class="font-medium"><?php echo $skill['duration_months']; ?> months</div>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Topics</div>
                                <div class="font-medium"><?php echo $syllabus_count; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Topic Form -->
            <div class="form-card">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-plus-circle text-green-600 mr-2"></i> Add New Topic
                </h3>

                <form method="post" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Topic Title *</label>
                            <input type="text"
                                name="topic_title"
                                placeholder="Enter topic title"
                                required
                                class="w-full p-2 border border-gray-300 rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order Number *</label>
                            <input type="number"
                                name="topic_order"
                                placeholder="Sequence number"
                                required
                                min="1"
                                class="w-full p-2 border border-gray-300 rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PDF File</label>
                            <input type="file"
                                name="topic_pdf"
                                accept="application/pdf"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            name="add_topic"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-plus-circle mr-1"></i> Add Topic
                        </button>
                    </div>
                </form>
            </div>

            <!-- Syllabus Topics -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Course Topics (<?php echo $syllabus_count; ?>)</h3>
                </div>

                <?php if ($syllabus_count > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Order</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Topic Title</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">PDF Material</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($syllabus_result)): ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
                                                <span class="text-blue-700 font-medium text-sm"><?php echo $row['topic_order']; ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <form method="post" enctype="multipart/form-data" class="flex items-center gap-3">
                                                <input type="hidden" name="topic_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="topic_order" value="<?php echo $row['topic_order']; ?>">
                                                <input type="text"
                                                    name="topic_title"
                                                    value="<?php echo htmlspecialchars($row['topic_title']); ?>"
                                                    class="w-full p-2 border border-gray-300 rounded text-sm"
                                                    required>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-col gap-2">
                                                <?php if (!empty($row['file_path'])): ?>
                                                    <a href="/academy_management_system/admin/skills<?php echo $row['file_path']; ?>"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-xs font-medium">
                                                        <i class="fas fa-file-pdf"></i>
                                                        View PDF
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-500 font-medium">No PDF</span>
                                                <?php endif; ?>
                                                <input type="file"
                                                    name="topic_pdf"
                                                    accept="application/pdf"
                                                    class="text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700">
                                            </div>
                                        </td>

                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <button type="submit"
                                                    name="edit_topic"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Save">
                                                    <i class="fas fa-save text-xs"></i>
                                                </button>
                                                </form>
                                                <a href="syllabus.php?skill_id=<?php echo $skill_id; ?>&delete=<?php echo $row['id']; ?>"
                                                    onclick="return confirm('Delete this topic?');"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-file-alt text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Topics Added Yet</h3>
                        <p class="text-gray-500 text-sm mb-4">Start by adding your first topic</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const addForm = document.querySelector('form[action*="syllabus.php"]');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    const title = this.querySelector('input[name="topic_title"]');
                    const order = this.querySelector('input[name="topic_order"]');

                    if (!title.value.trim()) {
                        alert('Please enter a topic title.');
                        title.focus();
                        e.preventDefault();
                        return false;
                    }

                    if (!order.value || order.value < 1) {
                        alert('Please enter a valid order number (minimum 1).');
                        order.focus();
                        e.preventDefault();
                        return false;
                    }

                    return true;
                });
            }

            // Edit forms validation
            const editForms = document.querySelectorAll('form[method="post"]');
            editForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (this.querySelector('button[name="edit_topic"]')) {
                        const title = this.querySelector('input[name="topic_title"]');
                        if (!title.value.trim()) {
                            alert('Please enter a topic title.');
                            title.focus();
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            });

            // Confirm delete with topic title
            document.addEventListener('click', function(e) {
                if (e.target.closest('a') && e.target.closest('a').href.includes('delete')) {
                    const topicRow = e.target.closest('tr');
                    if (topicRow) {
                        const topicTitle = topicRow.querySelector('input[name="topic_title"]').value;
                        if (!confirm(`Delete "${topicTitle}"?`)) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>
<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Get student ID from students table using user_id from session
$user_id = $_SESSION['user_id'];

// Fetch student details
$student_query = "SELECT * FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($student_query);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit;
}

$student_id = $student['id'];

// Get filters from URL
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get enrolled courses for dropdown
$enrolled_query = "
SELECT DISTINCT 
    s.id as skill_id,
    s.skill_name,
    b.id as batch_id,
    b.batch_name,
    ses.session_name,
    t.name as teacher_name
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
LEFT JOIN batch_teachers bt ON b.id = bt.batch_id
LEFT JOIN teachers t ON bt.teacher_id = t.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name, b.batch_name
";
$stmt_enrolled = $conn->prepare($enrolled_query);
$stmt_enrolled->bind_param("i", $student_id);
$stmt_enrolled->execute();
$enrolled_courses = $stmt_enrolled->get_result();

// If no skill_id specified but student has courses, use the first one
if ($skill_id == 0 && $enrolled_courses->num_rows > 0) {
    $first_course = $enrolled_courses->fetch_assoc();
    $skill_id = $first_course['skill_id'];
    $batch_id = $first_course['batch_id'];
    mysqli_data_seek($enrolled_courses, 0); // Reset pointer
}

// Fetch teaching materials
$materials_query = "
SELECT 
    tm.*,
    s.skill_name,
    b.batch_name,
    t.name as teacher_name,
    t.teacher_code,
    DATE_FORMAT(tm.uploaded_at, '%b %d, %Y %h:%i %p') as formatted_date,
    CASE 
        WHEN tm.file_type LIKE 'image%' THEN 'image'
        WHEN tm.file_type LIKE 'video%' THEN 'video'
        WHEN tm.file_type LIKE 'application/pdf' THEN 'pdf'
        WHEN tm.file_type LIKE 'application/msword' THEN 'doc'
        WHEN tm.file_type LIKE 'application/vnd.ms-powerpoint' THEN 'ppt'
        ELSE 'other'
    END as file_category
FROM teaching_materials tm
JOIN batches b ON tm.batch_id = b.id
JOIN skills s ON (SELECT skill_id FROM student_enrollments WHERE batch_id = b.id AND student_id = ? LIMIT 1) = s.id
LEFT JOIN teachers t ON tm.teacher_id = t.id
WHERE tm.status = 'active'
";

$params = array($student_id);
$types = "i";

// Apply filters
if ($skill_id > 0) {
    // Get batch IDs for the selected skill
    $materials_query .= " AND tm.batch_id IN (SELECT batch_id FROM student_enrollments WHERE student_id = ? AND skill_id = ?)";
    $params[] = $student_id;
    $params[] = $skill_id;
    $types .= "ii";
}

if ($batch_id > 0) {
    $materials_query .= " AND tm.batch_id = ?";
    $params[] = $batch_id;
    $types .= "i";
}

if (!empty($category) && $category !== 'all') {
    $materials_query .= " AND tm.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $materials_query .= " AND (tm.title LIKE ? OR tm.description LIKE ? OR s.skill_name LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$materials_query .= " ORDER BY tm.uploaded_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($materials_query);

// Bind parameters dynamically
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $params[0]);
}

$stmt->execute();
$materials = $stmt->get_result();
$total_materials = $materials->num_rows;

// Get categories for filter
$categories_query = "
SELECT DISTINCT category 
FROM teaching_materials tm
JOIN batches b ON tm.batch_id = b.id
WHERE b.id IN (SELECT batch_id FROM student_enrollments WHERE student_id = ?)
AND tm.status = 'active'
ORDER BY category
";
$stmt_categories = $conn->prepare($categories_query);
$stmt_categories->bind_param("i", $student_id);
$stmt_categories->execute();
$categories_result = $stmt_categories->get_result();

// Get course info if selected
$course_info = null;
if ($skill_id > 0 && $batch_id > 0) {
    $course_info_query = "
    SELECT 
        s.skill_name,
        b.batch_name,
        t.name as teacher_name,
        ses.session_name
    FROM student_enrollments se
    JOIN skills s ON se.skill_id = s.id
    JOIN batches b ON se.batch_id = b.id
    JOIN sessions ses ON se.session_id = ses.id
    LEFT JOIN batch_teachers bt ON b.id = bt.batch_id
    LEFT JOIN teachers t ON bt.teacher_id = t.id
    WHERE se.student_id = ? AND se.skill_id = ? AND se.batch_id = ?
    LIMIT 1
    ";
    $stmt_course = $conn->prepare($course_info_query);
    $stmt_course->bind_param("iii", $student_id, $skill_id, $batch_id);
    $stmt_course->execute();
    $course_info = $stmt_course->get_result()->fetch_assoc();
}

// Count materials by category
$category_counts = [];
if ($skill_id > 0 || $batch_id > 0) {
    $count_query = "
    SELECT 
        CASE 
            WHEN tm.file_type LIKE 'image%' THEN 'Images'
            WHEN tm.file_type LIKE 'video%' THEN 'Videos'
            WHEN tm.file_type LIKE 'application/pdf' THEN 'PDFs'
            WHEN tm.file_type LIKE 'application/msword' THEN 'Documents'
            WHEN tm.file_type LIKE 'application/vnd.ms-powerpoint' THEN 'Presentations'
            ELSE 'Others'
        END as category_name,
        COUNT(*) as count
    FROM teaching_materials tm
    WHERE tm.status = 'active'
    ";
    
    if ($skill_id > 0) {
        $count_query .= " AND tm.batch_id IN (SELECT batch_id FROM student_enrollments WHERE student_id = ? AND skill_id = ?)";
        $stmt_count = $conn->prepare($count_query);
        $stmt_count->bind_param("ii", $student_id, $skill_id);
    } else {
        $count_query .= " AND tm.batch_id = ?";
        $stmt_count = $conn->prepare($count_query);
        $stmt_count->bind_param("i", $batch_id);
    }
    
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    while ($row = $count_result->fetch_assoc()) {
        $category_counts[$row['category_name']] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Study Materials | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .material-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .material-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        .file-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .icon-pdf { background: #fee2e2; color: #dc2626; }
        .icon-doc { background: #dbeafe; color: #1d4ed8; }
        .icon-ppt { background: #fef3c7; color: #d97706; }
        .icon-video { background: #e0e7ff; color: #4f46e5; }
        .icon-image { background: #fce7f3; color: #be185d; }
        .icon-other { background: #d1fae5; color: #047857; }

        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-lecture { background: #dbeafe; color: #1e40af; }
        .badge-assignment { background: #fef3c7; color: #92400e; }
        .badge-reference { background: #d1fae5; color: #065f46; }
        .badge-project { background: #e0e7ff; color: #3730a3; }
        .badge-notes { background: #f3e8ff; color: #6b21a8; }

        .preview-container {
            height: 200px;
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .preview-placeholder {
            color: #9ca3af;
            text-align: center;
        }

        .download-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .filter-tab.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-tab:hover:not(.active) {
            background: #f1f5f9;
            border-color: #e5e7eb;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

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
                        <h1 class="text-3xl font-bold text-gray-800">Study Materials</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-download text-blue-500 mr-2"></i>
                            Access and download course materials uploaded by your teachers
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <i class="fas fa-folder-open mr-2"></i>
                            <span class="font-medium"><?php echo $total_materials; ?> Materials</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="material-card p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 mb-2">Filter Materials</h2>
                        <p class="text-sm text-gray-600">Find specific study materials</p>
                    </div>
                    
                    <!-- Search Box -->
                    <form method="GET" class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search materials..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            Search
                        </button>
                        <?php if (!empty($search) || !empty($category) || $skill_id > 0): ?>
                            <a href="materials.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Course Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Course</label>
                        <div class="flex gap-2">
                            <select id="courseSelect" onchange="changeCourse()" 
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- All Courses --</option>
                                <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['skill_id']; ?>_<?php echo $course['batch_id']; ?>"
                                            <?php echo ($skill_id == $course['skill_id'] && $batch_id == $course['batch_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['skill_name'] . ' - ' . $course['batch_name'] . ' (' . ($course['teacher_name'] ?? 'No Teacher') . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" onchange="window.location.href='materials.php?category='+this.value+'&skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>&search=<?php echo urlencode($search); ?>'" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all" <?php echo $category == 'all' || empty($category) ? 'selected' : ''; ?>>All Categories</option>
                            <?php while ($cat = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                        <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Quick Stats -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quick Stats</label>
                        <div class="flex gap-2">
                            <div class="flex-1 bg-gray-50 p-3 rounded-lg text-center">
                                <div class="text-lg font-bold text-blue-600"><?php echo $total_materials; ?></div>
                                <div class="text-xs text-gray-500">Materials</div>
                            </div>
                            <div class="flex-1 bg-gray-50 p-3 rounded-lg text-center">
                                <div class="text-lg font-bold text-green-600">
                                    <?php echo $enrolled_courses->num_rows; ?>
                                </div>
                                <div class="text-xs text-gray-500">Courses</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Tabs -->
                <?php if (!empty($category_counts)): ?>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Material Types</h3>
                        <div class="flex flex-wrap gap-2">
                            <a href="materials.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>&category=all&search=<?php echo urlencode($search); ?>"
                               class="filter-tab <?php echo empty($category) || $category == 'all' ? 'active' : ''; ?>">
                                All (<?php echo $total_materials; ?>)
                            </a>
                            <?php foreach ($category_counts as $cat_name => $count): ?>
                                <a href="materials.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>&category=<?php echo urlencode($cat_name); ?>&search=<?php echo urlencode($search); ?>"
                                   class="filter-tab <?php echo $category == $cat_name ? 'active' : ''; ?>">
                                    <?php echo $cat_name; ?> (<?php echo $count; ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Course Info (if selected) -->
            <?php if ($course_info): ?>
                <div class="material-card p-6 mb-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($course_info['skill_name']); ?></h2>
                            <div class="flex flex-wrap gap-3">
                                <span class="text-gray-600">
                                    <i class="fas fa-users mr-1"></i>
                                    Batch: <?php echo htmlspecialchars($course_info['batch_name']); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                                    Teacher: <?php echo htmlspecialchars($course_info['teacher_name'] ?? 'Not Assigned'); ?>
                                </span>
                                <span class="text-gray-600">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Session: <?php echo htmlspecialchars($course_info['session_name']); ?>
                                </span>
                            </div>
                        </div>
                        <a href="syllabus.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-book"></i>
                            View Syllabus
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Materials Grid -->
            <?php if ($total_materials > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php while ($material = $materials->fetch_assoc()): 
                        $file_ext = pathinfo($material['file_path'], PATHINFO_EXTENSION);
                        $file_size_mb = round($material['file_size'] / (1024 * 1024), 2);
                        
                        // Determine icon and color based on file type
                        $icon_class = 'icon-other';
                        $icon = 'fas fa-file';
                        
                        switch($material['file_category']) {
                            case 'pdf':
                                $icon_class = 'icon-pdf';
                                $icon = 'fas fa-file-pdf';
                                break;
                            case 'doc':
                                $icon_class = 'icon-doc';
                                $icon = 'fas fa-file-word';
                                break;
                            case 'ppt':
                                $icon_class = 'icon-ppt';
                                $icon = 'fas fa-file-powerpoint';
                                break;
                            case 'video':
                                $icon_class = 'icon-video';
                                $icon = 'fas fa-file-video';
                                break;
                            case 'image':
                                $icon_class = 'icon-image';
                                $icon = 'fas fa-file-image';
                                break;
                        }
                        
                        // Determine category badge
                        $category_badge_class = 'badge-other';
                        switch(strtolower($material['category'])) {
                            case 'lecture':
                                $category_badge_class = 'badge-lecture';
                                break;
                            case 'assignment':
                                $category_badge_class = 'badge-assignment';
                                break;
                            case 'reference':
                                $category_badge_class = 'badge-reference';
                                break;
                            case 'project':
                                $category_badge_class = 'badge-project';
                                break;
                            case 'notes':
                                $category_badge_class = 'badge-notes';
                                break;
                        }
                    ?>
                        <div class="material-card">
                            <!-- File Preview -->
                            <div class="preview-container p-4">
                                <?php if ($material['file_category'] == 'image' && file_exists($material['file_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($material['file_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($material['title']); ?>"
                                         class="preview-image">
                                <?php elseif ($material['file_category'] == 'pdf'): ?>
                                    <div class="text-center">
                                        <div class="<?php echo $icon_class; ?> file-icon mx-auto mb-2">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <p class="text-sm text-gray-500">PDF Document</p>
                                    </div>
                                <?php else: ?>
                                    <div class="preview-placeholder">
                                        <div class="<?php echo $icon_class; ?> file-icon mx-auto mb-2">
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <p class="text-sm text-gray-500"><?php echo strtoupper($file_ext); ?> File</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Content -->
                            <div class="p-6">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="<?php echo $category_badge_class; ?> category-badge mb-2">
                                            <?php echo htmlspecialchars($material['category']); ?>
                                        </span>
                                        <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($material['title']); ?></h3>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-download mr-1"></i>
                                        <?php echo $material['download_count']; ?>
                                    </span>
                                </div>

                                <!-- Description -->
                                <?php if (!empty($material['description'])): ?>
                                    <p class="text-gray-600 text-sm mb-4">
                                        <?php 
                                        $desc = $material['description'];
                                        if (strlen($desc) > 100) {
                                            echo htmlspecialchars(substr($desc, 0, 100)) . '...';
                                        } else {
                                            echo htmlspecialchars($desc);
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Course Info -->
                                <div class="text-sm text-gray-500 mb-4">
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="fas fa-book"></i>
                                        <span><?php echo htmlspecialchars($material['skill_name']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span><?php echo htmlspecialchars($material['teacher_name'] ?? 'Teacher'); ?></span>
                                    </div>
                                </div>

                                <!-- File Details -->
                                <div class="flex justify-between text-sm text-gray-600 mb-6">
                                    <div>
                                        <i class="far fa-file mr-1"></i>
                                        <span><?php echo strtoupper($file_ext); ?> • <?php echo $file_size_mb; ?> MB</span>
                                    </div>
                                    <div>
                                        <i class="far fa-clock mr-1"></i>
                                        <span><?php echo $material['formatted_date']; ?></span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2">
                                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" 
                                       target="_blank"
                                       class="download-btn flex-1 text-center"
                                       onclick="incrementDownload(<?php echo $material['id']; ?>)">
                                        <i class="fas fa-download mr-2"></i>
                                        Download
                                    </a>
                                    <?php if ($material['file_category'] == 'pdf'): ?>
                                        <a href="view_pdf.php?file=<?php echo urlencode($material['file_path']); ?>" 
                                           target="_blank"
                                           class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- No Materials Found -->
                <div class="material-card p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-4">
                        <?php if ($skill_id > 0): ?>
                            No Materials Available
                        <?php else: ?>
                            Select a Course
                        <?php endif; ?>
                    </h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        <?php if ($skill_id > 0): ?>
                            No study materials have been uploaded for this course yet.
                            Please check back later or contact your teacher.
                        <?php else: ?>
                            Please select a course from the dropdown above to view available study materials.
                        <?php endif; ?>
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <?php if ($skill_id > 0): ?>
                            <a href="my_courses.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2">
                                <i class="fas fa-arrow-left"></i>
                                Back to My Courses
                            </a>
                        <?php else: ?>
                            <button onclick="document.getElementById('courseSelect').focus()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2">
                                <i class="fas fa-search"></i>
                                Select a Course
                            </button>
                        <?php endif; ?>
                        <a href="syllabus.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-book"></i>
                            View Syllabus
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Uploads -->
            <?php if ($total_materials > 0): ?>
                <div class="material-card p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-history text-blue-500"></i>
                        Recently Uploaded Materials
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Material</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Uploaded</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php mysqli_data_seek($materials, 0); ?>
                                <?php for ($i = 0; $i < min(5, $total_materials); $i++): 
                                    $material = $materials->fetch_assoc();
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                                                    <i class="fas fa-file text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($material['title']); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($material['category']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($material['skill_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($material['batch_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($material['teacher_name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo $material['formatted_date']; ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <a href="<?php echo htmlspecialchars($material['file_path']); ?>" 
                                                   target="_blank"
                                                   class="text-blue-600 hover:text-blue-800 text-sm"
                                                   onclick="incrementDownload(<?php echo $material['id']; ?>)">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="syllabus.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>"
                                                   class="text-green-600 hover:text-green-800 text-sm">
                                                    <i class="fas fa-book"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Change course function
        function changeCourse() {
            const select = document.getElementById('courseSelect');
            const value = select.value;
            if (value) {
                const [skill_id, batch_id] = value.split('_');
                window.location.href = `materials.php?skill_id=${skill_id}&batch_id=${batch_id}`;
            } else {
                window.location.href = 'materials.php';
            }
        }

        // Increment download count
        function incrementDownload(materialId) {
            fetch('increment_download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `material_id=${materialId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Download count updated');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Preview file function
        function previewFile(filePath, fileType) {
            if (fileType.includes('image')) {
                window.open(filePath, '_blank');
            } else if (fileType.includes('pdf')) {
                window.open('view_pdf.php?file=' + encodeURIComponent(filePath), '_blank');
            } else {
                window.open(filePath, '_blank');
            }
        }

        // Auto-focus search on Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
        });
    </script>

</body>

</html>
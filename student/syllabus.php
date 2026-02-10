<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch student info
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

// Get skill_id and batch_id from URL
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Fetch enrolled courses for dropdown
$enrolled_query = "
SELECT DISTINCT 
    s.id AS skill_id,
    s.skill_name,
    b.id AS batch_id,
    b.batch_name,
    se.session_id,
    ses.session_name
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name, b.batch_name
";

$stmt_enrolled = $conn->prepare($enrolled_query);
$stmt_enrolled->bind_param("i", $student_id);
$stmt_enrolled->execute();
$enrolled_courses = $stmt_enrolled->get_result();

// Default to first course if none specified
if ($skill_id === 0 && $enrolled_courses->num_rows > 0) {
    $first_course = $enrolled_courses->fetch_assoc();
    $skill_id = $first_course['skill_id'];
    $batch_id = $first_course['batch_id'];
    mysqli_data_seek($enrolled_courses, 0); // Reset pointer
}

// Fetch syllabus for selected course
$syllabus = [];
$total_topics = 0;
$total_hours = 0;

if ($skill_id > 0 && $batch_id > 0) {
    $syllabus_query = "
    SELECT 
        ss.*,
        s.skill_name,
        b.batch_name,
        t.name AS teacher_name,
        ses.session_name
    FROM skill_syllabus ss
    JOIN skills s ON ss.skill_id = s.id
    JOIN batches b ON ss.batch_id = b.id
    JOIN student_enrollments se 
        ON se.skill_id = ss.skill_id 
       AND se.batch_id = ss.batch_id
       AND se.student_id = ?
    JOIN sessions ses ON se.session_id = ses.id
    LEFT JOIN teachers t ON ss.created_by = t.user_id
    WHERE ss.skill_id = ? AND ss.batch_id = ? AND ss.status = 'Active'
    ORDER BY ss.topic_order ASC
    ";

    $stmt_syllabus = $conn->prepare($syllabus_query);
    $stmt_syllabus->bind_param("iii", $student_id, $skill_id, $batch_id);
    $stmt_syllabus->execute();
    $syllabus_result = $stmt_syllabus->get_result();

    while ($topic = $syllabus_result->fetch_assoc()) {
        $syllabus[] = $topic;
        $total_topics++;
        $total_hours += $topic['duration_hours'] ?? 0;
    }

    // Fetch course info
    $course_info_query = "
    SELECT 
        s.skill_name,
        s.description,
        s.level,
        s.duration_months,
        b.batch_name,
        b.start_time,
        b.end_time,
        t.name AS teacher_name,
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

    // Fetch completed topics
    $completed_topics = 0;
    $progress_query = "
    SELECT topics_completed 
    FROM skill_progress 
    WHERE enrollment_id = (
        SELECT id FROM student_enrollments 
        WHERE student_id = ? AND skill_id = ? AND batch_id = ?
    )
    ";
    $stmt_progress = $conn->prepare($progress_query);
    $stmt_progress->bind_param("iii", $student_id, $skill_id, $batch_id);
    $stmt_progress->execute();
    $progress_result = $stmt_progress->get_result();
    if ($progress_row = $progress_result->fetch_assoc()) {
        $completed_topics = $progress_row['topics_completed'] ?? 0;
    }

    $completion_percentage = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;
} else {
    $course_info = null;
    $completed_topics = 0;
    $completion_percentage = 0;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>Course Syllabus | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .syllabus-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .syllabus-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .topic-card {
            border-left: 4px solid #3b82f6;
            background: linear-gradient(to right, #f8fafc, #ffffff);
        }

        .topic-card.completed {
            border-left-color: #10b981;
            background: linear-gradient(to right, #f0fdf4, #ffffff);
        }

        .topic-card.current {
            border-left-color: #f59e0b;
            background: linear-gradient(to right, #fffbeb, #ffffff);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
        }

        .file-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pdf { background: #fee2e2; color: #dc2626; }
        .badge-doc { background: #dbeafe; color: #1d4ed8; }
        .badge-ppt { background: #fef3c7; color: #d97706; }
        .badge-video { background: #e0e7ff; color: #4f46e5; }
        .badge-image { background: #fce7f3; color: #be185d; }
        .badge-link { background: #d1fae5; color: #047857; }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #6366f1 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .accordion-header {
            cursor: pointer;
            transition: all 0.2s;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .accordion-content.open {
            max-height: 1000px;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .toggle-icon.open {
            transform: rotate(180deg);
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
                        <h1 class="text-3xl font-bold text-gray-800">Course Syllabus</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-book text-blue-500 mr-2"></i>
                            Detailed course curriculum and learning materials
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <i class="fas fa-graduation-cap mr-2"></i>
                            <span class="font-medium">Student Portal</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Selection & Progress -->
            <div class="syllabus-card p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                    <!-- Course Selection -->
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Course</label>
                        <div class="flex gap-2">
                            <select id="courseSelect" onchange="changeCourse()" 
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Select a Course --</option>
                                <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['skill_id']; ?>_<?php echo $course['batch_id']; ?>"
                                            <?php echo ($skill_id == $course['skill_id'] && $batch_id == $course['batch_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['skill_name'] . ' - ' . $course['batch_name'] . ' (' . $course['session_name'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button onclick="changeCourse()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Stats -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600"><?php echo $total_topics; ?></div>
                                <div class="text-xs text-gray-500">Total Topics</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600"><?php echo $completed_topics; ?></div>
                                <div class="text-xs text-gray-500">Completed</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600"><?php echo $total_hours; ?></div>
                                <div class="text-xs text-gray-500">Hours</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <?php if ($course_info): ?>
                    <div class="mb-6">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Course Progress</span>
                            <span class="font-bold text-blue-600"><?php echo $completion_percentage; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <?php echo $completed_topics; ?> of <?php echo $total_topics; ?> topics completed
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($course_info): ?>
                <!-- Course Information -->
                <div class="syllabus-card p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Course Details -->
                        <div class="md:col-span-2">
                            <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($course_info['skill_name']); ?></h2>
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars($course_info['description'] ?? 'No description available.'); ?>
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                    <i class="fas fa-layer-group mr-1"></i>
                                    <?php echo htmlspecialchars($course_info['level'] ?? 'Beginner'); ?> Level
                                </span>
                                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?php echo $course_info['duration_months'] ?? 0; ?> Months
                                </span>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    <?php echo htmlspecialchars($course_info['session_name']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Batch & Teacher Info -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Course Details</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Batch:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($course_info['batch_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Timing:</span>
                                    <span class="font-medium">
                                        <?php echo date("h:i A", strtotime($course_info['start_time'])); ?> - <?php echo date("h:i A", strtotime($course_info['end_time'])); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Teacher:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($course_info['teacher_name'] ?? 'Not Assigned'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Syllabus Content -->
                <div class="syllabus-card p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Course Curriculum</h2>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                <span class="font-medium"><?php echo $completed_topics; ?></span> completed
                            </span>
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-clock text-yellow-500 mr-1"></i>
                                <span class="font-medium"><?php echo $total_hours; ?></span> total hours
                            </span>
                        </div>
                    </div>

                    <?php if (count($syllabus) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($syllabus as $index => $topic): 
                                $is_completed = ($index + 1) <= $completed_topics;
                                $is_current = ($index + 1) == ($completed_topics + 1);
                                $card_class = $is_completed ? 'topic-card completed' : ($is_current ? 'topic-card current' : 'topic-card');
                            ?>
                                <div class="<?php echo $card_class; ?> p-4 rounded-lg">
                                    <div class="accordion-header flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0 w-8 h-8 rounded-full <?php echo $is_completed ? 'bg-green-100' : ($is_current ? 'bg-yellow-100' : 'bg-blue-100'); ?> flex items-center justify-center">
                                                    <?php if ($is_completed): ?>
                                                        <i class="fas fa-check text-green-600"></i>
                                                    <?php elseif ($is_current): ?>
                                                        <i class="fas fa-play text-yellow-600"></i>
                                                    <?php else: ?>
                                                        <span class="text-blue-600 font-bold"><?php echo $index + 1; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-gray-800 mb-1">
                                                        <?php echo htmlspecialchars($topic['topic_title']); ?>
                                                        <?php if ($is_current): ?>
                                                            <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                                                <i class="fas fa-circle text-yellow-500 mr-1 text-xs"></i>Current Topic
                                                            </span>
                                                        <?php endif; ?>
                                                    </h3>
                                                    <div class="flex flex-wrap gap-2 mb-2">
                                                        <span class="text-sm text-gray-600">
                                                            <i class="far fa-clock mr-1"></i>
                                                            <?php echo $topic['duration_hours'] ?? 0; ?> hours
                                                        </span>
                                                        <span class="text-sm text-gray-600">
                                                            <i class="fas fa-sort-numeric-up mr-1"></i>
                                                            Topic <?php echo $topic['topic_order']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button onclick="toggleAccordion(<?php echo $index; ?>)" 
                                                class="text-gray-400 hover:text-gray-600 ml-4">
                                            <i class="fas fa-chevron-down toggle-icon" id="toggleIcon<?php echo $index; ?>"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="accordion-content ml-11" id="accordionContent<?php echo $index; ?>">
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <!-- Topic Description -->
                                            <?php if (!empty($topic['topic_description'])): ?>
                                                <div class="mb-4">
                                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Description</h4>
                                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($topic['topic_description']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Learning Outcomes -->
                                            <?php if (!empty($topic['learning_outcomes'])): ?>
                                                <div class="mb-4">
                                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Learning Outcomes</h4>
                                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($topic['learning_outcomes']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Prerequisites -->
                                            <?php if (!empty($topic['prerequisites'])): ?>
                                                <div class="mb-4">
                                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Prerequisites</h4>
                                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($topic['prerequisites']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Resources -->
                                            <div class="mb-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Learning Resources</h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php if (!empty($topic['file_path']) && file_exists($topic['file_path'])): 
                                                        $file_ext = pathinfo($topic['file_name'], PATHINFO_EXTENSION);
                                                        $badge_class = 'badge-pdf';
                                                        $icon = 'fas fa-file-pdf';
                                                        
                                                        switch(strtolower($file_ext)) {
                                                            case 'pdf':
                                                                $badge_class = 'badge-pdf';
                                                                $icon = 'fas fa-file-pdf';
                                                                break;
                                                            case 'doc':
                                                            case 'docx':
                                                                $badge_class = 'badge-doc';
                                                                $icon = 'fas fa-file-word';
                                                                break;
                                                            case 'ppt':
                                                            case 'pptx':
                                                                $badge_class = 'badge-ppt';
                                                                $icon = 'fas fa-file-powerpoint';
                                                                break;
                                                            case 'jpg':
                                                            case 'jpeg':
                                                            case 'png':
                                                            case 'gif':
                                                                $badge_class = 'badge-image';
                                                                $icon = 'fas fa-file-image';
                                                                break;
                                                            default:
                                                                $badge_class = 'badge-doc';
                                                                $icon = 'fas fa-file';
                                                        }
                                                    ?>
                                                        <a href="<?php echo htmlspecialchars($topic['file_path']); ?>" 
                                                           target="_blank" 
                                                           class="file-badge <?php echo $badge_class; ?> hover:opacity-80">
                                                            <i class="<?php echo $icon; ?>"></i>
                                                            <?php echo htmlspecialchars($topic['file_name']); ?>
                                                            <span class="text-xs">(<?php echo round($topic['file_size'] / 1024, 1); ?> KB)</span>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!empty($topic['external_link'])): ?>
                                                        <a href="<?php echo htmlspecialchars($topic['external_link']); ?>" 
                                                           target="_blank" 
                                                           class="file-badge badge-link hover:opacity-80">
                                                            <i class="fas fa-external-link-alt"></i>
                                                            External Resource
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!empty($topic['content_text'])): ?>
                                                        <button onclick="showContentModal('<?php echo addslashes($topic['content_text']); ?>', '<?php echo htmlspecialchars($topic['topic_title']); ?>')"
                                                                class="file-badge badge-doc hover:opacity-80">
                                                            <i class="fas fa-file-alt"></i>
                                                            Text Content
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex gap-2">
                                                <?php if (!$is_completed): ?>
                                                    <button onclick="markTopicComplete(<?php echo $topic['id']; ?>, <?php echo $index; ?>)"
                                                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                                                        <i class="fas fa-check mr-1"></i> Mark as Complete
                                                    </button>
                                                <?php endif; ?>
                                                <a href="../student/materials.php?skill_id=<?php echo $skill_id; ?>&batch_id=<?php echo $batch_id; ?>&topic_id=<?php echo $topic['id']; ?>"
                                                   class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                                                    <i class="fas fa-book-open mr-1"></i> Study Materials
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-book text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Syllabus Available</h3>
                            <p class="text-gray-500 mb-6">The syllabus for this course has not been published yet.</p>
                            <p class="text-sm text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                Please check back later or contact your teacher
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="syllabus-card p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-list-ol text-blue-600"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-800"><?php echo $total_topics; ?></div>
                                <div class="text-xs text-gray-500">Total Topics</div>
                            </div>
                        </div>
                    </div>

                    <div class="syllabus-card p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-800"><?php echo $completed_topics; ?></div>
                                <div class="text-xs text-gray-500">Completed</div>
                            </div>
                        </div>
                    </div>

                    <div class="syllabus-card p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-hourglass-half text-yellow-600"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-800"><?php echo $total_topics - $completed_topics; ?></div>
                                <div class="text-xs text-gray-500">Remaining</div>
                            </div>
                        </div>
                    </div>

                    <div class="syllabus-card p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-purple-600"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-800"><?php echo $total_hours; ?></div>
                                <div class="text-xs text-gray-500">Total Hours</div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- No Course Selected -->
                <div class="syllabus-card p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-book-open text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-4">Select a Course</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        Please select a course from the dropdown above to view its syllabus and learning materials.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="my_courses.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Back to My Courses
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Content Modal -->
    <div id="contentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 id="modalTitle" class="text-xl font-bold text-gray-800"></h3>
                    <button onclick="closeContentModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div id="modalContent" class="text-gray-600"></div>
            </div>
            <div class="p-6 border-t border-gray-200 text-right">
                <button onclick="closeContentModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Change course function
        function changeCourse() {
            const select = document.getElementById('courseSelect');
            const value = select.value;
            if (value) {
                const [skill_id, batch_id] = value.split('_');
                window.location.href = `syllabus.php?skill_id=${skill_id}&batch_id=${batch_id}`;
            }
        }

        // Accordion toggle
        function toggleAccordion(index) {
            const content = document.getElementById('accordionContent' + index);
            const icon = document.getElementById('toggleIcon' + index);
            
            content.classList.toggle('open');
            icon.classList.toggle('open');
        }

        // Mark topic as complete
        function markTopicComplete(topicId, index) {
            if (confirm('Mark this topic as completed?')) {
                // AJAX call to update progress
                fetch('update_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `topic_id=${topicId}&action=complete`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Topic marked as complete!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }

        // Content modal
        function showContentModal(content, title) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = content.replace(/\n/g, '<br>');
            document.getElementById('contentModal').classList.remove('hidden');
            document.getElementById('contentModal').classList.add('flex');
        }

        function closeContentModal() {
            document.getElementById('contentModal').classList.add('hidden');
            document.getElementById('contentModal').classList.remove('flex');
        }

        // Auto-open current topic
        document.addEventListener('DOMContentLoaded', function() {
            const completedTopics = <?php echo $completed_topics; ?>;
            if (completedTopics < <?php echo $total_topics; ?>) {
                // Open the current topic (next one to complete)
                setTimeout(() => {
                    const currentIndex = completedTopics;
                    const content = document.getElementById('accordionContent' + currentIndex);
                    const icon = document.getElementById('toggleIcon' + currentIndex);
                    if (content && icon) {
                        content.classList.add('open');
                        icon.classList.add('open');
                    }
                }, 500);
            }
        });
    </script>

</body>

</html>
<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

// Get teacher ID from teachers table using user_id from session
$user_id = $_SESSION['user_id'];

// Fetch teacher details
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
$_SESSION['teacher_id'] = $teacher_id;

// Get filter parameters
$skill_filter = isset($_GET['skill']) ? intval($_GET['skill']) : 0;
$session_filter = isset($_GET['session']) ? intval($_GET['session']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the base query
$query = "
SELECT 
    b.id AS batch_id,
    b.batch_name,
    b.start_time,
    b.end_time,
    b.max_students,
    b.status AS batch_status,
    s.id AS skill_id,
    s.skill_name,
    se.id AS session_id,
    se.session_name,
    
    COUNT(se_student.student_id) AS enrolled_students
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
JOIN sessions se ON b.session_id = se.id
LEFT JOIN student_enrollments se_student ON b.id = se_student.batch_id AND se_student.status = 'active'
WHERE ta.teacher_id = ?
";

// Add conditions based on filters
$conditions = [];
$params = [$teacher_id];
$param_types = "i";

if ($skill_filter > 0) {
    $conditions[] = "b.skill_id = ?";
    $params[] = $skill_filter;
    $param_types .= "i";
}

if ($session_filter > 0) {
    $conditions[] = "b.session_id = ?";
    $params[] = $session_filter;
    $param_types .= "i";
}

if ($status_filter !== 'all') {
    if ($status_filter === 'active') {
        $conditions[] = "b.status = 'active'";
    } elseif ($status_filter === 'completed') {
        $conditions[] = "b.status = 'completed'";
    }
}

if (!empty($search_query)) {
    $conditions[] = "(b.batch_name LIKE ? OR s.skill_name LIKE ?)";
    $search_term = "%{$search_query}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "ss";
}

// Add conditions to query
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY b.id ORDER BY 
    CASE b.status 
        WHEN 'active' THEN 1 
        WHEN 'completed' THEN 2 
        ELSE 3 
    END,
    b.start_time, b.batch_name";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$batches = $stmt->get_result();
$total_batches = $batches->num_rows;

// Get available skills for filter
$skills_query = "
SELECT DISTINCT s.id, s.skill_name
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE ta.teacher_id = ?
ORDER BY s.skill_name
";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $teacher_id);
$stmt_skills->execute();
$available_skills = $stmt_skills->get_result();

// Get available sessions for filter
$sessions_query = "
SELECT DISTINCT se.id, se.session_name
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN sessions se ON b.session_id = se.id
WHERE ta.teacher_id = ?
ORDER BY se.created_at DESC
";
$stmt_sessions = $conn->prepare($sessions_query);
$stmt_sessions->bind_param("i", $teacher_id);
$stmt_sessions->execute();
$available_sessions = $stmt_sessions->get_result();

// Count batches by status
$active_count = 0;
$completed_count = 0;

if ($total_batches > 0) {
    mysqli_data_seek($batches, 0);
    while ($row = $batches->fetch_assoc()) {
        if ($row['batch_status'] === 'active') {
            $active_count++;
        } elseif ($row['batch_status'] === 'completed') {
            $completed_count++;
        }
    }
    mysqli_data_seek($batches, 0); // Reset pointer
}

// Calculate batch statistics
$total_enrolled_students = 0;
$total_capacity = 0;

if ($total_batches > 0) {
    mysqli_data_seek($batches, 0);
    while ($row = $batches->fetch_assoc()) {
        $total_enrolled_students += $row['enrolled_students'];
        $total_capacity += $row['max_students'];
    }
    mysqli_data_seek($batches, 0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Batches | Teacher Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
            --warning: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 4px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #e5e7eb;
            color: #4b5563;
        }

        .batch-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .batch-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .tab-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
                        <h1 class="text-3xl font-bold text-gray-800">My Batches</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-users text-blue-500 mr-2"></i>
                            Manage and view all your assigned batches
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-user-tie mr-1"></i>
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Batches</h3>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $total_batches; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-layer-group text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        All batches assigned to you
                    </p>
                </div>

                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Active Batches</h3>
                            <p class="text-3xl font-bold text-green-600"><?php echo $active_count; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-play-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Currently running batches
                    </p>
                </div>

                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Completed Batches</h3>
                            <p class="text-3xl font-bold text-gray-600"><?php echo $completed_count; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-gray-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Past completed batches
                    </p>
                </div>

                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Students</h3>
                            <p class="text-3xl font-bold text-purple-600"><?php echo $total_enrolled_students; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-graduate text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Students across all batches
                    </p>
                    <?php if ($total_capacity > 0): ?>
                        <div class="mt-2">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(($total_enrolled_students / $total_capacity) * 100, 100); ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo $total_enrolled_students; ?>/<?php echo $total_capacity; ?> capacity
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-gray-800 mb-2">Filter Batches</h3>
                        <p class="text-sm text-gray-500">Find specific batches using filters</p>
                    </div>

                    <!-- Status Tabs -->
                    <div class="flex flex-wrap gap-2">
                        <a href="?status=all<?php echo $skill_filter ? '&skill=' . $skill_filter : ''; ?><?php echo $session_filter ? '&session=' . $session_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="tab-btn <?php echo $status_filter === 'all' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                            All (<?php echo $total_batches; ?>)
                        </a>
                        <a href="?status=active<?php echo $skill_filter ? '&skill=' . $skill_filter : ''; ?><?php echo $session_filter ? '&session=' . $session_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="tab-btn <?php echo $status_filter === 'active' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                            Active (<?php echo $active_count; ?>)
                        </a>
                        <a href="?status=completed<?php echo $skill_filter ? '&skill=' . $skill_filter : ''; ?><?php echo $session_filter ? '&session=' . $session_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="tab-btn <?php echo $status_filter === 'completed' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>">
                            Completed (<?php echo $completed_count; ?>)
                        </a>
                    </div>
                </div>

                <!-- Filter Form -->
                <form method="GET" class="mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-search mr-1"></i> Search
                            </label>
                            <input type="text"
                                name="search"
                                value="<?php echo htmlspecialchars($search_query); ?>"
                                placeholder="Search batches..."
                                class="w-full filter-input px-4 py-2 rounded-lg">
                        </div>

                        <!-- Skill Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-book mr-1"></i> Skill/Course
                            </label>
                            <select name="skill"
                                class="w-full filter-input px-4 py-2 rounded-lg">
                                <option value="0">All Skills</option>
                                <?php while ($skill = $available_skills->fetch_assoc()): ?>
                                    <option value="<?php echo $skill['id']; ?>"
                                        <?php echo $skill_filter == $skill['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Session Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt mr-1"></i> Session
                            </label>
                            <select name="session"
                                class="w-full filter-input px-4 py-2 rounded-lg">
                                <option value="0">All Sessions</option>
                                <?php while ($session = $available_sessions->fetch_assoc()): ?>
                                    <option value="<?php echo $session['id']; ?>"
                                        <?php echo $session_filter == $session['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($session['session_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-medium transition-colors">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                            <a href="my_batches.php"
                                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2.5 rounded-lg font-medium transition-colors text-center">
                                <i class="fas fa-redo mr-2"></i> Reset
                            </a>
                        </div>
                    </div>

                    <!-- Hidden status field -->
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                </form>

                <!-- Active Filters -->
                <?php if ($skill_filter > 0 || $session_filter > 0 || !empty($search_query)): ?>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-600 mb-2">Active Filters:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php if ($skill_filter > 0):
                                mysqli_data_seek($available_skills, 0);
                                $skill_name = '';
                                while ($skill = $available_skills->fetch_assoc()) {
                                    if ($skill['id'] == $skill_filter) {
                                        $skill_name = $skill['skill_name'];
                                        break;
                                    }
                                }
                            ?>
                                <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                                    Skill: <?php echo htmlspecialchars($skill_name); ?>
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['skill']);
                                                echo http_build_query($params);
                                                ?>" class="text-blue-600 hover:text-blue-800 ml-1">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>

                            <?php if ($session_filter > 0):
                                mysqli_data_seek($available_sessions, 0);
                                $session_name = '';
                                while ($session = $available_sessions->fetch_assoc()) {
                                    if ($session['id'] == $session_filter) {
                                        $session_name = $session['session_name'];
                                        break;
                                    }
                                }
                            ?>
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                                    Session: <?php echo htmlspecialchars($session_name); ?>
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['session']);
                                                echo http_build_query($params);
                                                ?>" class="text-green-600 hover:text-green-800 ml-1">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($search_query)): ?>
                                <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">
                                    Search: "<?php echo htmlspecialchars($search_query); ?>"
                                    <a href="?<?php
                                                $params = $_GET;
                                                unset($params['search']);
                                                echo http_build_query($params);
                                                ?>" class="text-purple-600 hover:text-purple-800 ml-1">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Batches Grid/Table -->
            <?php if ($total_batches > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php while ($batch = $batches->fetch_assoc()):
                        $batch_id = $batch['batch_id'];
                        $enrolled_students = $batch['enrolled_students'];
                        $max_students = $batch['max_students'];
                        $percentage = $max_students > 0 ? ($enrolled_students / $max_students) * 100 : 0;
                        $is_active = $batch['batch_status'] === 'active';

                        // Calculate days left for active batches
                        $days_left = '';
                        if ($is_active && !empty($batch['end_date'])) {
                            $end_date = new DateTime($batch['end_date']);
                            $today = new DateTime();
                            $interval = $today->diff($end_date);
                            $days_left = $interval->days;
                        }

                        // Get today's attendance count
                        $today = date('Y-m-d');
                        $att_today_count = 0;
                        try {
                            $att_today_query = "SELECT COUNT(*) as att_count FROM student_attendance 
                                               WHERE batch_id = ? AND attendance_date = ?";
                            $stmt_att = $conn->prepare($att_today_query);
                            $stmt_att->bind_param("is", $batch_id, $today);
                            $stmt_att->execute();
                            $att_result = $stmt_att->get_result()->fetch_assoc();
                            $att_today_count = $att_result['att_count'] ?? 0;
                        } catch (Exception $e) {
                            // Table might not exist, set default value
                            $att_today_count = 0;
                        }

                        // Get average score (handle missing table)
                        $avg_score = 'N/A';
                        try {
                            $avg_score_query = "SELECT AVG(score) as avg_score FROM quiz_results 
                                              WHERE batch_id = ?";
                            $stmt_score = $conn->prepare($avg_score_query);
                            $stmt_score->bind_param("i", $batch_id);
                            $stmt_score->execute();
                            $score_result = $stmt_score->get_result()->fetch_assoc();
                            if ($score_result['avg_score']) {
                                $avg_score = round($score_result['avg_score'], 1) . '%';
                            }
                        } catch (Exception $e) {
                            // Table doesn't exist, keep N/A
                        }

                        // Get materials count (handle missing table)
                        $mat_count = 0;
                        try {
                            $mat_query = "SELECT COUNT(*) as mat_count FROM teaching_materials 
                                        WHERE batch_id = ?";
                            $stmt_mat = $conn->prepare($mat_query);
                            $stmt_mat->bind_param("i", $batch_id);
                            $stmt_mat->execute();
                            $mat_result = $stmt_mat->get_result()->fetch_assoc();
                            $mat_count = $mat_result['mat_count'] ?? 0;
                        } catch (Exception $e) {
                            // Table doesn't exist, set to 0
                            $mat_count = 0;
                        }
                    ?>
                        <div class="batch-card card p-5 hover:border-blue-300">
                            <!-- Batch Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($batch['batch_name']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($batch['skill_name']); ?></p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-completed'; ?>">
                                        <i class="fas fa-<?php echo $is_active ? 'play-circle' : 'check-circle'; ?>"></i>
                                        <?php echo ucfirst($batch['batch_status']); ?>
                                    </span>
                                    <?php if ($is_active && !empty($days_left)): ?>
                                        <span class="text-xs text-yellow-600 mt-1">
                                            <i class="fas fa-clock"></i> <?php echo $days_left; ?> days left
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Batch Details -->
                            <div class="space-y-3 mb-4">
                                <div class="flex items-center gap-3 text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-calendar-alt w-4"></i>
                                        <span><?php echo htmlspecialchars($batch['session_name']); ?></span>
                                    </div>
                                    <?php if (!empty($batch['start_date'])): ?>
                                        <div class="flex items-center gap-1">
                                            <i class="fas fa-calendar w-4"></i>
                                            <span><?php echo date('M d, Y', strtotime($batch['start_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-3 text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-clock w-4"></i>
                                        <span><?php echo date('h:i A', strtotime($batch['start_time'])); ?> - <?php echo date('h:i A', strtotime($batch['end_time'])); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-user-graduate w-4"></i>
                                        <span><?php echo $enrolled_students; ?> students</span>
                                    </div>
                                </div>

                                <!-- Capacity Progress Bar -->
                                <div>
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Capacity</span>
                                        <span><?php echo $enrolled_students; ?>/<?php echo $max_students; ?></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo round($percentage, 1); ?>% filled
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-100">
                                <a href="attendance.php?batch_id=<?php echo $batch_id; ?>"
                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100 flex-1 justify-center"
                                    title="Take Attendance">
                                    <i class="fas fa-clipboard-check"></i>
                                    Attendance
                                </a>

                                <a href="student_progress.php?batch_id=<?php echo $batch_id; ?>"
                                    class="action-btn bg-green-50 text-green-700 hover:bg-green-100 flex-1 justify-center"
                                    title="Student Progress">
                                    <i class="fas fa-chart-line"></i>
                                    Progress
                                </a>

                                <a href="quizzes.php?batch_id=<?php echo $batch_id; ?>"
                                    class="action-btn bg-purple-50 text-purple-700 hover:bg-purple-100 flex-1 justify-center"
                                    title="Quizzes">
                                    <i class="fas fa-question-circle"></i>
                                    Quizzes
                                </a>

                                <!-- View Details Button -->
                                <button onclick="showBatchDetails(<?php echo $batch_id; ?>)"
                                    class="action-btn bg-gray-50 text-gray-700 hover:bg-gray-100 flex-1 justify-center"
                                    title="View Details">
                                    <i class="fas fa-eye"></i>
                                    Details
                                </button>
                            </div>

                            <!-- Quick Stats -->
                            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                                <div class="p-2 bg-gray-50 rounded">
                                    <p class="text-xs text-gray-500">Today's Att.</p>
                                    <p class="font-medium text-gray-800">
                                        <?php echo $att_today_count; ?>
                                    </p>
                                </div>
                                <div class="p-2 bg-gray-50 rounded">
                                    <p class="text-xs text-gray-500">Avg. Score</p>
                                    <p class="font-medium text-gray-800">
                                        <?php echo $avg_score; ?>
                                    </p>
                                </div>
                                <div class="p-2 bg-gray-50 rounded">
                                    <p class="text-xs text-gray-500">Materials</p>
                                    <p class="font-medium text-gray-800">
                                        <?php echo $mat_count; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- No Batches Found -->
                <div class="card p-8 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-gray-300 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Batches Found</h3>
                    <p class="text-gray-500 mb-6">
                        <?php if ($skill_filter > 0 || $session_filter > 0 || !empty($search_query)): ?>
                            No batches match your current filters.
                        <?php else: ?>
                            You don't have any batches assigned yet.
                        <?php endif; ?>
                    </p>
                    <div class="flex justify-center gap-3">
                        <?php if ($skill_filter > 0 || $session_filter > 0 || !empty($search_query)): ?>
                            <a href="my_batches.php"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                                <i class="fas fa-redo mr-2"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                        <a href="../admin/batches.php"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-info-circle mr-2"></i> Contact Admin
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Batch Details Modal (Hidden by default) -->
            <div id="batchDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
                <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800">Batch Details</h3>
                            <button onclick="hideBatchDetails()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>
                        <div id="batchDetailsContent">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Show batch details modal
        function showBatchDetails(batchId) {
            // Show loading
            document.getElementById('batchDetailsContent').innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-3xl mb-4"></i>
                    <p class="text-gray-600">Loading batch details...</p>
                </div>
            `;

            // Show modal
            document.getElementById('batchDetailsModal').classList.remove('hidden');
            document.getElementById('batchDetailsModal').classList.add('flex');

            // Load batch details via AJAX
            fetch(`get_batch_details.php?batch_id=${batchId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('batchDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('batchDetailsContent').innerHTML = `
                        <div class="text-center py-12">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                            <p class="text-gray-600">Failed to load batch details.</p>
                            <button onclick="showBatchDetails(${batchId})" class="mt-4 text-blue-600 hover:text-blue-800">
                                <i class="fas fa-redo mr-1"></i> Try Again
                            </button>
                        </div>
                    `;
                });
        }

        // Hide batch details modal
        function hideBatchDetails() {
            document.getElementById('batchDetailsModal').classList.add('hidden');
            document.getElementById('batchDetailsModal').classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('batchDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideBatchDetails();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideBatchDetails();
            }
        });
    </script>
</body>

</html>
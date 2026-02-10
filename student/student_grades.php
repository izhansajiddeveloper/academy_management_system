<?php
// grades_results.php
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

// Get filter parameters
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$time_filter = isset($_GET['time_filter']) ? $_GET['time_filter'] : 'all';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

// Calculate date ranges for time filter
$date_condition = "";
if ($time_filter === 'week') {
    $date_condition = "AND qr.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($time_filter === 'month') {
    $date_condition = "AND qr.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($time_filter === 'quarter') {
    $date_condition = "AND qr.submitted_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
}

// Calculate status filter condition
$status_condition = "";
if ($status_filter === 'passed') {
    $status_condition = "AND (qr.score / q.total_marks) >= 0.6";
} elseif ($status_filter === 'failed') {
    $status_condition = "AND (qr.score / q.total_marks) < 0.6";
}

// Fetch all quiz results for the student with filters
$results_query = "
SELECT 
    qr.id as result_id,
    qr.quiz_id,
    qr.score,
    qr.correct_answers,
    qr.submitted_at,
    q.title as quiz_title,
    q.description,
    q.total_marks,
    q.total_questions,
    b.batch_name,
    s.skill_name,
    s.id as skill_id,
    b.id as batch_id,
    ROUND((qr.score / q.total_marks) * 100, 1) as percentage,
    CASE 
        WHEN (qr.score / q.total_marks) >= 0.9 THEN 'A'
        WHEN (qr.score / q.total_marks) >= 0.8 THEN 'B'
        WHEN (qr.score / q.total_marks) >= 0.7 THEN 'C'
        WHEN (qr.score / q.total_marks) >= 0.6 THEN 'D'
        ELSE 'F'
    END as grade
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
JOIN batches b ON q.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE qr.student_id = ?
";

// Add conditions based on filters
if ($skill_id > 0) {
    $results_query .= " AND s.id = ?";
}
if ($batch_id > 0) {
    $results_query .= " AND b.id = ?";
}
$results_query .= " $date_condition $status_condition ORDER BY qr.submitted_at DESC";

// Prepare and execute query
$stmt_results = $conn->prepare($results_query);
if ($skill_id > 0 && $batch_id > 0) {
    $stmt_results->bind_param("iii", $student_id, $skill_id, $batch_id);
} elseif ($skill_id > 0) {
    $stmt_results->bind_param("ii", $student_id, $skill_id);
} elseif ($batch_id > 0) {
    $stmt_results->bind_param("ii", $student_id, $batch_id);
} else {
    $stmt_results->bind_param("i", $student_id);
}
$stmt_results->execute();
$results = $stmt_results->get_result();

// Fetch all skills and batches for filter dropdowns
$skills_query = "
SELECT DISTINCT s.id, s.skill_name
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
JOIN batches b ON q.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE qr.student_id = ?
ORDER BY s.skill_name
";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $student_id);
$stmt_skills->execute();
$skills = $stmt_skills->get_result();

$batches_query = "
SELECT DISTINCT b.id, b.batch_name, s.skill_name
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
JOIN batches b ON q.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE qr.student_id = ?
ORDER BY b.batch_name
";
$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $student_id);
$stmt_batches->execute();
$batches = $stmt_batches->get_result();

// Calculate overall statistics
$overall_query = "
SELECT 
    COUNT(*) as total_quizzes,
    SUM(q.total_marks) as total_possible_marks,
    SUM(qr.score) as total_earned_marks,
    AVG(qr.score / q.total_marks) * 100 as avg_percentage,
    SUM(CASE WHEN (qr.score / q.total_marks) >= 0.6 THEN 1 ELSE 0 END) as passed_quizzes,
    SUM(CASE WHEN (qr.score / q.total_marks) < 0.6 THEN 1 ELSE 0 END) as failed_quizzes
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
WHERE qr.student_id = ? $date_condition $status_condition
";
$stmt_overall = $conn->prepare($overall_query);
$stmt_overall->bind_param("i", $student_id);
$stmt_overall->execute();
$overall_stats = $stmt_overall->get_result()->fetch_assoc();

// Calculate grade distribution
$grade_distribution_query = "
SELECT 
    CASE 
        WHEN (qr.score / q.total_marks) >= 0.9 THEN 'A'
        WHEN (qr.score / q.total_marks) >= 0.8 THEN 'B'
        WHEN (qr.score / q.total_marks) >= 0.7 THEN 'C'
        WHEN (qr.score / q.total_marks) >= 0.6 THEN 'D'
        ELSE 'F'
    END as grade,
    COUNT(*) as count
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
WHERE qr.student_id = ? $date_condition $status_condition
GROUP BY 
    CASE 
        WHEN (qr.score / q.total_marks) >= 0.9 THEN 'A'
        WHEN (qr.score / q.total_marks) >= 0.8 THEN 'B'
        WHEN (qr.score / q.total_marks) >= 0.7 THEN 'C'
        WHEN (qr.score / q.total_marks) >= 0.6 THEN 'D'
        ELSE 'F'
    END
ORDER BY 
    CASE grade
        WHEN 'A' THEN 1
        WHEN 'B' THEN 2
        WHEN 'C' THEN 3
        WHEN 'D' THEN 4
        WHEN 'F' THEN 5
    END
";
$stmt_grades = $conn->prepare($grade_distribution_query);
$stmt_grades->bind_param("i", $student_id);
$stmt_grades->execute();
$grade_distribution = $stmt_grades->get_result();

// Prepare data for performance trend chart
$trend_query = "
SELECT 
    DATE(qr.submitted_at) as quiz_date,
    AVG((qr.score / q.total_marks) * 100) as avg_percentage,
    COUNT(*) as quiz_count
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
WHERE qr.student_id = ? 
    AND qr.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(qr.submitted_at)
ORDER BY quiz_date ASC
";
$stmt_trend = $conn->prepare($trend_query);
$stmt_trend->bind_param("i", $student_id);
$stmt_trend->execute();
$trend_data = $stmt_trend->get_result();

$trend_labels = [];
$trend_percentages = [];
while ($trend = $trend_data->fetch_assoc()) {
    $trend_labels[] = date('M d', strtotime($trend['quiz_date']));
    $trend_percentages[] = round($trend['avg_percentage'], 1);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Grades & Results | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .grade-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .grade-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .grade-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
        }

        .grade-a {
            background: #10b981;
            color: white;
        }

        .grade-b {
            background: #3b82f6;
            color: white;
        }

        .grade-c {
            background: #f59e0b;
            color: white;
        }

        .grade-d {
            background: #ef4444;
            color: white;
        }

        .grade-f {
            background: #7f1d1d;
            color: white;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            position: absolute;
            top: 0;
            left: 0;
            transition: width 0.5s ease;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            background: #e5e7eb;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .filter-tag.active {
            background: #3b82f6;
            color: white;
        }

        .performance-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .performance-excellent {
            background: #d1fae5;
            color: #065f46;
            border: 3px solid #10b981;
        }

        .performance-good {
            background: #dbeafe;
            color: #1e40af;
            border: 3px solid #3b82f6;
        }

        .performance-average {
            background: #fef3c7;
            color: #92400e;
            border: 3px solid #f59e0b;
        }

        .performance-poor {
            background: #fee2e2;
            color: #991b1b;
            border: 3px solid #ef4444;
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
                        <h1 class="text-3xl font-bold text-gray-800">My Grades & Results</h1>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-graduation-cap text-blue-500 mr-2"></i>
                            Track your quiz performance and progress
                        </p>
                    </div>
                    <div>
                        <a href="student_quiz.php" class="text-gray-600 hover:text-gray-800 mr-4">
                            <i class="fas fa-list mr-2"></i> View Quizzes
                        </a>
                        <button onclick="printGrades()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-print mr-2"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-2"><?php echo $overall_stats['total_quizzes'] ?? 0; ?></div>
                        <div class="text-sm opacity-90">Total Quizzes Taken</div>
                        <i class="fas fa-clipboard-check absolute top-4 right-4 text-xl opacity-50"></i>
                    </div>
                </div>

                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-2"><?php echo round($overall_stats['avg_percentage'] ?? 0, 1); ?>%</div>
                        <div class="text-sm opacity-90">Average Score</div>
                        <i class="fas fa-chart-line absolute top-4 right-4 text-xl opacity-50"></i>
                    </div>
                </div>

                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-2"><?php echo $overall_stats['passed_quizzes'] ?? 0; ?></div>
                        <div class="text-sm opacity-90">Quizzes Passed</div>
                        <i class="fas fa-check-circle absolute top-4 right-4 text-xl opacity-50"></i>
                    </div>
                </div>

                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-2">
                            <?php
                            $total = $overall_stats['total_quizzes'] ?? 1;
                            $passed = $overall_stats['passed_quizzes'] ?? 0;
                            echo $total > 0 ? round(($passed / $total) * 100, 0) : 0;
                            ?>%
                        </div>
                        <div class="text-sm opacity-90">Pass Rate</div>
                        <i class="fas fa-trophy absolute top-4 right-4 text-xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="grade-card p-6 mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-800">Filter Results</h3>

                    <div class="flex flex-wrap gap-4">
                        <!-- Skill Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Skill</label>
                            <select id="skillFilter" class="w-48 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="0">All Skills</option>
                                <?php while ($skill = $skills->fetch_assoc()): ?>
                                    <option value="<?php echo $skill['id']; ?>" <?php echo $skill_id == $skill['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Batch Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                            <select id="batchFilter" class="w-48 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="0">All Batches</option>
                                <?php while ($batch = $batches->fetch_assoc()): ?>
                                    <option value="<?php echo $batch['id']; ?>" <?php echo $batch_id == $batch['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($batch['batch_name'] . ' - ' . $batch['skill_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Time Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                            <select id="timeFilter" class="w-48 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $time_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="week" <?php echo $time_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $time_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="quarter" <?php echo $time_filter == 'quarter' ? 'selected' : ''; ?>>Last 90 Days</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="w-48 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Results</option>
                                <option value="passed" <?php echo $status_filter == 'passed' ? 'selected' : ''; ?>>Passed Only</option>
                                <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed Only</option>
                            </select>
                        </div>

                        <!-- Apply Filters Button -->
                        <div class="self-end">
                            <button onclick="applyFilters()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-2">Active Filters:</p>
                    <div class="flex flex-wrap">
                        <?php if ($skill_id > 0):
                            $skills->data_seek(0);
                            while ($skill = $skills->fetch_assoc()) {
                                if ($skill['id'] == $skill_id) {
                                    $skill_name = $skill['skill_name'];
                                    break;
                                }
                            }
                        ?>
                            <span class="filter-tag active">
                                Skill: <?php echo htmlspecialchars($skill_name); ?>
                                <button onclick="removeSkillFilter()" class="ml-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </span>
                        <?php endif; ?>

                        <?php if ($batch_id > 0):
                            $batches->data_seek(0);
                            while ($batch = $batches->fetch_assoc()) {
                                if ($batch['id'] == $batch_id) {
                                    $batch_name = $batch['batch_name'];
                                    break;
                                }
                            }
                        ?>
                            <span class="filter-tag active">
                                Batch: <?php echo htmlspecialchars($batch_name); ?>
                                <button onclick="removeBatchFilter()" class="ml-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </span>
                        <?php endif; ?>

                        <?php if ($time_filter !== 'all'): ?>
                            <span class="filter-tag active">
                                Period: <?php echo ucfirst($time_filter); ?>
                                <button onclick="removeTimeFilter()" class="ml-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </span>
                        <?php endif; ?>

                        <?php if ($status_filter !== 'all'): ?>
                            <span class="filter-tag active">
                                Status: <?php echo ucfirst($status_filter); ?>
                                <button onclick="removeStatusFilter()" class="ml-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </span>
                        <?php endif; ?>

                        <?php if ($skill_id > 0 || $batch_id > 0 || $time_filter !== 'all' || $status_filter !== 'all'): ?>
                            <span class="filter-tag">
                                <a href="grades_results.php" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-times-circle mr-1"></i> Clear All
                                </a>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Performance Trend -->
                <div class="grade-card p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Performance Trend (Last 30 Days)</h3>
                    <div class="h-64">
                        <canvas id="performanceTrendChart"></canvas>
                    </div>
                </div>

                <!-- Grade Distribution -->
                <div class="grade-card p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Grade Distribution</h3>
                    <div class="h-64">
                        <canvas id="gradeDistributionChart"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <?php
                        $grade_colors = ['A' => '#10b981', 'B' => '#3b82f6', 'C' => '#f59e0b', 'D' => '#ef4444', 'F' => '#7f1d1d'];
                        $grade_labels = ['A' => 'Excellent', 'B' => 'Good', 'C' => 'Average', 'D' => 'Poor', 'F' => 'Fail'];
                        $grade_counts = [];

                        $grade_distribution->data_seek(0);
                        while ($grade = $grade_distribution->fetch_assoc()) {
                            $grade_counts[$grade['grade']] = $grade['count'];
                        }

                        foreach (['A', 'B', 'C', 'D', 'F'] as $grade):
                            $count = $grade_counts[$grade] ?? 0;
                            $percentage = $total > 0 ? round(($count / $total) * 100, 0) : 0;
                        ?>
                            <div class="text-center">
                                <div class="grade-badge grade-<?php echo strtolower($grade); ?> mb-2">
                                    <?php echo $grade; ?>
                                </div>
                                <div class="text-xl font-bold"><?php echo $count; ?></div>
                                <div class="text-sm text-gray-600"><?php echo $percentage; ?>%</div>
                                <div class="text-xs text-gray-500"><?php echo $grade_labels[$grade]; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quiz Results Table -->
            <div class="grade-card p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Quiz Results</h3>
                    <div class="text-sm text-gray-600">
                        Showing <?php echo $results->num_rows; ?> result(s)
                    </div>
                </div>

                <?php if ($results->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Quiz Title</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Skill & Batch</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Score</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Grade</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Status</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = $results->fetch_assoc()):
                                    $is_passed = $row['percentage'] >= 60;
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['quiz_title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $row['total_questions']; ?> questions</div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($row['skill_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['batch_name']); ?></div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($row['submitted_at'])); ?></div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-900">
                                                <?php echo $row['score']; ?>/<?php echo $row['total_marks']; ?>
                                            </div>
                                            <div class="text-sm <?php echo $is_passed ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $row['percentage']; ?>%
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="grade-badge grade-<?php echo strtolower($row['grade']); ?>">
                                                <?php echo $row['grade']; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $is_passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php if ($is_passed): ?>
                                                    <i class="fas fa-check-circle mr-1"></i> Passed
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle mr-1"></i> Failed
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex space-x-2">
                                                <a href="quiz_result_view.php?result_id=<?php echo $row['result_id']; ?>"
                                                    class="text-blue-600 hover:text-blue-800"
                                                    title="View Detailed Result">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="quiz_result_view.php?quiz_id=<?php echo $row['quiz_id']; ?>"
                                                    class="text-green-600 hover:text-green-800"
                                                    title="View Latest Attempt">
                                                    <i class="fas fa-redo"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">No Quiz Results Found</h4>
                        <p class="text-gray-500 mb-6">You haven't taken any quizzes yet or no results match your filters.</p>
                        <a href="student_quiz.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-play mr-2"></i> Take a Quiz
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Performance Summary -->
            <div class="grade-card p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-6">Performance Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Best Performance -->
                    <div class="text-center p-4 border rounded-lg">
                        <div class="performance-indicator performance-excellent mx-auto mb-3">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="text-sm text-gray-500 mb-1">Best Score</div>
                        <?php
                        $results->data_seek(0);
                        $best_score = 0;
                        $best_quiz = '';
                        while ($row = $results->fetch_assoc()) {
                            if ($row['percentage'] > $best_score) {
                                $best_score = $row['percentage'];
                                $best_quiz = $row['quiz_title'];
                            }
                        }
                        ?>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $best_score; ?>%</div>
                        <div class="text-xs text-gray-600 truncate" title="<?php echo htmlspecialchars($best_quiz); ?>">
                            <?php echo htmlspecialchars($best_quiz); ?>
                        </div>
                    </div>

                    <!-- Weakest Area -->
                    <div class="text-center p-4 border rounded-lg">
                        <div class="performance-indicator performance-poor mx-auto mb-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="text-sm text-gray-500 mb-1">Needs Improvement</div>
                        <?php
                        $results->data_seek(0);
                        $worst_score = 100;
                        $worst_quiz = '';
                        while ($row = $results->fetch_assoc()) {
                            if ($row['percentage'] < $worst_score) {
                                $worst_score = $row['percentage'];
                                $worst_quiz = $row['quiz_title'];
                            }
                        }
                        ?>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $worst_score; ?>%</div>
                        <div class="text-xs text-gray-600 truncate" title="<?php echo htmlspecialchars($worst_quiz); ?>">
                            <?php echo htmlspecialchars($worst_quiz); ?>
                        </div>
                    </div>

                    <!-- Average Time -->
                    <div class="text-center p-4 border rounded-lg">
                        <div class="performance-indicator performance-average mx-auto mb-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="text-sm text-gray-500 mb-1">Average Score</div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo round($overall_stats['avg_percentage'] ?? 0, 1); ?>%</div>
                        <div class="text-xs text-gray-600">Based on <?php echo $overall_stats['total_quizzes'] ?? 0; ?> quizzes</div>
                    </div>

                    <!-- Completion Rate -->
                    <div class="text-center p-4 border rounded-lg">
                        <div class="performance-indicator performance-good mx-auto mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="text-sm text-gray-500 mb-1">Pass Rate</div>
                        <div class="text-2xl font-bold text-gray-800">
                            <?php
                            $total = $overall_stats['total_quizzes'] ?? 1;
                            $passed = $overall_stats['passed_quizzes'] ?? 0;
                            echo $total > 0 ? round(($passed / $total) * 100, 0) : 0;
                            ?>%
                        </div>
                        <div class="text-xs text-gray-600"><?php echo $passed; ?> passed out of <?php echo $total; ?></div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center gap-4">
                <a href="student_quiz.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-play mr-2"></i> Take More Quizzes
                </a>
                <button onclick="printGrades()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-print mr-2"></i> Print Report
                </button>
                <button onclick="exportResults()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-download mr-2"></i> Export Results
                </button>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Performance Trend Chart
        const trendCtx = document.getElementById('performanceTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Average Score (%)',
                    data: <?php echo json_encode($trend_percentages); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Score (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Score: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });

        // Grade Distribution Chart
        const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
        new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: ['A', 'B', 'C', 'D', 'F'],
                datasets: [{
                    data: [
                        <?php echo $grade_counts['A'] ?? 0; ?>,
                        <?php echo $grade_counts['B'] ?? 0; ?>,
                        <?php echo $grade_counts['C'] ?? 0; ?>,
                        <?php echo $grade_counts['D'] ?? 0; ?>,
                        <?php echo $grade_counts['F'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                        '#7f1d1d'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Filter Functions
        function applyFilters() {
            const skillId = document.getElementById('skillFilter').value;
            const batchId = document.getElementById('batchFilter').value;
            const timeFilter = document.getElementById('timeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            let url = 'grades_results.php?';
            if (skillId > 0) url += `skill_id=${skillId}&`;
            if (batchId > 0) url += `batch_id=${batchId}&`;
            if (timeFilter !== 'all') url += `time_filter=${timeFilter}&`;
            if (statusFilter !== 'all') url += `status_filter=${statusFilter}&`;

            // Remove trailing & or ?
            url = url.replace(/[&?]$/, '');
            window.location.href = url;
        }

        function removeSkillFilter() {
            const url = new URL(window.location.href);
            url.searchParams.delete('skill_id');
            window.location.href = url.toString();
        }

        function removeBatchFilter() {
            const url = new URL(window.location.href);
            url.searchParams.delete('batch_id');
            window.location.href = url.toString();
        }

        function removeTimeFilter() {
            const url = new URL(window.location.href);
            url.searchParams.delete('time_filter');
            window.location.href = url.toString();
        }

        function removeStatusFilter() {
            const url = new URL(window.location.href);
            url.searchParams.delete('status_filter');
            window.location.href = url.toString();
        }

        // Print function
        function printGrades() {
            const printContent = document.createElement('div');
            printContent.innerHTML = `
                <style>
                    @media print {
                        body * { visibility: hidden; }
                        .print-area, .print-area * { visibility: visible; }
                        .print-area { position: absolute; left: 0; top: 0; }
                        .no-print { display: none !important; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                    }
                    .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                    .print-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0; }
                    .print-stat { text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
                </style>
                <div class="print-area">
                    <div class="print-header">
                        <h1>Student Performance Report</h1>
                        <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                        <p>Student ID: <?php echo $student_id; ?> | Generated on: <?php echo date('M d, Y H:i:s'); ?></p>
                        <p>Academy Management System</p>
                    </div>
                    
                    <div class="print-stats">
                        <div class="print-stat">
                            <h3>Total Quizzes</h3>
                            <p><?php echo $overall_stats['total_quizzes'] ?? 0; ?></p>
                        </div>
                        <div class="print-stat">
                            <h3>Average Score</h3>
                            <p><?php echo round($overall_stats['avg_percentage'] ?? 0, 1); ?>%</p>
                        </div>
                        <div class="print-stat">
                            <h3>Quizzes Passed</h3>
                            <p><?php echo $overall_stats['passed_quizzes'] ?? 0; ?></p>
                        </div>
                        <div class="print-stat">
                            <h3>Pass Rate</h3>
                            <p>
                                <?php
                                $total = $overall_stats['total_quizzes'] ?? 1;
                                $passed = $overall_stats['passed_quizzes'] ?? 0;
                                echo $total > 0 ? round(($passed / $total) * 100, 0) : 0;
                                ?>%
                            </p>
                        </div>
                    </div>
                    
                    <h3>Quiz Results</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Skill & Batch</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $results->data_seek(0);
                            while ($row = $results->fetch_assoc()):
                                $is_passed = $row['percentage'] >= 60;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['quiz_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['skill_name'] . ' - ' . $row['batch_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                <td><?php echo $row['score']; ?>/<?php echo $row['total_marks']; ?> (<?php echo $row['percentage']; ?>%)</td>
                                <td><?php echo $row['grade']; ?></td>
                                <td><?php echo $is_passed ? 'Passed' : 'Failed'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 30px; text-align: center; font-style: italic;">
                        <p>This report is generated for academic purposes only.</p>
                    </div>
                </div>
            `;

            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Student Performance Report</title></head><body>');
            printWindow.document.write(printContent.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        // Export results function
        function exportResults() {
            // In a real application, this would generate a CSV or PDF file
            alert('Export feature would generate a downloadable report file with all your results and statistics.');

            // For demonstration, show what would be exported
            const exportData = {
                student: "<?php echo htmlspecialchars($student['name']); ?>",
                studentId: "<?php echo $student_id; ?>",
                dateRange: "<?php echo $time_filter === 'all' ? 'All Time' : ucfirst($time_filter); ?>",
                totalQuizzes: <?php echo $overall_stats['total_quizzes'] ?? 0; ?>,
                averageScore: <?php echo round($overall_stats['avg_percentage'] ?? 0, 1); ?>,
                passedQuizzes: <?php echo $overall_stats['passed_quizzes'] ?? 0; ?>,
                passRate: <?php
                            $total = $overall_stats['total_quizzes'] ?? 1;
                            $passed = $overall_stats['passed_quizzes'] ?? 0;
                            echo $total > 0 ? round(($passed / $total) * 100, 0) : 0;
                            ?>,
                results: []
            };

            // Add quiz results to export data
            <?php
            $results->data_seek(0);
            while ($row = $results->fetch_assoc()):
                $is_passed = $row['percentage'] >= 60;
            ?>
                exportData.results.push({
                    quizTitle: "<?php echo htmlspecialchars($row['quiz_title']); ?>",
                    skill: "<?php echo htmlspecialchars($row['skill_name']); ?>",
                    batch: "<?php echo htmlspecialchars($row['batch_name']); ?>",
                    date: "<?php echo date('M d, Y', strtotime($row['submitted_at'])); ?>",
                    score: "<?php echo $row['score']; ?>/<?php echo $row['total_marks']; ?>",
                    percentage: <?php echo $row['percentage']; ?>,
                    grade: "<?php echo $row['grade']; ?>",
                    status: "<?php echo $is_passed ? 'Passed' : 'Failed'; ?>"
                });
            <?php endwhile; ?>

            console.log('Export Data:', exportData);
        }
    </script>
</body>

</html>
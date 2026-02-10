<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Get student ID
$user_id = $_SESSION['user_id'];
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
$skill_filter = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$batch_filter = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Get all skills student is enrolled in
$skills_query = "
SELECT DISTINCT
    s.id as skill_id,
    s.skill_name,
   
    s.description
FROM student_enrollments se
JOIN batches b ON se.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name
";

$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $student_id);
$stmt_skills->execute();
$student_skills = $stmt_skills->get_result();

// Get progress data
$progress_query = "
SELECT 
    sp.*,
    s.skill_name,

    b.batch_name,
    sess.session_name,
    t.name as teacher_name,
    DATE_FORMAT(sp.last_updated, '%M %d, %Y') as last_updated_formatted,
    DATE_FORMAT(sp.created_at, '%M %d, %Y') as created_date
FROM skill_progress sp
JOIN skills s ON sp.skill_id = s.id
JOIN batches b ON sp.batch_id = b.id
JOIN sessions sess ON sp.session_id = sess.id
LEFT JOIN teachers t ON sp.updated_by = t.id
WHERE sp.student_id = ?
";

$params = [$student_id];
$param_types = "i";

if ($skill_filter > 0) {
    $progress_query .= " AND sp.skill_id = ?";
    $params[] = $skill_filter;
    $param_types .= "i";
}

if ($batch_filter > 0) {
    $progress_query .= " AND sp.batch_id = ?";
    $params[] = $batch_filter;
    $param_types .= "i";
}

$progress_query .= " ORDER BY sp.last_updated DESC";

$stmt_progress = $conn->prepare($progress_query);
if (!empty($params)) {
    $stmt_progress->bind_param($param_types, ...$params);
}
$stmt_progress->execute();
$progress_data = $stmt_progress->get_result();

// Calculate overall statistics
$stats_query = "
SELECT 
    COUNT(*) as total_records,
    AVG(sp.progress_percent) as avg_progress,
    AVG(sp.overall_performance) as avg_performance,
    MIN(sp.progress_percent) as min_progress,
    MAX(sp.progress_percent) as max_progress
FROM skill_progress sp
WHERE sp.student_id = ?
";

if ($skill_filter > 0) {
    $stats_query .= " AND sp.skill_id = ?";
}

$stmt_stats = $conn->prepare($stats_query);
if ($skill_filter > 0) {
    $stmt_stats->bind_param("ii", $student_id, $skill_filter);
} else {
    $stmt_stats->bind_param("i", $student_id);
}
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result()->fetch_assoc();

$stats = [
    'total_records' => $stats_result['total_records'] ?? 0,
    'avg_progress' => round($stats_result['avg_progress'] ?? 0, 1),
    'avg_performance' => round($stats_result['avg_performance'] ?? 0, 1),
    'min_progress' => round($stats_result['min_progress'] ?? 0, 1),
    'max_progress' => round($stats_result['max_progress'] ?? 0, 1)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Progress | Student Portal</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .performance-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-excellent { background: #d1fae5; color: #065f46; }
        .badge-advanced { background: #dbeafe; color: #1e40af; }
        .badge-intermediate { background: #fef3c7; color: #92400e; }
        .badge-beginner { background: #f3f4f6; color: #4b5563; }

        .progress-ring {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php 
        // Reuse sidebar from dashboard or create specific one
        $current_page = 'progress';
        include __DIR__ . '/includes/sidebar.php'; 
        ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">My Learning Progress</h1>
                <p class="text-gray-500 mt-2">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    Track your skill development and topic completion
                </p>
            </div>

            <!-- Filters -->
            <div class="card p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Progress</h3>
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Skill</label>
                        <select name="skill_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                            <option value="0">All Skills</option>
                            <?php while ($skill = $student_skills->fetch_assoc()): ?>
                                <option value="<?php echo $skill['skill_id']; ?>"
                                    <?php echo $skill_filter == $skill['skill_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-4">
                        <button type="submit" class="bg-primary text-blue-600 px-6 py-2.5 rounded-lg font-medium">
                            Apply Filters
                        </button>
                        <a href="student_progress.php" class="text-gray-600 hover:text-gray-800 px-4 py-2.5 rounded-lg font-medium">
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Average Progress</p>
                            <p class="text-2xl font-bold text-primary"><?php echo $stats['avg_progress']; ?>%</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Avg. Performance</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $stats['avg_performance']; ?>%</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-star text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Highest Progress</p>
                            <p class="text-2xl font-bold text-purple-600"><?php echo $stats['max_progress']; ?>%</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-arrow-up text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Progress Records</p>
                            <p class="text-2xl font-bold text-gray-700"><?php echo $stats['total_records']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list-check text-gray-600"></i>
                        </div>
                    </div>
                </div>
            </div>

           

            <!-- Progress Records -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Detailed Progress Records</h3>
                    <p class="text-sm text-gray-600 mt-1">Updated by your teachers</p>
                </div>

                <?php if ($progress_data->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Skill</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Progress</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Performance</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Scores</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Updated</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Teacher</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = $progress_data->fetch_assoc()): ?>
                                    <?php
                                    $progress_color = $row['progress_percent'] >= 80 ? 'text-green-600' : 
                                                   ($row['progress_percent'] >= 50 ? 'text-yellow-600' : 'text-red-600');
                                    
                                    $badge_class = '';
                                    switch ($row['performance_level']) {
                                        case 'Excellent': $badge_class = 'badge-excellent'; break;
                                        case 'Advanced': $badge_class = 'badge-advanced'; break;
                                        case 'Intermediate': $badge_class = 'badge-intermediate'; break;
                                        case 'Beginner': $badge_class = 'badge-beginner'; break;
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <div>
                                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($row['skill_name']); ?></h4>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($row['batch_name']); ?></p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="font-semibold <?php echo $progress_color; ?>">
                                                        <?php echo $row['progress_percent']; ?>%
                                                    </span>
                                                    <span class="text-sm text-gray-500">
                                                        <?php echo $row['topics_completed']; ?>/<?php echo $row['total_topics']; ?>
                                                    </span>
                                                </div>
                                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500"
                                                         style="width: <?php echo $row['progress_percent']; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="<?php echo $badge_class; ?> performance-badge inline-block">
                                                <?php echo $row['performance_level']; ?>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <?php echo $row['overall_performance']; ?>%
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 w-16">Quiz:</span>
                                                    <span class="text-sm font-medium"><?php echo $row['quiz_score']; ?>%</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 w-16">Assignment:</span>
                                                    <span class="text-sm font-medium"><?php echo $row['assignment_score']; ?>%</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 w-16">Project:</span>
                                                    <span class="text-sm font-medium"><?php echo $row['project_score']; ?>%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm">
                                                <div class="text-gray-800"><?php echo $row['last_updated_formatted']; ?></div>
                                                <div class="text-gray-500"><?php echo $row['created_date']; ?></div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php if ($row['teacher_name']): ?>
                                                <div class="text-sm text-gray-600">
                                                    <i class="fas fa-user-tie mr-1"></i>
                                                    <?php echo htmlspecialchars($row['teacher_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-line text-gray-400 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-700 mb-2">No Progress Records</h4>
                        <p class="text-gray-500">Your progress will appear here once your teacher updates it.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Progress Chart
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Progress %',
                    data: [65, 75, 70, 85, 90, 95],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Performance %',
                    data: [60, 70, 75, 80, 85, 88],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
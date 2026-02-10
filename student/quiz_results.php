<?php
// quiz_results.php
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
$month = isset($_GET['month']) ? $_GET['month'] : '';
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';

// Get all quiz results
$results_query = "
SELECT 
    qr.*,
    q.title as quiz_title,
    q.description,
    q.total_marks,
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
    END as grade,
    DATE_FORMAT(qr.submitted_at, '%Y-%m') as result_month
FROM quiz_results qr
JOIN quizzes q ON qr.quiz_id = q.id
JOIN batches b ON q.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE qr.student_id = ?
";

$params = [$student_id];
$types = "i";

// Apply filters
if ($skill_id > 0) {
    $results_query .= " AND s.id = ?";
    $params[] = $skill_id;
    $types .= "i";
}

if (!empty($month)) {
    $results_query .= " AND DATE_FORMAT(qr.submitted_at, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

if (!empty($grade)) {
    $grade_ranges = [
        'A' => "AND (qr.score / q.total_marks) >= 0.9",
        'B' => "AND (qr.score / q.total_marks) >= 0.8 AND (qr.score / q.total_marks) < 0.9",
        'C' => "AND (qr.score / q.total_marks) >= 0.7 AND (qr.score / q.total_marks) < 0.8",
        'D' => "AND (qr.score / q.total_marks) >= 0.6 AND (qr.score / q.total_marks) < 0.7",
        'F' => "AND (qr.score / q.total_marks) < 0.6"
    ];
    if (isset($grade_ranges[$grade])) {
        $results_query .= " " . $grade_ranges[$grade];
    }
}

$results_query .= " ORDER BY qr.submitted_at DESC";

$stmt_results = $conn->prepare($results_query);
if (strlen($types) > 1) {
    $stmt_results->bind_param($types, ...$params);
} else {
    $stmt_results->bind_param($types, $student_id);
}
$stmt_results->execute();
$results = $stmt_results->get_result();

// Get enrolled courses for filter
$courses_query = "
SELECT DISTINCT s.id, s.skill_name 
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name
";
$stmt_courses = $conn->prepare($courses_query);
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result();

// Get months with results for filter
$months_query = "
SELECT DISTINCT DATE_FORMAT(submitted_at, '%Y-%m') as month,
DATE_FORMAT(submitted_at, '%M %Y') as month_name
FROM quiz_results 
WHERE student_id = ?
ORDER BY month DESC
";
$stmt_months = $conn->prepare($months_query);
$stmt_months->bind_param("i", $student_id);
$stmt_months->execute();
$months = $stmt_months->get_result();

// Calculate overall statistics
$stats_query = "
SELECT 
    COUNT(*) as total_quizzes,
    AVG(score) as avg_score,
    MAX(score) as max_score,
    MIN(score) as min_score,
    AVG((score / total_marks) * 100) as avg_percentage
FROM (
    SELECT qr.score, q.total_marks
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
    WHERE qr.student_id = ?
) quiz_data
";

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("i", $student_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Calculate grade distribution
$grades_query = "
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
WHERE qr.student_id = ?
GROUP BY grade
ORDER BY grade
";

$stmt_grades = $conn->prepare($grades_query);
$stmt_grades->bind_param("i", $student_id);
$stmt_grades->execute();
$grade_distribution = $stmt_grades->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>All Quiz Results | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .result-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .grade-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .grade-a { background: #10b981; }
        .grade-b { background: #3b82f6; }
        .grade-c { background: #f59e0b; }
        .grade-d { background: #ef4444; }
        .grade-f { background: #7f1d1d; }

        .score-progress {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            border-radius: 4px;
        }

        .filter-tab {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .filter-tab.active {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
            font-weight: 600;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        .trend-neutral { color: #6b7280; }

        .result-row:hover {
            background: #f8fafc;
        }

        .export-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .export-btn:hover {
            background: #059669;
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
                        <h1 class="text-3xl font-bold text-gray-800">All Quiz Results</h1>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                            View your quiz performance history
                        </p>
                    </div>
                    <div>
                        <a href="student_quiz.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Quizzes
                        </a>
                    </div>
                </div>
            </div>

           
            <!-- Filters -->
            <div class="result-card p-6 mb-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Filter Results</h2>
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                        <select name="skill_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">All Courses</option>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $skill_id == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['skill_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <select name="month" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">All Months</option>
                            <?php while ($m = $months->fetch_assoc()): ?>
                                <option value="<?php echo $m['month']; ?>" 
                                    <?php echo $month == $m['month'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['month_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Grade</label>
                        <select name="grade" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">All Grades</option>
                            <option value="A" <?php echo $grade == 'A' ? 'selected' : ''; ?>>A (90%+)</option>
                            <option value="B" <?php echo $grade == 'B' ? 'selected' : ''; ?>>B (80-89%)</option>
                            <option value="C" <?php echo $grade == 'C' ? 'selected' : ''; ?>>C (70-79%)</option>
                            <option value="D" <?php echo $grade == 'D' ? 'selected' : ''; ?>>D (60-69%)</option>
                            <option value="F" <?php echo $grade == 'F' ? 'selected' : ''; ?>>F (Below 60%)</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <a href="quiz_results.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>

                <!-- Quick Filter Tabs -->
                <div class="mt-4">
                    <div class="flex gap-2">
                        <button onclick="setFilter('all')" class="filter-tab active">All Results</button>
                        <button onclick="setFilter('recent')" class="filter-tab">Recent 10</button>
                        <button onclick="setFilter('top')" class="filter-tab">Top Scores</button>
                        <button onclick="setFilter('improve')" class="filter-tab">Needs Improvement</button>
                    </div>
                </div>
            </div>

            <!-- Charts -->
           

            <!-- Results Table -->
            <div class="result-card overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Quiz Results</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Showing <?php echo $results->num_rows; ?> result<?php echo $results->num_rows != 1 ? 's' : ''; ?>
                        </p>
                    </div>
                    <div>
                        <button onclick="exportResults()" class="export-btn">
                            <i class="fas fa-download mr-2"></i> Export Results
                        </button>
                    </div>
                </div>

                <?php if ($results->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Quiz Title</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Score</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Percentage</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Grade</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($result = $results->fetch_assoc()): ?>
                                    <tr class="result-row hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars($result['quiz_title']); ?>
                                            </div>
                                            <?php if (!empty($result['description'])): ?>
                                                <div class="text-sm text-gray-500 mt-1">
                                                    <?php echo htmlspecialchars(substr($result['description'], 0, 60)); ?>
                                                    <?php if (strlen($result['description']) > 60): ?>...<?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <?php echo htmlspecialchars($result['skill_name']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($result['batch_name']); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">
                                                <?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?>
                                            </div>
                                            <div class="score-progress mt-1">
                                                <div class="score-fill bg-blue-500" 
                                                     style="width: <?php echo min($result['percentage'], 100); ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-bold <?php 
                                                echo $result['percentage'] >= 80 ? 'text-green-600' : 
                                                    ($result['percentage'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                <?php echo $result['percentage']; ?>%
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="grade-badge grade-<?php echo strtolower($result['grade']); ?>">
                                                <?php echo $result['grade']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($result['submitted_at'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                <?php echo date('h:i A', strtotime($result['submitted_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="quiz_result_view.php?result_id=<?php echo $result['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="quiz_result_view.php?result_id=<?php echo $result['id']; ?>&print=true" 
                                               class="text-gray-600 hover:text-gray-800"
                                               target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-bar text-gray-300 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Quiz Results Found</h3>
                        <p class="text-gray-500 mb-6">
                            <?php if ($skill_id || $month || $grade): ?>
                                No results match your filter criteria.
                            <?php else: ?>
                                You haven't taken any quizzes yet.
                            <?php endif; ?>
                        </p>
                        <a href="student_quiz.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-play mr-2"></i> Take a Quiz
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Grade distribution chart
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        <?php
        $grade_data = [];
        $grade_labels = [];
        $grade_colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#7f1d1d'];
        
        mysqli_data_seek($grade_distribution, 0);
        while ($g = $grade_distribution->fetch_assoc()) {
            $grade_labels[] = $g['grade'];
            $grade_data[] = $g['count'];
        }
        ?>
        
        new Chart(gradeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($grade_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($grade_data); ?>,
                    backgroundColor: <?php echo json_encode($grade_colors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Performance trend chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        <?php
        // Get monthly performance data
        $monthly_query = "
        SELECT 
            DATE_FORMAT(qr.submitted_at, '%Y-%m') as month,
            DATE_FORMAT(qr.submitted_at, '%b %Y') as month_name,
            AVG((qr.score / q.total_marks) * 100) as avg_percentage
        FROM quiz_results qr
        JOIN quizzes q ON qr.quiz_id = q.id
        WHERE qr.student_id = ?
        GROUP BY DATE_FORMAT(qr.submitted_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
        ";
        
        $stmt_monthly = $conn->prepare($monthly_query);
        $stmt_monthly->bind_param("i", $student_id);
        $stmt_monthly->execute();
        $monthly_data = $stmt_monthly->get_result();
        
        $trend_months = [];
        $trend_scores = [];
        while ($row = $monthly_data->fetch_assoc()) {
            $trend_months[] = $row['month_name'];
            $trend_scores[] = round($row['avg_percentage'], 1);
        }
        
        // Reverse to show chronological order
        $trend_months = array_reverse($trend_months);
        $trend_scores = array_reverse($trend_scores);
        ?>
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_months); ?>,
                datasets: [{
                    label: 'Average Percentage',
                    data: <?php echo json_encode($trend_scores); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage (%)'
                        }
                    }
                },
                plugins: {
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

        // Filter functions
        function setFilter(type) {
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // In a real application, you would apply filters via AJAX
            switch(type) {
                case 'recent':
                    console.log('Showing recent 10 results');
                    break;
                case 'top':
                    console.log('Showing top scores');
                    break;
                case 'improve':
                    console.log('Showing quizzes needing improvement');
                    break;
                default:
                    console.log('Showing all results');
            }
        }

        // Export results
        function exportResults() {
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Quiz Title,Course,Batch,Score,Total Marks,Percentage,Grade,Date\n";
            
            <?php
            mysqli_data_seek($results, 0);
            while ($result = $results->fetch_assoc()):
            ?>
                csvContent += `"<?php echo addslashes($result['quiz_title']); ?>",`;
                csvContent += `"<?php echo addslashes($result['skill_name']); ?>",`;
                csvContent += `"<?php echo addslashes($result['batch_name']); ?>",`;
                csvContent += `<?php echo $result['score']; ?>,`;
                csvContent += `<?php echo $result['total_marks']; ?>,`;
                csvContent += `<?php echo $result['percentage']; ?>%,`;
                csvContent += `<?php echo $result['grade']; ?>,`;
                csvContent += `"<?php echo date('M d, Y', strtotime($result['submitted_at'])); ?>"\n`;
            <?php endwhile; ?>
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "quiz_results_<?php echo date('Y-m-d'); ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners to filter tabs
            const filterTabs = document.querySelectorAll('.filter-tab');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Auto-refresh chart data every minute
            setInterval(() => {
                console.log('Refreshing chart data...');
                // In a real app, you would fetch updated data via AJAX
            }, 60000);
        });
    </script>
</body>

</html>
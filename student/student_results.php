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

// Get comprehensive results data
$results_query = "
SELECT 
    sp.id as progress_id,
    sp.student_id,
    sp.batch_id,
    sp.overall_grade,
    sp.comments as teacher_comments,
    sp.report_date,
    s.id as skill_id,
    s.skill_name,
    s.skill_code,
    b.batch_name,
    t.name as teacher_name,
    skp.progress_percent,
    skp.overall_performance,
    skp.performance_level,
    skp.quiz_score,
    skp.assignment_score,
    skp.project_score,
    skp.topics_completed,
    skp.total_topics,
    skp.remarks as progress_remarks,
    skp.last_updated as progress_updated
FROM student_progress sp
JOIN batches b ON sp.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
JOIN teachers t ON sp.teacher_id = t.id
LEFT JOIN skill_progress skp ON sp.student_id = skp.student_id 
    AND sp.batch_id = skp.batch_id 
    AND s.id = skp.skill_id
WHERE sp.student_id = ?
ORDER BY sp.report_date DESC, s.skill_name
";

$stmt_results = $conn->prepare($results_query);
$stmt_results->bind_param("i", $student_id);
$stmt_results->execute();
$results_data = $stmt_results->get_result();

// Calculate summary statistics
$summary_query = "
SELECT 
    COUNT(DISTINCT sp.skill_id) as total_courses,
    AVG(CASE 
        WHEN sp.overall_grade = 'A' THEN 4.0
        WHEN sp.overall_grade = 'B' THEN 3.0
        WHEN sp.overall_grade = 'C' THEN 2.0
        WHEN sp.overall_grade = 'D' THEN 1.0
        WHEN sp.overall_grade = 'F' THEN 0.0
        ELSE NULL
    END) as cumulative_gpa,
    COUNT(DISTINCT CASE WHEN sp.overall_grade = 'A' THEN sp.skill_id END) as a_courses,
    COUNT(DISTINCT CASE WHEN sp.overall_grade = 'B' THEN sp.skill_id END) as b_courses,
    COUNT(DISTINCT CASE WHEN sp.overall_grade = 'C' THEN sp.skill_id END) as c_courses,
    AVG(skp.overall_performance) as avg_performance,
    MAX(skp.progress_percent) as best_progress
FROM student_progress sp
JOIN batches b ON sp.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
LEFT JOIN skill_progress skp ON sp.student_id = skp.student_id 
    AND sp.batch_id = skp.batch_id 
    AND s.id = skp.skill_id
WHERE sp.student_id = ?
";

$stmt_summary = $conn->prepare($summary_query);
$stmt_summary->bind_param("i", $student_id);
$stmt_summary->execute();
$summary_result = $stmt_summary->get_result()->fetch_assoc();

$summary = [
    'total_courses' => $summary_result['total_courses'] ?? 0,
    'cumulative_gpa' => round($summary_result['cumulative_gpa'] ?? 0, 2),
    'a_courses' => $summary_result['a_courses'] ?? 0,
    'b_courses' => $summary_result['b_courses'] ?? 0,
    'c_courses' => $summary_result['c_courses'] ?? 0,
    'avg_performance' => round($summary_result['avg_performance'] ?? 0, 1),
    'best_progress' => round($summary_result['best_progress'] ?? 0, 1)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Results | Student Portal</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .result-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        
        .grade-badge-lg {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 1.3rem;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
        }
        
        .performance-meter {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }
        
        .performance-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .performance-excellent { background: linear-gradient(90deg, #10b981, #34d399); }
        .performance-good { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .performance-average { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .performance-poor { background: linear-gradient(90deg, #ef4444, #f87171); }
        
        .trophy-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php 
        $current_page = 'results';
        include __DIR__ . '/student_sidebar.php'; 
        ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">My Academic Results</h1>
                <p class="text-gray-500 mt-2">
                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                    Comprehensive overview of your academic performance
                </p>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="result-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Cumulative GPA</p>
                            <p class="text-3xl font-bold text-primary"><?php echo $summary['cumulative_gpa']; ?>/4.0</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="performance-meter">
                            <div class="performance-fill performance-good" 
                                 style="width: <?php echo ($summary['cumulative_gpa'] / 4) * 100; ?>%">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="result-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Avg. Performance</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo $summary['avg_performance']; ?>%</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        Across <?php echo $summary['total_courses']; ?> courses
                    </div>
                </div>

                <div class="result-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Courses Completed</p>
                            <p class="text-3xl font-bold text-purple-600"><?php echo $summary['total_courses']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex gap-2 text-sm">
                            <span class="text-green-600">A: <?php echo $summary['a_courses']; ?></span>
                            <span class="text-blue-600">B: <?php echo $summary['b_courses']; ?></span>
                            <span class="text-yellow-600">C: <?php echo $summary['c_courses']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="result-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Best Progress</p>
                            <p class="text-3xl font-bold text-orange-600"><?php echo $summary['best_progress']; ?>%</p>
                        </div>
                        <div class="trophy-icon">
                            <i class="fas fa-trophy text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        Highest course completion rate
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="result-card overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Course Results</h3>
                            <p class="text-sm text-gray-600">Detailed results for each course</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="exportResults()" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 flex items-center gap-2">
                                <i class="fas fa-file-export"></i>
                                Export
                            </button>
                            <button onclick="printResults()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg flex items-center gap-2">
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($results_data->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Course</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Final Grade</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Performance</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Progress</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Assessment Scores</th>
                                    <th class="py-3 px-4 text-left font-medium text-gray-700">Report Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = $results_data->fetch_assoc()): ?>
                                    <?php
                                    $grade_class = '';
                                    switch (strtoupper($row['overall_grade'])) {
                                        case 'A': $grade_class = 'grade-A'; break;
                                        case 'B': $grade_class = 'grade-B'; break;
                                        case 'C': $grade_class = 'grade-C'; break;
                                        case 'D': $grade_class = 'grade-D'; break;
                                        case 'F': $grade_class = 'grade-F'; break;
                                    }
                                    
                                    $performance_class = 'performance-average';
                                    if ($row['overall_performance'] >= 80) {
                                        $performance_class = 'performance-excellent';
                                    } elseif ($row['overall_performance'] >= 60) {
                                        $performance_class = 'performance-good';
                                    } elseif ($row['overall_performance'] < 40) {
                                        $performance_class = 'performance-poor';
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <div>
                                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($row['skill_name']); ?></h4>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($row['batch_name']); ?></p>
                                                <p class="text-xs text-gray-400 flex items-center gap-1">
                                                    <i class="fas fa-user-tie"></i>
                                                    <?php echo htmlspecialchars($row['teacher_name']); ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="<?php echo $grade_class; ?> grade-badge-lg">
                                                <?php echo $row['overall_grade']; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="space-y-2">
                                                <div class="flex justify-between items-center">
                                                    <span class="font-medium"><?php echo $row['overall_performance']; ?>%</span>
                                                    <span class="text-sm text-gray-600"><?php echo $row['performance_level']; ?></span>
                                                </div>
                                                <div class="performance-meter">
                                                    <div class="performance-fill <?php echo $performance_class; ?>" 
                                                         style="width: <?php echo $row['overall_performance']; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="font-medium"><?php echo $row['progress_percent']; ?>%</span>
                                                    <span class="text-sm text-gray-600">
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
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-gray-500">Quiz:</span>
                                                    <span class="text-sm font-medium"><?php echo $row['quiz_score']; ?>%</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-gray-500">Assignment:</span>
                                                    <span class="text-sm font-medium"><?php echo $row['assignment_score']; ?>%</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-gray-500">Project:</span>
                                                    <span class="text-sm font-medium"><?php echo $row['project_score']; ?>%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="text-sm">
                                                <div class="text-gray-800">
                                                    <?php echo date('M d, Y', strtotime($row['report_date'])); ?>
                                                </div>
                                                <?php if ($row['progress_updated']): ?>
                                                    <div class="text-gray-500 text-xs">
                                                        Updated: <?php echo date('M d', strtotime($row['progress_updated'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-trophy text-gray-400 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-700 mb-2">No Results Yet</h4>
                        <p class="text-gray-500">Your results will appear here once courses are completed and graded.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Performance Insights -->
            <div class="result-card p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">Performance Insights</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Areas of Strength</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </div>
                                    <span class="text-gray-700">Project Work</span>
                                </div>
                                <span class="font-bold text-green-600">Excellent</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-blue-600"></i>
                                    </div>
                                    <span class="text-gray-700">Topic Completion</span>
                                </div>
                                <span class="font-bold text-blue-600">Good</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3">Areas for Improvement</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-exclamation text-yellow-600"></i>
                                    </div>
                                    <span class="text-gray-700">Quiz Scores</span>
                                </div>
                                <span class="font-bold text-yellow-600">Needs Work</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-exclamation text-red-600"></i>
                                    </div>
                                    <span class="text-gray-700">Timely Submission</span>
                                </div>
                                <span class="font-bold text-red-600">Attention Needed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function exportResults() {
            // In a real application, you would generate and download an export file
            alert('Exporting results to CSV/PDF...');
        }

        function printResults() {
            window.print();
        }
    </script>
</body>
</html>
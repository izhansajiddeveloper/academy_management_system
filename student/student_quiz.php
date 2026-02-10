<?php
// student_quiz.php
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

// Get skill filter
$selected_skill = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;

// Get enrolled skills for dropdown
$skills_query = "
SELECT DISTINCT s.id, s.skill_name 
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name
";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $student_id);
$stmt_skills->execute();
$enrolled_skills = $stmt_skills->get_result();

// If no skill_id specified but student has skills, use the first one
if ($selected_skill == 0 && $enrolled_skills->num_rows > 0) {
    $first_skill = $enrolled_skills->fetch_assoc();
    $selected_skill = $first_skill['id'];
    mysqli_data_seek($enrolled_skills, 0); // Reset pointer
}

// Get available quizzes with skill filter
$quizzes_query = "
SELECT 
    q.*,
    b.batch_name,
    s.skill_name,
    s.id as skill_id,
    b.id as batch_id,
    qr.id as result_id,
    qr.score,
    qr.submitted_at,
    qr.correct_answers,
    CASE 
        WHEN qr.id IS NOT NULL THEN 'completed'
        WHEN q.end_date IS NOT NULL AND q.end_date < NOW() THEN 'expired'
        WHEN q.start_date > NOW() THEN 'upcoming'
        ELSE 'available'
    END as status_label
FROM quizzes q
JOIN batches b ON q.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
LEFT JOIN quiz_results qr ON q.id = qr.quiz_id AND qr.student_id = ?
WHERE q.status = 'published'
AND q.batch_id IN (
    SELECT batch_id 
    FROM student_enrollments 
    WHERE student_id = ? AND status = 'active'
)
";

$params = [$student_id, $student_id];
$types = "ii";

if ($selected_skill > 0) {
    $quizzes_query .= " AND s.id = ?";
    $params[] = $selected_skill;
    $types .= "i";
}

$quizzes_query .= " ORDER BY 
    CASE 
        WHEN qr.id IS NOT NULL THEN 3
        WHEN q.end_date IS NOT NULL AND q.end_date < NOW() THEN 2
        ELSE 1
    END,
    q.start_date DESC";

$stmt_quizzes = $conn->prepare($quizzes_query);
$stmt_quizzes->bind_param($types, ...$params);
$stmt_quizzes->execute();
$quizzes_result = $stmt_quizzes->get_result();

// Count quizzes by status
$available_count = 0;
$completed_count = 0;
$expired_count = 0;
$upcoming_count = 0;

$quizzes_result->data_seek(0);
while ($quiz = $quizzes_result->fetch_assoc()) {
    switch ($quiz['status_label']) {
        case 'available':
            $available_count++;
            break;
        case 'completed':
            $completed_count++;
            break;
        case 'expired':
            $expired_count++;
            break;
        case 'upcoming':
            $upcoming_count++;
            break;
    }
}
$quizzes_result->data_seek(0); // Reset pointer
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quizzes | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .quiz-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .quiz-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-completed {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .status-expired {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #9ca3af;
        }

        .status-upcoming {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
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

        .skill-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background-color: #e0e7ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
        }

        .quiz-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 6px;
        }

        .count-available {
            background: #d1fae5;
            color: #065f46;
        }

        .count-completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .count-expired {
            background: #f3f4f6;
            color: #4b5563;
        }

        .count-upcoming {
            background: #fef3c7;
            color: #92400e;
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
                        <h1 class="text-3xl font-bold text-gray-800">Quizzes</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-question-circle text-blue-500 mr-2"></i>
                            Take quizzes and view your results
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="quiz_results.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-chart-bar"></i>
                            View Results
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Skill Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Skill/Course</label>
                        <div class="flex gap-2">
                            <select id="skillSelect" onchange="changeSkill()"
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="0">All Skills</option>
                                <?php
                                $enrolled_skills->data_seek(0);
                                while ($skill = $enrolled_skills->fetch_assoc()): ?>
                                    <option value="<?php echo $skill['id']; ?>"
                                        <?php echo $selected_skill == $skill['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <?php
                            if ($selected_skill > 0) {
                                $enrolled_skills->data_seek(0);
                                while ($skill = $enrolled_skills->fetch_assoc()) {
                                    if ($skill['id'] == $selected_skill) {
                                        echo "Showing quizzes for: " . htmlspecialchars($skill['skill_name']);
                                        break;
                                    }
                                }
                            } else {
                                echo "Showing quizzes for all enrolled skills";
                            }
                            ?>
                        </p>
                    </div>

                    <!-- Quiz Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Status</label>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="filterStatus('all')" class="filter-tab active">
                                All
                                <span class="quiz-count-badge"><?php echo $available_count + $completed_count + $expired_count + $upcoming_count; ?></span>
                            </button>
                            <button onclick="filterStatus('available')" class="filter-tab">
                                Available
                                <span class="quiz-count-badge count-available"><?php echo $available_count; ?></span>
                            </button>
                            <button onclick="filterStatus('completed')" class="filter-tab">
                                Completed
                                <span class="quiz-count-badge count-completed"><?php echo $completed_count; ?></span>
                            </button>
                            <button onclick="filterStatus('expired')" class="filter-tab">
                                Expired
                                <span class="quiz-count-badge count-expired"><?php echo $expired_count; ?></span>
                            </button>
                            <button onclick="filterStatus('upcoming')" class="filter-tab">
                                Upcoming
                                <span class="quiz-count-badge count-upcoming"><?php echo $upcoming_count; ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="text-blue-600">
                            <span class="font-medium">Total Quizzes:</span> <?php echo $available_count + $completed_count + $expired_count + $upcoming_count; ?>
                        </div>
                        <?php if ($available_count > 0): ?>
                            <div class="text-green-600">
                                <span class="font-medium">Available:</span> <?php echo $available_count; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($completed_count > 0): ?>
                            <div class="text-blue-600">
                                <span class="font-medium">Completed:</span> <?php echo $completed_count; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($expired_count > 0): ?>
                            <div class="text-gray-600">
                                <span class="font-medium">Expired:</span> <?php echo $expired_count; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($upcoming_count > 0): ?>
                            <div class="text-yellow-600">
                                <span class="font-medium">Upcoming:</span> <?php echo $upcoming_count; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quizzes List -->
            <div class="quiz-card">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800">
                        <?php
                        if ($selected_skill > 0) {
                            $enrolled_skills->data_seek(0);
                            while ($skill = $enrolled_skills->fetch_assoc()) {
                                if ($skill['id'] == $selected_skill) {
                                    echo htmlspecialchars($skill['skill_name']) . " Quizzes";
                                    break;
                                }
                            }
                        } else {
                            echo "All Quizzes";
                        }
                        ?>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Take quizzes assigned by your teachers</p>
                </div>

                <div class="p-6">
                    <?php if ($quizzes_result->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="quizList">
                            <?php while ($quiz = $quizzes_result->fetch_assoc()):
                                $status = $quiz['status_label'];
                                $percentage = $quiz['score'] ? round(($quiz['score'] / $quiz['total_marks']) * 100, 1) : 0;
                                $time_limit = $quiz['time_limit'] ? $quiz['time_limit'] . ' mins' : 'No time limit';
                            ?>
                                <div class="quiz-card p-5 quiz-item" data-status="<?php echo $status; ?>">
                                    <!-- Quiz Header -->
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-gray-800 text-lg mb-1">
                                                <?php echo htmlspecialchars($quiz['title']); ?>
                                            </h3>
                                            <?php if ($selected_skill == 0): ?>
                                                <div class="mb-2">
                                                    <span class="skill-badge">
                                                        <?php echo htmlspecialchars($quiz['skill_name']); ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500 ml-2">
                                                        <?php echo htmlspecialchars($quiz['batch_name']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="status-badge status-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </div>

                                    <!-- Quiz Description -->
                                    <?php if (!empty($quiz['description'])): ?>
                                        <p class="text-sm text-gray-600 mb-4">
                                            <?php echo htmlspecialchars(substr($quiz['description'], 0, 120)); ?>
                                            <?php if (strlen($quiz['description']) > 120): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Quiz Details -->
                                    <div class="grid grid-cols-2 gap-3 mb-4">
                                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                                            <div class="text-xs text-gray-500">Questions</div>
                                            <div class="font-bold text-gray-800"><?php echo $quiz['total_questions']; ?></div>
                                        </div>
                                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                                            <div class="text-xs text-gray-500">Marks</div>
                                            <div class="font-bold text-gray-800"><?php echo $quiz['total_marks']; ?></div>
                                        </div>
                                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                                            <div class="text-xs text-gray-500">Time Limit</div>
                                            <div class="font-medium text-gray-800"><?php echo $time_limit; ?></div>
                                        </div>
                                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                                            <div class="text-xs text-gray-500">Status</div>
                                            <div class="font-medium text-gray-800">
                                                <?php if ($status === 'completed'): ?>
                                                    <?php echo $quiz['score']; ?>/<?php echo $quiz['total_marks']; ?>
                                                <?php else: ?>
                                                    <?php echo ucfirst($status); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dates -->
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <?php if ($quiz['start_date']): ?>
                                            <span class="text-xs text-gray-500">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                <?php echo date('M d, Y', strtotime($quiz['start_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($quiz['end_date']): ?>
                                            <span class="text-xs text-gray-500">
                                                <i class="fas fa-clock mr-1"></i>
                                                Due: <?php echo date('M d, Y', strtotime($quiz['end_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Button -->
                                    <div class="mt-4">
                                        <?php if ($status === 'available'): ?>
                                            <a href="take_quiz.php?quiz_id=<?php echo $quiz['id']; ?>"
                                                class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-medium transition-colors">
                                                <i class="fas fa-play mr-2"></i> Start Quiz
                                            </a>
                                        <?php elseif ($status === 'completed'): ?>
                                            <a href="quiz_result_view.php?quiz_id=<?php echo $quiz['id']; ?>"
                                                class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg font-medium transition-colors">
                                                <i class="fas fa-chart-bar mr-2"></i> View Result
                                            </a>
                                        <?php elseif ($status === 'expired'): ?>
                                            <div class="text-center py-3 text-gray-500 border border-gray-200 rounded-lg">
                                                <i class="fas fa-exclamation-triangle mr-2"></i> Quiz Expired
                                            </div>
                                        <?php elseif ($status === 'upcoming'): ?>
                                            <div class="text-center py-3 text-yellow-600 border border-yellow-200 rounded-lg bg-yellow-50">
                                                <i class="fas fa-clock mr-2"></i> Starts Soon
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Additional Info -->
                                    <?php if ($status === 'completed'): ?>
                                        <div class="mt-3 text-center">
                                            <div class="text-sm text-gray-600">
                                                Score: <span class="font-bold text-blue-600"><?php echo $quiz['score']; ?>/<?php echo $quiz['total_marks']; ?></span>
                                                <span class="text-gray-500">(<?php echo $percentage; ?>%)</span>
                                            </div>
                                            <?php if ($quiz['submitted_at']): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Submitted: <?php echo date('M d, Y h:i A', strtotime($quiz['submitted_at'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-question-circle text-gray-300 text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Quizzes Found</h3>
                            <p class="text-gray-500 mb-6">
                                <?php if ($selected_skill > 0): ?>
                                    No quizzes available for the selected skill/course.
                                <?php else: ?>
                                    You don't have any quizzes assigned yet.
                                <?php endif; ?>
                            </p>
                            <?php if ($selected_skill > 0): ?>
                                <a href="quizzes.php" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-arrow-left mr-2"></i> View All Quizzes
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Empty State (when filtered) -->
            <div id="noResults" class="hidden text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-gray-300 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700 mb-2">No Quizzes Match Your Filter</h3>
                <p class="text-gray-500">Try selecting a different status or skill.</p>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Store quiz data for filtering
        const quizData = <?php echo json_encode(array_values($fees_data)); ?>;

        // Change skill function
        function changeSkill() {
            const select = document.getElementById('skillSelect');
            const skill_id = select.value;
            window.location.href = `quizzes.php?skill_id=${skill_id}`;
        }

        // Filter by status
        function filterStatus(status) {
            const quizzes = document.querySelectorAll('.quiz-item');
            const tabs = document.querySelectorAll('.filter-tab');
            const noResults = document.getElementById('noResults');

            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            let visibleCount = 0;

            // Filter quizzes
            quizzes.forEach(quiz => {
                const quizStatus = quiz.getAttribute('data-status');

                if (status === 'all' || quizStatus === status) {
                    quiz.style.display = 'block';
                    visibleCount++;
                } else {
                    quiz.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
                document.querySelector('.quiz-card .p-6').style.display = 'none';
            } else {
                noResults.classList.add('hidden');
                document.querySelector('.quiz-card .p-6').style.display = 'block';
            }
        }

        // Search functionality
        function searchQuizzes() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const quizzes = document.querySelectorAll('.quiz-item');
            const noResults = document.getElementById('noResults');

            let visibleCount = 0;

            quizzes.forEach(quiz => {
                const title = quiz.querySelector('h3').textContent.toLowerCase();
                const description = quiz.querySelector('p.text-sm.text-gray-600')?.textContent.toLowerCase() || '';

                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    quiz.style.display = 'block';
                    visibleCount++;
                } else {
                    quiz.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
                document.querySelector('.quiz-card .p-6').style.display = 'none';
            } else {
                noResults.classList.add('hidden');
                document.querySelector('.quiz-card .p-6').style.display = 'block';
            }
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

            // Initialize search if exists
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', searchQuizzes);
            }
        });
    </script>
</body>

</html>
<?php
// take_quiz.php
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
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if (!$quiz_id) {
    header("Location: student_quiz.php?error=no_quiz_id");
    exit;
}

// Check if quiz exists and is accessible
$quiz_query = "
SELECT q.*, b.batch_name, s.skill_name, b.id as batch_id
FROM quizzes q
JOIN batches b ON q.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE q.id = ? 
AND q.status = 'published'
AND q.batch_id IN (
    SELECT batch_id 
    FROM student_enrollments 
    WHERE student_id = ? AND status = 'active'
)
";

$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("ii", $quiz_id, $student_id);
$stmt->execute();
$quiz_result = $stmt->get_result();

if ($quiz_result->num_rows === 0) {
    header("Location: student_quiz.php?error=quiz_not_found");
    exit;
}

$quiz = $quiz_result->fetch_assoc();

// Check if student has already taken this quiz
$result_query = "SELECT id FROM quiz_results WHERE quiz_id = ? AND student_id = ?";
$stmt_result = $conn->prepare($result_query);
$stmt_result->bind_param("ii", $quiz_id, $student_id);
$stmt_result->execute();
$existing_result = $stmt_result->get_result();

if ($existing_result->num_rows > 0) {
    // Redirect to result page if already taken
    header("Location: quiz_result_view.php?quiz_id=" . $quiz_id);
    exit;
}

// Check if quiz has ended
if (!empty($quiz['end_date']) && strtotime($quiz['end_date']) < time()) {
    header("Location: student_quiz.php?error=quiz_expired");
    exit;
}

// Check if quiz has started
if (!empty($quiz['start_date']) && strtotime($quiz['start_date']) > time()) {
    header("Location: student_quiz.php?error=quiz_not_started");
    exit;
}

// Get quiz questions
$questions_query = "
SELECT * FROM quiz_questions 
WHERE quiz_id = ? 
ORDER BY id
";
$stmt_questions = $conn->prepare($questions_query);
$stmt_questions->bind_param("i", $quiz_id);
$stmt_questions->execute();
$questions = $stmt_questions->get_result();

if ($questions->num_rows === 0) {
    header("Location: student_quiz.php?error=no_questions");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $total_questions = $_POST['total_questions'] ?? 0;
    
    // Calculate score
    $score = 0;
    $correct_answers = 0;
    $detailed_answers = [];
    
    $questions->data_seek(0); // Reset pointer
    while ($question = $questions->fetch_assoc()) {
        $question_id = $question['id'];
        $student_answer = $answers[$question_id] ?? null;
        
        // Store answer
        $detailed_answers[$question_id] = $student_answer;
        
        // Check if answer is correct
        if ($student_answer !== null && $student_answer == $question['correct_answer']) {
            $score += $question['marks'];
            $correct_answers++;
        }
    }
    
    // Insert result
$insert_query = "
INSERT INTO quiz_results (
    quiz_id, 
    student_id, 
    batch_id, 
    score, 
    total_questions, 
    correct_answers, 
    answers,
    submitted_at
) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt_insert = $conn->prepare($insert_query);

if (!$stmt_insert) {
    die("Prepare failed: " . $conn->error);
}

$answers_json = json_encode($detailed_answers);

$stmt_insert->bind_param(
    "iiidiis",
    $quiz_id,
    $student_id,
    $quiz['batch_id'],
    $score,
    $total_questions,
    $correct_answers,
    $answers_json
);

if ($stmt_insert->execute()) {
    $result_id = $stmt_insert->insert_id;

    // Redirect to result page
    header("Location: quiz_result_view.php?quiz_id=" . $quiz_id . "&result_id=" . $result_id);
    exit;
} else {
    $error = "Failed to submit quiz. Please try again. " . $stmt_insert->error;
}

// Reset pointer for display
$questions->data_seek(0);

// Calculate remaining time in seconds
$time_limit_seconds = $quiz['time_limit'] ? $quiz['time_limit'] * 60 : 0;
$end_time = time() + $time_limit_seconds;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Take Quiz: <?php echo htmlspecialchars($quiz['title']); ?> | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .quiz-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .question-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.2s;
        }

        .question-card.active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-label:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .option-label.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .option-radio {
            display: none;
        }

        .option-marker {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .option-label.selected .option-marker {
            border-color: #3b82f6;
            background: #3b82f6;
        }

        .option-label.selected .option-marker::after {
            content: '';
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        .timer-container {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .timer-display {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .progress-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .question-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .question-nav-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .question-nav-btn:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .question-nav-btn.active {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }

        .question-nav-btn.answered {
            border-color: #10b981;
            background: #d1fae5;
            color: #065f46;
        }

        .nav-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-btn.prev {
            border: 2px solid #e5e7eb;
            background: white;
            color: #4b5563;
        }

        .nav-btn.prev:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .nav-btn.next {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .nav-btn.next:hover {
            background: #2563eb;
        }

        .nav-btn.submit {
            background: #10b981;
            color: white;
            border: none;
        }

        .nav-btn.submit:hover {
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
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                        <p class="text-gray-600 mt-1">
                            <i class="fas fa-book-open text-blue-500 mr-2"></i>
                            <?php echo htmlspecialchars($quiz['skill_name'] . ' - ' . $quiz['batch_name']); ?>
                        </p>
                    </div>
                    <div>
                        <a href="student_quiz.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Quizzes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quiz Info -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="quiz-container p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-question-circle text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Questions</p>
                            <p class="font-bold text-gray-800"><?php echo $questions->num_rows; ?></p>
                        </div>
                    </div>
                </div>

                <div class="quiz-container p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-star text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Marks</p>
                            <p class="font-bold text-gray-800"><?php echo $quiz['total_marks']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="quiz-container p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Time Limit</p>
                            <p class="font-bold text-gray-800"><?php echo $quiz['time_limit'] ? $quiz['time_limit'] . ' mins' : 'No limit'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="timer-container">
                    <p class="text-sm opacity-90">Time Remaining</p>
                    <div id="timer" class="timer-display">00:00</div>
                    <div class="text-xs opacity-90">Complete before time runs out</div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Instructions -->
            <div class="quiz-container p-6 mb-6 bg-yellow-50 border-yellow-200">
                <h3 class="font-bold text-gray-800 mb-3">Instructions</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>Read each question carefully before answering.</li>
                    <li>You can navigate between questions using the buttons below.</li>
                    <li>Your answers will be saved automatically as you progress.</li>
                    <li>Once submitted, you cannot change your answers.</li>
                    <?php if ($quiz['time_limit']): ?>
                        <li>The quiz will auto-submit when time runs out.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <form method="POST" action="" id="quizForm">
                <input type="hidden" name="total_questions" value="<?php echo $questions->num_rows; ?>">
                
                <!-- Question Navigation -->
                <div class="quiz-container p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Question Navigation</h3>
                    <div class="question-nav" id="questionNav">
                        <?php 
                        $questions->data_seek(0);
                        $question_count = 0;
                        while ($question = $questions->fetch_assoc()): 
                            $question_count++;
                        ?>
                            <div class="question-nav-btn" 
                                 data-question="<?php echo $question_count; ?>"
                                 onclick="goToQuestion(<?php echo $question_count; ?>)">
                                <?php echo $question_count; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-100 border border-blue-400 rounded"></div>
                            <span class="text-xs text-gray-600">Current</span>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <div class="w-3 h-3 bg-green-100 border border-green-400 rounded"></div>
                            <span class="text-xs text-gray-600">Answered</span>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <div class="w-3 h-3 bg-gray-100 border border-gray-400 rounded"></div>
                            <span class="text-xs text-gray-600">Unanswered</span>
                        </div>
                    </div>
                </div>

                <!-- Questions Container -->
                <div class="quiz-container p-6">
                    <div id="questionsContainer">
                        <?php 
                        $questions->data_seek(0);
                        $question_number = 0;
                        while ($question = $questions->fetch_assoc()): 
                            $question_number++;
                            $options = $question['options'] ? json_decode($question['options'], true) : [];
                        ?>
                            <div class="question-card" id="question-<?php echo $question_number; ?>" 
                                 style="display: <?php echo $question_number === 1 ? 'block' : 'none'; ?>">
                                
                                <!-- Question Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800 mb-2">
                                            Question <?php echo $question_number; ?>
                                            <span class="text-sm font-normal text-gray-500 ml-2">
                                                (<?php echo $question['marks']; ?> marks)
                                            </span>
                                        </h3>
                                        <p class="text-gray-700"><?php echo htmlspecialchars($question['question']); ?></p>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>
                                    </div>
                                </div>

                                <!-- Multiple Choice Options -->
                                <?php if ($question['question_type'] === 'multiple_choice' && !empty($options)): ?>
                                    <div class="space-y-2">
                                        <?php foreach ($options as $key => $value): ?>
                                            <label class="option-label">
                                                <input type="radio" 
                                                       name="answers[<?php echo $question['id']; ?>]" 
                                                       value="<?php echo htmlspecialchars($key); ?>"
                                                       class="option-radio"
                                                       onchange="markQuestionAnswered(<?php echo $question_number; ?>)">
                                                <div class="option-marker"></div>
                                                <div class="flex-1">
                                                    <span class="font-medium mr-2"><?php echo htmlspecialchars($key); ?>.</span>
                                                    <span><?php echo htmlspecialchars($value); ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <!-- True/False Options -->
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <div class="space-y-2">
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="answers[<?php echo $question['id']; ?>]" 
                                                   value="true"
                                                   class="option-radio"
                                                   onchange="markQuestionAnswered(<?php echo $question_number; ?>)">
                                            <div class="option-marker"></div>
                                            <span class="font-medium">True</span>
                                        </label>
                                        <label class="option-label">
                                            <input type="radio" 
                                                   name="answers[<?php echo $question['id']; ?>]" 
                                                   value="false"
                                                   class="option-radio"
                                                   onchange="markQuestionAnswered(<?php echo $question_number; ?>)">
                                            <div class="option-marker"></div>
                                            <span class="font-medium">False</span>
                                        </label>
                                    </div>
                                
                                <!-- Short Answer -->
                                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                    <div>
                                        <textarea name="answers[<?php echo $question['id']; ?>]" 
                                                  rows="3"
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                  placeholder="Type your answer here..."
                                                  oninput="markQuestionAnswered(<?php echo $question_number; ?>)"></textarea>
                                        <p class="text-xs text-gray-500 mt-2">Enter a short answer for this question.</p>
                                    </div>
                                
                                <!-- Essay -->
                                <?php elseif ($question['question_type'] === 'essay'): ?>
                                    <div>
                                        <textarea name="answers[<?php echo $question['id']; ?>]" 
                                                  rows="6"
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                  placeholder="Write your essay answer here..."
                                                  oninput="markQuestionAnswered(<?php echo $question_number; ?>)"></textarea>
                                        <p class="text-xs text-gray-500 mt-2">Write a detailed essay answer.</p>
                                    </div>
                                
                                <!-- Fill in the Blanks -->
                                <?php elseif ($question['question_type'] === 'fill_blank'): ?>
                                    <div>
                                        <input type="text" 
                                               name="answers[<?php echo $question['id']; ?>]"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Enter the missing word/phrase..."
                                               oninput="markQuestionAnswered(<?php echo $question_number; ?>)">
                                        <p class="text-xs text-gray-500 mt-2">Fill in the blank with the correct answer.</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Question Footer -->
                                <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
                                    <div class="text-sm text-gray-500">
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            Select one correct option
                                        <?php elseif ($question['question_type'] === 'true_false'): ?>
                                            Select True or False
                                        <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                            Brief answer required
                                        <?php elseif ($question['question_type'] === 'essay'): ?>
                                            Detailed answer required
                                        <?php elseif ($question['question_type'] === 'fill_blank'): ?>
                                            Fill in the blank
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Marks: <?php echo $question['marks']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                        <button type="button" 
                                id="prevBtn" 
                                class="nav-btn prev"
                                onclick="prevQuestion()"
                                style="display: none;">
                            <i class="fas fa-arrow-left mr-2"></i> Previous
                        </button>
                        
                        <div class="flex gap-3">
                            <button type="button" 
                                    id="nextBtn" 
                                    class="nav-btn next"
                                    onclick="nextQuestion()">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            
                            <?php if ($question_count > 1): ?>
                                <button type="button" 
                                        id="submitBtn" 
                                        class="nav-btn submit"
                                        style="display: none;"
                                        onclick="submitQuiz()">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Quiz
                                </button>
                            <?php else: ?>
                                <button type="submit" 
                                        class="nav-btn submit">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Quiz
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-6">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Progress</span>
                            <span id="progressText">0/<?php echo $question_count; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div id="progressFill" class="progress-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Confirm Submission Modal -->
            <div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl max-w-md w-full">
                    <div class="p-6">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-question-circle text-blue-600 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Submit Quiz?</h3>
                            <p class="text-gray-600">Are you sure you want to submit your quiz? You cannot change answers after submission.</p>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" 
                                    onclick="closeConfirmModal()" 
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-medium">
                                Cancel
                            </button>
                            <button type="button" 
                                    onclick="confirmSubmission()" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium">
                                Yes, Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Quiz state
        let currentQuestion = 1;
        const totalQuestions = <?php echo $question_count; ?>;
        let answeredQuestions = new Set();
        let timeLeft = <?php echo $time_limit_seconds; ?>;
        let timerInterval = null;

        // Initialize timer if time limit exists
        <?php if ($time_limit_seconds > 0): ?>
            function updateTimer() {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    submitQuiz();
                    return;
                }

                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                document.getElementById('timer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Change color when time is running low
                if (timeLeft < 60) {
                    document.getElementById('timer').classList.add('text-red-500');
                }
            }

            // Start timer
            timerInterval = setInterval(updateTimer, 1000);
            updateTimer(); // Initial call
        <?php endif; ?>

        // Update progress
        function updateProgress() {
            const progress = answeredQuestions.size;
            const percentage = (progress / totalQuestions) * 100;
            
            document.getElementById('progressText').textContent = `${progress}/${totalQuestions}`;
            document.getElementById('progressFill').style.width = `${percentage}%`;
        }

        // Mark question as answered
        function markQuestionAnswered(questionNum) {
            answeredQuestions.add(questionNum);
            updateNavigation();
            updateProgress();
            
            // Show/hide submit button
            if (answeredQuestions.size === totalQuestions) {
                document.getElementById('submitBtn').style.display = 'block';
            }
        }

        // Update navigation buttons
        function updateNavigation() {
            const navButtons = document.querySelectorAll('.question-nav-btn');
            navButtons.forEach(btn => {
                const questionNum = parseInt(btn.dataset.question);
                btn.classList.remove('active', 'answered');
                
                if (questionNum === currentQuestion) {
                    btn.classList.add('active');
                } else if (answeredQuestions.has(questionNum)) {
                    btn.classList.add('answered');
                }
            });
            
            // Update prev/next buttons
            document.getElementById('prevBtn').style.display = currentQuestion === 1 ? 'none' : 'block';
            document.getElementById('nextBtn').style.display = currentQuestion === totalQuestions ? 'none' : 'block';
        }

        // Navigate to question
        function goToQuestion(questionNum) {
            // Hide current question
            document.getElementById(`question-${currentQuestion}`).style.display = 'none';
            
            // Show new question
            currentQuestion = questionNum;
            document.getElementById(`question-${currentQuestion}`).style.display = 'block';
            
            updateNavigation();
        }

        // Next question
        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                goToQuestion(currentQuestion + 1);
            }
        }

        // Previous question
        function prevQuestion() {
            if (currentQuestion > 1) {
                goToQuestion(currentQuestion - 1);
            }
        }

        // Show confirmation modal
        function submitQuiz() {
            if (answeredQuestions.size < totalQuestions) {
                document.getElementById('confirmModal').classList.remove('hidden');
            } else {
                confirmSubmission();
            }
        }

        // Close confirmation modal
        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        // Confirm submission
        function confirmSubmission() {
            <?php if ($time_limit_seconds > 0): ?>
                clearInterval(timerInterval);
            <?php endif; ?>
            document.getElementById('quizForm').submit();
        }

        // Auto-save answers periodically
        function autoSaveAnswers() {
            const formData = new FormData(document.getElementById('quizForm'));
            // In a real application, you would send this data to the server via AJAX
            console.log('Auto-saving answers...');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateNavigation();
            updateProgress();
            
            // Auto-save every 30 seconds
            setInterval(autoSaveAnswers, 30000);
            
            // Prevent accidental navigation
            window.addEventListener('beforeunload', function(e) {
                if (answeredQuestions.size > 0 && answeredQuestions.size < totalQuestions) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved answers. Are you sure you want to leave?';
                }
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl + S to save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    autoSaveAnswers();
                    return false;
                }
                
                // Left arrow for previous question
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    prevQuestion();
                }
                
                // Right arrow for next question
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextQuestion();
                }
                
                // Number keys to jump to questions
                if (e.key >= '1' && e.key <= '9') {
                    const num = parseInt(e.key);
                    if (num <= totalQuestions) {
                        e.preventDefault();
                        goToQuestion(num);
                    }
                }
            });
        });

        // Show warning before leaving
        window.onbeforeunload = function() {
            if (answeredQuestions.size > 0 && answeredQuestions.size < totalQuestions) {
                return 'You have unsaved answers. Are you sure you want to leave?';
            }
        };
    </script>
</body>

</html>
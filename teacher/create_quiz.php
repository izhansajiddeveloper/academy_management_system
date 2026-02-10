<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$quiz_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit = $quiz_id > 0;
$quiz = null;
$questions = [];

// If editing, fetch quiz data
if ($is_edit) {
    // Check if quiz belongs to this teacher
    $quiz_query = "
    SELECT q.*, b.batch_name, s.skill_name 
    FROM quizzes q
    LEFT JOIN batches b ON q.batch_id = b.id
    LEFT JOIN skills s ON b.skill_id = s.id
    WHERE q.id = ? AND q.teacher_id = ?
    ";

    $stmt = $conn->prepare($quiz_query);
    $stmt->bind_param("ii", $quiz_id, $teacher_id);
    $stmt->execute();
    $quiz_result = $stmt->get_result();

    if ($quiz_result->num_rows > 0) {
        $quiz = $quiz_result->fetch_assoc();

        // Fetch questions if any
        $questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id";
        $stmt_q = $conn->prepare($questions_query);
        $stmt_q->bind_param("i", $quiz_id);
        $stmt_q->execute();
        $questions_result = $stmt_q->get_result();
        $questions = $questions_result->fetch_all(MYSQLI_ASSOC);
    } else {
        $_SESSION['error'] = "Quiz not found or unauthorized to edit.";
        header("Location: my_quiz.php");
        exit;
    }
}

// Fetch teacher's batches
$batches_query = "
SELECT b.id, b.batch_name, s.skill_name 
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
WHERE ta.teacher_id = ? AND b.status = 'active'
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$teacher_batches = $stmt_batches->get_result();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = intval($_POST['batch_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $total_questions = intval($_POST['total_questions']);
    $total_marks = intval($_POST['total_marks']);
    $time_limit = intval($_POST['time_limit']);
    $status = $_POST['status'];


    // Validate
    if (empty($title) || $batch_id <= 0 || $total_questions <= 0 || $total_marks <= 0) {
    } else {
        if ($is_edit) {
            // Update existing quiz
            $update_query = "
            UPDATE quizzes SET 
                batch_id = ?,
                title = ?,
                description = ?,
                total_questions = ?,
                total_marks = ?,
                time_limit = ?,
                status = ?
            WHERE id = ? AND teacher_id = ?
            ";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(
                "issiiisii",
                $batch_id,
                $title,
                $description,
                $total_questions,
                $total_marks,
                $time_limit,
                $status,
                $quiz_id,
                $teacher_id
            );

            if ($stmt->execute()) {
                $_SESSION['success'] = "Quiz updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update quiz.";
            }
        } else {
            // Insert new quiz
            $insert_query = "
            INSERT INTO quizzes (batch_id, teacher_id, title, description, total_questions, total_marks, time_limit, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param(
                "iissiiis",
                $batch_id,
                $teacher_id,
                $title,
                $description,
                $total_questions,
                $total_marks,
                $time_limit,
                $status
            );

            if ($stmt->execute()) {
                $quiz_id = $stmt->insert_id;
                $_SESSION['success'] = "Quiz created successfully!";
                header("Location: create_quiz.php?edit=" . $quiz_id);
                exit;
            } else {
                $_SESSION['error'] = "Failed to create quiz.";
            }
        }
    }
}

// Handle question addition/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_question' && $quiz_id > 0) {
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $question_text = trim($_POST['question_text']);
        $question_type = $_POST['question_type'];
        $marks = intval($_POST['marks']);
        $correct_answer = trim($_POST['correct_answer']);

        // Process options for multiple choice
        $options = [];
        if ($question_type === 'multiple_choice') {
            $options = [
                'A' => trim($_POST['option_a']),
                'B' => trim($_POST['option_b']),
                'C' => trim($_POST['option_c']),
                'D' => trim($_POST['option_d'])
            ];
            $options_json = json_encode($options);
        } else {
            $options_json = null;
        }

        if ($question_id > 0) {
            // Update question
            $update_q = "UPDATE quiz_questions SET question = ?, question_type = ?, marks = ?, options = ?, correct_answer = ? WHERE id = ? AND quiz_id = ?";
            $stmt = $conn->prepare($update_q);
            $stmt->bind_param("ssissii", $question_text, $question_type, $marks, $options_json, $correct_answer, $question_id, $quiz_id);
            $stmt->execute();

            $_SESSION['success'] = "Question updated successfully!";
        } else {
            // Insert new question
            $insert_q = "INSERT INTO quiz_questions (quiz_id, question, question_type, marks, options, correct_answer) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_q);
            $stmt->bind_param("ississ", $quiz_id, $question_text, $question_type, $marks, $options_json, $correct_answer);
            $stmt->execute();

            $_SESSION['success'] = "Question added successfully!";
        }

        // Update total marks in quiz
        $update_marks = "UPDATE quizzes SET total_marks = (SELECT SUM(marks) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?";
        $stmt_m = $conn->prepare($update_marks);
        $stmt_m->bind_param("ii", $quiz_id, $quiz_id);
        $stmt_m->execute();

        header("Location: create_quiz.php?edit=" . $quiz_id);
        exit;
    }
}

// Handle question deletion
if (isset($_GET['delete_question']) && $quiz_id > 0) {
    $question_id = intval($_GET['delete_question']);

    $delete_q = "DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?";
    $stmt = $conn->prepare($delete_q);
    $stmt->bind_param("ii", $question_id, $quiz_id);

    if ($stmt->execute()) {
        // Update total marks
        $update_marks = "UPDATE quizzes SET total_marks = (SELECT SUM(marks) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?";
        $stmt_m = $conn->prepare($update_marks);
        $stmt_m->bind_param("ii", $quiz_id, $quiz_id);
        $stmt_m->execute();

        $_SESSION['success'] = "Question deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete question.";
    }

    header("Location: create_quiz.php?edit=" . $quiz_id);
    exit;
}

// Check if there are submissions for this quiz (for warning message)
if ($is_edit) {
    $submissions_query = "SELECT COUNT(*) as total_submissions FROM quiz_results WHERE quiz_id = ?";
    $stmt_sub = $conn->prepare($submissions_query);
    $stmt_sub->bind_param("i", $quiz_id);
    $stmt_sub->execute();
    $submissions_result = $stmt_sub->get_result()->fetch_assoc();
    $has_submissions = ($submissions_result['total_submissions'] ?? 0) > 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Quiz | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .question-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .question-card.active {
            border-color: #3b82f6;
        }

        .option-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }

        .option-input {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.5rem;
            width: 100%;
        }

        .option-input.correct {
            border-color: #10b981;
            background-color: #f0fdf4;
        }

        @media (max-width: 768px) {
            .option-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">
                            <?php echo $is_edit ? 'Edit Quiz' : 'Create New Quiz'; ?>
                        </h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-question-circle text-blue-500 mr-2"></i>
                            <?php echo $is_edit ? 'Update quiz details and questions' : 'Create a new quiz for your batch'; ?>
                        </p>
                    </div>
                    <div>
                        <a href="my_quiz.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Quizzes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Warning if quiz has submissions -->
            <?php if ($is_edit && $has_submissions): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                        <div>
                            <h3 class="font-medium text-yellow-800">Quiz Has Student Submissions</h3>
                            <p class="text-sm text-yellow-700">
                                This quiz has <?php echo $submissions_result['total_submissions']; ?> student submission(s).
                                Editing questions may affect existing results.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Quiz Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Quiz Details</h2>

                        <form method="POST" action="">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Batch *</label>
                                    <select name="batch_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="">-- Select Batch --</option>
                                        <?php while ($batch = $teacher_batches->fetch_assoc()): ?>
                                            <option value="<?php echo $batch['id']; ?>"
                                                <?php echo ($is_edit && $quiz['batch_id'] == $batch['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($batch['skill_name'] . ' - ' . $batch['batch_name']); ?>
                                            </option>
                                        <?php endwhile;
                                        mysqli_data_seek($teacher_batches, 0); ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quiz Title *</label>
                                    <input type="text" name="title" required
                                        value="<?php echo htmlspecialchars($quiz['title'] ?? ''); ?>"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="description" rows="3"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2"><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Questions *</label>
                                        <input type="number" name="total_questions" required min="1" max="100"
                                            value="<?php echo $quiz['total_questions'] ?? '10'; ?>"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Marks *</label>
                                        <input type="number" name="total_marks" required min="1" max="500"
                                            value="<?php echo $quiz['total_marks'] ?? '10'; ?>"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Time Limit (minutes)</label>
                                        <input type="number" name="time_limit" min="5" max="180"
                                            value="<?php echo $quiz['time_limit'] ?? '60'; ?>"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                        <option value="draft" <?php echo ($quiz['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo ($quiz['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="archived" <?php echo ($quiz['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                        <i class="fas fa-save mr-2"></i>
                                        <?php echo $is_edit ? 'Update Quiz Details' : 'Create Quiz'; ?>
                                    </button>

                                    <?php if ($is_edit): ?>
                                        <a href="my_quiz.php" class="ml-3 text-gray-600 hover:text-gray-800">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Questions Section (Only for edit mode) -->
                    <?php if ($is_edit && $quiz): ?>
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-lg font-bold text-gray-800">Quiz Questions</h2>
                                <button type="button" onclick="openAddQuestionModal()"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                    <i class="fas fa-plus mr-2"></i> Add Question
                                </button>
                            </div>

                            <?php if (count($questions) > 0): ?>
                                <div class="space-y-4">
                                    <?php foreach ($questions as $index => $question): ?>
                                        <div class="question-card">
                                            <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                                                <div class="flex items-center">
                                                    <span class="font-medium text-gray-700 mr-3">Q<?php echo $index + 1; ?></span>
                                                    <span class="text-sm text-gray-500">
                                                        (<?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?> - <?php echo $question['marks']; ?> marks)
                                                    </span>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="button" onclick="editQuestion(<?php echo $question['id']; ?>)"
                                                        class="text-blue-600 hover:text-blue-800">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?edit=<?php echo $quiz_id; ?>&delete_question=<?php echo $question['id']; ?>"
                                                        class="text-red-600 hover:text-red-800"
                                                        onclick="return confirm('Are you sure you want to delete this question?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                <p class="text-gray-800 mb-3"><?php echo htmlspecialchars($question['question']); ?></p>

                                                <?php if ($question['question_type'] === 'multiple_choice' && $question['options']): ?>
                                                    <?php $options = json_decode($question['options'], true); ?>
                                                    <div class="space-y-2">
                                                        <?php foreach ($options as $key => $value): ?>
                                                            <div class="flex items-center">
                                                                <div class="w-6 h-6 border border-gray-300 rounded flex items-center justify-center mr-2">
                                                                    <?php echo $key; ?>
                                                                </div>
                                                                <span class="<?php echo $question['correct_answer'] == $key ? 'text-green-600 font-medium' : 'text-gray-600'; ?>">
                                                                    <?php echo htmlspecialchars($value); ?>
                                                                </span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                                    <div class="text-gray-600">
                                                        Correct Answer: <span class="font-medium"><?php echo ucfirst($question['correct_answer']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-gray-600">
                                                        Answer: <span class="font-medium"><?php echo htmlspecialchars($question['correct_answer']); ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Marking Instructions -->
                                                <div class="mt-3 pt-3 border-t border-gray-200">
                                                    <p class="text-xs text-gray-500">
                                                        <i class="fas fa-info-circle mr-1"></i>
                                                        <strong>For marking:</strong> Students will receive <?php echo $question['marks']; ?> marks for correct answer, 0 for incorrect.
                                                        <?php if ($question['question_type'] === 'short_answer'): ?>
                                                            Teacher can give partial marks manually.
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-question text-gray-300 text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Questions Added Yet</h3>
                                    <p class="text-gray-500 mb-4">Add questions to your quiz.</p>
                                    <button type="button" onclick="openAddQuestionModal()"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                                        <i class="fas fa-plus mr-2"></i> Add First Question
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($is_edit): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                                <div>
                                    <h3 class="font-medium text-yellow-800">Save Quiz First</h3>
                                    <p class="text-sm text-yellow-700">Save the quiz details first to add questions.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Stats & Instructions -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Quiz Stats</h2>

                        <div class="space-y-4">
                            <?php if ($is_edit && $quiz): ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Questions Added:</span>
                                    <span class="font-medium"><?php echo count($questions); ?>/<?php echo $quiz['total_questions']; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Marks:</span>
                                    <span class="font-medium"><?php echo $quiz['total_marks']; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Time Limit:</span>
                                    <span class="font-medium"><?php echo $quiz['time_limit']; ?> minutes</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $quiz['status'] === 'published' ? 'bg-green-100 text-green-800' : ($quiz['status'] === 'archived' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($quiz['status']); ?>
                                    </span>
                                </div>
                                <?php if ($has_submissions): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Submissions:</span>
                                        <span class="font-medium"><?php echo $submissions_result['total_submissions']; ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="text-sm"><?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-gray-500">
                                    <i class="fas fa-chart-bar text-3xl mb-2"></i>
                                    <p>Stats will appear after creating the quiz</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_edit && $quiz['status'] === 'published'): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <a href="quiz_result.php?quiz_id=<?php echo $quiz_id; ?>"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium text-sm flex items-center justify-center">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Mark Student Submissions
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Instructions</h2>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                Select the correct batch for the quiz
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                Set appropriate time limit for the quiz
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                Add questions after saving quiz details
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                Publish quiz to make it available to students
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                After students submit, mark quizzes from "My Quizzes" page
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                MCQ/True-False auto-graded, Short Answer needs manual marking
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Question Modal -->
    <div id="questionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Add Question</h3>
                    <button type="button" onclick="closeQuestionModal()"
                        class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <form id="questionForm" method="POST" action="">
                    <input type="hidden" name="action" value="save_question">
                    <input type="hidden" name="question_id" id="questionId" value="0">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Question Type *</label>
                            <select name="question_type" id="questionType" required
                                onchange="toggleAnswerFields()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Question Text *</label>
                            <textarea name="question_text" id="questionText" required rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marks *</label>
                            <input type="number" name="marks" id="questionMarks" required min="1" max="10"
                                value="1" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>

                        <!-- Multiple Choice Options -->
                        <div id="multipleChoiceOptions" class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Options *</label>

                            <div class="option-container">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Option A</label>
                                    <input type="text" name="option_a" id="optionA"
                                        class="option-input" placeholder="Enter option A">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Option B</label>
                                    <input type="text" name="option_b" id="optionB"
                                        class="option-input" placeholder="Enter option B">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Option C</label>
                                    <input type="text" name="option_c" id="optionC"
                                        class="option-input" placeholder="Enter option C">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Option D</label>
                                    <input type="text" name="option_d" id="optionD"
                                        class="option-input" placeholder="Enter option D">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Correct Answer *</label>
                                <select name="correct_answer" id="mcqCorrectAnswer"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                        </div>

                        <!-- True/False Answer -->
                        <div id="trueFalseAnswer" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Correct Answer *</label>
                            <select name="correct_answer" id="tfCorrectAnswer"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="true">True</option>
                                <option value="false">False</option>
                            </select>
                        </div>

                        <!-- Short Answer -->
                        <div id="shortAnswer" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Correct Answer *</label>
                            <textarea name="correct_answer" id="saCorrectAnswer" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                For short answer questions, you'll need to manually mark student responses and award marks.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <button type="button" onclick="closeQuestionModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                            Save Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let editingQuestionId = 0;

        function openAddQuestionModal() {
            document.getElementById('modalTitle').textContent = 'Add Question';
            document.getElementById('questionId').value = '0';
            document.getElementById('questionForm').reset();
            document.getElementById('questionType').value = 'multiple_choice';
            document.getElementById('questionMarks').value = '1';
            toggleAnswerFields();
            document.getElementById('questionModal').classList.remove('hidden');
        }

        function editQuestion(questionId) {
            // Fetch question data via AJAX
            fetch(`get_question.php?question_id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Question';
                    document.getElementById('questionId').value = questionId;
                    document.getElementById('questionType').value = data.question_type;
                    document.getElementById('questionText').value = data.question;
                    document.getElementById('questionMarks').value = data.marks;

                    if (data.question_type === 'multiple_choice' && data.options) {
                        const options = JSON.parse(data.options);
                        document.getElementById('optionA').value = options.A || '';
                        document.getElementById('optionB').value = options.B || '';
                        document.getElementById('optionC').value = options.C || '';
                        document.getElementById('optionD').value = options.D || '';
                        document.getElementById('mcqCorrectAnswer').value = data.correct_answer;
                    } else if (data.question_type === 'true_false') {
                        document.getElementById('tfCorrectAnswer').value = data.correct_answer;
                    } else {
                        document.getElementById('saCorrectAnswer').value = data.correct_answer;
                    }

                    toggleAnswerFields();
                    document.getElementById('questionModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load question data');
                });
        }

        function closeQuestionModal() {
            document.getElementById('questionModal').classList.add('hidden');
        }

        function toggleAnswerFields() {
            const questionType = document.getElementById('questionType').value;

            // Hide all answer fields first
            document.getElementById('multipleChoiceOptions').classList.add('hidden');
            document.getElementById('trueFalseAnswer').classList.add('hidden');
            document.getElementById('shortAnswer').classList.add('hidden');

            // Show relevant fields
            if (questionType === 'multiple_choice') {
                document.getElementById('multipleChoiceOptions').classList.remove('hidden');
            } else if (questionType === 'true_false') {
                document.getElementById('trueFalseAnswer').classList.remove('hidden');
            } else if (questionType === 'short_answer') {
                document.getElementById('shortAnswer').classList.remove('hidden');
            }
        }

        // Close modal when clicking outside
        document.getElementById('questionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuestionModal();
            }
        });

        // Auto-calculate total marks when questions are added/removed
        function updateTotalMarks() {
            // This would be updated via AJAX after each question operation
            // For now, it's handled server-side
        }
    </script>
</body>

</html>
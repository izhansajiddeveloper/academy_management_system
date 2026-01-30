<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Handle quiz deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Check if quiz belongs to this teacher
    $check_query = "SELECT id FROM quizzes WHERE id = ? AND teacher_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $delete_id, $teacher_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        // Delete quiz (cascade will delete questions)
        $delete_query = "DELETE FROM quizzes WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_query);
        $stmt_delete->bind_param("i", $delete_id);

        if ($stmt_delete->execute()) {
            $_SESSION['success'] = "Quiz deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete quiz.";
        }
    } else {
        $_SESSION['error'] = "Quiz not found or unauthorized.";
    }
    header("Location: my_quiz.php");
    exit;
}

// Handle status change
if (isset($_GET['change_status'])) {
    $quiz_id = intval($_GET['quiz_id']);
    $new_status = $_GET['new_status'];

    // Validate status
    $valid_statuses = ['draft', 'published', 'archived'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status.";
        header("Location: my_quiz.php");
        exit;
    }

    // Check ownership
    $check_query = "SELECT id FROM quizzes WHERE id = ? AND teacher_id = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ii", $quiz_id, $teacher_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $update_query = "UPDATE quizzes SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("si", $new_status, $quiz_id);

        if ($stmt_update->execute()) {
            $_SESSION['success'] = "Quiz status updated to " . ucfirst($new_status) . "!";
        } else {
            $_SESSION['error'] = "Failed to update status.";
        }
    } else {
        $_SESSION['error'] = "Quiz not found or unauthorized.";
    }
    header("Location: my_quiz.php");
    exit;
}

// Fetch quizzes with batch information
$query = "
SELECT 
    q.*,
    b.batch_name,
    s.skill_name,
    COUNT(qq.id) as actual_questions,
    COUNT(qr.id) as total_submissions,
    COUNT(CASE WHEN qr.score IS NOT NULL THEN 1 END) as marked_submissions
FROM quizzes q
LEFT JOIN batches b ON q.batch_id = b.id
LEFT JOIN skills s ON b.skill_id = s.id
LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
WHERE q.teacher_id = ?
GROUP BY q.id
ORDER BY q.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$quizzes = $stmt->get_result();

// Fetch teacher's batches for filter
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Quizzes | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-draft {
            background: #f3f4f6;
            color: #6b7280;
        }

        .status-published {
            background: #d1fae5;
            color: #065f46;
        }

        .status-archived {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .submission-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .submission-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .submission-marked {
            background: #d1fae5;
            color: #065f46;
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
                        <h1 class="text-3xl font-bold text-gray-800">My Quizzes</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-question-circle text-blue-500 mr-2"></i>
                            Create and manage quizzes for your batches
                        </p>
                    </div>
                    <div>
                        <a href="create_quiz.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-plus mr-2"></i> Create New Quiz
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

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-wrap gap-4 items-center">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="all">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                        <select id="batchFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="all">All Batches</option>
                            <?php while ($batch = $teacher_batches->fetch_assoc()): ?>
                                <option value="<?php echo $batch['id']; ?>">
                                    <?php echo htmlspecialchars($batch['skill_name'] . ' - ' . $batch['batch_name']); ?>
                                </option>
                            <?php endwhile;
                            mysqli_data_seek($teacher_batches, 0); ?>
                        </select>
                    </div>
                    <div class="ml-auto">
                        <button id="resetFilters" class="text-gray-600 hover:text-gray-800 text-sm">
                            <i class="fas fa-redo mr-1"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quizzes Table -->
            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Quiz Title</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Questions</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Marks</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Submissions</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Created</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="quizTableBody">
                            <?php if ($quizzes->num_rows > 0): ?>
                                <?php while ($quiz = $quizzes->fetch_assoc()):
                                    $pending_submissions = $quiz['total_submissions'] - $quiz['marked_submissions'];
                                ?>
                                    <tr class="hover:bg-gray-50"
                                        data-status="<?php echo $quiz['status']; ?>"
                                        data-batch="<?php echo $quiz['batch_id']; ?>">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars($quiz['title']); ?>
                                            </div>
                                            <?php if (!empty($quiz['description'])): ?>
                                                <div class="text-sm text-gray-500 mt-1">
                                                    <?php echo htmlspecialchars(substr($quiz['description'], 0, 60)); ?>
                                                    <?php if (strlen($quiz['description']) > 60): ?>...<?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <?php echo htmlspecialchars($quiz['skill_name'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($quiz['batch_name'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <?php echo $quiz['actual_questions']; ?>/<?php echo $quiz['total_questions']; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-medium text-gray-900">
                                                <?php echo $quiz['total_marks']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-col gap-1">
                                                <span class="text-gray-700">
                                                    <?php echo $quiz['total_submissions']; ?> total
                                                </span>
                                                <?php if ($quiz['total_submissions'] > 0): ?>
                                                    <?php if ($pending_submissions > 0): ?>
                                                        <span class="submission-badge submission-pending">
                                                            <i class="fas fa-clock mr-1 text-xs"></i>
                                                            <?php echo $pending_submissions; ?> pending
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="submission-badge submission-marked">
                                                            <i class="fas fa-check mr-1 text-xs"></i>
                                                            All marked
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="status-badge status-<?php echo $quiz['status']; ?>">
                                                <?php echo ucfirst($quiz['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <?php if ($quiz['status'] === 'draft'): ?>
                                                    <a href="create_quiz.php?edit=<?php echo $quiz['id']; ?>"
                                                        class="text-blue-600 hover:text-blue-800"
                                                        title="Edit Quiz">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- MARK QUIZ BUTTON -->
                                                <a href="quiz_result.php?quiz_id=<?php echo $quiz['id']; ?>"
                                                    class="text-green-600 hover:text-green-800 <?php echo $quiz['status'] !== 'published' ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                                    title="Mark Quiz"
                                                    <?php echo $quiz['status'] !== 'published' ? 'onclick="return false;"' : ''; ?>>
                                                    <i class="fas fa-check-circle"></i>
                                                </a>

                                                <?php if ($quiz['total_submissions'] == 0): ?>
                                                    <a href="?delete_id=<?php echo $quiz['id']; ?>"
                                                        class="text-red-600 hover:text-red-800"
                                                        onclick="return confirm('Are you sure you want to delete this quiz?');"
                                                        title="Delete Quiz">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Status Dropdown -->
                                                <div class="relative">
                                                    <button class="text-gray-600 hover:text-gray-800 focus:outline-none"
                                                        onclick="toggleStatusDropdown('status-<?php echo $quiz['id']; ?>')">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div id="status-<?php echo $quiz['id']; ?>"
                                                        class="absolute right-0 mt-2 w-32 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden">
                                                        <?php if ($quiz['status'] !== 'published'): ?>
                                                            <a href="?change_status&quiz_id=<?php echo $quiz['id']; ?>&new_status=published"
                                                                class="block px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                                                <i class="fas fa-check mr-2"></i> Publish
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($quiz['status'] !== 'draft'): ?>
                                                            <a href="?change_status&quiz_id=<?php echo $quiz['id']; ?>&new_status=draft"
                                                                class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                                                <i class="fas fa-edit mr-2"></i> Set as Draft
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($quiz['status'] !== 'archived'): ?>
                                                            <a href="?change_status&quiz_id=<?php echo $quiz['id']; ?>&new_status=archived"
                                                                class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                                <i class="fas fa-archive mr-2"></i> Archive
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="py-8 px-4 text-center">
                                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-question-circle text-gray-300 text-3xl"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Quizzes Created Yet</h3>
                                        <p class="text-gray-500 mb-4">Create your first quiz for your batches.</p>
                                        <a href="create_quiz.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-plus mr-2"></i> Create Your First Quiz
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-question-circle text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Quizzes</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo $quizzes->num_rows; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Published Quizzes</p>
                            <p class="text-xl font-bold text-gray-800">
                                <?php
                                mysqli_data_seek($quizzes, 0);
                                $published = 0;
                                while ($q = $quizzes->fetch_assoc()) {
                                    if ($q['status'] === 'published') $published++;
                                }
                                echo $published;
                                mysqli_data_seek($quizzes, 0);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Pending Marking</p>
                            <p class="text-xl font-bold text-gray-800">
                                <?php
                                mysqli_data_seek($quizzes, 0);
                                $pending_total = 0;
                                while ($q = $quizzes->fetch_assoc()) {
                                    $pending_total += ($q['total_submissions'] - $q['marked_submissions']);
                                }
                                echo $pending_total;
                                mysqli_data_seek($quizzes, 0);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleStatusDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('hidden');

            // Close other dropdowns
            document.querySelectorAll('[id^="status-"]').forEach(d => {
                if (d.id !== id) d.classList.add('hidden');
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[id^="status-"]') && !e.target.closest('button')) {
                document.querySelectorAll('[id^="status-"]').forEach(d => {
                    d.classList.add('hidden');
                });
            }
        });

        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('batchFilter').addEventListener('change', filterTable);
        document.getElementById('resetFilters').addEventListener('click', resetFilters);

        function filterTable() {
            const statusFilter = document.getElementById('statusFilter').value;
            const batchFilter = document.getElementById('batchFilter').value;
            const rows = document.querySelectorAll('#quizTableBody tr');

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const batch = row.getAttribute('data-batch');
                let show = true;

                if (statusFilter !== 'all' && status !== statusFilter) {
                    show = false;
                }

                if (batchFilter !== 'all' && batch !== batchFilter) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('batchFilter').value = 'all';
            filterTable();
        }
    </script>
</body>

</html>
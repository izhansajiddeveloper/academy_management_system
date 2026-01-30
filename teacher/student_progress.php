<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

// Get teacher ID
$user_id = $_SESSION['user_id'];
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

// Handle overall grade and comments update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    $student_id = intval($_POST['student_id']);
    $batch_id = intval($_POST['batch_id']);
    $overall_grade = trim($_POST['overall_grade']);
    $comments = trim($_POST['comments']);

    // Verify teacher has access to this student
    $verify_query = "
    SELECT 1 FROM student_enrollments se
    JOIN teacher_assignments ta ON se.batch_id = ta.batch_id
    WHERE se.student_id = ? AND se.batch_id = ? AND ta.teacher_id = ?
    ";

    $stmt_verify = $conn->prepare($verify_query);
    $stmt_verify->bind_param("iii", $student_id, $batch_id, $teacher_id);
    $stmt_verify->execute();

    if ($stmt_verify->get_result()->num_rows > 0) {
        // Check if progress report record exists
        $check_query = "SELECT id FROM student_progress WHERE student_id = ? AND batch_id = ?";
        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bind_param("ii", $student_id, $batch_id);
        $stmt_check->execute();
        $exists = $stmt_check->get_result()->num_rows > 0;

        if ($exists) {
            // Update existing record
            $update_query = "
            UPDATE student_progress SET 
                overall_grade = ?,
                comments = ?,
                report_date = CURDATE(),
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ? AND batch_id = ?
            ";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssii", $overall_grade, $comments, $student_id, $batch_id);
        } else {
            // Insert new record
            $insert_query = "
            INSERT INTO student_progress (student_id, batch_id, teacher_id, overall_grade, comments, report_date)
            VALUES (?, ?, ?, ?, ?, CURDATE())
            ";

            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiiss", $student_id, $batch_id, $teacher_id, $overall_grade, $comments);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Student progress report updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update student progress report.";
        }
    } else {
        $_SESSION['error_message'] = "Unauthorized to update this student's progress.";
    }

    // Redirect to prevent form resubmission
    header("Location: student_progress.php?batch_id={$batch_id}&student_id={$student_id}");
    exit;
}

// Get filter parameters
$batch_filter = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$skill_filter = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$student_filter = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get teacher's assigned batches
$batches_query = "
SELECT 
    b.id AS batch_id,
    b.batch_name,
    b.skill_id,
    s.skill_name,
    b.session_id,
    se.session_name
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
JOIN sessions se ON b.session_id = se.id
WHERE ta.teacher_id = ? AND b.status = 'active'
ORDER BY b.batch_name
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$assigned_batches = $stmt_batches->get_result();

// Get students for selected batch
$students = [];
$batch_skill_id = 0;
$batch_session_id = 0;
if ($batch_filter > 0) {
    $students_query = "
    SELECT 
        se.id as enrollment_id,
        se.student_id,
        s.name,
        s.student_code,
        s.phone,
        u.email,
        sp.overall_grade,
        sp.comments,
        sp.report_date
    FROM student_enrollments se
    JOIN students s ON se.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_progress sp ON se.student_id = sp.student_id AND se.batch_id = sp.batch_id
    WHERE se.batch_id = ? AND se.status = 'active'
    ORDER BY s.name
    ";

    $stmt_students = $conn->prepare($students_query);
    $stmt_students->bind_param("i", $batch_filter);
    $stmt_students->execute();
    $students = $stmt_students->get_result();

    // Get batch details for skill_id and session_id
    $batch_query = "SELECT skill_id, session_id FROM batches WHERE id = ?";
    $stmt_batch = $conn->prepare($batch_query);
    $stmt_batch->bind_param("i", $batch_filter);
    $stmt_batch->execute();
    $batch_result = $stmt_batch->get_result()->fetch_assoc();
    if ($batch_result) {
        $batch_skill_id = $batch_result['skill_id'];
        $batch_session_id = $batch_result['session_id'];
    }
}

// Handle skill progress update
$success_message = '';
$error_message = '';

if (isset($_POST['update_progress'])) {
    $progress_id = intval($_POST['progress_id']);
    $student_id = intval($_POST['student_id']);
    $batch_id = intval($_POST['batch_id']);

    // Get skill_id from POST or use batch skill_id
    if (isset($_POST['skill_id']) && intval($_POST['skill_id']) > 0) {
        $skill_id = intval($_POST['skill_id']);
    } else {
        $skill_id = $batch_skill_id;
    }

    // Validate required fields
    if (!$student_id || !$batch_id || !$skill_id) {
        $error_message = "Missing required information. Please select student and ensure batch has a skill.";
    } else {
        // Get current progress for history
        $current_progress = null;
        if ($progress_id > 0) {
            $current_query = "SELECT * FROM skill_progress WHERE id = ?";
            $stmt_current = $conn->prepare($current_query);
            $stmt_current->bind_param("i", $progress_id);
            $stmt_current->execute();
            $current_result = $stmt_current->get_result();
            if ($current_result->num_rows > 0) {
                $current_progress = $current_result->fetch_assoc();
            }
        }

        // Collect form data
        $topics_completed = intval($_POST['topics_completed']);
        $total_topics = intval($_POST['total_topics']);
        $progress_percent = $total_topics > 0 ? round(($topics_completed / $total_topics) * 100, 2) : 0;

        $quiz_score = floatval($_POST['quiz_score']);
        $assignment_score = floatval($_POST['assignment_score']);
        $project_score = floatval($_POST['project_score']);

        // Calculate overall performance (weighted average)
        $overall_performance = ($quiz_score * 0.4) + ($assignment_score * 0.3) + ($project_score * 0.3);
        $overall_performance = round($overall_performance, 2);

        // Determine performance level
        if ($overall_performance >= 90) {
            $performance_level = 'Excellent';
        } elseif ($overall_performance >= 75) {
            $performance_level = 'Advanced';
        } elseif ($overall_performance >= 60) {
            $performance_level = 'Intermediate';
        } else {
            $performance_level = 'Beginner';
        }

        $remarks = $_POST['remarks'];
        $status = $_POST['status'];

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            if ($progress_id > 0 && $current_progress) {
                // Update existing progress
                $update_query = "
                UPDATE skill_progress 
                SET 
                    topics_completed = ?,
                    total_topics = ?,
                    progress_percent = ?,
                    quiz_score = ?,
                    assignment_score = ?,
                    project_score = ?,
                    overall_performance = ?,
                    performance_level = ?,
                    remarks = ?,
                    status = ?,
                    updated_by = ?,
                    last_updated = NOW()
                WHERE id = ?
                ";

                $stmt_update = $conn->prepare($update_query);
                $stmt_update->bind_param(
                    "iidddddsssii",
                    $topics_completed,
                    $total_topics,
                    $progress_percent,
                    $quiz_score,
                    $assignment_score,
                    $project_score,
                    $overall_performance,
                    $performance_level,
                    $remarks,
                    $status,
                    $teacher_id,
                    $progress_id
                );

                if ($stmt_update->execute()) {
                    // Add to history
                    $history_query = "
                    INSERT INTO progress_history (
                        progress_id,
                        student_id,
                        skill_id,
                        batch_id,
                        old_progress_percent,
                        new_progress_percent,
                        changed_by,
                        change_type,
                        change_description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Progress Update', ?)
                    ";

                    $stmt_history = $conn->prepare($history_query);
                    $change_desc = "Progress updated by teacher. Topics: {$topics_completed}/{$total_topics}";
                    $stmt_history->bind_param(
                        "iiiiddis",
                        $progress_id,
                        $student_id,
                        $skill_id,
                        $batch_id,
                        $current_progress['progress_percent'],
                        $progress_percent,
                        $teacher_id,
                        $change_desc
                    );
                    $stmt_history->execute();
                }
            } else {
                // Get enrollment_id - FIXED: Use the correct parameter
                $enrollment_query = "SELECT id FROM student_enrollments WHERE student_id = ? AND batch_id = ?";
                $stmt_enrollment = $conn->prepare($enrollment_query);
                $stmt_enrollment->bind_param("ii", $student_id, $batch_id);
                $stmt_enrollment->execute();
                $enrollment_result = $stmt_enrollment->get_result();

                if ($enrollment_result->num_rows > 0) {
                    $enrollment_row = $enrollment_result->fetch_assoc();
                    $enrollment_id = $enrollment_row['id'];

                    // Get session_id from batch
                    $session_query = "SELECT session_id FROM batches WHERE id = ?";
                    $stmt_session = $conn->prepare($session_query);
                    $stmt_session->bind_param("i", $batch_id);
                    $stmt_session->execute();
                    $session_result = $stmt_session->get_result()->fetch_assoc();
                    $session_id = $session_result['session_id'];

                    // Check if progress record already exists
                    $check_exists_query = "
                    SELECT id FROM skill_progress 
                    WHERE student_id = ? AND skill_id = ? AND batch_id = ?
                    ";
                    $stmt_check = $conn->prepare($check_exists_query);
                    $stmt_check->bind_param("iii", $student_id, $skill_id, $batch_id);
                    $stmt_check->execute();
                    $check_result = $stmt_check->get_result();

                    if ($check_result->num_rows > 0) {
                        // Update existing record
                        $existing = $check_result->fetch_assoc();
                        $update_existing_query = "
                        UPDATE skill_progress 
                        SET 
                            topics_completed = ?,
                            total_topics = ?,
                            progress_percent = ?,
                            quiz_score = ?,
                            assignment_score = ?,
                            project_score = ?,
                            overall_performance = ?,
                            performance_level = ?,
                            remarks = ?,
                            status = ?,
                            updated_by = ?,
                            last_updated = NOW()
                        WHERE id = ?
                        ";

                        $stmt_update = $conn->prepare($update_existing_query);
                        $stmt_update->bind_param(
                            "iidddddsssii",
                            $topics_completed,
                            $total_topics,
                            $progress_percent,
                            $quiz_score,
                            $assignment_score,
                            $project_score,
                            $overall_performance,
                            $performance_level,
                            $remarks,
                            $status,
                            $teacher_id,
                            $existing['id']
                        );

                        if ($stmt_update->execute()) {
                            $progress_id = $existing['id'];
                        }
                    } else {
                        // Create new progress record
                        $insert_query = "
                        INSERT INTO skill_progress (
                            enrollment_id,
                            student_id,
                            skill_id,
                            batch_id,
                            session_id,
                            topics_completed,
                            total_topics,
                            progress_percent,
                            quiz_score,
                            assignment_score,
                            project_score,
                            overall_performance,
                            performance_level,
                            remarks,
                            status,
                            updated_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ";

                        $stmt_insert = $conn->prepare($insert_query);
                        $stmt_insert->bind_param(
                            "iiiiiidddddssssi",
                            $enrollment_id,
                            $student_id,
                            $skill_id,
                            $batch_id,
                            $session_id,
                            $topics_completed,
                            $total_topics,
                            $progress_percent,
                            $quiz_score,
                            $assignment_score,
                            $project_score,
                            $overall_performance,
                            $performance_level,
                            $remarks,
                            $status,
                            $teacher_id
                        );

                        if ($stmt_insert->execute()) {
                            $progress_id = $conn->insert_id;
                        }
                    }

                    // Add to history
                    if ($progress_id > 0) {
                        $history_query = "
                        INSERT INTO progress_history (
                            progress_id,
                            student_id,
                            skill_id,
                            batch_id,
                            old_progress_percent,
                            new_progress_percent,
                            changed_by,
                            change_type,
                            change_description
                        ) VALUES (?, ?, ?, ?, 0, ?, ?, 'Progress Update', ?)
                        ";

                        $stmt_history = $conn->prepare($history_query);
                        $change_desc = "Initial progress created. Topics: {$topics_completed}/{$total_topics}";
                        $stmt_history->bind_param(
                            "iiiidis",
                            $progress_id,
                            $student_id,
                            $skill_id,
                            $batch_id,
                            $progress_percent,
                            $teacher_id,
                            $change_desc
                        );
                        $stmt_history->execute();
                    }
                } else {
                    throw new Exception("Student is not enrolled in this batch.");
                }
            }

            mysqli_commit($conn);
            $_SESSION['success_message'] = "Student progress updated successfully!";

            // Redirect to prevent form resubmission
            header("Location: student_progress.php?batch_id={$batch_filter}&student_id={$student_filter}&success=1");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Error updating progress: " . $e->getMessage();
        }
    }
}

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get progress data based on filters
$progress_data = [];
$overall_stats = [
    'total_students' => 0,
    'avg_progress' => 0,
    'excellent_count' => 0,
    'needs_attention_count' => 0
];

if ($batch_filter > 0) {
    $progress_query = "
    SELECT 
        sp.*,
        s.name as student_name,
        s.student_code,
        sk.skill_name,
        b.batch_name,
        se.session_name,
        t.name as updated_by_name,
        spr.overall_grade,
        spr.comments as report_comments,
        spr.report_date
    FROM skill_progress sp
    JOIN students s ON sp.student_id = s.id
    JOIN skills sk ON sp.skill_id = sk.id
    JOIN batches b ON sp.batch_id = b.id
    JOIN sessions se ON sp.session_id = se.id
    LEFT JOIN teachers t ON sp.updated_by = t.id
    LEFT JOIN student_progress spr ON s.id = spr.student_id AND b.id = spr.batch_id
    WHERE sp.batch_id = ? AND sp.updated_by = ?
    ";

    $params = [$batch_filter, $teacher_id];
    $param_types = "ii";

    if ($student_filter > 0) {
        $progress_query .= " AND sp.student_id = ?";
        $params[] = $student_filter;
        $param_types .= "i";
    }

    if ($status_filter !== 'all') {
        $progress_query .= " AND sp.status = ?";
        $params[] = $status_filter;
        $param_types .= "s";
    }

    $progress_query .= " ORDER BY sp.overall_performance DESC, s.name";

    $stmt_progress = $conn->prepare($progress_query);
    if (!empty($params)) {
        $stmt_progress->bind_param($param_types, ...$params);
    }
    $stmt_progress->execute();
    $progress_data = $stmt_progress->get_result();

    // Calculate overall statistics
    $stats_query = "
    SELECT 
        COUNT(DISTINCT sp.student_id) as total_students,
        AVG(sp.progress_percent) as avg_progress,
        SUM(CASE WHEN sp.performance_level = 'Excellent' THEN 1 ELSE 0 END) as excellent_count,
        SUM(CASE WHEN sp.status = 'Needs Attention' THEN 1 ELSE 0 END) as needs_attention_count
    FROM skill_progress sp
    WHERE sp.batch_id = ? AND sp.updated_by = ?
    ";

    $stmt_stats = $conn->prepare($stats_query);
    $stmt_stats->bind_param("ii", $batch_filter, $teacher_id);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result()->fetch_assoc();

    if ($stats_result) {
        $overall_stats = [
            'total_students' => $stats_result['total_students'] ?? 0,
            'avg_progress' => round($stats_result['avg_progress'] ?? 0, 1),
            'excellent_count' => $stats_result['excellent_count'] ?? 0,
            'needs_attention_count' => $stats_result['needs_attention_count'] ?? 0
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Student Progress Tracking | Teacher Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
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

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .performance-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-excellent {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }

        .badge-advanced {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 2px solid #3b82f6;
        }

        .badge-intermediate {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #f59e0b;
        }

        .badge-beginner {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 2px solid #9ca3af;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-needs-attention {
            background: #fee2e2;
            color: #991b1b;
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            background: white;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .score-indicator {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .score-excellent {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .score-good {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .score-average {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .score-poor {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .grade-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .grade-A {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .grade-B {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .grade-C {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .grade-D {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 1px solid #9ca3af;
        }

        .grade-F {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #ef4444;
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
                        <h1 class="text-3xl font-bold text-gray-800">Student Progress Tracking</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-chart-line text-primary mr-2"></i>
                            Monitor and update student learning progress and performance
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm bg-primary text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-user-tie mr-2"></i>
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 flex items-center justify-between animate-pulse">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">Success!</h4>
                            <p class="text-green-700">Student progress updated successfully!</p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">Success!</h4>
                            <p class="text-green-700"><?php echo $success_message; ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-red-800">Error!</h4>
                            <p class="text-red-700"><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="glass-card p-7 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <i class="fas fa-filter text-primary"></i>
                    Filter Progress Records
                </h3>
                <form method="GET" class="grid grid-cols-1 lg:grid-cols-4 gap-5">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-users text-primary"></i>
                            Select Batch
                        </label>
                        <select name="batch_id"
                            class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium"
                            onchange="this.form.submit()">
                            <option value="0">-- Select a Batch --</option>
                            <?php
                            $assigned_batches->data_seek(0);
                            while ($batch = $assigned_batches->fetch_assoc()): ?>
                                <option value="<?php echo $batch['batch_id']; ?>"
                                    <?php echo $batch_filter == $batch['batch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch['batch_name']); ?>
                                    (<?php echo htmlspecialchars($batch['skill_name']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <?php if ($batch_filter > 0 && $students->num_rows > 0): ?>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-user-graduate text-primary"></i>
                                Select Student
                            </label>
                            <select name="student_id"
                                class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium">
                                <option value="0">All Students</option>
                                <?php
                                $students->data_seek(0);
                                while ($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['student_id']; ?>"
                                        <?php echo $student_filter == $student['student_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-user-graduate text-primary"></i>
                                Select Student
                            </label>
                            <select name="student_id"
                                class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium"
                                disabled>
                                <option value="0">Select batch first</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-flag text-primary"></i>
                            Status Filter
                        </label>
                        <select name="status"
                            class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Needs Attention" <?php echo $status_filter === 'Needs Attention' ? 'selected' : ''; ?>>Needs Attention</option>
                        </select>
                    </div>

                    <div class="lg:col-span-1 flex items-end gap-3">
                        <button type="submit"
                            class="w-full btn-primary px-6 py-3.5 rounded-xl font-bold text-lg flex items-center justify-center gap-3">
                            <i class="fas fa-search"></i>
                            Apply Filters
                        </button>
                        <a href="student_progress.php"
                            class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3.5 rounded-xl font-bold text-lg flex items-center justify-center gap-3">
                            <i class="fas fa-redo"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <?php if ($batch_filter > 0): ?>
                <!-- Overall Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Students</h3>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $overall_stats['total_students']; ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-r from-primary to-primary-dark rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-white text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Students with progress records
                        </p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Avg. Progress</h3>
                                <p class="text-3xl font-bold text-green-600"><?php echo $overall_stats['avg_progress']; ?>%</p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-chart-line text-white text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full"
                                    style="width: <?php echo $overall_stats['avg_progress']; ?>%">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Excellent Students</h3>
                                <p class="text-3xl font-bold text-blue-600"><?php echo $overall_stats['excellent_count']; ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-star text-white text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Performance level: Excellent
                        </p>
                    </div>

                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Needs Attention</h3>
                                <p class="text-3xl font-bold text-red-600"><?php echo $overall_stats['needs_attention_count']; ?></p>
                            </div>
                            <div class="w-14 h-14 bg-gradient-to-r from-red-500 to-rose-500 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Requires additional support
                        </p>
                    </div>
                </div>

                <!-- Progress Records -->
                <div class="card overflow-hidden mb-8">
                    <div class="px-7 py-5 bg-gradient-to-r from-gray-50 to-gray-100 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                                <i class="fas fa-list-check text-primary"></i>
                                Student Progress Records
                                <span class="text-sm bg-primary text-white px-3 py-1 rounded-full">
                                    <?php echo $progress_data->num_rows; ?> Records
                                </span>
                            </h3>
                            <p class="text-sm text-gray-600 mt-2">
                                <i class="fas fa-info-circle mr-2"></i>
                                Click on a student to view details and update progress
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="showUpdateProgressModal()"
                                class="btn-success px-6 py-3 rounded-xl font-bold text-lg flex items-center gap-3">
                                <i class="fas fa-plus"></i>
                                Add Skill Progress
                            </button>
                        </div>
                    </div>

                    <?php if ($progress_data->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-full">
                                <thead>
                                    <tr class="bg-gradient-to-r from-gray-100 to-gray-50 text-left">
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Student</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Progress</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Overall Grade</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Performance Level</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Scores</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Status</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Updated</th>
                                        <th class="py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php
                                    $progress_data->data_seek(0);
                                    while ($row = $progress_data->fetch_assoc()): ?>
                                        <?php
                                        // Calculate color based on progress percentage
                                        $progress_color = '';
                                        if ($row['progress_percent'] >= 80) {
                                            $progress_color = 'text-green-600';
                                        } elseif ($row['progress_percent'] >= 50) {
                                            $progress_color = 'text-yellow-600';
                                        } else {
                                            $progress_color = 'text-red-600';
                                        }

                                        // Grade badge class
                                        $grade_badge_class = '';
                                        if ($row['overall_grade']) {
                                            switch (strtoupper($row['overall_grade'])) {
                                                case 'A':
                                                    $grade_badge_class = 'grade-A';
                                                    break;
                                                case 'B':
                                                    $grade_badge_class = 'grade-B';
                                                    break;
                                                case 'C':
                                                    $grade_badge_class = 'grade-C';
                                                    break;
                                                case 'D':
                                                    $grade_badge_class = 'grade-D';
                                                    break;
                                                case 'F':
                                                    $grade_badge_class = 'grade-F';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                            onclick="showStudentDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            <td class="py-5 px-6">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 bg-gradient-to-r from-primary/20 to-primary/10 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user-graduate text-primary text-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($row['student_name']); ?></h4>
                                                        <p class="text-sm text-gray-500">
                                                            <i class="fas fa-id-card mr-1"></i>
                                                            <?php echo htmlspecialchars($row['student_code']); ?>
                                                        </p>
                                                        <p class="text-xs text-gray-400 mt-1">
                                                            <i class="fas fa-book mr-1"></i>
                                                            <?php echo htmlspecialchars($row['skill_name']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="space-y-2">
                                                    <div class="flex justify-between items-center">
                                                        <span class="font-bold <?php echo $progress_color; ?>">
                                                            <?php echo $row['progress_percent']; ?>%
                                                        </span>
                                                        <span class="text-sm text-gray-500">
                                                            <?php echo $row['topics_completed']; ?>/<?php echo $row['total_topics']; ?> topics
                                                        </span>
                                                    </div>
                                                    <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full bg-gradient-to-r 
                                                            <?php echo $row['progress_percent'] >= 80 ? 'from-green-500 to-emerald-500' : ($row['progress_percent'] >= 50 ? 'from-yellow-500 to-amber-500' :
                                                                'from-red-500 to-rose-500'); ?>"
                                                            style="width: <?php echo min($row['progress_percent'], 100); ?>%">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <?php if ($row['overall_grade']): ?>
                                                    <div class="<?php echo $grade_badge_class; ?> grade-badge">
                                                        <i class="fas fa-award"></i>
                                                        <?php echo $row['overall_grade']; ?>
                                                    </div>
                                                    <?php if ($row['report_date']): ?>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <?php echo date('M d, Y', strtotime($row['report_date'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic text-sm">Not graded</span>
                                                    <div class="mt-1">
                                                        <button onclick="event.stopPropagation(); showUpdateReportModal(<?php echo $row['student_id']; ?>, '<?php echo htmlspecialchars($row['student_name']); ?>')"
                                                            class="text-xs text-primary hover:underline">
                                                            Add Grade
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-5 px-6">
                                                <?php
                                                $badge_class = '';
                                                switch ($row['performance_level']) {
                                                    case 'Excellent':
                                                        $badge_class = 'badge-excellent';
                                                        break;
                                                    case 'Advanced':
                                                        $badge_class = 'badge-advanced';
                                                        break;
                                                    case 'Intermediate':
                                                        $badge_class = 'badge-intermediate';
                                                        break;
                                                    case 'Beginner':
                                                        $badge_class = 'badge-beginner';
                                                        break;
                                                }
                                                ?>
                                                <div class="<?php echo $badge_class; ?> performance-badge">
                                                    <i class="fas 
                                                        <?php echo $row['performance_level'] == 'Excellent' ? 'fa-star' : ($row['performance_level'] == 'Advanced' ? 'fa-arrow-up' : ($row['performance_level'] == 'Intermediate' ? 'fa-chart-line' : 'fa-user-graduate')); ?>">
                                                    </i>
                                                    <?php echo $row['performance_level']; ?>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-sm text-gray-600 w-20">Quiz:</span>
                                                        <div class="flex-1">
                                                            <div class="score-indicator">
                                                                <div class="score-fill 
                                                                    <?php echo $row['quiz_score'] >= 80 ? 'score-excellent' : ($row['quiz_score'] >= 60 ? 'score-good' : ($row['quiz_score'] >= 40 ? 'score-average' : 'score-poor')); ?>"
                                                                    style="width: <?php echo $row['quiz_score']; ?>%">
                                                                </div>
                                                            </div>
                                                            <span class="text-xs font-medium text-gray-700"><?php echo $row['quiz_score']; ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-sm text-gray-600 w-20">Assignment:</span>
                                                        <div class="flex-1">
                                                            <div class="score-indicator">
                                                                <div class="score-fill 
                                                                    <?php echo $row['assignment_score'] >= 80 ? 'score-excellent' : ($row['assignment_score'] >= 60 ? 'score-good' : ($row['assignment_score'] >= 40 ? 'score-average' : 'score-poor')); ?>"
                                                                    style="width: <?php echo $row['assignment_score']; ?>%">
                                                                </div>
                                                            </div>
                                                            <span class="text-xs font-medium text-gray-700"><?php echo $row['assignment_score']; ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-sm text-gray-600 w-20">Overall:</span>
                                                        <div class="flex-1">
                                                            <div class="score-indicator">
                                                                <div class="score-fill 
                                                                    <?php echo $row['overall_performance'] >= 80 ? 'score-excellent' : ($row['overall_performance'] >= 60 ? 'score-good' : ($row['overall_performance'] >= 40 ? 'score-average' : 'score-poor')); ?>"
                                                                    style="width: <?php echo $row['overall_performance']; ?>%">
                                                                </div>
                                                            </div>
                                                            <span class="text-xs font-medium text-gray-700"><?php echo $row['overall_performance']; ?>%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <?php
                                                $status_class = '';
                                                switch ($row['status']) {
                                                    case 'Active':
                                                        $status_class = 'status-active';
                                                        break;
                                                    case 'Completed':
                                                        $status_class = 'status-completed';
                                                        break;
                                                    case 'Needs Attention':
                                                        $status_class = 'status-needs-attention';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="fas 
                                                        <?php echo $row['status'] == 'Active' ? 'fa-play-circle' : ($row['status'] == 'Completed' ? 'fa-check-circle' : 'fa-exclamation-circle'); ?> mr-1">
                                                    </i>
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="text-sm">
                                                    <div class="text-gray-800 font-medium">
                                                        <?php echo date('M d, Y', strtotime($row['last_updated'])); ?>
                                                    </div>
                                                    <div class="text-gray-500 text-xs">
                                                        <?php echo date('h:i A', strtotime($row['last_updated'])); ?>
                                                    </div>
                                                    <?php if ($row['updated_by_name']): ?>
                                                        <div class="text-gray-400 text-xs mt-1">
                                                            <i class="fas fa-user-tie mr-1"></i>
                                                            <?php echo htmlspecialchars($row['updated_by_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="flex items-center gap-2">
                                                    <button onclick="event.stopPropagation(); editProgress(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                        class="px-4 py-2 bg-gradient-to-r from-primary to-primary-dark text-blue-500 rounded-lg hover:opacity-90 transition-opacity">
                                                        <i class="fas fa-edit mr-1"></i> Edit
                                                    </button>
                                                    <button onclick="event.stopPropagation(); showUpdateReportModal(<?php echo $row['student_id']; ?>, '<?php echo htmlspecialchars($row['student_name']); ?>', '<?php echo $row['overall_grade']; ?>', '<?php echo htmlspecialchars($row['report_comments']); ?>')"
                                                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:opacity-90 transition-opacity">
                                                        <i class="fas fa-file-alt mr-1"></i> Report
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16">
                            <div class="mx-auto w-32 h-32 bg-gradient-to-r from-gray-200 to-gray-300 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-chart-line text-gray-400 text-5xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-700 mb-3">No Progress Records Found</h3>
                            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                                No student progress records found for the selected filters. Start by adding progress records for students.
                            </p>
                            <button onclick="showUpdateProgressModal()"
                                class="btn-success px-8 py-3.5 rounded-xl font-bold text-lg flex items-center gap-3 mx-auto">
                                <i class="fas fa-plus"></i>
                                Add Your First Progress Record
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- No Batch Selected -->
                <div class="glass-card p-12 text-center">
                    <div class="mx-auto w-40 h-40 bg-gradient-to-r from-primary/10 to-primary/5 rounded-full flex items-center justify-center mb-8">
                        <i class="fas fa-users text-primary text-6xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Welcome to Progress Tracking</h2>
                    <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                        Select a batch from the dropdown above to view and manage student progress.
                        Track topics completed, assessment scores, and performance levels for each student.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10 max-w-4xl mx-auto">
                        <div class="card p-6">
                            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-chart-line text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Track Progress</h4>
                            <p class="text-sm text-gray-600">Monitor topic completion percentages for each skill</p>
                        </div>
                        <div class="card p-6">
                            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-star text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Performance Levels</h4>
                            <p class="text-sm text-gray-600">Evaluate student performance from Beginner to Excellent</p>
                        </div>
                        <div class="card p-6">
                            <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-comment-alt text-white text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2">Add Remarks</h4>
                            <p class="text-sm text-gray-600">Provide personalized feedback and improvement suggestions</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Student Details Modal -->
    <div id="studentDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800">Student Progress Details</h3>
                    <button onclick="closeStudentDetails()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="studentDetailsContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div id="updateProgressModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">Update Student Progress</h3>
                    <button onclick="closeUpdateProgressModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="progress_id" id="progress_id" value="0">
                    <input type="hidden" name="student_id" id="modal_student_id" value="0">
                    <input type="hidden" name="batch_id" id="modal_batch_id" value="<?php echo $batch_filter; ?>">
                    <input type="hidden" name="skill_id" id="modal_skill_id" value="<?php echo $batch_skill_id; ?>">

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Student *</label>
                                <select name="student_id_select" id="student_id_select"
                                    class="w-full form-input px-4 py-3 rounded-lg" required
                                    onchange="document.getElementById('modal_student_id').value = this.value">
                                    <option value="">Select Student</option>
                                    <?php if ($batch_filter > 0): ?>
                                        <?php $students->data_seek(0); ?>
                                        <?php while ($student = $students->fetch_assoc()): ?>
                                            <option value="<?php echo $student['student_id']; ?>">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                                (<?php echo htmlspecialchars($student['student_code']); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Skill</label>
                                <?php if ($batch_filter > 0): ?>
                                    <?php
                                    $assigned_batches->data_seek(0);
                                    $batch_skill = $assigned_batches->fetch_assoc();
                                    ?>
                                    <input type="text"
                                        value="<?php echo htmlspecialchars($batch_skill['skill_name']); ?>"
                                        class="w-full form-input px-4 py-3 rounded-lg bg-gray-50"
                                        readonly>
                                <?php else: ?>
                                    <input type="text"
                                        value="Select a batch first"
                                        class="w-full form-input px-4 py-3 rounded-lg bg-gray-50"
                                        readonly>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Topics Completed *</label>
                                <input type="number" name="topics_completed" id="topics_completed"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="0" value="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Total Topics *</label>
                                <input type="number" name="total_topics" id="total_topics"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="1" value="1" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Quiz Score (%) *</label>
                                <input type="number" name="quiz_score" id="quiz_score"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="0" max="100" step="0.1" value="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Assignment Score (%) *</label>
                                <input type="number" name="assignment_score" id="assignment_score"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="0" max="100" step="0.1" value="0" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Project Score (%) *</label>
                                <input type="number" name="project_score" id="project_score"
                                    class="w-full form-input px-4 py-3 rounded-lg"
                                    min="0" max="100" step="0.1" value="0" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="status" class="w-full form-input px-4 py-3 rounded-lg">
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                                <option value="Needs Attention">Needs Attention</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                            <textarea name="remarks" id="remarks"
                                class="w-full form-input px-4 py-3 rounded-lg"
                                rows="4" placeholder="Add feedback and improvement suggestions..."></textarea>
                        </div>

                        <div class="flex justify-end gap-4 pt-6 border-t">
                            <button type="button" onclick="closeUpdateProgressModal()"
                                class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                Cancel
                            </button>
                            <button type="submit" name="update_progress"
                                class="px-6 py-3 btn-primary rounded-lg font-medium">
                                Save Progress
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Report Modal -->
    <div id="updateReportModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800" id="reportModalTitle">Update Student Report</h3>
                    <button onclick="closeUpdateReportModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="student_id" id="report_student_id">
                    <input type="hidden" name="batch_id" value="<?php echo $batch_filter; ?>">

                    <div class="space-y-6">
                        <div id="reportStudentInfo" class="p-4 bg-gray-50 rounded-lg mb-4">
                            <!-- Student info will be filled by JavaScript -->
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Overall Grade *</label>
                            <select name="overall_grade" id="overall_grade" class="w-full form-input px-4 py-3 rounded-lg" required>
                                <option value="">Select Grade</option>
                                <option value="A">A - Excellent</option>
                                <option value="B">B - Good</option>
                                <option value="C">C - Average</option>
                                <option value="D">D - Below Average</option>
                                <option value="F">F - Fail</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Comments</label>
                            <textarea name="comments" id="comments" class="w-full form-input px-4 py-3 rounded-lg" rows="4"
                                placeholder="Enter detailed comments about student's performance, areas of improvement, etc..."></textarea>
                        </div>

                        <div class="flex justify-end gap-4 pt-6 border-t">
                            <button type="button" onclick="closeUpdateReportModal()"
                                class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                Cancel
                            </button>
                            <button type="submit" name="update_report"
                                class="px-6 py-3 btn-success rounded-lg font-medium">
                                Save Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Student Details Modal Functions
        function showStudentDetails(studentData) {
            const modal = document.getElementById('studentDetailsModal');
            const content = document.getElementById('studentDetailsContent');

            // Format dates
            const lastUpdated = new Date(studentData.last_updated).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const createdDate = new Date(studentData.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Determine progress color
            let progressColor = 'text-red-600';
            let progressBarColor = 'from-red-500 to-rose-500';
            if (studentData.progress_percent >= 80) {
                progressColor = 'text-green-600';
                progressBarColor = 'from-green-500 to-emerald-500';
            } else if (studentData.progress_percent >= 50) {
                progressColor = 'text-yellow-600';
                progressBarColor = 'from-yellow-500 to-amber-500';
            }

            // Performance level badge
            let performanceBadge = '';
            switch (studentData.performance_level) {
                case 'Excellent':
                    performanceBadge = '<span class="badge-excellent performance-badge"><i class="fas fa-star"></i> Excellent</span>';
                    break;
                case 'Advanced':
                    performanceBadge = '<span class="badge-advanced performance-badge"><i class="fas fa-arrow-up"></i> Advanced</span>';
                    break;
                case 'Intermediate':
                    performanceBadge = '<span class="badge-intermediate performance-badge"><i class="fas fa-chart-line"></i> Intermediate</span>';
                    break;
                case 'Beginner':
                    performanceBadge = '<span class="badge-beginner performance-badge"><i class="fas fa-user-graduate"></i> Beginner</span>';
                    break;
            }

            // Grade badge
            let gradeBadge = '';
            if (studentData.overall_grade) {
                let gradeClass = '';
                switch (studentData.overall_grade.toUpperCase()) {
                    case 'A':
                        gradeClass = 'grade-A';
                        break;
                    case 'B':
                        gradeClass = 'grade-B';
                        break;
                    case 'C':
                        gradeClass = 'grade-C';
                        break;
                    case 'D':
                        gradeClass = 'grade-D';
                        break;
                    case 'F':
                        gradeClass = 'grade-F';
                        break;
                }
                gradeBadge = `<span class="${gradeClass} grade-badge"><i class="fas fa-award"></i> ${studentData.overall_grade}</span>`;
            } else {
                gradeBadge = '<span class="text-gray-400 italic">Not graded yet</span>';
            }

            // Status badge
            let statusBadge = '';
            switch (studentData.status) {
                case 'Active':
                    statusBadge = '<span class="status-badge status-active"><i class="fas fa-play-circle"></i> Active</span>';
                    break;
                case 'Completed':
                    statusBadge = '<span class="status-badge status-completed"><i class="fas fa-check-circle"></i> Completed</span>';
                    break;
                case 'Needs Attention':
                    statusBadge = '<span class="status-badge status-needs-attention"><i class="fas fa-exclamation-circle"></i> Needs Attention</span>';
                    break;
            }

            content.innerHTML = `
                <div class="space-y-8">
                    <!-- Student Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="card p-6">
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-user-graduate text-primary"></i>
                                Student Information
                            </h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-600 w-32">Name:</span>
                                    <span class="font-medium">${studentData.student_name}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-600 w-32">Student Code:</span>
                                    <span class="font-medium">${studentData.student_code}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-600 w-32">Skill:</span>
                                    <span class="font-medium">${studentData.skill_name}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-gray-600 w-32">Batch:</span>
                                    <span class="font-medium">${studentData.batch_name}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card p-6">
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-file-alt text-primary"></i>
                                Overall Report
                            </h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Overall Grade:</span>
                                    ${gradeBadge}
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Performance Level:</span>
                                    ${performanceBadge}
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Overall Score:</span>
                                    <span class="font-bold text-lg ${studentData.overall_performance >= 60 ? 'text-green-600' : 'text-red-600'}">
                                        ${studentData.overall_performance}%
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Status:</span>
                                    ${statusBadge}
                                </div>
                                ${studentData.report_date ? `
                                <div class="text-sm text-gray-500 border-t pt-3">
                                    Last Report: ${new Date(studentData.report_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Section -->
                    <div class="card p-6">
                        <h4 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-chart-line text-primary"></i>
                            Progress Details
                        </h4>
                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <h5 class="font-semibold text-gray-700">Topic Completion</h5>
                                        <p class="text-sm text-gray-500">Topics completed vs total topics</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-3xl font-bold ${progressColor}">${studentData.progress_percent}%</span>
                                        <p class="text-sm text-gray-500">${studentData.topics_completed}/${studentData.total_topics} topics</p>
                                    </div>
                                </div>
                                <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r ${progressBarColor}" 
                                         style="width: ${studentData.progress_percent}%">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600 mb-2">${studentData.quiz_score}%</div>
                                    <div class="text-sm text-gray-600">Quiz Score</div>
                                    <div class="h-2 bg-gray-200 rounded-full mt-2 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r ${getScoreColor(studentData.quiz_score)}"
                                             style="width: ${studentData.quiz_score}%">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600 mb-2">${studentData.assignment_score}%</div>
                                    <div class="text-sm text-gray-600">Assignment Score</div>
                                    <div class="h-2 bg-gray-200 rounded-full mt-2 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r ${getScoreColor(studentData.assignment_score)}"
                                             style="width: ${studentData.assignment_score}%">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600 mb-2">${studentData.project_score}%</div>
                                    <div class="text-sm text-gray-600">Project Score</div>
                                    <div class="h-2 bg-gray-200 rounded-full mt-2 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r ${getScoreColor(studentData.project_score)}"
                                             style="width: ${studentData.project_score}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Report Comments -->
                    ${studentData.report_comments ? `
                    <div class="card p-6">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-comment-alt text-primary"></i>
                            Teacher Report Comments
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700 whitespace-pre-wrap">${studentData.report_comments}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Remarks -->
                    <div class="card p-6">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-sticky-note text-primary"></i>
                            Progress Remarks
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            ${studentData.remarks ? `<p class="text-gray-700 whitespace-pre-wrap">${studentData.remarks}</p>` : 
                              `<p class="text-gray-500 italic">No remarks provided yet.</p>`}
                        </div>
                    </div>
                    
                    <!-- Meta Information -->
                    <div class="card p-6">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-primary"></i>
                            Meta Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-calendar-plus text-gray-400"></i>
                                <div>
                                    <div class="text-sm text-gray-500">Created On</div>
                                    <div class="font-medium">${createdDate}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-calendar-check text-gray-400"></i>
                                <div>
                                    <div class="text-sm text-gray-500">Last Updated</div>
                                    <div class="font-medium">${lastUpdated}</div>
                                </div>
                            </div>
                            ${studentData.updated_by_name ? `
                            <div class="flex items-center gap-3">
                                <i class="fas fa-user-tie text-gray-400"></i>
                                <div>
                                    <div class="text-sm text-gray-500">Updated By</div>
                                    <div class="font-medium">${studentData.updated_by_name}</div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-4 pt-6 border-t">
                        <button onclick="editProgress(${JSON.stringify(studentData)})"
                                class="px-6 py-3 bg-gradient-to-r from-primary to-primary-dark text-white rounded-lg hover:opacity-90 transition-opacity">
                            <i class="fas fa-edit mr-2"></i> Edit Progress
                        </button>
                        <button onclick="showUpdateReportModal(${studentData.student_id}, '${studentData.student_name}', '${studentData.overall_grade || ''}', \`${studentData.report_comments || ''}\`)"
                                class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:opacity-90 transition-opacity">
                            <i class="fas fa-file-alt mr-2"></i> Update Report
                        </button>
                        <button onclick="closeStudentDetails()"
                                class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                            Close
                        </button>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeStudentDetails() {
            document.getElementById('studentDetailsModal').classList.add('hidden');
            document.getElementById('studentDetailsModal').classList.remove('flex');
        }

        // Update Progress Modal Functions
        function showUpdateProgressModal() {
            if (<?php echo $batch_filter; ?> === 0) {
                alert('Please select a batch first.');
                return;
            }

            const modal = document.getElementById('updateProgressModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = modal.querySelector('form');

            // Reset form
            form.reset();
            document.getElementById('progress_id').value = '0';
            document.getElementById('modal_student_id').value = '0';
            document.getElementById('student_id_select').value = '';
            document.getElementById('topics_completed').value = '0';
            document.getElementById('total_topics').value = '1';
            document.getElementById('quiz_score').value = '0';
            document.getElementById('assignment_score').value = '0';
            document.getElementById('project_score').value = '0';
            document.getElementById('status').value = 'Active';
            document.getElementById('remarks').value = '';

            modalTitle.textContent = 'Add Student Progress';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function editProgress(studentData) {
            const modal = document.getElementById('updateProgressModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = modal.querySelector('form');

            // Fill form with existing data
            document.getElementById('progress_id').value = studentData.id;
            document.getElementById('modal_student_id').value = studentData.student_id;
            document.getElementById('student_id_select').value = studentData.student_id;
            document.getElementById('modal_skill_id').value = studentData.skill_id;
            document.getElementById('topics_completed').value = studentData.topics_completed;
            document.getElementById('total_topics').value = studentData.total_topics;
            document.getElementById('quiz_score').value = studentData.quiz_score;
            document.getElementById('assignment_score').value = studentData.assignment_score;
            document.getElementById('project_score').value = studentData.project_score;
            document.getElementById('status').value = studentData.status;
            document.getElementById('remarks').value = studentData.remarks;

            modalTitle.textContent = 'Edit Student Progress';
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Close student details modal if open
            closeStudentDetails();
        }

        function closeUpdateProgressModal() {
            document.getElementById('updateProgressModal').classList.add('hidden');
            document.getElementById('updateProgressModal').classList.remove('flex');
        }

        // Update Report Modal Functions
        function showUpdateReportModal(studentId, studentName, currentGrade = '', currentComments = '') {
            if (<?php echo $batch_filter; ?> === 0) {
                alert('Please select a batch first.');
                return;
            }

            const modal = document.getElementById('updateReportModal');
            const modalTitle = document.getElementById('reportModalTitle');
            const studentInfo = document.getElementById('reportStudentInfo');

            // Set student info
            document.getElementById('report_student_id').value = studentId;
            studentInfo.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-primary/20 to-primary/10 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-graduate text-primary"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">${studentName}</h4>
                        <p class="text-sm text-gray-500">Batch: <?php
                                                                if ($batch_filter > 0) {
                                                                    $assigned_batches->data_seek(0);
                                                                    $batch = $assigned_batches->fetch_assoc();
                                                                    echo htmlspecialchars($batch['batch_name']);
                                                                }
                                                                ?></p>
                    </div>
                </div>
            `;

            // Set current values if they exist
            if (currentGrade) {
                document.getElementById('overall_grade').value = currentGrade;
            } else {
                document.getElementById('overall_grade').value = '';
            }

            if (currentComments) {
                document.getElementById('comments').value = currentComments;
            } else {
                document.getElementById('comments').value = '';
            }

            modalTitle.textContent = currentGrade ? 'Update Student Report' : 'Add Student Report';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeUpdateReportModal() {
            document.getElementById('updateReportModal').classList.add('hidden');
            document.getElementById('updateReportModal').classList.remove('flex');
        }

        // Helper function to get score color
        function getScoreColor(score) {
            if (score >= 80) return 'from-green-500 to-emerald-500';
            if (score >= 60) return 'from-blue-500 to-indigo-500';
            if (score >= 40) return 'from-yellow-500 to-amber-500';
            return 'from-red-500 to-rose-500';
        }
    </script>

</body>

</html>
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

// Check if we're in edit mode
$edit_mode = false;
$edit_attendance_id = 0;
$attendance_to_edit = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_attendance_id = intval($_GET['edit']);

    // Get the attendance record to edit
    $edit_query = "
    SELECT 
        sa.*,
        s.name as student_name,
        b.batch_name,
        sk.skill_name
    FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    JOIN batches b ON sa.batch_id = b.id
    JOIN skills sk ON sa.skill_id = sk.id
    WHERE sa.id = ? AND sa.marked_by = ?
    ";

    $stmt_edit = $conn->prepare($edit_query);
    $stmt_edit->bind_param("ii", $edit_attendance_id, $teacher_id);
    $stmt_edit->execute();
    $attendance_to_edit = $stmt_edit->get_result()->fetch_assoc();

    if ($attendance_to_edit) {
        $edit_mode = true;
        // Set batch and date from the attendance record
        $selected_batch_id = $attendance_to_edit['batch_id'];
        $selected_date = $attendance_to_edit['attendance_date'];
    } else {
        // If no access or not found, redirect to normal mode
        $edit_mode = false;
        $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $selected_batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
    }
} else {
    // Normal mode
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $selected_batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
}

// Get teacher's assigned batches for dropdown
$batches_query = "
SELECT 
    b.id AS batch_id,
    b.batch_name,
    b.start_time,
    b.end_time,
    s.skill_name,
    se.session_name
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
JOIN sessions se ON b.session_id = se.id
WHERE ta.teacher_id = ? AND b.status = 'active'
ORDER BY b.start_time
";

$stmt_batches = $conn->prepare($batches_query);
$stmt_batches->bind_param("i", $teacher_id);
$stmt_batches->execute();
$assigned_batches = $stmt_batches->get_result();

// Handle attendance submission
$success_message = '';
$error_message = '';

if (isset($_POST['submit_attendance']) && $selected_batch_id > 0) {
    $attendance_date = $_POST['attendance_date'];
    $batch_id = intval($_POST['batch_id']);

    // Verify teacher has access to this batch
    $verify_query = "SELECT * FROM teacher_assignments WHERE teacher_id = ? AND batch_id = ?";
    $stmt_verify = $conn->prepare($verify_query);
    $stmt_verify->bind_param("ii", $teacher_id, $batch_id);
    $stmt_verify->execute();

    if ($stmt_verify->get_result()->num_rows > 0) {
        // Get enrolled students for this batch
        $students_query = "
        SELECT 
            se.id as enrollment_id,
            se.student_id,
            s.name,
            s.student_code
        FROM student_enrollments se
        JOIN students s ON se.student_id = s.id
        WHERE se.batch_id = ? AND se.status = 'active'
        ORDER BY s.name
        ";

        $stmt_students = $conn->prepare($students_query);
        $stmt_students->bind_param("i", $batch_id);
        $stmt_students->execute();
        $students = $stmt_students->get_result();

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Delete existing attendance for this batch and date
            $delete_query = "DELETE FROM student_attendance WHERE batch_id = ? AND attendance_date = ?";
            $stmt_delete = $conn->prepare($delete_query);
            $stmt_delete->bind_param("is", $batch_id, $attendance_date);
            $stmt_delete->execute();

            // Insert new attendance records
            $inserted = 0;
            while ($student = $students->fetch_assoc()) {
                $student_id = $student['student_id'];
                $enrollment_id = $student['enrollment_id'];

                // Get skill_id and session_id from batch
                $batch_info = $conn->query("SELECT skill_id, session_id FROM batches WHERE id = $batch_id")->fetch_assoc();
                $skill_id = $batch_info['skill_id'];
                $session_id = $batch_info['session_id'];

                // Get attendance status from form
                $attendance_status = isset($_POST['attendance'][$student_id]) ? $_POST['attendance'][$student_id] : 'absent';
                $remarks = isset($_POST['remarks'][$student_id]) ? $_POST['remarks'][$student_id] : '';

                // Calculate attendance percentage
                $attendance_percentage = $attendance_status === 'present' ? 100.00 : 0.00;

                // Insert attendance record
                $insert_query = "
                INSERT INTO student_attendance (
                    enrollment_id,
                    student_id,
                    skill_id,
                    session_id,
                    batch_id,
                    attendance_date,
                    attendance_status,
                    attendance_percentage,
                    marked_by,
                    remarks,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                ";

                $stmt_insert = $conn->prepare($insert_query);
                $stmt_insert->bind_param(
                    "iiiiisdsis",
                    $enrollment_id,
                    $student_id,
                    $skill_id,
                    $session_id,
                    $batch_id,
                    $attendance_date,
                    $attendance_status,
                    $attendance_percentage,
                    $teacher_id,
                    $remarks
                );

                if ($stmt_insert->execute()) {
                    $inserted++;
                }
            }

            mysqli_commit($conn);
            $success_message = "Attendance marked successfully for $inserted students!";

            // Redirect to prevent form resubmission
            header("Location: attendance.php?batch_id=$batch_id&date=$attendance_date&success=1");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Error marking attendance: " . $e->getMessage();
        }
    } else {
        $error_message = "You don't have access to mark attendance for this batch.";
    }
}

// If batch is selected, load students for attendance
$students = [];
$batch_info = null;
$existing_attendance = [];

if ($selected_batch_id > 0) {
    // Get batch information
    $batch_info_query = "
    SELECT 
        b.*,
        s.skill_name,
        se.session_name
    FROM batches b
    JOIN skills s ON b.skill_id = s.id
    JOIN sessions se ON b.session_id = se.id
    WHERE b.id = ?
    ";

    $stmt_batch_info = $conn->prepare($batch_info_query);
    $stmt_batch_info->bind_param("i", $selected_batch_id);
    $stmt_batch_info->execute();
    $batch_info = $stmt_batch_info->get_result()->fetch_assoc();

    // Verify teacher has access to this batch
    $verify_query = "SELECT * FROM teacher_assignments WHERE teacher_id = ? AND batch_id = ?";
    $stmt_verify = $conn->prepare($verify_query);
    $stmt_verify->bind_param("ii", $teacher_id, $selected_batch_id);
    $stmt_verify->execute();

    if ($stmt_verify->get_result()->num_rows > 0 && $batch_info) {
        // Get enrolled students
        $students_query = "
        SELECT 
            se.id AS enrollment_id,
            se.student_id,
            s.name,
            s.student_code,
            s.phone,
            u.email
        FROM student_enrollments se
        JOIN students s ON se.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE se.batch_id = ? AND se.status = 'active'
        ORDER BY s.name
        ";

        $stmt_students = $conn->prepare($students_query);
        $stmt_students->bind_param("i", $selected_batch_id);
        $stmt_students->execute();
        $students = $stmt_students->get_result();

        // Get existing attendance for this date
        $attendance_query = "
        SELECT 
            student_id,
            attendance_status,
            remarks
        FROM student_attendance 
        WHERE batch_id = ? AND attendance_date = ?
        ";

        $stmt_attendance = $conn->prepare($attendance_query);
        $stmt_attendance->bind_param("is", $selected_batch_id, $selected_date);
        $stmt_attendance->execute();
        $attendance_result = $stmt_attendance->get_result();

        while ($att = $attendance_result->fetch_assoc()) {
            $existing_attendance[$att['student_id']] = $att;
        }

        // If in edit mode and we have a specific student, highlight that student
        if ($edit_mode && $attendance_to_edit) {
            $edit_student_id = $attendance_to_edit['student_id'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Mark Attendance | Teacher Panel</title>
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

        .attendance-status {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .attendance-status:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-present {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-color: #10b981;
        }

        .status-absent {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-color: #ef4444;
        }

        .status-late {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-color: #f59e0b;
        }

        .status-leave {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-color: #3b82f6;
        }

        .attendance-status.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            transform: scale(1.05);
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

        .quick-action-btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .table-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e5e7eb;
        }

        .table-row {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .table-row:hover {
            background: #f9fafb;
            transform: translateX(2px);
        }

        .table-row.highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            animation: pulse-highlight 2s infinite;
        }

        @keyframes pulse-highlight {
            0% {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            }

            50% {
                background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
            }

            100% {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            }
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .batch-badge {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: var(--primary-dark);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .date-badge {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .attendance-radio {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
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
                        <h1 class="text-3xl font-bold text-gray-800">Mark Attendance</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>
                            Take attendance for your batches
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="date-badge">
                            <i class="fas fa-calendar-day mr-1"></i>
                            <?php echo date('F j, Y'); ?>
                        </div>
                        <a href="view_attendance.php" class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-2">
                            <i class="fas fa-history"></i>
                            View History
                        </a>
                    </div>
                </div>
            </div>

            <!-- Edit Mode Banner -->
            <?php if ($edit_mode && $attendance_to_edit): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-amber-400 rounded-full flex items-center justify-center">
                            <i class="fas fa-edit text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-yellow-800 text-lg">Edit Mode</h4>
                            <p class="text-sm text-yellow-700">
                                Editing attendance for <span class="font-semibold"><?php echo htmlspecialchars($attendance_to_edit['student_name']); ?></span>
                                in <span class="font-semibold"><?php echo htmlspecialchars($attendance_to_edit['batch_name']); ?></span>
                                on <span class="font-semibold"><?php echo date('F j, Y', strtotime($attendance_to_edit['attendance_date'])); ?></span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <span class="text-sm bg-gradient-to-r from-yellow-400 to-amber-400 text-white px-4 py-2 rounded-full font-medium">
                            <i class="fas fa-exclamation-circle mr-2"></i> Edit Mode Active
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 flex items-center justify-between animate-pulse">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">Success!</h4>
                            <p class="text-green-700">Attendance marked successfully!</p>
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

            <!-- Selection Form -->
            <div class="glass-card p-7 mb-8">
                <h3 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <i class="fas fa-filter text-primary"></i>
                    Select Batch & Date
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
                            <?php while ($batch = $assigned_batches->fetch_assoc()): ?>
                                <option value="<?php echo $batch['batch_id']; ?>"
                                    <?php echo $selected_batch_id == $batch['batch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch['batch_name']); ?>
                                    (<?php echo htmlspecialchars($batch['skill_name']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-primary"></i>
                            Select Date
                        </label>
                        <input type="date"
                            name="date"
                            value="<?php echo $selected_date; ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            class="w-full form-input px-5 py-3.5 rounded-xl text-gray-700 font-medium"
                            onchange="this.form.submit()">
                    </div>

                    <div class="lg:col-span-2 flex items-end">
                        <button type="submit"
                            class="w-full btn-primary px-6 py-3.5 rounded-xl font-bold text-lg flex items-center justify-center gap-3">
                            <i class="fas fa-search"></i>
                            Load Students
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($selected_batch_id > 0 && $batch_info): ?>
                <!-- Batch Information -->
                <div class="card p-7 mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-r from-primary to-primary-dark rounded-xl flex items-center justify-center">
                                    <i class="fas fa-layer-group text-white text-xl"></i>
                                </div>
                                Batch Information
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Batch Name</p>
                                    <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($batch_info['batch_name']); ?></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Skill/Course</p>
                                    <p class="text-lg font-bold text-primary"><?php echo htmlspecialchars($batch_info['skill_name']); ?></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Session</p>
                                    <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($batch_info['session_name']); ?></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Timing</p>
                                    <p class="text-lg font-bold text-gray-800">
                                        <?php echo date('h:i A', strtotime($batch_info['start_time'])); ?> -
                                        <?php echo date('h:i A', strtotime($batch_info['end_time'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center lg:text-right">
                            <div class="text-4xl font-bold text-primary mb-2">
                                <?php echo $students->num_rows; ?>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Total Students</p>
                            <div class="mt-3 text-sm bg-gray-100 text-gray-700 px-4 py-2 rounded-lg">
                                <i class="fas fa-calendar-day mr-2"></i>
                                <?php echo date('F j, Y', strtotime($selected_date)); ?>
                            </div>
                        </div>
                    </div>
                </div>

            
                     

                
                <!-- Attendance Form -->
                <?php if ($students->num_rows > 0): ?>
                    <form method="POST" id="attendanceForm">
                        <div class="table-container">
                            <!-- Form Header -->
                            <div class="px-7 py-5 table-header flex justify-between items-center">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                                        <i class="fas fa-user-graduate text-primary"></i>
                                        Student Attendance List
                                        <span class="text-sm bg-primary text-white px-3 py-1 rounded-full">
                                            <?php echo $students->num_rows; ?> Students
                                        </span>
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-2">
                                        <i class="fas fa-mouse-pointer mr-2"></i>
                                        Click on status buttons to mark attendance. Student being edited is highlighted.
                                    </p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <?php echo date('l, F j, Y', strtotime($selected_date)); ?>
                                </div>
                            </div>

                            <!-- Students Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-4 px-6 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                <div class="flex items-center gap-2">
                                                    <span>#</span>
                                                    <span>Student</span>
                                                </div>
                                            </th>
                                            <th class="py-4 px-6 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                Student Code
                                            </th>
                                            <th class="py-4 px-6 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                Contact Info
                                            </th>
                                            <th class="py-4 px-6 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                Attendance Status
                                            </th>
                                            <th class="py-4 px-6 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                Remarks
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $counter = 1;
                                        mysqli_data_seek($students, 0);
                                        while ($student = $students->fetch_assoc()):
                                            $student_id = $student['student_id'];
                                            $existing = isset($existing_attendance[$student_id]) ? $existing_attendance[$student_id] : null;

                                            // Check if this is the student being edited
                                            $is_edit_student = ($edit_mode && isset($edit_student_id) && $student_id == $edit_student_id);
                                            $row_class = $is_edit_student ? 'table-row highlight' : 'table-row';
                                        ?>
                                            <tr class="<?php echo $row_class; ?>" id="student-<?php echo $student_id; ?>">
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center gap-4">
                                                        <div class="student-avatar">
                                                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                                                <?php echo htmlspecialchars($student['name']); ?>
                                                                <?php if ($is_edit_student): ?>
                                                                    <span class="text-xs bg-gradient-to-r from-yellow-400 to-amber-400 text-white px-3 py-1 rounded-full font-bold animate-pulse">
                                                                        <i class="fas fa-edit mr-1"></i> EDITING
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500 mt-1">
                                                                ID: #<?php echo str_pad($student_id, 4, '0', STR_PAD_LEFT); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="font-mono text-gray-800 font-bold bg-gray-100 px-3 py-2 rounded-lg inline-block">
                                                        <?php echo htmlspecialchars($student['student_code']); ?>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="space-y-1">
                                                        <div class="flex items-center gap-2 text-gray-700">
                                                            <i class="fas fa-phone text-sm text-gray-400"></i>
                                                            <span class="text-sm font-medium"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></span>
                                                        </div>
                                                        <div class="flex items-center gap-2 text-gray-700">
                                                            <i class="fas fa-envelope text-sm text-gray-400"></i>
                                                            <span class="text-sm font-medium truncate max-w-xs"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <!-- Present -->
                                                        <label class="relative">
                                                            <input type="radio"
                                                                name="attendance[<?php echo $student_id; ?>]"
                                                                value="present"
                                                                class="attendance-radio"
                                                                <?php echo ($existing && $existing['attendance_status'] === 'present') ? 'checked' : ''; ?>
                                                                <?php echo ($is_edit_student && $attendance_to_edit['attendance_status'] === 'present') ? 'checked' : ''; ?>
                                                                <?php echo (!$existing && $counter == 1 && !$edit_mode) ? 'checked' : ''; ?>>
                                                            <span class="attendance-status status-present flex items-center justify-center">
                                                                <i class="fas fa-check mr-2"></i> Present
                                                            </span>
                                                        </label>

                                                        <!-- Absent -->
                                                        <label class="relative">
                                                            <input type="radio"
                                                                name="attendance[<?php echo $student_id; ?>]"
                                                                value="absent"
                                                                class="attendance-radio"
                                                                <?php echo ($existing && $existing['attendance_status'] === 'absent') ? 'checked' : ''; ?>
                                                                <?php echo ($is_edit_student && $attendance_to_edit['attendance_status'] === 'absent') ? 'checked' : ''; ?>>
                                                            <span class="attendance-status status-absent flex items-center justify-center">
                                                                <i class="fas fa-times mr-2"></i> Absent
                                                            </span>
                                                        </label>

                                                      

                                                        <!-- Leave -->
                                                        <label class="relative">
                                                            <input type="radio"
                                                                name="attendance[<?php echo $student_id; ?>]"
                                                                value="leave"
                                                                class="attendance-radio"
                                                                <?php echo ($existing && $existing['attendance_status'] === 'leave') ? 'checked' : ''; ?>
                                                                <?php echo ($is_edit_student && $attendance_to_edit['attendance_status'] === 'leave') ? 'checked' : ''; ?>>
                                                            <span class="attendance-status status-leave flex items-center justify-center">
                                                                <i class="fas fa-umbrella-beach mr-2"></i> Leave
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="relative">
                                                        <input type="text"
                                                            name="remarks[<?php echo $student_id; ?>]"
                                                            value="<?php
                                                                    if ($is_edit_student && $attendance_to_edit) {
                                                                        echo htmlspecialchars($attendance_to_edit['remarks']);
                                                                    } elseif ($existing) {
                                                                        echo htmlspecialchars($existing['remarks']);
                                                                    }
                                                                    ?>"
                                                            placeholder="Add remarks..."
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition-all duration-300 <?php echo $is_edit_student ? 'border-yellow-400 bg-yellow-50' : ''; ?>"
                                                            maxlength="100">
                                                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                                            <i class="fas fa-comment"></i>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php
                                            $counter++;
                                        endwhile;
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Form Footer -->
                            <div class="px-7 py-5 bg-gradient-to-r from-gray-50 to-gray-100 border-t flex flex-col md:flex-row justify-between items-center gap-4">
                                <div class="text-sm text-gray-600 flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <span>Present</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                        <span>Absent</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                        <span>Late</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                        <span>Leave</span>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                                    <input type="hidden" name="batch_id" value="<?php echo $selected_batch_id; ?>">

                                    <button type="button"
                                        onclick="if(confirm('Are you sure you want to reset all changes?')) location.reload()"
                                        class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all duration-300 flex items-center gap-2">
                                        <i class="fas fa-redo"></i>
                                        Reset All
                                    </button>

                                    <button type="submit"
                                        name="submit_attendance"
                                        class="btn-success px-8 py-3 rounded-xl font-bold text-lg flex items-center gap-3">
                                        <i class="fas fa-save"></i>
                                        Save Attendance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="card p-12 text-center">
                        <div class="w-24 h-24 bg-gradient-to-r from-gray-200 to-gray-300 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-user-graduate text-gray-400 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-700 mb-3">No Students Enrolled</h3>
                        <p class="text-gray-500 text-lg mb-8 max-w-md mx-auto">There are no students enrolled in this batch yet. Please contact the administrator to add students.</p>
                        <a href="my_batches.php" class="inline-flex items-center gap-2 text-primary hover:text-primary-dark font-bold text-lg">
                            <i class="fas fa-arrow-left"></i>
                            Back to Batches
                        </a>
                    </div>
                <?php endif; ?>
            <?php elseif ($selected_batch_id > 0): ?>
                <!-- No Access Message -->
                <div class="card p-12 text-center">
                    <div class="w-24 h-24 bg-gradient-to-r from-red-200 to-rose-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-3">Access Denied</h3>
                    <p class="text-gray-500 text-lg mb-8 max-w-md mx-auto">You don't have permission to mark attendance for this batch.</p>
                    <a href="attendance.php" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-primary-dark transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        Select Another Batch
                    </a>
                </div>
            <?php else: ?>
                <!-- No Batch Selected -->
                <div class="card p-12 text-center">
                    <div class="w-24 h-24 bg-gradient-to-r from-blue-200 to-indigo-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-primary text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-3">Select a Batch</h3>
                    <p class="text-gray-500 text-lg mb-6">Please select a batch and date to mark attendance.</p>
                    <div class="text-lg text-primary font-bold mb-8">
                        <i class="fas fa-info-circle mr-2"></i>
                        You have <?php echo $assigned_batches->num_rows; ?> active batches
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-w-3xl mx-auto">
                        <?php
                        mysqli_data_seek($assigned_batches, 0);
                        $batch_counter = 0;
                        while ($batch = $assigned_batches->fetch_assoc()):
                            if ($batch_counter >= 6) break;
                        ?>
                            <div class="bg-gradient-to-r from-gray-50 to-white p-4 rounded-xl border border-gray-200 hover:border-primary transition-colors">
                                <div class="font-bold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($batch['batch_name']); ?></div>
                                <div class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($batch['skill_name']); ?></div>
                                <a href="attendance.php?batch_id=<?php echo $batch['batch_id']; ?>&date=<?php echo date('Y-m-d'); ?>"
                                    class="inline-flex items-center gap-2 text-sm text-primary hover:text-primary-dark font-medium">
                                    <i class="fas fa-clipboard-check"></i>
                                    Mark Attendance
                                </a>
                            </div>
                        <?php
                            $batch_counter++;
                        endwhile;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Quick action functions
        function markAll(status) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
            radios.forEach(radio => {
                radio.checked = true;
                // Update visual state
                const label = radio.nextElementSibling;
                if (label) {
                    document.querySelectorAll('.attendance-status').forEach(l => {
                        l.classList.remove('active');
                    });
                    label.classList.add('active');
                }
            });

            // Show notification
            showNotification(`All students marked as ${status.toUpperCase()}`, 'success');
        }

        function clearAll() {
            const radios = document.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                radio.checked = false;
            });

            // Clear remarks
            const remarks = document.querySelectorAll('input[name^="remarks"]');
            remarks.forEach(input => {
                input.value = '';
            });

            // Clear visual state
            document.querySelectorAll('.attendance-status').forEach(label => {
                label.classList.remove('active');
            });

            showNotification('All selections cleared', 'info');
        }

        // Update label style when radio is checked
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('attendance-radio')) {
                // Remove active class from all labels in the same group
                const row = e.target.closest('tr');
                if (row) {
                    row.querySelectorAll('.attendance-status').forEach(label => {
                        label.classList.remove('active');
                    });

                    // Add active class to selected label
                    const selectedLabel = e.target.nextElementSibling;
                    if (selectedLabel) {
                        selectedLabel.classList.add('active');
                    }
                }
            }
        });

        // Initialize visual state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadios = document.querySelectorAll('.attendance-radio:checked');
            checkedRadios.forEach(radio => {
                const label = radio.nextElementSibling;
                if (label) {
                    label.classList.add('active');
                }
            });

            // Scroll to edited student if in edit mode
            <?php if ($edit_mode && isset($edit_student_id)): ?>
                const editRow = document.getElementById('student-<?php echo $edit_student_id; ?>');
                if (editRow) {
                    // Add smooth scroll to the row
                    setTimeout(() => {
                        editRow.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Add highlight animation
                        editRow.style.animation = 'pulse-highlight 2s infinite';
                    }, 500);
                }
            <?php endif; ?>
        });

        // Form submission confirmation
        document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
            const presentCount = document.querySelectorAll('input[type="radio"][value="present"]:checked').length;
            const totalStudents = <?php echo $students->num_rows; ?>;

            if (presentCount === 0) {
                if (!confirm('⚠️ No students are marked as present. Are you sure you want to submit?')) {
                    e.preventDefault();
                }
            } else if (presentCount === totalStudents) {
                if (!confirm('✅ All students are marked as present. Is this correct?')) {
                    e.preventDefault();
                }
            } else {
                const confirmed = confirm(`📊 You have marked ${presentCount} out of ${totalStudents} students as present. Do you want to save attendance?`);
                if (!confirmed) {
                    e.preventDefault();
                }
            }
        });

        // Notification function
        function showNotification(message, type = 'info') {
            const types = {
                'success': {
                    bg: 'from-green-500 to-emerald-500',
                    icon: 'fa-check-circle'
                },
                'error': {
                    bg: 'from-red-500 to-rose-500',
                    icon: 'fa-exclamation-circle'
                },
                'info': {
                    bg: 'from-blue-500 to-indigo-500',
                    icon: 'fa-info-circle'
                },
                'warning': {
                    bg: 'from-yellow-500 to-amber-500',
                    icon: 'fa-exclamation-triangle'
                }
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-6 right-6 bg-gradient-to-r ${types[type].bg} text-white px-6 py-4 rounded-xl shadow-2xl z-50 max-w-sm transform translate-x-full animate-slide-in`;
            notification.innerHTML = `
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas ${types[type].icon} text-white text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Add animation CSS
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slide-in {
                    from { transform: translateX(100%); }
                    to { transform: translateX(0); }
                }
                .animate-slide-in {
                    animation: slide-in 0.3s ease-out forwards;
                }
            `;
            document.head.appendChild(style);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.transform = 'translateX(100%)';
                    notification.style.transition = 'transform 0.3s ease-out';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                            style.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + 1 for present
            if (e.altKey && e.key === '1') {
                markAll('present');
                e.preventDefault();
            }
            // Alt + 2 for absent
            if (e.altKey && e.key === '2') {
                markAll('absent');
                e.preventDefault();
            }
            // Alt + 3 for late
            if (e.altKey && e.key === '3') {
                markAll('late');
                e.preventDefault();
            }
            // Alt + 4 for leave
            if (e.altKey && e.key === '4') {
                markAll('leave');
                e.preventDefault();
            }
            // Alt + R to reset
            if (e.altKey && e.key === 'r') {
                clearAll();
                e.preventDefault();
            }
            // Ctrl + S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('button[name="submit_attendance"]').click();
            }
        });

        // Add tooltip for keyboard shortcuts
        document.addEventListener('DOMContentLoaded', function() {
            // Create shortcut helper
            const shortcutHelper = document.createElement('div');
            shortcutHelper.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm opacity-0 hover:opacity-100 transition-opacity duration-300';
            shortcutHelper.innerHTML = `
                <div class="font-bold mb-1">Keyboard Shortcuts:</div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>Alt+1: All Present</div>
                    <div>Alt+2: All Absent</div>
                    <div>Alt+3: All Late</div>
                    <div>Alt+4: All Leave</div>
                    <div>Alt+R: Clear All</div>
                    <div>Ctrl+S: Save</div>
                </div>
            `;
            document.body.appendChild(shortcutHelper);

            // Show on Alt key press
            let altPressed = false;
            document.addEventListener('keydown', function(e) {
                if (e.altKey) {
                    shortcutHelper.style.opacity = '1';
                    altPressed = true;
                }
            });

            document.addEventListener('keyup', function(e) {
                if (e.key === 'Alt' && altPressed) {
                    setTimeout(() => {
                        shortcutHelper.style.opacity = '0';
                    }, 2000);
                    altPressed = false;
                }
            });
        });
    </script>
</body>

</html>
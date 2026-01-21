<?php
require_once __DIR__ . '/../../config/db.php';


// Get today's date
$today = date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = $_POST['attendance_date'];
    $batch_id = intval($_POST['batch_id']);
    $attendance_data = $_POST['attendance'];

    // Get marked_by (admin user id from session)
    $marked_by = $_SESSION['user_id'] ?? 1; // Default to admin id 1

    foreach ($attendance_data as $enrollment_id => $status) {
        $enrollment_id = intval($enrollment_id);
        $student_id = intval($_POST['student_id'][$enrollment_id]);
        $skill_id = intval($_POST['skill_id'][$enrollment_id]);
        $session_id = intval($_POST['session_id'][$enrollment_id]);
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks'][$enrollment_id] ?? '');

        // Check if attendance already exists for today
        $check_query = "SELECT id FROM student_attendance WHERE enrollment_id = $enrollment_id AND attendance_date = '$attendance_date' AND status='active'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $update_query = "UPDATE student_attendance SET attendance_status = '$status', remarks = '$remarks', updated_at = NOW() WHERE enrollment_id = $enrollment_id AND attendance_date = '$attendance_date'";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO student_attendance (enrollment_id, student_id, skill_id, session_id, batch_id, attendance_date, attendance_status, marked_by, remarks, status, created_at) VALUES ($enrollment_id, $student_id, $skill_id, $session_id, $batch_id, '$attendance_date', '$status', $marked_by, '$remarks', 'active', NOW())";
            mysqli_query($conn, $insert_query);
        }

        // Calculate and update attendance percentage for this student
        updateAttendancePercentage($conn, $student_id, $batch_id, $skill_id, $session_id);
    }

    header("Location: take_student_attendance.php?success=1&batch_id=$batch_id");
    exit;
}

// Function to calculate attendance percentage
function updateAttendancePercentage($conn, $student_id, $batch_id, $skill_id, $session_id)
{
    // Get total days (all attendance records for this student in this batch)
    $total_query = "SELECT COUNT(*) as total FROM student_attendance 
                    WHERE student_id = $student_id 
                    AND batch_id = $batch_id 
                    AND skill_id = $skill_id 
                    AND session_id = $session_id 
                    AND status = 'active'";
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_days = $total_row['total'];

    // Get present days
    $present_query = "SELECT COUNT(*) as present FROM student_attendance 
                      WHERE student_id = $student_id 
                      AND batch_id = $batch_id 
                      AND skill_id = $skill_id 
                      AND session_id = $session_id 
                      AND attendance_status = 'present' 
                      AND status = 'active'";
    $present_result = mysqli_query($conn, $present_query);
    $present_row = mysqli_fetch_assoc($present_result);
    $present_days = $present_row['present'];

    // Calculate percentage
    $percentage = ($total_days > 0) ? round(($present_days / $total_days) * 100, 2) : 0;

    // Update percentage in all records for this student (or you can update a separate table)
    $update_percentage_query = "UPDATE student_attendance 
                               SET attendance_percentage = $percentage 
                               WHERE student_id = $student_id 
                               AND batch_id = $batch_id 
                               AND skill_id = $skill_id 
                               AND session_id = $session_id 
                               AND status = 'active'";
    mysqli_query($conn, $update_percentage_query);

    return $percentage;
}

// Fetch all active batches for selection
$batches_query = "SELECT b.*, s.skill_name, se.session_name FROM batches b 
                  JOIN skills s ON b.skill_id = s.id 
                  JOIN sessions se ON b.session_id = se.id 
                  WHERE b.status='active' 
                  ORDER BY b.batch_name";
$batches_result = mysqli_query($conn, $batches_query);

// If batch is selected, fetch students
$students = [];
$selected_batch = null;
$attendance_percentages = [];

if (isset($_GET['batch_id'])) {
    $batch_id = intval($_GET['batch_id']);
    $selected_batch_query = "SELECT * FROM batches WHERE id = $batch_id";
    $selected_batch_result = mysqli_query($conn, $selected_batch_query);
    $selected_batch = mysqli_fetch_assoc($selected_batch_result);

    $students_query = "
        SELECT e.*, s.name as student_name, s.student_code, sk.skill_name, se.session_name
        FROM student_enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN skills sk ON e.skill_id = sk.id
        JOIN sessions se ON e.session_id = se.id
        WHERE e.batch_id = $batch_id AND e.status='active' AND s.status='active'
        ORDER BY s.name
    ";
    $students_result = mysqli_query($conn, $students_query);

    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;

        // Get attendance percentage for each student
        $percentage_query = "SELECT attendance_percentage FROM student_attendance 
                            WHERE student_id = {$row['student_id']} 
                            AND batch_id = $batch_id 
                            AND skill_id = {$row['skill_id']} 
                            AND session_id = {$row['session_id']} 
                            AND status = 'active' 
                            ORDER BY attendance_date DESC LIMIT 1";
        $percentage_result = mysqli_query($conn, $percentage_query);
        $percentage_row = mysqli_fetch_assoc($percentage_result);

        $attendance_percentages[$row['student_id']] = $percentage_row['attendance_percentage'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Take Student Attendance | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .attendance-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-present {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-leave {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-late {
            background: #fef3c7;
            color: #92400e;
        }

        .percentage-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
            display: inline-block;
        }

        .percentage-high {
            background: #d1fae5;
            color: #065f46;
        }

        .percentage-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .percentage-low {
            background: #fee2e2;
            color: #991b1b;
        }

        .search-box {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
        }

        .search-box:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            margin: 0;
            cursor: pointer;
        }

        .radio-option label {
            cursor: pointer;
            font-size: 14px;
            color: #4b5563;
        }

        .radio-present {
            color: #065f46;
        }

        .radio-absent {
            color: #991b1b;
        }

        .radio-leave {
            color: #3730a3;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
         <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Take Student Attendance</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-graduate text-blue-500 mr-1"></i>
                        Mark attendance for students in selected batch
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search students..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    Attendance saved successfully!
                </div>
            <?php endif; ?>

            <!-- Batch Selection Form -->
            <div class="form-container mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Select Batch</h3>
                <form method="GET" class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch</label>
                        <select name="batch_id" class="form-select" onchange="this.form.submit()" required>
                            <option value="">-- Select Batch --</option>
                            <?php
                            // Reset pointer for batches result
                            mysqli_data_seek($batches_result, 0);
                            while ($batch = mysqli_fetch_assoc($batches_result)): ?>
                                <option value="<?= $batch['id'] ?>" <?= isset($_GET['batch_id']) && $_GET['batch_id'] == $batch['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($batch['batch_name']) ?> - <?= htmlspecialchars($batch['skill_name']) ?> (<?= htmlspecialchars($batch['session_name']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-search mr-1"></i> Load Students
                        </button>
                    </div>
                </form>
            </div>

            <!-- Attendance Form -->
            <?php if (!empty($students)): ?>
                <div class="form-container">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Attendance for <?= htmlspecialchars($selected_batch['batch_name']) ?></h3>
                            <p class="text-sm text-gray-500"><?= count($students) ?> students found</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            Date: <?= date('F j, Y') ?>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="attendance_date" value="<?= $today ?>">
                        <input type="hidden" name="batch_id" value="<?= $batch_id ?>">

                        <div class="overflow-x-auto">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Code</th>
                                        <th>Student Name</th>
                                        <th>Skill</th>
                                        <th>Attendance %</th>
                                        <th>Attendance Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student):
                                        // Get existing attendance for today
                                        $existing_query = "SELECT * FROM student_attendance WHERE enrollment_id = {$student['id']} AND attendance_date = '$today' AND status='active'";
                                        $existing_result = mysqli_query($conn, $existing_query);
                                        $existing = mysqli_fetch_assoc($existing_result);

                                        // Get attendance percentage
                                        $percentage = $attendance_percentages[$student['student_id']] ?? 0;
                                        $percentage_class = 'percentage-low';
                                        if ($percentage >= 80) {
                                            $percentage_class = 'percentage-high';
                                        } elseif ($percentage >= 60) {
                                            $percentage_class = 'percentage-medium';
                                        }
                                    ?>
                                        <tr class="student-row">
                                            <td class="font-medium"><?= $index + 1 ?></td>
                                            <td>
                                                <?= $student['student_code'] ?>
                                                <input type="hidden" name="student_id[<?= $student['id'] ?>]" value="<?= $student['student_id'] ?>">
                                                <input type="hidden" name="skill_id[<?= $student['id'] ?>]" value="<?= $student['skill_id'] ?>">
                                                <input type="hidden" name="session_id[<?= $student['id'] ?>]" value="<?= $student['session_id'] ?>">
                                            </td>
                                            <td class="student-name font-medium"><?= htmlspecialchars($student['student_name']) ?></td>
                                            <td><?= htmlspecialchars($student['skill_name']) ?></td>
                                            <td>
                                                <span class="percentage-badge <?= $percentage_class ?>">
                                                    <?= $percentage ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <div class="radio-group">
                                                    <div class="radio-option">
                                                        <input type="radio"
                                                            id="present_<?= $student['id'] ?>"
                                                            name="attendance[<?= $student['id'] ?>]"
                                                            value="present"
                                                            <?= ($existing['attendance_status'] ?? 'present') == 'present' ? 'checked' : '' ?>
                                                            class="text-green-600">
                                                        <label for="present_<?= $student['id'] ?>" class="radio-present">
                                                            <i class="fas fa-check-circle mr-1"></i> Present
                                                        </label>
                                                    </div>
                                                    <div class="radio-option">
                                                        <input type="radio"
                                                            id="absent_<?= $student['id'] ?>"
                                                            name="attendance[<?= $student['id'] ?>]"
                                                            value="absent"
                                                            <?= ($existing['attendance_status'] ?? '') == 'absent' ? 'checked' : '' ?>
                                                            class="text-red-600">
                                                        <label for="absent_<?= $student['id'] ?>" class="radio-absent">
                                                            <i class="fas fa-times-circle mr-1"></i> Absent
                                                        </label>
                                                    </div>
                                                    <div class="radio-option">
                                                        <input type="radio"
                                                            id="leave_<?= $student['id'] ?>"
                                                            name="attendance[<?= $student['id'] ?>]"
                                                            value="leave"
                                                            <?= ($existing['attendance_status'] ?? '') == 'leave' ? 'checked' : '' ?>
                                                            class="text-indigo-600">
                                                        <label for="leave_<?= $student['id'] ?>" class="radio-leave">
                                                            <i class="fas fa-umbrella-beach mr-1"></i> Leave
                                                        </label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text"
                                                    name="remarks[<?= $student['id'] ?>]"
                                                    class="search-box"
                                                    placeholder="Optional remarks"
                                                    value="<?= htmlspecialchars($existing['remarks'] ?? '') ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Color Legend:</strong>
                                <span class="badge-present ml-2">Present</span>
                                <span class="badge-absent ml-2">Absent</span>
                                <span class="badge-leave ml-2">Leave</span>
                            </div>
                            <div class="flex gap-3">
                                <a href="attendance.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium">
                                    Cancel
                                </a>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium">
                                    <i class="fas fa-save mr-2"></i> Save Attendance
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php elseif (isset($_GET['batch_id'])): ?>
                <div class="form-container text-center py-12">
                    <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Students Found</h3>
                    <p class="text-gray-500 text-sm mb-4">No active students enrolled in this batch.</p>
                </div>
            <?php else: ?>
                <div class="form-container text-center py-12">
                    <i class="fas fa-clipboard-check text-gray-300 text-4xl mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Select a Batch</h3>
                    <p class="text-gray-500 text-sm mb-4">Please select a batch to take attendance.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.student-row');

            rows.forEach(row => {
                const name = row.querySelector('.student-name').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Auto-save functionality (optional)
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // You can add auto-save functionality here if needed
                console.log('Attendance changed:', this.name, this.value);
            });
        });
    </script>

</body>

</html>
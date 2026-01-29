<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

// Get teacher ID from teachers table using user_id from session
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

// Fetch teacher details
$teacher_query = "SELECT * FROM teachers WHERE user_id = ?";
$stmt_teacher = $conn->prepare($teacher_query);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    // If no teacher record found, redirect to login
    header("Location: ../auth/login.php?error=teacher_not_found");
    exit;
}

$teacher_id = $teacher['id']; // Get the actual teacher ID from teachers table

// Store teacher_id in session for future use
$_SESSION['teacher_id'] = $teacher_id;

// Fetch assigned batches with skill & session
$query = "
SELECT 
    b.id AS batch_id,
    b.batch_name,
    b.start_time,
    b.end_time,
    b.max_students,
    s.skill_name,
    se.session_name
FROM teacher_assignments ta
JOIN batches b ON ta.batch_id = b.id
JOIN skills s ON b.skill_id = s.id
JOIN sessions se ON b.session_id = se.id
WHERE ta.teacher_id = ? AND b.status = 'active'
ORDER BY b.start_time, b.batch_name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$batches = $stmt->get_result();

// Count total assigned batches
$total_batches = $batches->num_rows;

// Count total students in assigned batches
$student_query = "
SELECT COUNT(DISTINCT se.student_id) AS total_students
FROM student_enrollments se
WHERE se.batch_id IN (
    SELECT ta.batch_id FROM teacher_assignments ta WHERE ta.teacher_id = ?
) AND se.status = 'active'
";
$stmt2 = $conn->prepare($student_query);
$stmt2->bind_param("i", $teacher_id);
$stmt2->execute();
$student_result = $stmt2->get_result()->fetch_assoc();
$total_students = $student_result['total_students'] ?? 0;

// Check today's attendance status
$today = date('Y-m-d');
$attendance_query = "
SELECT COUNT(*) AS marked_today 
FROM student_attendance 
WHERE batch_id IN (
    SELECT ta.batch_id FROM teacher_assignments ta WHERE ta.teacher_id = ?
)
AND attendance_date = ?
";
$stmt3 = $conn->prepare($attendance_query);
$stmt3->bind_param("is", $teacher_id, $today);
$stmt3->execute();
$attendance_result = $stmt3->get_result()->fetch_assoc();
$attendance_marked = $attendance_result['marked_today'] > 0;

// Get batch statistics
$batch_stats = [];
if ($total_batches > 0) {
    mysqli_data_seek($batches, 0);
    while ($batch = $batches->fetch_assoc()) {
        // Count students in this batch
        $batch_student_query = "
        SELECT COUNT(*) as student_count
        FROM student_enrollments 
        WHERE batch_id = ? AND status = 'active'
        ";
        $stmt4 = $conn->prepare($batch_student_query);
        $stmt4->bind_param("i", $batch['batch_id']);
        $stmt4->execute();
        $batch_student_result = $stmt4->get_result()->fetch_assoc();

        $batch_stats[$batch['batch_id']] = [
            'name' => $batch['batch_name'],
            'students' => $batch_student_result['student_count'] ?? 0,
            'max_students' => $batch['max_students']
        ];
    }
    mysqli_data_seek($batches, 0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Teacher Dashboard | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
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

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            border-radius: 4px;
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
                        <h1 class="text-3xl font-bold text-gray-800">Teacher Dashboard</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-chalkboard-teacher text-blue-500 mr-2"></i>
                            Welcome, <?php echo htmlspecialchars($teacher['name'] ?? 'Teacher'); ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <span class="text-sm font-medium">Teacher Code:</span>
                            <span class="font-bold ml-2"><?php echo htmlspecialchars($teacher['teacher_code'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-calendar-day mr-1"></i>
                            <?php echo date('F j, Y'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Assigned Batches</h3>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $total_batches; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Total active batches assigned to you
                    </p>
                    <a href="my_batches.php" class="text-xs text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        <i class="fas fa-arrow-right mr-1"></i> View all batches
                    </a>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Students</h3>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $total_students; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-graduate text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Students across all your batches
                    </p>
                    <a href="student_progress.php" class="text-xs text-green-600 hover:text-green-800 mt-2 inline-block">
                        <i class="fas fa-arrow-right mr-1"></i> View student progress
                    </a>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Today's Attendance</h3>
                            <p class="text-3xl font-bold <?php echo $attendance_marked ? 'text-green-600' : 'text-yellow-600'; ?>">
                                <?php echo $attendance_marked ? "Marked" : "Pending"; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 <?php echo $attendance_marked ? 'bg-green-100' : 'bg-yellow-100'; ?> rounded-full flex items-center justify-center">
                            <i class="fas fa-clipboard-check <?php echo $attendance_marked ? 'text-green-600' : 'text-yellow-600'; ?> text-xl"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?php echo $attendance_marked ? 'Attendance marked for today' : 'Attendance not marked yet'; ?>
                    </p>
                    <a href="attendance.php" class="text-xs <?php echo $attendance_marked ? 'text-green-600 hover:text-green-800' : 'text-yellow-600 hover:text-yellow-800'; ?> mt-2 inline-block">
                        <i class="fas fa-<?php echo $attendance_marked ? 'eye' : 'clipboard-check'; ?> mr-1"></i>
                        <?php echo $attendance_marked ? 'View attendance' : 'Mark attendance now'; ?>
                    </a>
                </div>
            </div>

            <!-- Today's Schedule & Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Today's Schedule -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-day text-blue-500"></i>
                        Today's Schedule
                    </h2>
                    <?php if ($total_batches > 0): ?>
                        <?php
                        $today_time = date('H:i:s');
                        $has_today_schedule = false;

                        mysqli_data_seek($batches, 0);
                        while ($batch = $batches->fetch_assoc()):
                            $start_time = strtotime($batch['start_time']);
                            $end_time = strtotime($batch['end_time']);
                            $current_time = strtotime($today_time);

                            // Check if current time is within batch time
                            if ($current_time >= $start_time && $current_time <= $end_time) {
                                $has_today_schedule = true;
                        ?>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-blue-800">Current Class</h3>
                                            <p class="text-sm text-gray-700"><?php echo htmlspecialchars($batch['skill_name']); ?> - <?php echo htmlspecialchars($batch['batch_name']); ?></p>
                                        </div>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                            In Progress
                                        </span>
                                    </div>
                                    <div class="mt-3 flex items-center gap-4 text-sm">
                                        <span class="text-gray-600">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date("h:i A", $start_time); ?> - <?php echo date("h:i A", $end_time); ?>
                                        </span>
                                        <a href="attendance.php?batch_id=<?php echo $batch['batch_id']; ?>" class="text-blue-600 hover:text-blue-800 ml-auto">
                                            <i class="fas fa-clipboard-check mr-1"></i> Take Attendance
                                        </a>
                                    </div>
                                </div>
                            <?php
                                break; // Show only one current class
                            }
                        endwhile;

                        if (!$has_today_schedule):
                            ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-calendar-times text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-700 mb-2">No classes scheduled now</h3>
                                <p class="text-gray-500 text-sm">Check your schedule for upcoming classes</p>
                                <a href="my_batches.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-calendar-alt mr-1"></i> View full schedule
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-users text-gray-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No batches assigned</h3>
                            <p class="text-gray-500 text-sm">You don't have any batches assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-500"></i>
                        Quick Actions
                    </h2>
                    <div class="space-y-3">
                        <a href="attendance.php" class="flex items-center justify-between p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-all duration-200 border border-blue-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clipboard-check text-blue-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Mark Attendance</h3>
                                    <p class="text-sm text-gray-600">Take attendance for today's classes</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-blue-400"></i>
                        </a>

                        <a href="create_quiz.php" class="flex items-center justify-between p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-all duration-200 border border-green-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-question-circle text-green-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Create Quiz</h3>
                                    <p class="text-sm text-gray-600">Create a new quiz or test</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-green-400"></i>
                        </a>

                        <a href="syllabus.php" class="flex items-center justify-between p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-all duration-200 border border-purple-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-book text-purple-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Update Syllabus</h3>
                                    <p class="text-sm text-gray-600">Add or update course materials</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-purple-400"></i>
                        </a>

                        <a href="materials.php" class="flex items-center justify-between p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition-all duration-200 border border-orange-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-file-upload text-orange-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Upload Materials</h3>
                                    <p class="text-sm text-gray-600">Share study materials with students</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-orange-400"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Assigned Batches Table -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">My Assigned Batches (<?php echo $total_batches; ?>)</h2>
                    <?php if ($total_batches > 0): ?>
                        <a href="my_batches.php" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="fas fa-external-link-alt"></i>
                            View All
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($total_batches > 0): ?>
                    <div class="table-container">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill (Course)</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch Name</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Timing</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Students</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php
                                    $display_count = 0;
                                    while ($row = $batches->fetch_assoc()):
                                        if ($display_count >= 5) break; // Show only 5 batches
                                        $batch_id = $row['batch_id'];
                                        $student_count = $batch_stats[$batch_id]['students'] ?? 0;
                                        $max_students = $row['max_students'];
                                        $percentage = $max_students > 0 ? ($student_count / $max_students) * 100 : 0;
                                        $display_count++;
                                    ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['skill_name']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['batch_name']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-gray-700"><?php echo htmlspecialchars($row['session_name']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-gray-700">
                                                    <?php echo date("h:i A", strtotime($row['start_time'])) . " - " . date("h:i A", strtotime($row['end_time'])); ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-gray-900"><?php echo $student_count; ?></span>
                                                    <span class="text-xs text-gray-500">/<?php echo $max_students; ?></span>
                                                </div>
                                                <div class="progress-bar mt-1">
                                                    <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex gap-2">
                                                    <a href="attendance.php?batch_id=<?php echo $batch_id; ?>"
                                                        class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1"
                                                        title="Take Attendance">
                                                        <i class="fas fa-clipboard-check text-xs"></i>
                                                    </a>
                                                    <a href="student_progress.php?batch_id=<?php echo $batch_id; ?>"
                                                        class="text-green-600 hover:text-green-800 text-sm flex items-center gap-1"
                                                        title="Student Progress">
                                                        <i class="fas fa-chart-line text-xs"></i>
                                                    </a>
                                                    <a href="my_batches.php?batch_id=<?php echo $batch_id; ?>"
                                                        class="text-purple-600 hover:text-purple-800 text-sm flex items-center gap-1"
                                                        title="View Details">
                                                        <i class="fas fa-eye text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($total_batches > 5): ?>
                        <div class="text-center mt-4">
                            <a href="my_batches.php" class="text-blue-600 hover:text-blue-800 text-sm flex items-center justify-center gap-1">
                                <i class="fas fa-chevron-down"></i>
                                Show <?php echo ($total_batches - 5); ?> more batches
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="table-container p-8 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-gray-300 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Batches Assigned</h3>
                        <p class="text-gray-500 mb-4">You don't have any batches assigned to you yet.</p>
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Contact the administrator to get assigned to batches
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Deadlines -->
            <?php if ($total_batches > 0): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-bell text-red-500"></i>
                        Upcoming Deadlines
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-question-circle text-yellow-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Create Weekly Quiz</h3>
                                    <p class="text-sm text-gray-600">Due: Tomorrow, 10:00 AM</p>
                                </div>
                            </div>
                            <a href="create_quiz.php" class="text-sm bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                Create Now
                            </a>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-line text-blue-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Monthly Progress Report</h3>
                                    <p class="text-sm text-gray-600">Due: 3 days</p>
                                </div>
                            </div>
                            <a href="student_progress.php" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                                Generate Report
                            </a>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-file-alt text-green-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800">Update Syllabus</h3>
                                    <p class="text-sm text-gray-600">Due: End of week</p>
                                </div>
                            </div>
                            <a href="syllabus.php" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                                Update Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>
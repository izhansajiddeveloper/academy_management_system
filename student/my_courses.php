<?php
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

// Handle search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$skill_filter = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get total courses count
$total_query = "SELECT COUNT(*) as total FROM student_enrollments WHERE student_id = ?";
$stmt_total = $conn->prepare($total_query);
$stmt_total->bind_param("i", $student_id);
$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total_courses_row = $total_result->fetch_assoc();
$total_courses = $total_courses_row['total'];

// Get all skills for filter dropdown
$skills_query = "
SELECT DISTINCT s.id, s.skill_name 
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
WHERE se.student_id = ?
ORDER BY s.skill_name
";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $student_id);
$stmt_skills->execute();
$all_skills = $stmt_skills->get_result();

// Get upcoming classes for today - UPDATED TO USE teacher_assignments TABLE
$today = date('Y-m-d');
$today_query = "
SELECT 
    s.skill_name,
    b.batch_name,
    b.start_time,
    b.end_time,
    ses.session_name,
    GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as teacher_names,
    GROUP_CONCAT(DISTINCT t.teacher_code SEPARATOR ', ') as teacher_codes
FROM student_enrollments se
JOIN batches b ON se.batch_id = b.id
JOIN skills s ON se.skill_id = s.id
JOIN sessions ses ON se.session_id = ses.id
LEFT JOIN teacher_assignments ta ON b.id = ta.batch_id
LEFT JOIN teachers t ON ta.teacher_id = t.id AND t.status = 'active'
WHERE se.student_id = ? AND se.status = 'active'
AND b.status = 'active'
GROUP BY s.id, b.id, ses.id, b.batch_name, b.start_time, b.end_time, ses.session_name
ORDER BY b.start_time
";
$stmt_today = $conn->prepare($today_query);
$stmt_today->bind_param("i", $student_id);
$stmt_today->execute();
$today_classes = $stmt_today->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Courses | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-completed {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #6366f1;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #f59e0b;
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
                        <h1 class="text-3xl font-bold text-gray-800">My Courses</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-book-open text-blue-500 mr-2"></i>
                            View and manage all your enrolled courses
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <span class="text-sm font-medium">Total Courses:</span>
                            <span class="font-bold ml-2"><?php echo $total_courses; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter and Search Section -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 mb-2">Filter Courses</h2>
                        <p class="text-sm text-gray-600">Find specific courses using filters</p>
                    </div>

                    <!-- Search Box -->
                    <form method="GET" class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text"
                                name="search"
                                placeholder="Search courses..."
                                value="<?php echo htmlspecialchars($search); ?>"
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            Search
                        </button>
                        <?php if (!empty($search) || $skill_filter > 0 || $status_filter != 'all'): ?>
                            <a href="my_courses.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                                Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" onchange="window.location.href='my_courses.php?status='+this.value+'&search=<?php echo urlencode($search); ?>&skill_id=<?php echo $skill_filter; ?>'"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>

                    <!-- Skill Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Skill/Course</label>
                        <select name="skill_id" onchange="window.location.href='my_courses.php?skill_id='+this.value+'&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>'"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="0">All Skills</option>
                            <?php while ($skill = $all_skills->fetch_assoc()): ?>
                                <option value="<?php echo $skill['id']; ?>" <?php echo $skill_filter == $skill['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Quick Actions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quick Actions</label>
                        <div class="flex gap-2">
                            <a href="progress.php" class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-2 rounded-lg">
                                <i class="fas fa-chart-line mr-2"></i>Progress
                            </a>
                            <a href="attendance.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg">
                                <i class="fas fa-calendar-check mr-2"></i>Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Classes -->
            <?php if ($today_classes->num_rows > 0): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-day text-blue-500"></i>
                        Today's Classes Schedule
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Timing</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($class = $today_classes->fetch_assoc()): ?>
                                    <?php
                                    $current_time = date('H:i:s');
                                    $start_time = strtotime($class['start_time']);
                                    $end_time = strtotime($class['end_time']);
                                    $now_time = strtotime($current_time);
                                    $is_current = ($now_time >= $start_time && $now_time <= $end_time);
                                    ?>
                                    <tr class="hover:bg-gray-50 <?php echo $is_current ? 'bg-blue-50' : ''; ?>">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($class['skill_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($class['batch_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <?php
                                                if (!empty($class['teacher_names'])) {
                                                    echo htmlspecialchars($class['teacher_names']);
                                                    if (!empty($class['teacher_codes'])) {
                                                        echo ' (' . htmlspecialchars($class['teacher_codes']) . ')';
                                                    }
                                                } else {
                                                    echo 'Teacher Not Assigned';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700">
                                                <?php echo date("h:i A", $start_time) . " - " . date("h:i A", $end_time); ?>
                                                <?php if ($is_current): ?>
                                                    <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                                        <i class="fas fa-circle text-green-500 mr-1 text-xs"></i>Now
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($class['session_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <a href="materials.php" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                                                    <i class="fas fa-download"></i> Materials
                                                </a>
                                                <a href="syllabus.php" class="text-green-600 hover:text-green-800 text-sm flex items-center gap-1">
                                                    <i class="fas fa-book"></i> Syllabus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Classes Today -->
                <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar-times text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-4">No Classes Today</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        You have no classes scheduled for today. Enjoy your day off!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>
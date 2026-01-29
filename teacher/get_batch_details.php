<?php
// File: teacher/get_batch_details.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only allow AJAX requests from teachers
if ($_SESSION['user_type'] !== 'teacher') {
    die('Access denied');
}

if (!isset($_GET['batch_id'])) {
    die('Batch ID required');
}

$batch_id = intval($_GET['batch_id']);
$user_id = $_SESSION['user_id'];

// Verify teacher has access to this batch
$verify_query = "
SELECT ta.* FROM teacher_assignments ta
JOIN teachers t ON ta.teacher_id = t.id
WHERE t.user_id = ? AND ta.batch_id = ?
";
$stmt_verify = $conn->prepare($verify_query);
$stmt_verify->bind_param("ii", $user_id, $batch_id);
$stmt_verify->execute();
$verify_result = $stmt_verify->get_result();

if ($verify_result->num_rows === 0) {
    die('Access denied to this batch');
}

// Get batch details
$query = "
SELECT 
    b.*,
    s.skill_name,
    s.description as skill_description,
    se.session_name,
   
    COUNT(DISTINCT se_student.student_id) as enrolled_students
FROM batches b
JOIN skills s ON b.skill_id = s.id
JOIN sessions se ON b.session_id = se.id
LEFT JOIN student_enrollments se_student ON b.id = se_student.batch_id AND se_student.status = 'active'
WHERE b.id = ?
GROUP BY b.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();

if (!$batch) {
    die('Batch not found');
}

// Get attendance sessions count (safely)
try {
    $attendance_query = "SELECT COUNT(*) as total_attendance_sessions FROM student_attendance WHERE batch_id = ?";
    $stmt_att = $conn->prepare($attendance_query);
    $stmt_att->bind_param("i", $batch_id);
    $stmt_att->execute();
    $attendance_result = $stmt_att->get_result()->fetch_assoc();
    $total_attendance_sessions = $attendance_result['total_attendance_sessions'] ?? 0;
} catch (Exception $e) {
    $total_attendance_sessions = 0;
}

// Get quizzes count (safely)
try {
    $quizzes_query = "SELECT COUNT(*) as total_quizzes FROM quizzes WHERE batch_id = ?";
    $stmt_quiz = $conn->prepare($quizzes_query);
    $stmt_quiz->bind_param("i", $batch_id);
    $stmt_quiz->execute();
    $quizzes_result = $stmt_quiz->get_result()->fetch_assoc();
    $total_quizzes = $quizzes_result['total_quizzes'] ?? 0;
} catch (Exception $e) {
    $total_quizzes = 0;
}

// Get materials count (safely)
try {
    $materials_query = "SELECT COUNT(*) as total_materials FROM teaching_materials WHERE batch_id = ?";
    $stmt_mat = $conn->prepare($materials_query);
    $stmt_mat->bind_param("i", $batch_id);
    $stmt_mat->execute();
    $materials_result = $stmt_mat->get_result()->fetch_assoc();
    $total_materials = $materials_result['total_materials'] ?? 0;
} catch (Exception $e) {
    $total_materials = 0;
}

// Get students in this batch
$students_query = "
SELECT 
    s.id,
    s.name,
    s.student_code,
    s.phone
FROM student_enrollments se
JOIN students s ON se.student_id = s.id
WHERE se.batch_id = ? AND se.status = 'active'
ORDER BY s.name
";

$stmt_students = $conn->prepare($students_query);
$stmt_students->bind_param("i", $batch_id);
$stmt_students->execute();
$students = $stmt_students->get_result();
?>

<div class="space-y-6">
    <!-- Batch Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h4 class="font-medium text-gray-800 mb-2">Batch Information</h4>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Batch Name:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($batch['batch_name']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Skill/Course:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($batch['skill_name']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Session:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($batch['session_name']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Timing:</dt>
                    <dd class="font-medium">
                        <?php echo date('h:i A', strtotime($batch['start_time'])); ?> -
                        <?php echo date('h:i A', strtotime($batch['end_time'])); ?>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Max Students:</dt>
                    <dd class="font-medium"><?php echo $batch['max_students']; ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Enrolled Students:</dt>
                    <dd class="font-medium"><?php echo $batch['enrolled_students']; ?></dd>
                </div>
                <?php if (!empty($batch['start_date'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Start Date:</dt>
                        <dd class="font-medium"><?php echo date('M d, Y', strtotime($batch['start_date'])); ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($batch['end_date'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">End Date:</dt>
                        <dd class="font-medium"><?php echo date('M d, Y', strtotime($batch['end_date'])); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>

        <div>
            <h4 class="font-medium text-gray-800 mb-2">Batch Statistics</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded-lg text-center">
                    <p class="text-sm text-gray-600">Attendance Sessions</p>
                    <p class="text-xl font-bold text-blue-600"><?php echo $total_attendance_sessions; ?></p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg text-center">
                    <p class="text-sm text-gray-600">Quizzes Created</p>
                    <p class="text-xl font-bold text-green-600"><?php echo $total_quizzes; ?></p>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg text-center">
                    <p class="text-sm text-gray-600">Materials Uploaded</p>
                    <p class="text-xl font-bold text-purple-600"><?php echo $total_materials; ?></p>
                </div>
                <div class="bg-yellow-50 p-3 rounded-lg text-center">
                    <p class="text-sm text-gray-600">Capacity</p>
                    <p class="text-xl font-bold text-yellow-600">
                        <?php echo round(($batch['enrolled_students'] / $batch['max_students']) * 100, 1); ?>%
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div>
        <div class="flex justify-between items-center mb-4">
            <h4 class="font-medium text-gray-800">Enrolled Students (<?php echo $students->num_rows; ?>)</h4>
            <a href="attendance.php?batch_id=<?php echo $batch_id; ?>"
                class="text-sm text-blue-600 hover:text-blue-800">
                <i class="fas fa-clipboard-check mr-1"></i> Take Attendance
            </a>
        </div>

        <?php if ($students->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="py-2 px-3 text-left">Student Name</th>
                            <th class="py-2 px-3 text-left">Code</th>
                            <th class="py-2 px-3 text-left">Phone</th>
                            <th class="py-2 px-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-3">
                                    <a href="student_progress.php?student_id=<?php echo $student['id']; ?>&batch_id=<?php echo $batch_id; ?>"
                                        class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center gap-1">
                                        <i class="fas fa-chart-line"></i>
                                        <span>Progress</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">No students enrolled in this batch yet.</p>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="pt-4 border-t border-gray-100">
        <h4 class="font-medium text-gray-800 mb-4">Quick Actions</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="attendance.php?batch_id=<?php echo $batch_id; ?>"
                class="bg-blue-50 hover:bg-blue-100 text-blue-700 p-3 rounded-lg text-center transition-colors">
                <i class="fas fa-clipboard-check text-lg mb-2 block"></i>
                <span class="text-sm font-medium">Take Attendance</span>
            </a>
            <a href="quizzes.php?batch_id=<?php echo $batch_id; ?>"
                class="bg-green-50 hover:bg-green-100 text-green-700 p-3 rounded-lg text-center transition-colors">
                <i class="fas fa-question-circle text-lg mb-2 block"></i>
                <span class="text-sm font-medium">Manage Quizzes</span>
            </a>
            <a href="syllabus.php?batch_id=<?php echo $batch_id; ?>"
                class="bg-purple-50 hover:bg-purple-100 text-purple-700 p-3 rounded-lg text-center transition-colors">
                <i class="fas fa-book text-lg mb-2 block"></i>
                <span class="text-sm font-medium">Update Syllabus</span>
            </a>
            <a href="materials.php?batch_id=<?php echo $batch_id; ?>"
                class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 p-3 rounded-lg text-center transition-colors">
                <i class="fas fa-file-upload text-lg mb-2 block"></i>
                <span class="text-sm font-medium">Upload Materials</span>
            </a>
        </div>
    </div>
</div>
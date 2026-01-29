<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['user_type'] !== 'teacher') {
    die('Access denied');
}

if (!isset($_GET['id'])) {
    die('ID required');
}

$attendance_id = intval($_GET['id']);
$teacher_id = $_SESSION['teacher_id'] ?? 0;

// Get attendance details
$query = "
SELECT 
    sa.*,
    s.name as student_name,
    s.student_code,
    s.phone,

    s.address,
    b.batch_name,
    sk.skill_name,
    se.session_name,
    t.name as teacher_name,
    t.teacher_code
FROM student_attendance sa
JOIN students s ON sa.student_id = s.id
JOIN batches b ON sa.batch_id = b.id
JOIN skills sk ON sa.skill_id = sk.id
JOIN sessions se ON sa.session_id = se.id
JOIN teachers t ON sa.marked_by = t.id
WHERE sa.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $attendance_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();

if (!$attendance) {
    die('Attendance record not found');
}

// Verify teacher has access
if ($attendance['marked_by'] != $teacher_id) {
    die('Access denied');
}
?>

<div class="space-y-6">
    <!-- Basic Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h4 class="font-medium text-gray-800 mb-2">Attendance Information</h4>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Date:</dt>
                    <dd class="font-medium"><?php echo date('F j, Y', strtotime($attendance['attendance_date'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Status:</dt>
                    <dd class="font-medium">
                        <?php
                        $status_classes = [
                            'present' => 'text-green-600 bg-green-100',
                            'absent' => 'text-red-600 bg-red-100',
                            'late' => 'text-yellow-600 bg-yellow-100',
                            'leave' => 'text-blue-600 bg-blue-100'
                        ];
                        ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $status_classes[$attendance['attendance_status']] ?? 'bg-gray-100'; ?>">
                            <?php echo ucfirst($attendance['attendance_status']); ?>
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Percentage:</dt>
                    <dd class="font-medium"><?php echo $attendance['attendance_percentage']; ?>%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Marked At:</dt>
                    <dd class="font-medium"><?php echo date('h:i A', strtotime($attendance['created_at'])); ?></dd>
                </div>
            </dl>
        </div>

        <div>
            <h4 class="font-medium text-gray-800 mb-2">Student Information</h4>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-600">Name:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($attendance['student_name']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Student Code:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($attendance['student_code']); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Phone:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($attendance['phone'] ?? 'N/A'); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600">Email:</dt>
                    <dd class="font-medium"><?php echo htmlspecialchars($attendance['email'] ?? 'N/A'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Batch Information -->
    <div>
        <h4 class="font-medium text-gray-800 mb-2">Batch Information</h4>
        <dl class="space-y-2">
            <div class="flex justify-between">
                <dt class="text-gray-600">Batch Name:</dt>
                <dd class="font-medium"><?php echo htmlspecialchars($attendance['batch_name']); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-600">Skill/Course:</dt>
                <dd class="font-medium"><?php echo htmlspecialchars($attendance['skill_name']); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-600">Session:</dt>
                <dd class="font-medium"><?php echo htmlspecialchars($attendance['session_name']); ?></dd>
            </div>
        </dl>
    </div>

    <!-- Teacher Information -->
    <div>
        <h4 class="font-medium text-gray-800 mb-2">Marked By</h4>
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chalkboard-teacher text-blue-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($attendance['teacher_name']); ?></p>
                <p class="text-sm text-gray-600">Teacher Code: <?php echo htmlspecialchars($attendance['teacher_code']); ?></p>
            </div>
        </div>
    </div>

    <!-- Remarks -->
    <?php if (!empty($attendance['remarks'])): ?>
        <div class="pt-4 border-t border-gray-100">
            <h4 class="font-medium text-gray-800 mb-2">Remarks</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($attendance['remarks'])); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="pt-4 border-t border-gray-100">
        <div class="flex gap-3">
            <a href="attendance.php?batch_id=<?php echo $attendance['batch_id']; ?>&date=<?php echo $attendance['attendance_date']; ?>"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="fas fa-edit mr-2"></i> Edit Attendance
            </a>
            <button onclick="deleteAttendance(<?php echo $attendance_id; ?>)"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="fas fa-trash mr-2"></i> Delete Record
            </button>
        </div>
    </div>
</div>
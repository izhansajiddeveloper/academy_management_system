<?php
require_once __DIR__ . '/../../config/db.php';


$id = intval($_GET['id']);
$batch_res = mysqli_query($conn, "SELECT * FROM batches WHERE id=$id");
$batch = mysqli_fetch_assoc($batch_res);

if (!$batch) {
    echo "<div class='p-6'>Batch not found!</div>";
    exit;
}

// Check if batch has teacher assigned
$teacher_query = mysqli_query($conn, "
    SELECT t.name 
    FROM batch_teachers bt 
    JOIN teachers t ON bt.teacher_id = t.id 
    WHERE bt.batch_id = $id AND bt.status = 'active'
");
$current_teacher = mysqli_fetch_assoc($teacher_query);

$skills = mysqli_query($conn, "SELECT * FROM skills WHERE status='active' ORDER BY skill_name");
$sessions = mysqli_query($conn, "SELECT * FROM sessions WHERE status='active' ORDER BY session_name");

if (isset($_POST['submit'])) {
    $skill_id = intval($_POST['skill_id']);
    $session_id = intval($_POST['session_id']);
    $batch_name = mysqli_real_escape_string($conn, $_POST['batch_name']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_students = intval($_POST['max_students']);
    $status = $_POST['status'];

    mysqli_query($conn, "
        UPDATE batches 
        SET skill_id=$skill_id, session_id=$session_id, batch_name='$batch_name', 
            start_time='$start_time', end_time='$end_time', max_students=$max_students, status='$status'
        WHERE id=$id
    ");

    header("Location: batches.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Batch | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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


        .form-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }

        .status-completed {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #d97706 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
        }

        .info-badge {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Edit Batch</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-edit text-yellow-500 mr-2"></i>
                        Update batch information and status
                    </p>
                </div>
                <div>
                    <a href="batches.php"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-3 rounded-lg font-medium hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Batches
                    </a>
                </div>
            </div>

            <!-- Current Batch Info -->
            <div class="max-w-2xl mx-auto mb-6">
                <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-info-circle text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Batch Information</h3>
                                <p class="text-sm text-gray-500">Editing: <span class="font-medium"><?= htmlspecialchars($batch['batch_name']) ?></span></p>
                            </div>
                        </div>
                        <span class="<?= $batch['status'] == 'active' ? 'status-active' : 'status-completed' ?> status-badge">
                            <i class="fas fa-circle text-xs"></i>
                            <?= ucfirst($batch['status']) ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-clock text-yellow-500 mr-2"></i>
                                Time: <?= date('h:i A', strtotime($batch['start_time'])) ?> - <?= date('h:i A', strtotime($batch['end_time'])) ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-users text-green-500 mr-2"></i>
                                Max Students: <?= $batch['max_students'] ?>
                            </span>
                        </div>
                        <?php if ($current_teacher): ?>
                            <div class="md:col-span-2">
                                <span class="info-badge">
                                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                                    Teacher: <?= htmlspecialchars($current_teacher['name']) ?>
                                </span>
                                <a href="assign_teacher.php?batch_id=<?= $id ?>" class="ml-2 text-sm text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-edit mr-1"></i>Change Teacher
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="md:col-span-2">
                                <span class="text-sm text-red-500">
                                    <i class="fas fa-user-times mr-1"></i>
                                    No teacher assigned
                                </span>
                                <a href="assign_teacher.php?batch_id=<?= $id ?>" class="ml-2 text-sm text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-user-plus mr-1"></i>Assign Teacher
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="max-w-2xl mx-auto">
                <div class="form-card p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-edit text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Update Batch Details</h2>
                            <p class="text-sm text-gray-500">Modify the batch information as needed</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-6">
                        <!-- Batch Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-heading text-blue-500 mr-2"></i>
                                Batch Name *
                            </label>
                            <input type="text"
                                name="batch_name"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= htmlspecialchars($batch['batch_name']) ?>"
                                required>
                        </div>

                        <!-- Skill Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-book text-purple-500 mr-2"></i>
                                Select Skill *
                            </label>
                            <select name="skill_id"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="">-- Select Skill --</option>
                                <?php mysqli_data_seek($skills, 0);
                                while ($s = mysqli_fetch_assoc($skills)) { ?>
                                    <option value="<?= $s['id'] ?>" <?= $batch['skill_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['skill_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Session Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                Select Session *
                            </label>
                            <select name="session_id"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="">-- Select Session --</option>
                                <?php mysqli_data_seek($sessions, 0);
                                while ($se = mysqli_fetch_assoc($sessions)) { ?>
                                    <option value="<?= $se['id'] ?>" <?= $batch['session_id'] == $se['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($se['session_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Time Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-clock text-yellow-500 mr-2"></i>
                                    Start Time *
                                </label>
                                <input type="time"
                                    name="start_time"
                                    class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value="<?= $batch['start_time'] ?>"
                                    required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-clock text-red-500 mr-2"></i>
                                    End Time *
                                </label>
                                <input type="time"
                                    name="end_time"
                                    class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value="<?= $batch['end_time'] ?>"
                                    required>
                            </div>
                        </div>

                        <!-- Max Students -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-users text-green-500 mr-2"></i>
                                Maximum Students *
                            </label>
                            <input type="number"
                                name="max_students"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="<?= $batch['max_students'] ?>"
                                min="1"
                                max="100"
                                required>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-toggle-on text-blue-500 mr-2"></i>
                                Batch Status
                            </label>
                            <select name="status"
                                class="w-full px-4 py-3 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" <?= $batch['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= $batch['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <div class="mt-2 space-y-1">
                                <p class="text-xs <?= $batch['status'] == 'active' ? 'text-green-600' : 'text-yellow-600' ?>">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?= $batch['status'] == 'active'
                                        ? 'Active batches appear on the main batches page'
                                        : 'Completed batches are moved to the completed batches page' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4 border-t border-gray-100">
                            <button type="submit"
                                name="submit"
                                class="flex-1 btn-primary px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>
                                Update Batch
                            </button>
                            <a href="batches.php"
                                class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update status info when select changes
            const statusSelect = document.querySelector('select[name="status"]');
            const statusInfo = document.querySelector('.text-xs');

            if (statusSelect && statusInfo) {
                statusSelect.addEventListener('change', function() {
                    if (this.value === 'active') {
                        statusInfo.textContent = 'Active batches appear on the main batches page';
                        statusInfo.classList.remove('text-yellow-600');
                        statusInfo.classList.add('text-green-600');
                    } else {
                        statusInfo.textContent = 'Completed batches are moved to the completed batches page';
                        statusInfo.classList.remove('text-green-600');
                        statusInfo.classList.add('text-yellow-600');
                    }
                });
            }

            // Time validation
            const startTime = document.querySelector('input[name="start_time"]');
            const endTime = document.querySelector('input[name="end_time"]');

            if (startTime && endTime) {
                startTime.addEventListener('change', function() {
                    if (endTime.value && this.value >= endTime.value) {
                        alert('Start time must be before end time');
                        this.value = '';
                    }
                });

                endTime.addEventListener('change', function() {
                    if (startTime.value && this.value <= startTime.value) {
                        alert('End time must be after start time');
                        this.value = '';
                    }
                });
            }
        });
    </script>
</body>

</html>
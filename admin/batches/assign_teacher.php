<?php
require_once __DIR__ . '/../../config/db.php';


if (!isset($_GET['batch_id'])) {
    header("Location: batches.php");
    exit;
}

$batch_id = intval($_GET['batch_id']);

/* ===========================
   FETCH BATCH DETAILS
=========================== */
$batch_q = mysqli_query($conn, "
    SELECT b.*, s.skill_name, se.session_name
    FROM batches b
    JOIN skills s ON b.skill_id = s.id
    JOIN sessions se ON b.session_id = se.id
    WHERE b.id = $batch_id
");

$batch = mysqli_fetch_assoc($batch_q);

if (!$batch) {
    header("Location: batches.php");
    exit;
}

/* ===========================
   HANDLE ASSIGNMENT
=========================== */
if (isset($_POST['assign_teacher'])) {

    $teacher_id = intval($_POST['teacher_id']);

    // Inactivate old teacher assignment
    mysqli_query($conn, "
        UPDATE batch_teachers 
        SET status = 'inactive' 
        WHERE batch_id = $batch_id AND status = 'active'
    ");

    // Assign new teacher
    mysqli_query($conn, "
        INSERT INTO batch_teachers (batch_id, teacher_id)
        VALUES ($batch_id, $teacher_id)
    ");

    header("Location: batches.php?assigned=success");
    exit;
}

/* ===========================
   FETCH TEACHERS
=========================== */
$teachers = mysqli_query($conn, "
    SELECT id, name
    FROM teachers
    WHERE status = 'active'
    ORDER BY name
");


/* ===========================
   FETCH CURRENT TEACHER
=========================== */
$current_teacher_q = mysqli_query($conn, "
    SELECT t.id, t.name
    FROM batch_teachers bt
    JOIN teachers t ON bt.teacher_id = t.id
    WHERE bt.batch_id = $batch_id AND bt.status = 'active'
");


$current_teacher = mysqli_fetch_assoc($current_teacher_q);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assign Teacher | Academy Management System</title>
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .batch-info-card {
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f9ff 100%);
            border-radius: 12px;
            border: 1px solid #dbeafe;
        }

        .teacher-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .teacher-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
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
                    <h1 class="text-3xl font-bold text-gray-800">Assign Teacher</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-user-tie text-purple-500 mr-2"></i>
                        Assign or change teacher for this batch
                    </p>
                </div>
                <div>
                    <a href="batches.php"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-3 rounded-lg font-medium hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Batches
                    </a>
                </div>
            </div>

            <!-- Batch Information -->
            <div class="max-w-4xl mx-auto mb-8">
                <div class="batch-info-card p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Batch Information</h2>
                            <p class="text-sm text-gray-600">Assigning teacher to the following batch</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Batch Name</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($batch['batch_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Skill</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($batch['skill_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Session</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($batch['session_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Time</p>
                            <p class="font-medium text-gray-800">
                                <?= date('h:i A', strtotime($batch['start_time'])) ?> - <?= date('h:i A', strtotime($batch['end_time'])) ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($current_teacher): ?>
                        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-green-800 mb-1">Currently Assigned Teacher</h4>
                                    <p class="text-green-600">
                                        <i class="fas fa-chalkboard-teacher mr-2"></i>
                                        <?= htmlspecialchars($current_teacher['name']) ?>
                                    </p>
                                    <?php if (!empty($current_teacher['name'])): ?>
                                        <p class="text-sm text-green-500 mt-1">
                                            <i class="fas fa-user mr-2"></i>
                                            <?= htmlspecialchars($current_teacher['name']) ?>
                                        </p>


                                    <?php endif; ?>
                                </div>
                                <div class="text-xs bg-green-100 text-green-800 px-3 py-1 rounded-full">
                                    Currently Assigned
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-yellow-800">No Teacher Assigned</h4>
                                    <p class="text-sm text-yellow-600">This batch doesn't have a teacher assigned yet.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teacher Assignment Form -->
            <div class="max-w-4xl mx-auto">
                <div class="form-card p-8">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-tie text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Select Teacher</h2>
                            <p class="text-sm text-gray-500">Choose a teacher to assign to this batch</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-6">
                        <!-- Teacher Selection -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 mb-4">
                                <i class="fas fa-chalkboard-teacher text-purple-500 mr-2"></i>
                                Available Teachers
                            </label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                                    <label class="teacher-card p-4 cursor-pointer hover:border-purple-300">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-shrink-0">
                                                <input type="radio"
                                                    name="teacher_id"
                                                    value="<?= $t['id'] ?>"
                                                    class="h-4 w-4 text-purple-600 border-gray-300 focus:ring-purple-500"
                                                    <?= $current_teacher && $current_teacher['name'] == $t['name'] ? 'checked' : '' ?>
                                                    required>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <h4 class="font-medium text-gray-900"><?= htmlspecialchars($t['name']) ?></h4>
                                                    <?php if ($current_teacher && $current_teacher['name'] == $t['name']): ?>
                                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                                            Current
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <i class="fas fa-user mr-1"></i>
                                                    <?= htmlspecialchars($t['name']) ?>
                                                </p>

                                            </div>
                                        </div>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- Important Notes -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-yellow-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-yellow-800 mb-1">Important Information</h4>
                                    <ul class="text-sm text-yellow-600 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-info-circle text-xs"></i>
                                            <span>Assigning a new teacher will deactivate the current teacher assignment</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-info-circle text-xs"></i>
                                            <span>Only active teachers are shown in the list</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-info-circle text-xs"></i>
                                            <span>You can change the teacher anytime by re-assigning</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4 border-t border-gray-100">
                            <button type="submit"
                                name="assign_teacher"
                                class="flex-1 btn-primary px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-user-tie"></i>
                                Assign Teacher
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
            // Add click effect to teacher cards
            const teacherCards = document.querySelectorAll('.teacher-card');
            teacherCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.type !== 'radio') {
                        const radio = this.querySelector('input[type="radio"]');
                        if (radio) {
                            radio.checked = true;
                            // Remove selected style from all cards
                            teacherCards.forEach(c => c.classList.remove('border-purple-500', 'bg-purple-50'));
                            // Add selected style to clicked card
                            this.classList.add('border-purple-500', 'bg-purple-50');
                        }
                    }
                });
            });

            // Style the currently checked radio on page load
            const checkedRadio = document.querySelector('input[name="teacher_id"]:checked');
            if (checkedRadio) {
                const checkedCard = checkedRadio.closest('.teacher-card');
                if (checkedCard) {
                    checkedCard.classList.add('border-purple-500', 'bg-purple-50');
                }
            }
        });
    </script>
</body>

</html>
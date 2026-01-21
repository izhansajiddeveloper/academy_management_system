<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)$_GET['id'];
$skill = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM skills WHERE id=$id"));

if (!$skill) {
    die("Skill not found!");
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['skill_name']);
    $duration = (int)$_POST['duration_months'];
    $level = $_POST['level'];
    $description = trim($_POST['description']);
    $has_syllabus = isset($_POST['has_syllabus']) ? 1 : 0;
    $has_practice = isset($_POST['has_practice']) ? 1 : 0;

    // Validation
    if (empty($name) || empty($duration) || empty($level)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if skill name already exists (excluding current skill)
        $check_sql = "SELECT id FROM skills WHERE skill_name = '$name' AND id != $id AND status='active'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "A skill with this name already exists.";
        } else {
            $sql = "UPDATE skills
                    SET skill_name='$name', duration_months=$duration, level='$level',
                        description='$description', has_syllabus=$has_syllabus, has_practice=$has_practice,
                        updated_at=NOW()
                    WHERE id=$id";

            if (mysqli_query($conn, $sql)) {
                $success_message = "Skill updated successfully!";
                // Refresh skill data
                $skill = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM skills WHERE id=$id"));
            } else {
                $error_message = "Error updating skill: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Skill | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .required:after {
            content: " *";
            color: #ef4444;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }

        .skill-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .level-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .level-beginner {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }

        .level-intermediate {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
        }

        .level-advanced {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
        }

        .duration-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .feature-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .feature-card.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .level-option {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .level-option.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR (Same as before) -->
         <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Edit Skill / Course</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-edit text-blue-500 mr-2"></i>
                        Update skill information and configuration
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="skills.php"
                        class="bg-white text-gray-700 px-5 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Skills
                    </a>
                </div>
            </div>

            <!-- Skill Header Info -->
            <div class="skill-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($skill['skill_name']); ?></h2>
                        <div class="flex flex-wrap gap-4">
                            <div>
                                <div class="text-sm opacity-80">Skill ID</div>
                                <div class="font-mono font-bold">#<?php echo $skill['id']; ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Level</div>
                                <?php
                                $level_badge_class = '';
                                switch ($skill['level']) {
                                    case 'Beginner':
                                        $level_badge_class = 'level-beginner';
                                        break;
                                    case 'Intermediate':
                                        $level_badge_class = 'level-intermediate';
                                        break;
                                    case 'Advanced':
                                        $level_badge_class = 'level-advanced';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $level_badge_class; ?> level-badge">
                                    <i class="fas fa-signal text-xs"></i>
                                    <?php echo $skill['level']; ?>
                                </span>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Duration</div>
                                <span class="duration-badge">
                                    <i class="fas fa-calendar-alt text-xs"></i>
                                    <?php echo $skill['duration_months']; ?> months
                                </span>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Created</div>
                                <div class="font-medium"><?php echo date('M d, Y', strtotime($skill['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-green-800">Success!</p>
                        <p class="text-green-600 text-sm"><?php echo $success_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-red-800">Error!</p>
                        <p class="text-red-600 text-sm"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- System Info -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-sm text-gray-500 mb-1">Syllabus Available</div>
                    <div class="font-semibold text-gray-800">
                        <?php echo $skill['has_syllabus'] ? '✅ Yes' : '❌ No'; ?>
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-sm text-gray-500 mb-1">Practice Sessions</div>
                    <div class="font-semibold text-gray-800">
                        <?php echo $skill['has_practice'] ? '✅ Yes' : '❌ No'; ?>
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-sm text-gray-500 mb-1">Last Updated</div>
                    <div class="font-semibold text-gray-800">
                        <?php
                        if ($skill['updated_at'] && $skill['updated_at'] != '0000-00-00 00:00:00') {
                            echo date('M d, Y', strtotime($skill['updated_at']));
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-sm text-gray-500 mb-1">Status</div>
                    <div class="font-semibold text-green-600">
                        <?php echo ucfirst($skill['status']); ?>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form method="post" class="form-card p-8 max-w-4xl mx-auto">
                <!-- Basic Information -->
                <div class="mb-8">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </h3>

                    <div class="space-y-6">
                        <div>
                            <label class="form-label required">Skill/Course Name</label>
                            <input type="text"
                                name="skill_name"
                                placeholder="Enter skill name"
                                required
                                class="form-input"
                                value="<?php echo htmlspecialchars($skill['skill_name']); ?>">
                        </div>

                        <div>
                            <label class="form-label required">Description</label>
                            <textarea name="description"
                                rows="4"
                                placeholder="Describe the skill or course..."
                                class="form-input"><?php echo htmlspecialchars($skill['description']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label required">Duration (Months)</label>
                                <div class="relative">
                                    <input type="number"
                                        name="duration_months"
                                        placeholder="Months"
                                        required
                                        min="1"
                                        max="24"
                                        class="form-input"
                                        value="<?php echo $skill['duration_months']; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label required">Skill Level</label>
                                <div class="grid grid-cols-3 gap-3" id="levelOptions">
                                    <div class="level-option text-center <?php echo $skill['level'] == 'Beginner' ? 'active' : ''; ?>" data-value="Beginner">
                                        <div class="w-10 h-10 rounded-full <?php echo $skill['level'] == 'Beginner' ? 'bg-green-100' : 'bg-gray-100'; ?> flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-seedling <?php echo $skill['level'] == 'Beginner' ? 'text-green-600' : 'text-gray-500'; ?>"></i>
                                        </div>
                                        <div class="text-sm font-medium">Beginner</div>
                                    </div>
                                    <div class="level-option text-center <?php echo $skill['level'] == 'Intermediate' ? 'active' : ''; ?>" data-value="Intermediate">
                                        <div class="w-10 h-10 rounded-full <?php echo $skill['level'] == 'Intermediate' ? 'bg-blue-100' : 'bg-gray-100'; ?> flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-chart-line <?php echo $skill['level'] == 'Intermediate' ? 'text-blue-600' : 'text-gray-500'; ?>"></i>
                                        </div>
                                        <div class="text-sm font-medium">Intermediate</div>
                                    </div>
                                    <div class="level-option text-center <?php echo $skill['level'] == 'Advanced' ? 'active' : ''; ?>" data-value="Advanced">
                                        <div class="w-10 h-10 rounded-full <?php echo $skill['level'] == 'Advanced' ? 'bg-purple-100' : 'bg-gray-100'; ?> flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-rocket <?php echo $skill['level'] == 'Advanced' ? 'text-purple-600' : 'text-gray-500'; ?>"></i>
                                        </div>
                                        <div class="text-sm font-medium">Advanced</div>
                                    </div>
                                </div>
                                <input type="hidden" name="level" id="selectedLevel" value="<?php echo $skill['level']; ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="mb-8">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i> Course Features
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="feature-card <?php echo $skill['has_syllabus'] ? 'active' : ''; ?>" id="syllabusFeature">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 <?php echo $skill['has_syllabus'] ? 'bg-blue-100' : 'bg-gray-100'; ?> rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-alt <?php echo $skill['has_syllabus'] ? 'text-blue-600' : 'text-gray-500'; ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Syllabus Available</h4>
                                    <p class="text-sm text-gray-500">Course content and structure</p>
                                </div>
                            </div>
                            <input type="checkbox" name="has_syllabus" class="hidden" value="1" <?php echo $skill['has_syllabus'] ? 'checked' : ''; ?>>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Enable syllabus for this course</span>
                                <div class="relative">
                                    <div class="w-10 h-6 <?php echo $skill['has_syllabus'] ? 'bg-green-500' : 'bg-gray-300'; ?> rounded-full toggle-switch">
                                        <div class="toggle-circle" style="transform: translateX(<?php echo $skill['has_syllabus'] ? '20px' : '0'; ?>);"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="feature-card <?php echo $skill['has_practice'] ? 'active' : ''; ?>" id="practiceFeature">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 <?php echo $skill['has_practice'] ? 'bg-green-100' : 'bg-gray-100'; ?> rounded-lg flex items-center justify-center">
                                    <i class="fas fa-flask <?php echo $skill['has_practice'] ? 'text-green-600' : 'text-gray-500'; ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Practice Sessions</h4>
                                    <p class="text-sm text-gray-500">Hands-on exercises and labs</p>
                                </div>
                            </div>
                            <input type="checkbox" name="has_practice" class="hidden" value="1" <?php echo $skill['has_practice'] ? 'checked' : ''; ?>>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Include practice exercises</span>
                                <div class="relative">
                                    <div class="w-10 h-6 <?php echo $skill['has_practice'] ? 'bg-green-500' : 'bg-gray-300'; ?> rounded-full toggle-switch">
                                        <div class="toggle-circle" style="transform: translateX(<?php echo $skill['has_practice'] ? '20px' : '0'; ?>);"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center mt-8 pt-8 border-t border-gray-200">
                    <div>
                        <a href="skills.php?delete=<?php echo $id; ?>"
                            onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($skill['skill_name']); ?>? This will remove it from available courses.')"
                            class="bg-red-50 text-red-700 px-6 py-3 rounded-lg font-medium hover:bg-red-100 transition-all duration-300">
                            <i class="fas fa-trash mr-2"></i> Delete Skill
                        </a>
                    </div>
                    <div class="flex gap-4">
                        <a href="skills.php"
                            class="bg-white text-gray-700 px-6 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        <button type="submit"
                            class="btn-primary flex items-center gap-2">
                            <i class="fas fa-save"></i> Update Skill
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Level selection
            const levelOptions = document.querySelectorAll('.level-option');
            const selectedLevelInput = document.getElementById('selectedLevel');

            levelOptions.forEach(option => {
                option.addEventListener('click', function() {
                    levelOptions.forEach(opt => {
                        opt.classList.remove('active');
                        opt.querySelector('div').classList.remove('bg-green-100', 'bg-blue-100', 'bg-purple-100');
                        opt.querySelector('div').classList.add('bg-gray-100');
                        opt.querySelector('i').classList.remove('text-green-600', 'text-blue-600', 'text-purple-600');
                        opt.querySelector('i').classList.add('text-gray-500');
                    });

                    this.classList.add('active');
                    const value = this.dataset.value;
                    selectedLevelInput.value = value;

                    // Update icon color based on selection
                    const iconDiv = this.querySelector('div');
                    const icon = this.querySelector('i');

                    switch (value) {
                        case 'Beginner':
                            iconDiv.classList.remove('bg-gray-100');
                            iconDiv.classList.add('bg-green-100');
                            icon.classList.remove('text-gray-500');
                            icon.classList.add('text-green-600');
                            break;
                        case 'Intermediate':
                            iconDiv.classList.remove('bg-gray-100');
                            iconDiv.classList.add('bg-blue-100');
                            icon.classList.remove('text-gray-500');
                            icon.classList.add('text-blue-600');
                            break;
                        case 'Advanced':
                            iconDiv.classList.remove('bg-gray-100');
                            iconDiv.classList.add('bg-purple-100');
                            icon.classList.remove('text-gray-500');
                            icon.classList.add('text-purple-600');
                            break;
                    }
                });
            });

            // Feature toggles
            const syllabusFeature = document.getElementById('syllabusFeature');
            const practiceFeature = document.getElementById('practiceFeature');

            [syllabusFeature, practiceFeature].forEach(feature => {
                feature.addEventListener('click', function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    const toggleSwitch = this.querySelector('.toggle-switch');
                    const toggleCircle = this.querySelector('.toggle-circle');
                    const iconDiv = this.querySelector('.w-10.h-10');
                    const icon = this.querySelector('i');

                    checkbox.checked = !checkbox.checked;

                    if (checkbox.checked) {
                        this.classList.add('active');
                        toggleSwitch.classList.remove('bg-gray-300');
                        toggleSwitch.classList.add('bg-green-500');
                        toggleCircle.style.transform = 'translateX(20px)';

                        // Update icon colors
                        if (this.id === 'syllabusFeature') {
                            iconDiv.classList.remove('bg-gray-100');
                            iconDiv.classList.add('bg-blue-100');
                            icon.classList.remove('text-gray-500');
                            icon.classList.add('text-blue-600');
                        } else {
                            iconDiv.classList.remove('bg-gray-100');
                            iconDiv.classList.add('bg-green-100');
                            icon.classList.remove('text-gray-500');
                            icon.classList.add('text-green-600');
                        }
                    } else {
                        this.classList.remove('active');
                        toggleSwitch.classList.remove('bg-green-500');
                        toggleSwitch.classList.add('bg-gray-300');
                        toggleCircle.style.transform = 'translateX(0)';

                        // Update icon colors
                        iconDiv.classList.remove('bg-blue-100', 'bg-green-100');
                        iconDiv.classList.add('bg-gray-100');
                        icon.classList.remove('text-blue-600', 'text-green-600');
                        icon.classList.add('text-gray-500');
                    }
                });
            });

            // Initialize toggle switches
            const toggleSwitches = document.querySelectorAll('.toggle-switch');
            toggleSwitches.forEach(switchEl => {
                const toggleCircle = switchEl.querySelector('.toggle-circle');
                toggleCircle.style.transition = 'transform 0.3s ease';
                toggleCircle.style.position = 'absolute';
                toggleCircle.style.top = '2px';
                toggleCircle.style.left = '2px';
                toggleCircle.style.width = '16px';
                toggleCircle.style.height = '16px';
                toggleCircle.style.backgroundColor = 'white';
                toggleCircle.style.borderRadius = '50%';
                toggleCircle.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            });

            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const durationInput = document.querySelector('input[name="duration_months"]');

                if (!selectedLevelInput.value) {
                    alert('Please select a skill level.');
                    e.preventDefault();
                    return false;
                }

                if (!durationInput.value || durationInput.value < 1 || durationInput.value > 24) {
                    alert('Please enter a valid duration (1-24 months).');
                    durationInput.focus();
                    e.preventDefault();
                    return false;
                }

                return true;
            });
        });
    </script>

</body>

</html>
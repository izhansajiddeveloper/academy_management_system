<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $skill_name = trim($_POST['skill_name']);
    $duration = (int)$_POST['duration_months'];
    $level = $_POST['level'];
    $description = trim($_POST['description']);
    $has_syllabus = isset($_POST['has_syllabus']) ? 1 : 0;
    $has_practice = isset($_POST['has_practice']) ? 1 : 0;

    // Validation
    if (empty($skill_name) || empty($duration) || empty($level)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if skill already exists
        $check_sql = "SELECT id FROM skills WHERE skill_name = '$skill_name' AND status='active'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "A skill with this name already exists.";
        } else {
            // Insert skill
            $sql = "INSERT INTO skills 
                    (skill_name, duration_months, level, description, has_syllabus, has_practice, status, created_at)
                    VALUES ('$skill_name', $duration, '$level', '$description', $has_syllabus, $has_practice, 'active', NOW())";

            if (mysqli_query($conn, $sql)) {
                $skill_id = mysqli_insert_id($conn);
                $success_message = "Skill '$skill_name' added successfully! ID: $skill_id";
                $_POST = array(); // Clear form
            } else {
                $error_message = "Error adding skill: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Skill | Academy Management System</title>
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

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }

        .feature-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: var(--primary);
            background: #f0f9ff;
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

        .level-option:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        .level-option.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .icon-beginner {
            color: #10b981;
            background: #d1fae5;
        }

        .icon-intermediate {
            color: #3b82f6;
            background: #dbeafe;
        }

        .icon-advanced {
            color: #8b5cf6;
            background: #ede9fe;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR (Same as before) -->
        <aside class="w-64 bg-white shadow-xl sticky top-0 h-screen">
            <div class="p-6 text-center border-b">
                <h2 class="text-2xl font-bold text-[var(--primary)]">
                    🎓 EduSkill Pro
                </h2>
                <p class="text-sm text-gray-500 mt-1">Admin Panel</p>
            </div>

            <nav class="p-4 space-y-2 text-gray-700">
                <a href="../dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="skills.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-[var(--primary)] font-semibold">
                    <i class="fas fa-book-open text-[var(--primary)]"></i> Skills / Courses
                </a>
                <div class="ml-8 space-y-1">
                    <a href="skills.php" class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-list text-sm"></i> All Skills
                    </a>
                    <a href="add_skill.php" class="flex items-center gap-2 p-2 rounded-lg bg-blue-100 text-[var(--primary)] font-semibold">
                        <i class="fas fa-plus text-sm"></i> Add Skill
                    </a>
                </div>
                <!-- Other menu items... -->
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Add New Skill / Course</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                        Create a new training program or course
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="skills.php"
                        class="bg-white text-gray-700 px-5 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Skills
                    </a>
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
                                placeholder="e.g., Web Development, Data Science, Digital Marketing"
                                required
                                class="form-input"
                                value="<?php echo isset($_POST['skill_name']) ? htmlspecialchars($_POST['skill_name']) : ''; ?>">
                        </div>

                        <div>
                            <label class="form-label required">Description</label>
                            <textarea name="description"
                                rows="3"
                                placeholder="Describe the skill or course, learning objectives, target audience..."
                                class="form-input"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label required">Duration</label>
                                <div class="relative">
                                    <input type="number"
                                        name="duration_months"
                                        placeholder="Months"
                                        required
                                        min="1"
                                        max="24"
                                        class="form-input"
                                        value="<?php echo isset($_POST['duration_months']) ? $_POST['duration_months'] : ''; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Course duration in months (1-24)</p>
                            </div>

                            <div>
                                <label class="form-label required">Skill Level</label>
                                <div class="grid grid-cols-3 gap-3" id="levelOptions">
                                    <div class="level-option text-center" data-value="Beginner">
                                        <div class="w-10 h-10 rounded-full icon-beginner flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-seedling"></i>
                                        </div>
                                        <div class="text-sm font-medium">Beginner</div>
                                    </div>
                                    <div class="level-option text-center" data-value="Intermediate">
                                        <div class="w-10 h-10 rounded-full icon-intermediate flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="text-sm font-medium">Intermediate</div>
                                    </div>
                                    <div class="level-option text-center" data-value="Advanced">
                                        <div class="w-10 h-10 rounded-full icon-advanced flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-rocket"></i>
                                        </div>
                                        <div class="text-sm font-medium">Advanced</div>
                                    </div>
                                </div>
                                <input type="hidden" name="level" id="selectedLevel" required>
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
                        <div class="feature-card" id="syllabusFeature">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Syllabus Available</h4>
                                    <p class="text-sm text-gray-500">Course content and structure</p>
                                </div>
                            </div>
                            <input type="checkbox" name="has_syllabus" class="hidden" value="1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Enable syllabus for this course</span>
                                <div class="relative">
                                    <div class="w-10 h-6 bg-gray-300 rounded-full toggle-switch">
                                        <div class="toggle-circle"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="feature-card" id="practiceFeature">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-flask text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Practice Sessions</h4>
                                    <p class="text-sm text-gray-500">Hands-on exercises and labs</p>
                                </div>
                            </div>
                            <input type="checkbox" name="has_practice" class="hidden" value="1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Include practice exercises</span>
                                <div class="relative">
                                    <div class="w-10 h-6 bg-gray-300 rounded-full toggle-switch">
                                        <div class="toggle-circle"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="mb-8">
                    <h3 class="section-title">
                        <i class="fas fa-eye"></i> Preview
                    </h3>

                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-code text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800" id="previewName">New Skill</h4>
                                    <div class="flex gap-2">
                                        <span class="text-xs px-3 py-1 bg-gray-200 text-gray-700 rounded-full" id="previewLevel">Select Level</span>
                                        <span class="text-xs px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full" id="previewDuration">0 months</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4" id="previewDescription">Description will appear here...</p>
                        <div class="flex gap-2" id="previewFeatures">
                            <!-- Features will appear here -->
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-4 mt-8 pt-8 border-t border-gray-200">
                    <a href="skills.php"
                        class="bg-white text-gray-700 px-6 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit"
                        class="btn-primary flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Create Skill
                    </button>
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
                    levelOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    selectedLevelInput.value = this.dataset.value;

                    // Update preview
                    document.getElementById('previewLevel').textContent = this.dataset.value;
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

                    checkbox.checked = !checkbox.checked;

                    if (checkbox.checked) {
                        this.classList.add('active');
                        toggleSwitch.classList.add('bg-green-500');
                        toggleCircle.style.transform = 'translateX(20px)';

                        // Add feature to preview
                        const featureName = this.querySelector('h4').textContent;
                        const featureId = checkbox.name;
                        addFeatureToPreview(featureId, featureName);
                    } else {
                        this.classList.remove('active');
                        toggleSwitch.classList.remove('bg-green-500');
                        toggleCircle.style.transform = 'translateX(0)';

                        // Remove feature from preview
                        const featureId = checkbox.name;
                        removeFeatureFromPreview(featureId);
                    }
                });
            });

            // Real-time preview updates
            const nameInput = document.querySelector('input[name="skill_name"]');
            const descInput = document.querySelector('textarea[name="description"]');
            const durationInput = document.querySelector('input[name="duration_months"]');

            nameInput.addEventListener('input', function() {
                document.getElementById('previewName').textContent = this.value || 'New Skill';
            });

            descInput.addEventListener('input', function() {
                document.getElementById('previewDescription').textContent = this.value || 'Description will appear here...';
            });

            durationInput.addEventListener('input', function() {
                document.getElementById('previewDuration').textContent = this.value + ' months';
            });

            // Feature preview functions
            function addFeatureToPreview(id, name) {
                const previewFeatures = document.getElementById('previewFeatures');
                const featureId = 'preview-' + id;

                if (!document.getElementById(featureId)) {
                    const featureBadge = document.createElement('span');
                    featureBadge.id = featureId;
                    featureBadge.className = 'text-xs px-3 py-1 bg-blue-100 text-blue-800 rounded-full';
                    featureBadge.textContent = name;
                    previewFeatures.appendChild(featureBadge);
                }
            }

            function removeFeatureFromPreview(id) {
                const featureBadge = document.getElementById('preview-' + id);
                if (featureBadge) {
                    featureBadge.remove();
                }
            }

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
                if (!selectedLevelInput.value) {
                    alert('Please select a skill level.');
                    e.preventDefault();
                    return false;
                }

                if (!durationInput.value || durationInput.value < 1) {
                    alert('Please enter a valid duration (minimum 1 month).');
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
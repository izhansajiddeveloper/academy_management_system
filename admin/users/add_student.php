<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

// Get current year
$current_year = date('Y');

// Fetch all active skills
$skills = mysqli_query($conn, "SELECT id, skill_name FROM skills WHERE status='active' ORDER BY skill_name");

// Fetch current year session (2026)
$current_session_result = mysqli_query($conn, "SELECT id, session_name FROM sessions WHERE session_name LIKE '%$current_year%' AND status='active' LIMIT 1");
$current_session = mysqli_fetch_assoc($current_session_result);
$current_session_id = $current_session ? $current_session['id'] : 0;

// Fetch all active batches
$batches_query = "SELECT id, batch_name, skill_id FROM batches WHERE status='active' ORDER BY skill_id, batch_name";
$all_batches_result = mysqli_query($conn, $batches_query);

// Store all batches for JavaScript
$all_batches = [];
while ($batch = mysqli_fetch_assoc($all_batches_result)) {
    $all_batches[] = $batch;
}

// Get Web Development skill ID
$web_dev_result = mysqli_query($conn, "SELECT id FROM skills WHERE skill_name = 'Web Development' AND status='active' LIMIT 1");
$web_dev = mysqli_fetch_assoc($web_dev_result);
$web_dev_id = $web_dev ? $web_dev['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $name     = trim($_POST['name']);
    $father_name = trim($_POST['father_name']);
    $gender   = trim($_POST['gender']);
    $dob      = trim($_POST['dob']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);

    // Enrollment details - First Skill
    $skill_id_1 = isset($_POST['skill_id_1']) ? intval($_POST['skill_id_1']) : 0;
    $batch_id_1 = isset($_POST['batch_id_1']) ? intval($_POST['batch_id_1']) : 0;

    // Enrollment details - Second Skill (Optional)
    $skill_id_2 = isset($_POST['skill_id_2']) ? intval($_POST['skill_id_2']) : 0;
    $batch_id_2 = isset($_POST['batch_id_2']) ? intval($_POST['batch_id_2']) : 0;

    $session_id = $current_session_id; // Always use current session
    $admission_date = isset($_POST['admission_date']) ? $_POST['admission_date'] : date('Y-m-d');

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($name)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if username or email already exists
        $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Username or email already exists.";
        } else {
            // Insert into users table including username
            $user_sql = "INSERT INTO users (username, email, password, user_type_id, status, created_at) 
                         VALUES ('$username', '$email', '$password', 3, 'active', NOW())";

            if (mysqli_query($conn, $user_sql)) {
                $user_id = mysqli_insert_id($conn);
                $student_code = 'STD-' . date('Ymd') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

                // Insert into students table
                $student_sql = "INSERT INTO students
                                (user_id, student_code, name, father_name, gender, dob, phone, address, status, created_at)
                                VALUES (
                                    '$user_id',
                                    '$student_code',
                                    '$name',
                                    '$father_name',
                                    '$gender',
                                    '$dob',
                                    '$phone',
                                    '$address',
                                    'active',
                                    NOW()
                                )";

                if (mysqli_query($conn, $student_sql)) {
                    $student_id = mysqli_insert_id($conn);
                    $enrollment_count = 0;

                    // If first enrollment details are provided, create enrollment
                    if ($skill_id_1 > 0 && $session_id > 0 && $batch_id_1 > 0) {
                        $enrollment_sql_1 = "INSERT INTO student_enrollments 
                                            (student_id, skill_id, session_id, batch_id, admission_date, status, created_at)
                                            VALUES (
                                                '$student_id',
                                                '$skill_id_1',
                                                '$session_id',
                                                '$batch_id_1',
                                                '$admission_date',
                                                'active',
                                                NOW()
                                            )";

                        if (mysqli_query($conn, $enrollment_sql_1)) {
                            $enrollment_count++;
                        }
                    }

                    // If second enrollment details are provided, create enrollment
                    if ($skill_id_2 > 0 && $session_id > 0 && $batch_id_2 > 0) {
                        $enrollment_sql_2 = "INSERT INTO student_enrollments 
                                            (student_id, skill_id, session_id, batch_id, admission_date, status, created_at)
                                            VALUES (
                                                '$student_id',
                                                '$skill_id_2',
                                                '$session_id',
                                                '$batch_id_2',
                                                '$admission_date',
                                                'active',
                                                NOW()
                                            )";

                        if (mysqli_query($conn, $enrollment_sql_2)) {
                            $enrollment_count++;
                        }
                    }

                    if ($enrollment_count > 0) {
                        $success_message = "Student added and enrolled in $enrollment_count skill(s)! Student Code: $student_code";
                    } else {
                        $success_message = "Student added successfully! Student Code: $student_code";
                    }

                    // Redirect to students.php after successful save
                    header("Location: students.php?success=" . urlencode("Student added successfully! Student Code: $student_code"));
                    exit();
                } else {
                    $error_message = "Error adding student details: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error creating user: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Student | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .form-container {
            background: white;
            border-radius: 6px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .required:after {
            content: " *";
            color: #ef4444;
        }

        .current-session {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .border-red-300 {
            border-color: #fca5a5;
        }

        .enrollment-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .enrollment-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 16px;
        }

        .optional-tag {
            background-color: #dbeafe;
            color: #1e40af;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
        }

        .disabled-option {
            color: #9ca3af;
            background-color: #f3f4f6;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR - INCLUDED FROM EXTERNAL FILE -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Add New Student</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-plus text-blue-500 mr-1"></i>
                        Register a new student and optionally enroll in courses
                    </p>
                </div>
                <div>
                    <a href="students.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Students
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Success</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p><?php echo $success_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p><?php echo $error_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Student Form -->
            <div class="form-container">
                <form method="POST">
                    <!-- Student Details Section -->
                    <div class="mb-8">
                        <h3 class="section-title">Student Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username -->
                            <div>
                                <label class="form-label required">Username</label>
                                <input type="text"
                                    name="username"
                                    class="form-input"
                                    placeholder="Enter username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="form-label required">Email</label>
                                <input type="email"
                                    name="email"
                                    class="form-input"
                                    placeholder="student@example.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="form-label required">Password</label>
                                <input type="text"
                                    name="password"
                                    class="form-input"
                                    placeholder="Enter password"
                                    value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Full Name -->
                            <div>
                                <label class="form-label required">Full Name</label>
                                <input type="text"
                                    name="name"
                                    class="form-input"
                                    placeholder="Enter full name"
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Father's Name -->
                            <div>
                                <label class="form-label">Father's Name</label>
                                <input type="text"
                                    name="father_name"
                                    class="form-input"
                                    placeholder="Enter father's name"
                                    value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>">
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="form-label required">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <label class="form-label">Date of Birth</label>
                                <input type="date"
                                    name="dob"
                                    class="form-input"
                                    value="<?php echo isset($_POST['dob']) ? $_POST['dob'] : ''; ?>">
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="form-label required">Phone Number</label>
                                <input type="text"
                                    name="phone"
                                    class="form-input"
                                    placeholder="Enter phone number"
                                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                    rows="2"
                                    class="form-input"
                                    placeholder="Enter complete address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Session - Auto-selected -->
                    <div class="mb-8">
                        <h3 class="section-title">Session Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Session</label>
                                <div class="current-session">
                                    <i class="fas fa-calendar-check text-blue-500"></i>
                                    <div>
                                        <div class="font-medium"><?= $current_session ? htmlspecialchars($current_session['session_name']) : "$current_year Session" ?></div>
                                        <div class="text-sm text-gray-600">Auto-selected (Current Session)</div>
                                    </div>
                                </div>
                                <input type="hidden" name="session_id" value="<?= $current_session_id ?>">
                            </div>

                            <!-- Admission Date -->
                            <div>
                                <label class="form-label">Admission Date</label>
                                <input type="date"
                                    name="admission_date"
                                    value="<?php echo isset($_POST['admission_date']) ? $_POST['admission_date'] : date('Y-m-d'); ?>"
                                    class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- First Enrollment Section -->
                    <div class="enrollment-section">
                        <div class="enrollment-section-title">
                            <i class="fas fa-book text-blue-500"></i>
                            <span>First Enrollment</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Skill -->
                            <div>
                                <label class="form-label">Select Skill</label>
                                <select name="skill_id_1" id="skillSelect1" class="form-select" onchange="filterBatches(1)">
                                    <option value="">Select Skill/Course</option>
                                    <?php
                                    mysqli_data_seek($skills, 0); // Reset pointer
                                    while ($sk = mysqli_fetch_assoc($skills)) { ?>
                                        <option value="<?= $sk['id'] ?>" <?php echo (isset($_POST['skill_id_1']) && $_POST['skill_id_1'] == $sk['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($sk['skill_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- First Batch -->
                            <div>
                                <label class="form-label">Select Batch</label>
                                <select name="batch_id_1" id="batchSelect1" class="form-select" onchange="updateBatchOptions()">
                                    <option value="">Select a skill first</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Second Enrollment Section (Optional) -->
                    <div class="enrollment-section">
                        <div class="enrollment-section-title">
                            <i class="fas fa-book text-blue-500"></i>
                            <span>Second Enrollment <span class="optional-tag">Optional</span></span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Second Skill -->
                            <div>
                                <label class="form-label">Select Skill</label>
                                <select name="skill_id_2" id="skillSelect2" class="form-select" onchange="filterBatches(2)">
                                    <option value="">Select Skill/Course</option>
                                    <?php
                                    mysqli_data_seek($skills, 0); // Reset pointer
                                    while ($sk = mysqli_fetch_assoc($skills)) { ?>
                                        <option value="<?= $sk['id'] ?>" <?php echo (isset($_POST['skill_id_2']) && $_POST['skill_id_2'] == $sk['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($sk['skill_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Second Batch -->
                            <div>
                                <label class="form-label">Select Batch</label>
                                <select name="batch_id_2" id="batchSelect2" class="form-select">
                                    <option value="">Select a skill first</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Rules Info -->
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Enrollment Rules:</strong>
                                </p>
                                <ul class="text-sm text-blue-700 mt-2 space-y-1">
                                    <li>• Student can enroll in up to 2 different skills</li>
                                    <li>• First enrollment must be in <strong>Batch A</strong></li>
                                    <li>• Second enrollment must be in <strong>Batch B</strong> (different from first batch)</li>
                                    <li>• If first enrollment is in Batch A, Batch A options will be disabled for second enrollment</li>
                                    <li>• Both enrollments are optional - you can add student without enrollment</li>
                                </ul>
                                <p class="text-sm text-blue-700 mt-2">
                                    <strong>Session:</strong> Current session (<?= $current_year ?>) is automatically selected.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-generated Information -->
                    <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Auto-generated Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-id-card text-gray-400"></i>
                                <span class="text-sm text-gray-600">
                                    Student Code: <span class="font-mono font-medium">STD-<?php echo date('Ymd'); ?>XXXX</span>
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar text-gray-400"></i>
                                <span class="text-sm text-gray-600">
                                    Registration Date: <?php echo date('F j, Y'); ?>
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-user-tag text-gray-400"></i>
                                <span class="text-sm text-gray-600">
                                    User Type: Student
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-circle text-green-400"></i>
                                <span class="text-sm text-gray-600">
                                    Status: Active
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="students.php"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // All batches data for filtering
        const allBatches = <?php echo json_encode($all_batches); ?>;
        const webDevId = <?php echo $web_dev_id; ?>;
        let selectedBatch1 = '';
        let selectedBatchType1 = ''; // 'A' or 'B'

        // Function to filter batches based on selected skill
        function filterBatches(enrollmentNumber) {
            const skillSelect = document.getElementById(`skillSelect${enrollmentNumber}`);
            const batchSelect = document.getElementById(`batchSelect${enrollmentNumber}`);
            const selectedSkillId = parseInt(skillSelect.value);

            // Clear current options
            batchSelect.innerHTML = '<option value="">Select Batch</option>';

            if (!selectedSkillId) {
                // If no skill selected, show message
                batchSelect.innerHTML = '<option value="">Select a skill first</option>';
                if (enrollmentNumber === 1) {
                    selectedBatch1 = '';
                    selectedBatchType1 = '';
                    updateBatchOptions();
                }
                return;
            }

            // Filter batches for the selected skill
            const filteredBatches = allBatches.filter(batch => batch.skill_id == selectedSkillId);

            // Add filtered batches
            filteredBatches.forEach(batch => {
                let batchName = batch.batch_name;

                // Format batch name nicely
                if (batchName.toLowerCase().includes('batch')) {
                    // Already has "Batch" in name
                    batchName = batchName.charAt(0).toUpperCase() + batchName.slice(1);
                } else {
                    // Add "Batch" prefix
                    batchName = "Batch " + batchName;
                }

                // Extract batch letter (A, B, etc.)
                const batchLetter = batch.batch_name.replace('Batch ', '').trim().toUpperCase();

                const option = new Option(batchName, batch.id);
                option.setAttribute('data-skill-id', batch.skill_id);
                option.setAttribute('data-batch-letter', batchLetter);

                // For second enrollment, disable Batch A if it was selected in first enrollment
                if (enrollmentNumber === 2 && selectedBatchType1 === 'A' && batchLetter === 'A') {
                    option.disabled = true;
                    option.classList.add('disabled-option');
                    option.textContent = batchName + ' (Already enrolled in Batch A for another skill)';
                }

                batchSelect.add(option);
            });

            // If no batches found, add a disabled option
            if (batchSelect.options.length === 1) {
                const option = new Option('No batches available for this skill', '');
                option.disabled = true;
                batchSelect.add(option);
            }
        }

        // Update batch options for second enrollment when first batch changes
        function updateBatchOptions() {
            const batchSelect1 = document.getElementById('batchSelect1');
            const selectedOption1 = batchSelect1.options[batchSelect1.selectedIndex];

            // Store selected batch info
            selectedBatch1 = batchSelect1.value;
            selectedBatchType1 = selectedOption1.getAttribute('data-batch-letter') || '';

            // If second skill is selected, refresh its batches
            const skillSelect2 = document.getElementById('skillSelect2');
            if (skillSelect2.value) {
                filterBatches(2);
            }

            // Enforce rule: First enrollment must be in Batch A
            if (selectedBatchType1 && selectedBatchType1 !== 'A') {
                alert('First enrollment must be in Batch A. Please select Batch A for first enrollment.');
                batchSelect1.value = '';
                selectedBatch1 = '';
                selectedBatchType1 = '';
                // Re-filter to show all options
                filterBatches(1);
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initially disable batch selections until skills are chosen
            document.getElementById('batchSelect1').innerHTML = '<option value="">Select a skill first</option>';
            document.getElementById('batchSelect2').innerHTML = '<option value="">Select a skill first</option>';

            // If skills are already selected from previous form submission, filter batches
            const skillSelect1 = document.getElementById('skillSelect1');
            const skillSelect2 = document.getElementById('skillSelect2');

            if (skillSelect1.value) {
                filterBatches(1);

                // If batch was previously selected, try to restore it
                <?php if (isset($_POST['batch_id_1']) && $_POST['batch_id_1']): ?>
                    const selectedBatchId1 = <?= $_POST['batch_id_1'] ?>;
                    setTimeout(() => {
                        const batchSelect1 = document.getElementById('batchSelect1');
                        for (let i = 0; i < batchSelect1.options.length; i++) {
                            if (batchSelect1.options[i].value == selectedBatchId1) {
                                batchSelect1.selectedIndex = i;
                                updateBatchOptions();
                                break;
                            }
                        }
                    }, 100);
                <?php endif; ?>
            }

            if (skillSelect2.value) {
                setTimeout(() => {
                    filterBatches(2);

                    // If batch was previously selected, try to restore it
                    <?php if (isset($_POST['batch_id_2']) && $_POST['batch_id_2']): ?>
                        const selectedBatchId2 = <?= $_POST['batch_id_2'] ?>;
                        setTimeout(() => {
                            const batchSelect2 = document.getElementById('batchSelect2');
                            for (let i = 0; i < batchSelect2.options.length; i++) {
                                if (batchSelect2.options[i].value == selectedBatchId2) {
                                    batchSelect2.selectedIndex = i;
                                    break;
                                }
                            }
                        }, 200);
                    <?php endif; ?>
                }, 150);
            }

            // Generate a suggested username from name
            document.querySelector('input[name="name"]')?.addEventListener('blur', function() {
                const name = this.value.trim();
                const usernameInput = document.querySelector('input[name="username"]');
                const emailInput = document.querySelector('input[name="email"]');

                if (name && !usernameInput.value) {
                    // Create username: firstname.lastname + random 2 digits
                    const nameParts = name.toLowerCase().split(' ');
                    let suggestedUsername = '';
                    if (nameParts.length >= 2) {
                        suggestedUsername = nameParts[0] + '.' + nameParts[nameParts.length - 1] + Math.floor(Math.random() * 100);
                    } else {
                        suggestedUsername = nameParts[0] + Math.floor(Math.random() * 1000);
                    }
                    usernameInput.value = suggestedUsername;

                    if (!emailInput.value) {
                        const suggestedEmail = suggestedUsername + '@eduskillpro.com';
                        emailInput.value = suggestedEmail;
                    }
                }
            });

            // Auto-suggest password
            const passwordInput = document.querySelector('input[name="password"]');
            if (!passwordInput.value) {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let password = '';
                for (let i = 0; i < 8; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                passwordInput.value = password;
            }

            // Set admission date to today if not set
            const admissionDateInput = document.querySelector('input[name="admission_date"]');
            if (!admissionDateInput.value) {
                admissionDateInput.value = '<?php echo date("Y-m-d"); ?>';
            }

            // Real-time validation
            const inputs = document.querySelectorAll('.form-input, .form-select');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && this.value.trim() === '') {
                        this.classList.add('border-red-300');
                    } else {
                        this.classList.remove('border-red-300');
                    }
                });

                input.addEventListener('input', function() {
                    this.classList.remove('border-red-300');
                });
            });
        });
    </script>

</body>

</html>
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

// Fetch all active skills
$skills = mysqli_query($conn, "SELECT id, skill_name FROM skills WHERE status='active' ORDER BY skill_name");

// Fetch all active sessions
$sessions = mysqli_query($conn, "SELECT id, session_name FROM sessions WHERE status='active' ORDER BY id DESC");

// Fetch all active batches
$batches = mysqli_query($conn, "SELECT id, batch_name FROM batches WHERE status='active' ORDER BY batch_name");

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

    // Enrollment details
    $skill_id = isset($_POST['skill_id']) ? intval($_POST['skill_id']) : 0;
    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    $batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;
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

                    // If enrollment details are provided, create enrollment
                    if ($skill_id > 0 && $session_id > 0 && $batch_id > 0) {
                        $enrollment_sql = "INSERT INTO student_enrollments 
                                            (student_id, skill_id, session_id, batch_id, admission_date, status, created_at)
                                            VALUES (
                                                '$student_id',
                                                '$skill_id',
                                                '$session_id',
                                                '$batch_id',
                                                '$admission_date',
                                                'active',
                                                NOW()
                                            )";

                        if (mysqli_query($conn, $enrollment_sql)) {
                            $success_message = "Student added and enrolled successfully! Student Code: $student_code";
                        } else {
                            $success_message = "Student added but enrollment failed! Student Code: $student_code";
                        }
                    } else {
                        $success_message = "Student added successfully! Student Code: $student_code";
                    }

                    // Clear form if needed
                    $_POST = array();
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

        .sidebar {
            background: #111827;
            color: white;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
            color: #d1d5db;
            text-decoration: none;
        }

        .sidebar-link:hover {
            background: #374151;
            color: white;
        }

        .sidebar-link.active {
            background: #3b82f6;
            color: white;
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
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 sidebar h-screen sticky top-0">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-bold text-white">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1">Admin Panel</p>
            </div>

            <nav class="p-3 space-y-1">
                <a href="../dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Student Management</p>
                    <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-user-check"></i> Enrollments
                    </a>
                    <a href="students.php" class="sidebar-link active">
                        <i class="fas fa-user-graduate"></i> Students
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Skills & Courses</p>
                    <a href="../skills/skills.php" class="sidebar-link">
                        <i class="fas fa-book-open"></i> Skills
                    </a>
                    <a href="../sessions/sessions.php" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Sessions
                    </a>
                    <a href="../batches/batches.php" class="sidebar-link">
                        <i class="fas fa-layer-group"></i> Batches
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Financial</p>
                    <a href="../fees/fee_structures.php" class="sidebar-link">
                        <i class="fas fa-calculator"></i> Fee Structures
                    </a>
                    <a href="../fees/fee_collection.php" class="sidebar-link">
                        <i class="fas fa-cash-register"></i> Fee Collection
                    </a>
                    <a href="../fees/fee_history.php" class="sidebar-link">
                        <i class="fas fa-history"></i> Fee History
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../expenses/expenses.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Expenses
                    </a>
                    <a href="../reports/student_report.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </div>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Add New Student</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-plus text-blue-500 mr-1"></i>
                        Register a new student and optionally enroll in a course
                    </p>
                </div>
                <div>
                    <a href="students.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
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

                    <!-- Enrollment Details Section -->
                    <div class="mb-8">
                        <h3 class="section-title">Enrollment Details </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Skill -->
                            <div>
                                <label class="form-label">Select Skill</label>
                                <select name="skill_id" class="form-select">
                                    <option value="">Select Skill/Course</option>
                                    <?php
                                    mysqli_data_seek($skills, 0); // Reset pointer
                                    while ($sk = mysqli_fetch_assoc($skills)) { ?>
                                        <option value="<?= $sk['id'] ?>" <?php echo (isset($_POST['skill_id']) && $_POST['skill_id'] == $sk['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($sk['skill_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Session -->
                            <div>
                                <label class="form-label">Select Session</label>
                                <select name="session_id" class="form-select">
                                    <option value="">Select Session</option>
                                    <?php
                                    mysqli_data_seek($sessions, 0); // Reset pointer
                                    while ($se = mysqli_fetch_assoc($sessions)) { ?>
                                        <option value="<?= $se['id'] ?>" <?php echo (isset($_POST['session_id']) && $_POST['session_id'] == $se['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($se['session_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Batch -->
                            <div>
                                <label class="form-label">Select Batch</label>
                                <select name="batch_id" class="form-select">
                                    <option value="">Select Batch</option>
                                    <?php
                                    mysqli_data_seek($batches, 0); // Reset pointer
                                    while ($b = mysqli_fetch_assoc($batches)) { ?>
                                        <option value="<?= $b['id'] ?>" <?php echo (isset($_POST['batch_id']) && $_POST['batch_id'] == $b['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($b['batch_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
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

                        <!-- Enrollment Info Note -->
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Note:</strong> Enrollment is optional. You can add the student now and enroll them in a course later.
                                        If you provide enrollment details, the student will be automatically enrolled.
                                    </p>
                                </div>
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
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-medium">
                            <i class="fas fa-save mr-2"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
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
        document.addEventListener('DOMContentLoaded', function() {
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
        });

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
    </script>

</body>

</html>
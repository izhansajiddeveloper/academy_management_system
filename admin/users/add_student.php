<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

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
                    $success_message = "Student added successfully! Student Code: $student_code";
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

        .sidebar-link:hover {
            background: #f1f5f9;
            color: var(--primary);
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

        .form-input.error {
            border-color: #ef4444;
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

        .btn-secondary {
            background: white;
            color: #374151;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            cursor: pointer;
        }

        .btn-secondary:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .info-box i {
            color: #0ea5e9;
            margin-right: 8px;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-xl sticky top-0 h-screen">
            <div class="p-6 text-center border-b">
                <h2 class="text-2xl font-bold text-[var(--primary)]">
                    🎓 EduSkill Pro
                </h2>
                <p class="text-sm text-gray-500 mt-1">Admin Panel</p>
            </div>

            <nav class="p-4 space-y-2 text-gray-700">
                <!-- Dashboard -->
                <a href="../dashboard.php"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700">
                    <i class="fas fa-chart-line text-[var(--primary)]"></i> Dashboard
                </a>

                <!-- USERS (Active) -->
                <a href="../users/students.php"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700">
                    <i class="fas fa-users text-[var(--primary)]"></i> Users
                </a>

                <!-- Users Submenu -->
                <div class="ml-8 space-y-1">
                    <a href="students.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-graduation-cap text-sm"></i>
                        <span>Students</span>
                    </a>
                    <a href="../users/teachers.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-chalkboard-teacher text-sm"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="../users/inactive_users.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-user-slash text-sm"></i>
                        <span>Inactive Users</span>
                    </a>
                </div>

                <!-- SKILLS / COURSES -->
                <a href="../skills/skills.php" class="sidebar-link">
                    <i class="fas fa-book-open"></i> Skills / Courses
                </a>

                <!-- SESSIONS -->
                <a href="../sessions/sessions.php" class="sidebar-link">
                    <i class="fas fa-calendar-alt"></i> Sessions
                </a>

                <!-- BATCHES -->
                <a href="../batches/batches.php" class="sidebar-link">
                    <i class="fas fa-layer-group"></i> Batches
                </a>

                <!-- ENROLLMENTS -->
                <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                    <i class="fas fa-user-check"></i> Enrollments
                </a>

                <!-- FEES -->
                <a href="../fees/fee_collection.php" class="sidebar-link">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>

                <!-- EXPENSES -->
                <a href="../expenses/expenses.php" class="sidebar-link">
                    <i class="fas fa-wallet"></i> Expenses
                </a>

                <!-- REPORTS -->
                <a href="../reports/student_report.php" class="sidebar-link">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </nav>

            <!-- Quick Actions -->
            <div class="mt-8 p-4 border-t border-gray-100">
                <h4 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wider">Quick Actions</h4>
                <div class="space-y-2">
                    <a href="add_student.php" class="flex items-center gap-2 text-sm p-2 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Student</span>
                    </a>
                    <a href="../sessions/add_session.php" class="flex items-center gap-2 text-sm p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Session</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Add New Student</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-user-graduate text-blue-500 mr-2"></i>
                        Register a new student to the academy
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="students.php"
                        class="btn-secondary flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Back to Students
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

            <!-- Info Box -->
            <div class="info-box mb-8">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle"></i>
                    Fill in all required fields marked with *. Student login credentials will be generated automatically.
                </p>
            </div>

            <!-- Form -->
            <form method="post" class="form-card p-8 max-w-4xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Personal Information Section -->
                    <div>
                        <h3 class="section-title">
                            <i class="fas fa-user-circle"></i> Personal Information
                        </h3>

                        <div class="space-y-6">
                            <!-- Avatar Preview -->
                            <div class="avatar-preview" id="avatarPreview">
                                <span id="avatarInitial">?</span>
                            </div>

                            <div>
                                <label class="form-label required">Full Name</label>
                                <input type="text"
                                    name="name"
                                    placeholder="Enter student's full name"
                                    required
                                    class="form-input"
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                    oninput="updateAvatar()">
                            </div>

                            <div>
                                <label class="form-label">Father's Name</label>
                                <input type="text"
                                    name="father_name"
                                    placeholder="Enter father's name"
                                    class="form-input"
                                    value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label required">Gender</label>
                                    <select name="gender" required class="form-input">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date"
                                        name="dob"
                                        class="form-input"
                                        value="<?php echo isset($_POST['dob']) ? $_POST['dob'] : ''; ?>">
                                </div>
                            </div>

                            <div>
                                <label class="form-label required">Phone Number</label>
                                <input type="tel"
                                    name="phone"
                                    placeholder="Enter phone number"
                                    required
                                    class="form-input"
                                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>

                            <div>
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                    placeholder="Enter complete address"
                                    rows="3"
                                    class="form-input"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Login Information Section -->
                    <div>
                        <h3 class="section-title">
                            <i class="fas fa-key"></i> Login Information
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <label class="form-label required">Username</label>
                                <div class="relative">
                                    <input type="text"
                                        name="username"
                                        placeholder="Choose a username for login"
                                        required
                                        class="form-input <?php echo ($error_message && stripos($error_message, 'username') !== false) ? 'error' : ''; ?>"
                                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-at"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">This will be used for login</p>
                            </div>

                            <div>
                                <label class="form-label required">Email Address</label>
                                <div class="relative">
                                    <input type="email"
                                        name="email"
                                        placeholder="student@example.com"
                                        required
                                        class="form-input <?php echo ($error_message && stripos($error_message, 'email') !== false) ? 'error' : ''; ?>"
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Login and communication email</p>
                            </div>

                            <div>
                                <label class="form-label required">Password</label>
                                <div class="relative">
                                    <input type="text"
                                        name="password"
                                        placeholder="Enter password"
                                        required
                                        class="form-input"
                                        value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                    Password will be stored as plain text (as per requirement)
                                </p>
                            </div>

                            <!-- Auto-generated Info -->
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-700 mb-2">
                                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                    Auto-generated Information
                                </h4>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-id-card text-gray-400"></i>
                                        <span>Student Code: <span class="font-mono font-medium">STD-<?php echo date('Ymd'); ?>XXXX</span></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-gray-400"></i>
                                        <span>Registration Date: <?php echo date('F j, Y'); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user-tag text-gray-400"></i>
                                        <span>User Type: Student</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-circle text-green-400"></i>
                                        <span>Status: Active</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Important Notes -->
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <h4 class="font-medium text-blue-700 mb-2">
                                    <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                                    Important Notes
                                </h4>
                                <ul class="text-sm text-blue-600 space-y-1">
                                    <li>• Student will receive login credentials via email</li>
                                    <li>• Make sure the email address is correct and active</li>
                                    <li>• Phone number will be used for emergency contact</li>
                                    <li>• Student code will be generated automatically</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-4 mt-12 pt-8 border-t border-gray-200">
                    <a href="students.php"
                        class="btn-secondary flex items-center gap-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit"
                        class="btn-primary flex items-center gap-2">
                        <i class="fas fa-save"></i> Save Student
                    </button>
                </div>
            </form>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        function updateAvatar() {
            const nameInput = document.querySelector('input[name="name"]');
            const avatarInitial = document.getElementById('avatarInitial');
            const avatarPreview = document.getElementById('avatarPreview');

            if (nameInput.value.trim()) {
                const initial = nameInput.value.trim().charAt(0).toUpperCase();
                avatarInitial.textContent = initial;

                // Change avatar color based on gender (if selected)
                const genderSelect = document.querySelector('select[name="gender"]');
                if (genderSelect.value === 'male') {
                    avatarPreview.style.background = 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)';
                } else if (genderSelect.value === 'female') {
                    avatarPreview.style.background = 'linear-gradient(135deg, #ec4899 0%, #f472b6 100%)';
                } else {
                    avatarPreview.style.background = 'linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%)';
                }
            } else {
                avatarInitial.textContent = '?';
                avatarPreview.style.background = 'linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%)';
            }
        }

        // Initialize avatar
        document.addEventListener('DOMContentLoaded', function() {
            updateAvatar();

            // Add gender change listener
            const genderSelect = document.querySelector('select[name="gender"]');
            genderSelect.addEventListener('change', updateAvatar);

            // Add real-time validation
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '' && this.hasAttribute('required')) {
                        this.classList.add('error');
                    } else {
                        this.classList.remove('error');
                    }
                });

                input.addEventListener('input', function() {
                    this.classList.remove('error');
                });
            });
        });

        // Generate a suggested username from name
        document.querySelector('input[name="name"]').addEventListener('blur', function() {
            const name = this.value.trim();
            const usernameInput = document.querySelector('input[name="username"]');
            const emailInput = document.querySelector('input[name="email"]');

            if (name && !usernameInput.value) {
                const suggestedUsername = name.toLowerCase().replace(/\s+/g, '.') + Math.floor(Math.random() * 100);
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
        });
    </script>

</body>

</html>
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $name = trim($_POST['name']);
    $qualification = trim($_POST['qualification']);
    $experience_years = intval($_POST['experience_years']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization'] ?? '');

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
            // Insert into users table
            $user_sql = "INSERT INTO users (username, email, password, user_type_id, status, created_at) 
                         VALUES ('$username', '$email', '$password', 2, 'active', NOW())";

            if (mysqli_query($conn, $user_sql)) {
                $user_id = mysqli_insert_id($conn);
                $teacher_code = 'TCH-' . date('Ymd') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

                // Insert into teachers table
                $teacher_sql = "INSERT INTO teachers 
                                (user_id, teacher_code, name, qualification, experience_years,  phone, status, created_at)
                                VALUES (
                                    '$user_id',
                                    '$teacher_code',
                                    '$name',
                                    '$qualification',
                                    '$experience_years',
                                
                                    '$phone',
                                    'active',
                                    NOW()
                                )";

                if (mysqli_query($conn, $teacher_sql)) {
                    $success_message = "Teacher added successfully! Teacher Code: $teacher_code";
                    $_POST = array(); // Clear form
                } else {
                    $error_message = "Error adding teacher details: " . mysqli_error($conn);
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
    <title>Add Teacher | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Same styles as add_student.php */
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

        <!-- SIDEBAR (Same as before) -->
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

                <!-- USERS -->
                <a href="teachers.php"
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
                    <a href="teachers.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-chalkboard-teacher text-sm"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="inactive_users.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-user-slash text-sm"></i>
                        <span>Inactive Users</span>
                    </a>
                </div>

                <!-- Other menu items -->
                <a href="../skills/skills.php" class="sidebar-link">
                    <i class="fas fa-book-open"></i> Skills / Courses
                </a>
                <a href="../sessions/sessions.php" class="sidebar-link">
                    <i class="fas fa-calendar-alt"></i> Sessions
                </a>
                <a href="../batches/batches.php" class="sidebar-link">
                    <i class="fas fa-layer-group"></i> Batches
                </a>
                <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                    <i class="fas fa-user-check"></i> Enrollments
                </a>
                <a href="../fees/fee_collection.php" class="sidebar-link">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>
                <a href="../expenses/expenses.php" class="sidebar-link">
                    <i class="fas fa-wallet"></i> Expenses
                </a>
                <a href="../reports/student_report.php" class="sidebar-link">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
            </nav>

            <div class="mt-8 p-4 border-t border-gray-100">
                <h4 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wider">Quick Actions</h4>
                <div class="space-y-2">
                    <a href="add_teacher.php" class="flex items-center gap-2 text-sm p-2 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Teacher</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Add New Teacher</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-chalkboard-teacher text-purple-500 mr-2"></i>
                        Register a new teacher to the academy
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="teachers.php"
                        class="bg-white text-gray-700 px-5 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Teachers
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Personal Information -->
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
                                    placeholder="Enter teacher's full name"
                                    required
                                    class="form-input"
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                    oninput="updateAvatar()">
                            </div>

                            <div>
                                <label class="form-label required">Qualification</label>
                                <input type="text"
                                    name="qualification"
                                    placeholder="e.g., M.Sc. Computer Science, B.Ed."
                                    required
                                    class="form-input"
                                    value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>">
                            </div>

                            <div>
                                <label class="form-label required">Specialization</label>
                                <input type="text"
                                    name="specialization"
                                    placeholder="e.g., Web Development, Data Science"
                                    class="form-input"
                                    value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label required">Experience (Years)</label>
                                    <input type="number"
                                        name="experience_years"
                                        placeholder="Years"
                                        required
                                        min="0"
                                        class="form-input"
                                        value="<?php echo isset($_POST['experience_years']) ? $_POST['experience_years'] : ''; ?>">
                                </div>
                                <div>
                                    <label class="form-label required">Phone Number</label>
                                    <input type="tel"
                                        name="phone"
                                        placeholder="Phone number"
                                        required
                                        class="form-input"
                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Login Information -->
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
                                        class="form-input"
                                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-at"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label required">Email Address</label>
                                <div class="relative">
                                    <input type="email"
                                        name="email"
                                        placeholder="teacher@example.com"
                                        required
                                        class="form-input"
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label required">Password</label>
                                <div class="relative">
                                    <input type="text"
                                        name="password"
                                        placeholder="Enter password"
                                        required
                                        class="form-input"
                                        value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>"
                                        id="passwordInput">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                    Password will be stored as plain text (as per requirement)
                                </p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-700 mb-2">
                                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                    Auto-generated Information
                                </h4>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-id-card text-gray-400"></i>
                                        <span>Teacher Code: <span class="font-mono font-medium">TCH-<?php echo date('Ymd'); ?>XXXX</span></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar text-gray-400"></i>
                                        <span>Registration Date: <?php echo date('F j, Y'); ?></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user-tag text-gray-400"></i>
                                        <span>User Type: Teacher</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-circle text-green-400"></i>
                                        <span>Status: Active</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-purple-50 rounded-lg">
                                <h4 class="font-medium text-purple-700 mb-2">
                                    <i class="fas fa-lightbulb text-purple-600 mr-2"></i>
                                    Important Notes
                                </h4>
                                <ul class="text-sm text-purple-600 space-y-1">
                                    <li>• Teacher will receive login credentials</li>
                                    <li>• Email address must be active for communication</li>
                                    <li>• Phone number will be used for emergency contact</li>
                                    <li>• Teacher code will be generated automatically</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-4 mt-12 pt-8 border-t border-gray-200">
                    <a href="teachers.php"
                        class="bg-white text-gray-700 px-6 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit"
                        class="btn-primary flex items-center gap-2">
                        <i class="fas fa-save"></i> Save Teacher
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
            } else {
                avatarInitial.textContent = '?';
            }
        }

        // Generate password if empty
        document.addEventListener('DOMContentLoaded', function() {
            updateAvatar();

            const passwordInput = document.getElementById('passwordInput');
            if (!passwordInput.value) {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let password = '';
                for (let i = 0; i < 8; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                passwordInput.value = password;
            }

            // Auto-suggest username
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
        });
    </script>

</body>

</html>
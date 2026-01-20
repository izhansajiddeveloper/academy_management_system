<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = $_GET['id'] ?? 0;

// Fetch student details with user information
$student_query = "
    SELECT s.*, u.username, u.email, u.status as user_status
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id='$id'
";
$student_result = mysqli_query($conn, $student_query);
$student = mysqli_fetch_assoc($student_result);

if (!$student) {
    die("Student not found!");
}

$success_message = '';
$error_message = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $father_name = trim($_POST['father_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status = trim($_POST['status']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);

    // Basic validation
    if (empty($name) || empty($email) || empty($username)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if email or username already exists for other users
        $check_sql = "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != '{$student['user_id']}'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Username or email already exists for another user.";
        } else {
            // Update students table
            $student_sql = "
                UPDATE students SET
                name='$name',
                father_name='$father_name',
                phone='$phone',
                address='$address',
                status='$status',
                updated_at=NOW()
                WHERE id='$id'
            ";

            if (mysqli_query($conn, $student_sql)) {
                // Update users table
                $user_sql = "
                    UPDATE users SET
                    username='$username',
                    email='$email',
                    status='$status',
                    updated_at=NOW()
                    WHERE id='{$student['user_id']}'
                ";

                if (mysqli_query($conn, $user_sql)) {
                    // ✅ Redirect to students.php after successful update
                    header("Location: students.php?success=1");
                    exit;
                } else {
                    $error_message = "Failed to update user information.";
                }
            } else {
                $error_message = "Failed to update student information.";
            }
        }
    }
}

 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Student | Academy Management System</title>
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

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
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
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
            margin: 0 auto 20px;
        }

        .student-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .status-active {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        }

        .status-inactive {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
        }

        .stat-item {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
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
                        class="flex items-center gap-2 p-2 rounded-lg bg-blue-100 text-[var(--primary)] font-semibold">
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
                    <a href="students.php" class="flex items-center gap-2 text-sm p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                        <i class="fas fa-list"></i>
                        <span>View All</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Edit Student</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-edit text-blue-500 mr-2"></i>
                        Update student information and details
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="students.php"
                        class="btn-secondary flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>
            </div>

            <!-- Student Header Info -->
            <div class="student-header">
                <div class="flex items-center gap-6">
                    <div class="avatar-preview <?php echo $student['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($student['name']); ?></h2>
                        <div class="flex flex-wrap gap-4">
                            <div>
                                <div class="text-sm opacity-80">Student Code</div>
                                <div class="font-mono font-bold"><?php echo $student['student_code']; ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Status</div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $student['status'] == 'active' ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300'; ?>">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Since</div>
                                <div class="font-medium"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></div>
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

            <!-- Student Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="stat-item">
                    <div class="stat-label">User ID</div>
                    <div class="stat-value"><?php echo $student['user_id']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Gender</div>
                    <div class="stat-value">
                        <?php
                        $gender = $student['gender'] ?? 'Not specified';
                        echo ucfirst($gender);
                        ?>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Date of Birth</div>
                    <div class="stat-value">
                        <?php echo $student['dob'] ? date('M d, Y', strtotime($student['dob'])) : 'Not specified'; ?>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Last Updated</div>
                    <div class="stat-value">
                        <?php echo $student['updated_at'] ? date('M d, Y', strtotime($student['updated_at'])) : 'Never'; ?>
                    </div>
                </div>
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
                            <div>
                                <label class="form-label required">Full Name</label>
                                <input type="text"
                                    name="name"
                                    placeholder="Enter student's full name"
                                    required
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($student['name']); ?>">
                            </div>

                            <div>
                                <label class="form-label">Father's Name</label>
                                <input type="text"
                                    name="father_name"
                                    placeholder="Enter father's name"
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
                            </div>

                            <div>
                                <label class="form-label required">Phone Number</label>
                                <div class="relative">
                                    <input type="tel"
                                        name="phone"
                                        placeholder="Enter phone number"
                                        required
                                        class="form-input"
                                        value="<?php echo htmlspecialchars($student['phone']); ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                    placeholder="Enter complete address"
                                    rows="4"
                                    class="form-input"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Login & Status Section -->
                    <div>
                        <h3 class="section-title">
                            <i class="fas fa-key"></i> Account Information
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
                                        value="<?php echo htmlspecialchars($student['username']); ?>">
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
                                        value="<?php echo htmlspecialchars($student['email']); ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Login and communication email</p>
                            </div>

                            <div>
                                <label class="form-label required">Account Status</label>
                                <select name="status" required class="form-input">
                                    <option value="active" <?php echo ($student['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($student['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo ($student['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Inactive students cannot login</p>
                            </div>

                            <!-- Password Reset (Optional) -->
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 class="font-medium text-yellow-800 mb-2">
                                    <i class="fas fa-key text-yellow-600 mr-2"></i>
                                    Password Management
                                </h4>
                                <p class="text-sm text-yellow-700 mb-3">
                                    To reset password, use the "Reset Password" feature from the student's profile.
                                </p>
                                <a href="#"
                                    class="inline-flex items-center gap-2 text-sm font-medium text-yellow-700 hover:text-yellow-800">
                                    <i class="fas fa-redo"></i> Reset Password
                                </a>
                            </div>

                            <!-- Important Notes -->
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <h4 class="font-medium text-blue-700 mb-2">
                                    <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                                    Update Notes
                                </h4>
                                <ul class="text-sm text-blue-600 space-y-1">
                                    <li>• Changes to username/email require re-login</li>
                                    <li>• Setting status to inactive will block login</li>
                                    <li>• Updated information will be reflected immediately</li>
                                    <li>• Student will be notified of significant changes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center mt-12 pt-8 border-t border-gray-200">
                    <div>
                        <a href="delete_student.php?id=<?php echo $id; ?>"
                            onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($student['name']); ?>? This action cannot be undone.')"
                            class="btn-danger flex items-center gap-2">
                            <i class="fas fa-trash"></i> Delete Student
                        </a>
                    </div>
                    <div class="flex gap-4">
                        <a href="students.php"
                            class="btn-secondary flex items-center gap-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div class="flex gap-4">
                            <!-- Update button (submits the form) -->
                            <button type="submit" class="btn-primary flex items-center gap-2">
                                <i class="fas fa-save"></i> Update Student
                            </button>

                           
                        </div>

                    </div>
                </div>
            </form>

            <!-- Additional Information -->
            <div class="form-card p-8 mt-8 max-w-4xl mx-auto">
                <h3 class="section-title">
                    <i class="fas fa-history"></i> System Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-500 mb-1">Created At</div>
                        <div class="font-medium"><?php echo date('F j, Y \a\t g:i A', strtotime($student['created_at'])); ?></div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-500 mb-1">Last Updated</div>
                        <div class="font-medium">
                            <?php
                            if ($student['updated_at'] && $student['updated_at'] != '0000-00-00 00:00:00') {
                                echo date('F j, Y \a\t g:i A', strtotime($student['updated_at']));
                            } else {
                                echo 'Never updated';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-500 mb-1">Record ID</div>
                        <div class="font-mono font-medium"><?php echo $student['id']; ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        // Real-time validation
        document.addEventListener('DOMContentLoaded', function() {
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

            // Status change warning
            const statusSelect = document.querySelector('select[name="status"]');
            const originalStatus = statusSelect.value;

            statusSelect.addEventListener('change', function() {
                if (this.value === 'inactive' && originalStatus === 'active') {
                    if (confirm('Setting student to inactive will block their login. Continue?')) {
                        return true;
                    } else {
                        this.value = originalStatus;
                        return false;
                    }
                }
            });

            // Form submission confirmation for status change
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                if (statusSelect.value !== originalStatus) {
                    if (!confirm(`Are you sure you want to change status from "${originalStatus}" to "${statusSelect.value}"?`)) {
                        e.preventDefault();
                        return false;
                    }
                }

                // Additional validation
                const email = document.querySelector('input[name="email"]');
                const username = document.querySelector('input[name="username"]');
                const name = document.querySelector('input[name="name"]');

                if (!email.value.includes('@')) {
                    alert('Please enter a valid email address.');
                    email.focus();
                    e.preventDefault();
                    return false;
                }

                if (username.value.length < 3) {
                    alert('Username must be at least 3 characters long.');
                    username.focus();
                    e.preventDefault();
                    return false;
                }

                if (name.value.length < 2) {
                    alert('Please enter a valid name.');
                    name.focus();
                    e.preventDefault();
                    return false;
                }

                return true;
            });
        });

        // Update avatar color based on status
        function updateAvatarColor() {
            const statusSelect = document.querySelector('select[name="status"]');
            const avatarPreview = document.querySelector('.avatar-preview');

            if (statusSelect.value === 'active') {
                avatarPreview.classList.remove('status-inactive');
                avatarPreview.classList.add('status-active');
            } else {
                avatarPreview.classList.remove('status-active');
                avatarPreview.classList.add('status-inactive');
            }
        }

        // Initialize avatar color
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.querySelector('select[name="status"]');
            statusSelect.addEventListener('change', updateAvatarColor);

            // Auto-format phone number
            const phoneInput = document.querySelector('input[name="phone"]');
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) value = value.substring(0, 10);
                if (value.length > 6) {
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
                } else if (value.length > 3) {
                    value = value.replace(/(\d{3})(\d{3})/, '$1-$2');
                }
                e.target.value = value;
            });
        });
    </script>

</body>

</html>
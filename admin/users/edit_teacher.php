<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = intval($_GET['id'] ?? 0);

$sql = "SELECT t.*, u.username, u.email, u.password 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id
        WHERE t.id = $id LIMIT 1";

$result = mysqli_query($conn, $sql);
$teacher = mysqli_fetch_assoc($result);

if (!$teacher) {
    die("Teacher not found!");
}

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
    $status = $_POST['status'];

    if (empty($username) || empty($email) || empty($password) || empty($name)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if username or email exists for other users
        $check_sql = "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != '{$teacher['user_id']}'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Username or email already exists for another user.";
        } else {
            // Update users table
            $user_sql = "UPDATE users 
                        SET username='$username', email='$email', password='$password', status='$status', updated_at=NOW()
                        WHERE id={$teacher['user_id']}";

            if (mysqli_query($conn, $user_sql)) {
                // Update teachers table
                $teacher_sql = "
    UPDATE teachers 
    SET 
        name='$name',
        qualification='$qualification',
        experience_years='$experience_years',
        phone='$phone',
        status='$status',
        updated_at=NOW()
    WHERE id=$id
";

                if (mysqli_query($conn, $teacher_sql)) {
                    $success_message = "Teacher information updated successfully!";
                    // Refresh teacher data
                    $result = mysqli_query($conn, $sql);
                    $teacher = mysqli_fetch_assoc($result);
                } else {
                    $error_message = "Error updating teacher information: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error updating user information: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Teacher | Academy Management System</title>
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

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
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

        .teacher-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
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

        .stat-item {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
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
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex min-h-screen">

        <!-- SIDEBAR (Same as before) -->
        <aside class="w-64 bg-white shadow-xl sticky top-0 h-screen">
            <!-- Same sidebar code as teachers.php -->
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
                <a href="teachers.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-700">
                    <i class="fas fa-users text-[var(--primary)]"></i> Users
                </a>
                <div class="ml-8 space-y-1">
                    <a href="students.php" class="flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                        <i class="fas fa-graduation-cap text-sm"></i> Students
                    </a>
                    <a href="teachers.php" class="flex items-center gap-2 p-2 rounded-lg bg-blue-100 text-[var(--primary)] font-semibold">
                        <i class="fas fa-chalkboard-teacher text-sm"></i> Teachers
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
                    <h1 class="text-3xl font-bold text-gray-800">Edit Teacher</h1>
                    <p class="text-gray-500 mt-2">
                        <i class="fas fa-edit text-purple-500 mr-2"></i>
                        Update teacher information and details
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="teachers.php"
                        class="bg-white text-gray-700 px-5 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Teachers
                    </a>
                </div>
            </div>

            <!-- Teacher Header Info -->
            <div class="teacher-header">
                <div class="flex items-center gap-6">
                    <div class="avatar-preview"
                        style="background: linear-gradient(135deg, <?php echo $teacher['status'] == 'active' ? '#10b981' : '#6b7280'; ?> 0%, <?php echo $teacher['status'] == 'active' ? '#34d399' : '#9ca3af'; ?> 100%);">
                        <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($teacher['name']); ?></h2>
                        <div class="flex flex-wrap gap-4">
                            <div>
                                <div class="text-sm opacity-80">Teacher Code</div>
                                <div class="font-mono font-bold"><?php echo $teacher['teacher_code']; ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Status</div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $teacher['status'] == 'active' ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300'; ?>">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    <?php echo ucfirst($teacher['status']); ?>
                                </span>
                            </div>
                            <div>
                                <div class="text-sm opacity-80">Since</div>
                                <div class="font-medium"><?php echo date('M d, Y', strtotime($teacher['created_at'])); ?></div>
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

            <!-- Teacher Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="stat-item">
                    <div class="text-sm text-gray-500 mb-1">User ID</div>
                    <div class="text-lg font-semibold text-gray-800"><?php echo $teacher['user_id']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="text-sm text-gray-500 mb-1">Experience</div>
                    <div class="text-lg font-semibold text-gray-800"><?php echo $teacher['experience_years']; ?> years</div>
                </div>
                <div class="stat-item">
                    <div class="text-sm text-gray-500 mb-1">Qualification</div>
                    <div class="text-lg font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($teacher['qualification']); ?></div>
                </div>
                <div class="stat-item">
                    <div class="text-sm text-gray-500 mb-1">Last Updated</div>
                    <div class="text-lg font-semibold text-gray-800">
                        <?php echo $teacher['updated_at'] && $teacher['updated_at'] != '0000-00-00 00:00:00' ? date('M d, Y', strtotime($teacher['updated_at'])) : 'Never'; ?>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form method="post" class="form-card p-8 max-w-4xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Professional Information -->
                    <div>
                        <h3 class="section-title">
                            <i class="fas fa-user-tie"></i> Professional Information
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <label class="form-label required">Full Name</label>
                                <input type="text"
                                    name="name"
                                    placeholder="Enter teacher's full name"
                                    required
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($teacher['name']); ?>">
                            </div>

                            <div>
                                <label class="form-label required">Qualification</label>
                                <input type="text"
                                    name="qualification"
                                    placeholder="e.g., M.Sc. Computer Science"
                                    required
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($teacher['qualification']); ?>">
                            </div>

                            <div>
                                <label class="form-label">Specialization</label>
                                <input type="text"
                                    name="specialization"
                                    placeholder="e.g., Web Development, Data Science"
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($teacher['specialization'] ?? ''); ?>">
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
                                        value="<?php echo htmlspecialchars($teacher['experience_years']); ?>">
                                </div>
                                <div>
                                    <label class="form-label required">Phone Number</label>
                                    <input type="tel"
                                        name="phone"
                                        placeholder="Phone number"
                                        required
                                        class="form-input"
                                        value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
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
                                        class="form-input"
                                        value="<?php echo htmlspecialchars($teacher['username']); ?>">
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
                                        value="<?php echo htmlspecialchars($teacher['email']); ?>">
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
                                        value="<?php echo htmlspecialchars($teacher['password']); ?>">
                                    <div class="absolute right-3 top-3 text-gray-400">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                    Password is stored as plain text
                                </p>
                            </div>

                            <div>
                                <label class="form-label required">Account Status</label>
                                <select name="status" required class="form-input">
                                    <option value="active" <?php echo ($teacher['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($teacher['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 class="font-medium text-yellow-800 mb-2">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    Important Note
                                </h4>
                                <p class="text-sm text-yellow-700">
                                    Changing username or password will require the teacher to use new credentials for login.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center mt-12 pt-8 border-t border-gray-200">
                    <div>
                        <a href="teachers.php?delete=<?php echo $id; ?>"
                            onclick="return confirm('Are you sure you want to mark <?php echo htmlspecialchars($teacher['name']); ?> as inactive? This will revoke their login access.')"
                            class="btn-danger flex items-center gap-2">
                            <i class="fas fa-user-slash"></i> Mark as Inactive
                        </a>
                    </div>
                    <div class="flex gap-4">
                        <a href="teachers.php"
                            class="bg-white text-gray-700 px-6 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all duration-300">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        <button type="submit"
                            class="btn-primary flex items-center gap-2">
                            <i class="fas fa-save"></i> Update Teacher
                        </button>
                    </div>
                </div>
            </form>

            <!-- System Information -->
            <div class="form-card p-8 mt-8 max-w-4xl mx-auto">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i> System Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-500 mb-1">Created At</div>
                        <div class="font-medium"><?php echo date('F j, Y \a\t g:i A', strtotime($teacher['created_at'])); ?></div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-500 mb-1">Last Updated</div>
                        <div class="font-medium">
                            <?php
                            if ($teacher['updated_at'] && $teacher['updated_at'] != '0000-00-00 00:00:00') {
                                echo date('F j, Y \a\t g:i A', strtotime($teacher['updated_at']));
                            } else {
                                echo 'Never updated';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm text-gray-500 mb-1">Record ID</div>
                        <div class="font-mono font-medium"><?php echo $teacher['id']; ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Status change warning
            const statusSelect = document.querySelector('select[name="status"]');
            const originalStatus = statusSelect.value;

            statusSelect.addEventListener('change', function() {
                if (this.value === 'inactive' && originalStatus === 'active') {
                    if (confirm('Setting teacher to inactive will block their login. Continue?')) {
                        return true;
                    } else {
                        this.value = originalStatus;
                        return false;
                    }
                }
            });

            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
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
    </script>

</body>

</html>
<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only teacher
if ($_SESSION['user_type'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

// Get teacher ID and details
$user_id = $_SESSION['user_id'];
$teacher_query = "SELECT * FROM teachers WHERE user_id = ?";
$stmt_teacher = $conn->prepare($teacher_query);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    header("Location: ../auth/login.php?error=teacher_not_found");
    exit;
}

$teacher_id = $teacher['id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

// Handle form submissions
$success_message = '';
$error_message = '';

// Update Profile Information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $experience_years = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
    $gender = trim($_POST['gender'] ?? '');
    $bio = trim($_POST['bio'] ?? '');



    // Validate required fields
    if (empty($name)) {
        $error_message = "Name is required.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update teacher table
            $update_teacher_query = "
            UPDATE teachers SET 
                name = ?,
                phone = ?,
                qualification = ?,
                experience_years = ?,
                gender = ?,
                bio = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            ";

            $stmt_teacher_update = $conn->prepare($update_teacher_query);
            $stmt_teacher_update->bind_param(
                "sssisssi",
                $name,
                $phone,
                $qualification,
                $experience_years,
                $gender,
                $bio,
                $teacher_id
            );

            if ($stmt_teacher_update->execute()) {
                // Also update user table name if it exists
                $update_user_query = "UPDATE users SET name = ? WHERE id = ?";
                $stmt_user_update = $conn->prepare($update_user_query);
                $stmt_user_update->bind_param("si", $name, $user_id);
                $stmt_user_update->execute();

                // Update session
                $_SESSION['user_name'] = $name;
                $teacher['name'] = $name;
                $teacher['phone'] = $phone;
                $teacher['qualification'] = $qualification;
                $teacher['experience_years'] = $experience_years;
                $teacher['gender'] = $gender;
                $teacher['bio'] = $bio;

                mysqli_commit($conn);
                $success_message = "Profile updated successfully!";
            } else {
                throw new Exception("Failed to update profile.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $check_query = "SELECT password FROM users WHERE id = ?";
        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result()->fetch_assoc();

        if (password_verify($current_password, $check_result['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass_query = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt_update_pass = $conn->prepare($update_pass_query);
            $stmt_update_pass->bind_param("si", $hashed_password, $user_id);

            if ($stmt_update_pass->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to update password.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}

// Update Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $new_email = trim($_POST['new_email']);
    $confirm_email = trim($_POST['confirm_email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($new_email) || empty($confirm_email) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($new_email !== $confirm_email) {
        $error_message = "Email addresses do not match.";
    } else {
        // Check if email already exists
        $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check_email = $conn->prepare($check_email_query);
        $stmt_check_email->bind_param("si", $new_email, $user_id);
        $stmt_check_email->execute();

        if ($stmt_check_email->get_result()->num_rows > 0) {
            $error_message = "This email is already registered by another user.";
        } else {
            // Verify password
            $check_query = "SELECT password FROM users WHERE id = ?";
            $stmt_check = $conn->prepare($check_query);
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $check_result = $stmt_check->get_result()->fetch_assoc();

            if (password_verify($password, $check_result['password'])) {
                // Update email
                $update_email_query = "UPDATE users SET email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt_update_email = $conn->prepare($update_email_query);
                $stmt_update_email->bind_param("si", $new_email, $user_id);

                if ($stmt_update_email->execute()) {
                    // Update session
                    $_SESSION['user_email'] = $new_email;
                    $user['email'] = $new_email;
                    $success_message = "Email updated successfully!";
                } else {
                    $error_message = "Failed to update email.";
                }
            } else {
                $error_message = "Password is incorrect.";
            }
        }
    }
}

// Update Notification Preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $progress_alerts = isset($_POST['progress_alerts']) ? 1 : 0;
    $assignment_alerts = isset($_POST['assignment_alerts']) ? 1 : 0;
    $weekly_reports = isset($_POST['weekly_reports']) ? 1 : 0;

    // Check if teacher_preferences table exists
    $table_exists_query = "SHOW TABLES LIKE 'teacher_preferences'";
    $table_result = $conn->query($table_exists_query);

    if ($table_result->num_rows > 0) {
        // Get existing preferences or insert new
        $check_prefs_query = "SELECT id FROM teacher_preferences WHERE teacher_id = ?";
        $stmt_check_prefs = $conn->prepare($check_prefs_query);
        $stmt_check_prefs->bind_param("i", $teacher_id);
        $stmt_check_prefs->execute();
        $exists = $stmt_check_prefs->get_result()->num_rows > 0;

        if ($exists) {
            // Update existing preferences
            $update_prefs_query = "
            UPDATE teacher_preferences SET 
                email_notifications = ?,
                progress_alerts = ?,
                assignment_alerts = ?,
                weekly_reports = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE teacher_id = ?
            ";

            $stmt_update_prefs = $conn->prepare($update_prefs_query);
            $stmt_update_prefs->bind_param(
                "iiiii",
                $email_notifications,
                $progress_alerts,
                $assignment_alerts,
                $weekly_reports,
                $teacher_id
            );
        } else {
            // Insert new preferences
            $insert_prefs_query = "
            INSERT INTO teacher_preferences (
                teacher_id,
                email_notifications,
                progress_alerts,
                assignment_alerts,
                weekly_reports
            ) VALUES (?, ?, ?, ?, ?)
            ";

            $stmt_update_prefs = $conn->prepare($insert_prefs_query);
            $stmt_update_prefs->bind_param(
                "iiiii",
                $teacher_id,
                $email_notifications,
                $progress_alerts,
                $assignment_alerts,
                $weekly_reports
            );
        }

        if ($stmt_update_prefs->execute()) {
            $success_message = "Notification preferences updated successfully!";
        } else {
            $error_message = "Failed to update notification preferences.";
        }
    } else {
        $error_message = "Notification preferences table does not exist.";
    }
}

// Get teacher preferences if they exist
$teacher_preferences = [
    'email_notifications' => 1,
    'progress_alerts' => 1,
    'assignment_alerts' => 1,
    'weekly_reports' => 0
];

// Check if teacher_preferences table exists before querying
$table_exists_query = "SHOW TABLES LIKE 'teacher_preferences'";
$table_result = $conn->query($table_exists_query);

if ($table_result->num_rows > 0) {
    $prefs_query = "SELECT * FROM teacher_preferences WHERE teacher_id = ?";
    $stmt_prefs = $conn->prepare($prefs_query);
    $stmt_prefs->bind_param("i", $teacher_id);
    $stmt_prefs->execute();
    $prefs_result = $stmt_prefs->get_result();
    if ($prefs_result->num_rows > 0) {
        $teacher_preferences = $prefs_result->fetch_assoc();
    }
}

// Get teacher privacy settings if they exist
$privacy_settings = [
    'profile_visibility' => 'public',
    'contact_visibility' => 'students_only',
    'show_online_status' => 1
];

// Check if teacher_privacy table exists
$table_exists_query = "SHOW TABLES LIKE 'teacher_privacy'";
$table_result = $conn->query($table_exists_query);

if ($table_result->num_rows > 0) {
    $privacy_query = "SELECT * FROM teacher_privacy WHERE teacher_id = ?";
    $stmt_privacy = $conn->prepare($privacy_query);
    $stmt_privacy->bind_param("i", $teacher_id);
    $stmt_privacy->execute();
    $privacy_result = $stmt_privacy->get_result();
    if ($privacy_result->num_rows > 0) {
        $privacy_settings = $privacy_result->fetch_assoc();
    }
}

// Get teacher system preferences if they exist
$system_preferences = [
    'theme' => 'light',
    'language' => 'en',
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
    'items_per_page' => 10
];

// Check if teacher_system_preferences table exists
$table_exists_query = "SHOW TABLES LIKE 'teacher_system_preferences'";
$table_result = $conn->query($table_exists_query);

if ($table_result->num_rows > 0) {
    $system_query = "SELECT * FROM teacher_system_preferences WHERE teacher_id = ?";
    $stmt_system = $conn->prepare($system_query);
    $stmt_system->bind_param("i", $teacher_id);
    $stmt_system->execute();
    $system_result = $stmt_system->get_result();
    if ($system_result->num_rows > 0) {
        $system_preferences = $system_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Settings | Teacher Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
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

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .form-input {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            background: white;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .setting-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--primary);
        }

        input:focus+.slider {
            box-shadow: 0 0 1px var(--primary);
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .tab-button {
            padding: 12px 24px;
            border: none;
            background: transparent;
            font-weight: 500;
            color: #6b7280;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .tab-button:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .tab-button.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            font-weight: bold;
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .info-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item i {
            width: 40px;
            color: var(--primary);
            font-size: 18px;
        }

        .info-label {
            flex: 1;
            font-weight: 500;
            color: #4b5563;
        }

        .info-value {
            flex: 2;
            color: #1f2937;
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .strength-weak {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .strength-medium {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .strength-strong {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .danger-zone {
            border: 2px solid #fee2e2;
            background: #fef2f2;
            border-radius: 12px;
            padding: 24px;
        }

        .danger-zone h4 {
            color: #dc2626;
        }

        .danger-zone p {
            color: #7f1d1d;
        }
    </style>
</head>

<body class="min-h-screen">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Account Settings</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-cog text-primary mr-2"></i>
                            Manage your account preferences and security settings
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm bg-primary text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-user-tie mr-2"></i>
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 flex items-center justify-between animate-pulse">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-green-800">Success!</h4>
                            <p class="text-green-700"><?php echo $success_message; ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="mb-6 p-5 rounded-xl bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-red-800">Error!</h4>
                            <p class="text-red-700"><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="glass-card p-1 mb-8">
                <div class="flex flex-wrap gap-2 p-2">
                    <button class="tab-button active" onclick="showTab('profile')">
                        <i class="fas fa-user mr-2"></i>Profile
                    </button>
                    <button class="tab-button" onclick="showTab('security')">
                        <i class="fas fa-shield-alt mr-2"></i>Security
                    </button>
                    <button class="tab-button" onclick="showTab('notifications')">
                        <i class="fas fa-bell mr-2"></i>Notifications
                    </button>
                    <button class="tab-button" onclick="showTab('privacy')">
                        <i class="fas fa-lock mr-2"></i>Privacy
                    </button>
                    <button class="tab-button" onclick="showTab('system')">
                        <i class="fas fa-desktop mr-2"></i>System
                    </button>
                    <button class="tab-button" onclick="showTab('danger')">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                    </button>
                </div>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-content active">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Profile Info -->
                    <div class="col-span-1">
                        <div class="card p-6">
                            <div class="profile-avatar mb-6">
                                <?php
                                $initials = strtoupper(substr($teacher['name'], 0, 2));
                                echo $initials;
                                ?>
                            </div>

                            <div class="text-center mb-6">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></h3>
                                <p class="text-gray-500">Teacher</p>
                                <?php if (!empty($teacher['qualification'])): ?>
                                    <p class="text-gray-600 mt-2">
                                        <i class="fas fa-graduation-cap mr-1"></i>
                                        <?php echo htmlspecialchars($teacher['qualification']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-4">
                                <div class="info-item">
                                    <i class="fas fa-envelope"></i>
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>

                                <?php if (!empty($teacher['phone'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <div class="info-label">Phone</div>
                                        <div class="info-value"><?php echo htmlspecialchars($teacher['phone']); ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($teacher['gender'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <div class="info-label">Gender</div>
                                        <div class="info-value"><?php echo htmlspecialchars($teacher['gender']); ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div class="info-label">Member Since</div>
                                    <div class="info-value"><?php echo date('M d, Y', strtotime($teacher['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <div class="col-span-2">
                        <div class="card p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-edit text-primary"></i>
                                Edit Profile Information
                            </h3>

                            <form method="POST" action="">
                                <div class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Full Name *
                                            </label>
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($teacher['name']); ?>"
                                                class="w-full form-input px-4 py-3 rounded-lg"
                                                required>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Phone Number
                                            </label>
                                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>"
                                                class="w-full form-input px-4 py-3 rounded-lg">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Gender
                                            </label>
                                            <select name="gender" class="w-full form-input px-4 py-3 rounded-lg">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo (isset($teacher['gender']) && $teacher['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo (isset($teacher['gender']) && $teacher['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo (isset($teacher['gender']) && $teacher['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Qualification
                                            </label>
                                            <input type="text" name="qualification" value="<?php echo htmlspecialchars($teacher['qualification'] ?? ''); ?>"
                                                class="w-full form-input px-4 py-3 rounded-lg"
                                                placeholder="e.g., B.Sc. in Computer Science">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                Experience (Years)
                                            </label>
                                            <input type="number" name="experience_years" value="<?php echo htmlspecialchars($teacher['experience_years'] ?? 0); ?>"
                                                class="w-full form-input px-4 py-3 rounded-lg"
                                                min="0" max="50">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Bio / About Me
                                        </label>
                                        <textarea name="bio" rows="4"
                                            class="w-full form-input px-4 py-3 rounded-lg"
                                            placeholder="Tell students about your experience and teaching philosophy..."><?php echo htmlspecialchars($teacher['bio'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="flex justify-end gap-4 pt-6 border-t">
                                        <button type="button" onclick="resetProfileForm()"
                                            class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                            Reset
                                        </button>
                                        <button type="submit" name="update_profile"
                                            class="px-6 py-3 btn-primary rounded-lg font-medium">
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div id="security" class="tab-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Change Password -->
                    <div class="card p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-key text-primary"></i>
                            Change Password
                        </h3>

                        <form method="POST" action="">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Current Password *
                                    </label>
                                    <input type="password" name="current_password"
                                        class="w-full form-input px-4 py-3 rounded-lg"
                                        required>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        New Password *
                                    </label>
                                    <input type="password" name="new_password" id="new_password"
                                        class="w-full form-input px-4 py-3 rounded-lg"
                                        required
                                        oninput="checkPasswordStrength(this.value)">
                                    <div class="password-strength">
                                        <div id="strengthFill" class="strength-fill" style="width: 0%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Must be at least 6 characters long
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Confirm New Password *
                                    </label>
                                    <input type="password" name="confirm_password" id="confirm_password"
                                        class="w-full form-input px-4 py-3 rounded-lg"
                                        required
                                        oninput="checkPasswordMatch()">
                                    <p id="passwordMatch" class="text-xs mt-2 hidden">
                                        <i class="fas mr-1"></i>
                                        <span></span>
                                    </p>
                                </div>

                                <div class="flex justify-end gap-4 pt-6 border-t">
                                    <button type="submit" name="change_password"
                                        class="px-6 py-3 btn-success rounded-lg font-medium">
                                        Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Update Email -->
                    <div class="card p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-envelope text-primary"></i>
                            Update Email Address
                        </h3>

                        <form method="POST" action="">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Current Email
                                    </label>
                                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                        class="w-full form-input px-4 py-3 rounded-lg bg-gray-50"
                                        readonly>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        New Email Address *
                                    </label>
                                    <input type="email" name="new_email"
                                        class="w-full form-input px-4 py-3 rounded-lg"
                                        required>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Confirm New Email *
                                    </label>
                                    <input type="email" name="confirm_email"
                                        class="w-full form-input px-4 py-3 rounded-lg"
                                        required>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Current Password (for verification) *
                                    </label>
                                    <input type="password" name="password"
                                        class="w-full form-input px-4 py-3 rounded-lg"
                                        required>
                                </div>

                                <div class="flex justify-end gap-4 pt-6 border-t">
                                    <button type="submit" name="update_email"
                                        class="px-6 py-3 btn-primary rounded-lg font-medium">
                                        Update Email
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div id="notifications" class="tab-content">
                <div class="card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-bell text-primary"></i>
                        Notification Preferences
                    </h3>

                    <form method="POST" action="">
                        <div class="space-y-6">
                            <!-- Email Notifications -->
                            <div class="flex items-center justify-between py-4 border-b">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Email Notifications</h4>
                                    <p class="text-sm text-gray-600">Receive email updates about system activities</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="email_notifications"
                                        <?php echo $teacher_preferences['email_notifications'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- Progress Alerts -->
                            <div class="flex items-center justify-between py-4 border-b">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Student Progress Alerts</h4>
                                    <p class="text-sm text-gray-600">Get notified when students complete milestones</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="progress_alerts"
                                        <?php echo $teacher_preferences['progress_alerts'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- Assignment Alerts -->
                            <div class="flex items-center justify-between py-4 border-b">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Assignment Alerts</h4>
                                    <p class="text-sm text-gray-600">Notifications for new assignments and submissions</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="assignment_alerts"
                                        <?php echo $teacher_preferences['assignment_alerts'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- Weekly Reports -->
                            <div class="flex items-center justify-between py-4">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Weekly Progress Reports</h4>
                                    <p class="text-sm text-gray-600">Receive weekly summaries of student progress</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="weekly_reports"
                                        <?php echo $teacher_preferences['weekly_reports'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 pt-6 border-t">
                                <button type="button" onclick="resetNotificationForm()"
                                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                    Reset to Default
                                </button>
                                <button type="submit" name="update_notifications"
                                    class="px-6 py-3 btn-primary rounded-lg font-medium">
                                    Save Preferences
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Privacy Tab -->
            <div id="privacy" class="tab-content">
                <div class="card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-lock text-primary"></i>
                        Privacy Settings
                    </h3>

                    <p class="text-yellow-600 bg-yellow-50 p-4 rounded-lg mb-6">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Note: The privacy settings tables need to be created in the database first.
                        Contact your administrator or create the tables using the SQL provided in the documentation.
                    </p>

                    <form method="POST" action="">
                        <div class="space-y-8">
                            <!-- Profile Visibility -->
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-3">Profile Visibility</h4>
                                <p class="text-sm text-gray-600 mb-4">Control who can see your profile information</p>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input type="radio" id="profile_public" name="profile_visibility" value="public"
                                            <?php echo $privacy_settings['profile_visibility'] === 'public' ? 'checked' : ''; ?>
                                            class="mr-3">
                                        <label for="profile_public" class="flex-1">
                                            <div class="font-medium">Public</div>
                                            <div class="text-sm text-gray-600">Everyone can see your profile</div>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="profile_students" name="profile_visibility" value="students_only"
                                            <?php echo $privacy_settings['profile_visibility'] === 'students_only' ? 'checked' : ''; ?>
                                            class="mr-3">
                                        <label for="profile_students" class="flex-1">
                                            <div class="font-medium">Students Only</div>
                                            <div class="text-sm text-gray-600">Only your students can see your profile</div>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="profile_private" name="profile_visibility" value="private"
                                            <?php echo $privacy_settings['profile_visibility'] === 'private' ? 'checked' : ''; ?>
                                            class="mr-3">
                                        <label for="profile_private" class="flex-1">
                                            <div class="font-medium">Private</div>
                                            <div class="text-sm text-gray-600">Only you can see your profile</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-3">Contact Information</h4>
                                <p class="text-sm text-gray-600 mb-4">Control who can see your contact details</p>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input type="radio" id="contact_students" name="contact_visibility" value="students_only"
                                            <?php echo $privacy_settings['contact_visibility'] === 'students_only' ? 'checked' : ''; ?>
                                            class="mr-3">
                                        <label for="contact_students" class="flex-1">
                                            <div class="font-medium">Students Only</div>
                                            <div class="text-sm text-gray-600">Only your students can see your contact info</div>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="contact_private" name="contact_visibility" value="private"
                                            <?php echo $privacy_settings['contact_visibility'] === 'private' ? 'checked' : ''; ?>
                                            class="mr-3">
                                        <label for="contact_private" class="flex-1">
                                            <div class="font-medium">Private</div>
                                            <div class="text-sm text-gray-600">Only you can see your contact info</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Online Status -->
                            <div class="flex items-center justify-between py-4 border-t">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Show Online Status</h4>
                                    <p class="text-sm text-gray-600">Display when you're active on the platform</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="show_online_status"
                                        <?php echo $privacy_settings['show_online_status'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 pt-6 border-t">
                                <button type="button" onclick="resetPrivacyForm()"
                                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                    Reset
                                </button>
                                <button type="submit" name="update_privacy"
                                    class="px-6 py-3 btn-primary rounded-lg font-medium">
                                    Save Privacy Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Tab -->
            <div id="system" class="tab-content">
                <div class="card p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-desktop text-primary"></i>
                        System Preferences
                    </h3>

                    <p class="text-yellow-600 bg-yellow-50 p-4 rounded-lg mb-6">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Note: The system preferences tables need to be created in the database first.
                        Contact your administrator or create the tables using the SQL provided in the documentation.
                    </p>

                    <form method="POST" action="">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Theme -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Theme</label>
                                    <select name="theme" class="w-full form-input px-4 py-3 rounded-lg">
                                        <option value="light" <?php echo $system_preferences['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                                        <option value="dark" <?php echo $system_preferences['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                        <option value="auto" <?php echo $system_preferences['theme'] === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                    </select>
                                </div>

                                <!-- Language -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Language</label>
                                    <select name="language" class="w-full form-input px-4 py-3 rounded-lg">
                                        <option value="en" <?php echo $system_preferences['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                        <option value="es" <?php echo $system_preferences['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                        <option value="fr" <?php echo $system_preferences['language'] === 'fr' ? 'selected' : ''; ?>>French</option>
                                        <option value="de" <?php echo $system_preferences['language'] === 'de' ? 'selected' : ''; ?>>German</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Timezone -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Timezone</label>
                                    <select name="timezone" class="w-full form-input px-4 py-3 rounded-lg">
                                        <option value="UTC" <?php echo $system_preferences['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        <option value="America/New_York" <?php echo $system_preferences['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                        <option value="America/Chicago" <?php echo $system_preferences['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                        <option value="America/Denver" <?php echo $system_preferences['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                        <option value="America/Los_Angeles" <?php echo $system_preferences['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                        <option value="Europe/London" <?php echo $system_preferences['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                        <option value="Asia/Kolkata" <?php echo $system_preferences['timezone'] === 'Asia/Kolkata' ? 'selected' : ''; ?>>India</option>
                                    </select>
                                </div>

                                <!-- Date Format -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date Format</label>
                                    <select name="date_format" class="w-full form-input px-4 py-3 rounded-lg">
                                        <option value="Y-m-d" <?php echo $system_preferences['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="m/d/Y" <?php echo $system_preferences['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                        <option value="d/m/Y" <?php echo $system_preferences['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        <option value="F j, Y" <?php echo $system_preferences['date_format'] === 'F j, Y' ? 'selected' : ''; ?>>Month Day, Year</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Items per page -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Items Per Page</label>
                                <select name="items_per_page" class="w-full form-input px-4 py-3 rounded-lg">
                                    <option value="5" <?php echo $system_preferences['items_per_page'] == 5 ? 'selected' : ''; ?>>5 items</option>
                                    <option value="10" <?php echo $system_preferences['items_per_page'] == 10 ? 'selected' : ''; ?>>10 items</option>
                                    <option value="25" <?php echo $system_preferences['items_per_page'] == 25 ? 'selected' : ''; ?>>25 items</option>
                                    <option value="50" <?php echo $system_preferences['items_per_page'] == 50 ? 'selected' : ''; ?>>50 items</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Number of items to display per page in lists and tables
                                </p>
                            </div>

                            <div class="flex justify-end gap-4 pt-6 border-t">
                                <button type="button" onclick="resetSystemForm()"
                                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                                    Reset to Default
                                </button>
                                <button type="submit" name="update_system_preferences"
                                    class="px-6 py-3 btn-primary rounded-lg font-medium">
                                    Save Preferences
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone Tab -->
            <div id="danger" class="tab-content">
                <div class="space-y-6">
                    <!-- Export Data -->
                    <div class="card p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-download text-primary"></i>
                            Export Data
                        </h3>
                        <p class="text-gray-600 mb-4">
                            Download all your data including student progress, assignments, and profile information.
                        </p>
                        <button onclick="exportData()"
                            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg hover:opacity-90 transition-opacity">
                            <i class="fas fa-download mr-2"></i> Export My Data
                        </button>
                    </div>

                    <!-- Delete Account -->
                    <div class="danger-zone">
                        <h3 class="text-xl font-bold text-red-600 mb-4 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            Delete Account
                        </h3>
                        <p class="text-red-700 mb-4">
                            <strong>Warning:</strong> This action is permanent and cannot be undone. All your data including
                            student progress records, assignments, and profile information will be permanently deleted.
                        </p>
                        <button onclick="showDeleteAccountModal()"
                            class="px-6 py-3 btn-danger rounded-lg font-medium">
                            <i class="fas fa-trash mr-2"></i> Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div id="deleteAccountModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="glass-card max-w-md w-full">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-gradient-to-r from-red-500 to-rose-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Delete Account</h3>
                    <p class="text-gray-600 mb-4">
                        Are you absolutely sure you want to delete your account? This action cannot be undone.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Type "DELETE" to confirm
                        </label>
                        <input type="text" id="confirmDelete"
                            class="w-full form-input px-4 py-3 rounded-lg border-red-300"
                            placeholder="Type DELETE here">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Enter your password
                        </label>
                        <input type="password" id="deletePassword"
                            class="w-full form-input px-4 py-3 rounded-lg border-red-300">
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t">
                    <button onclick="closeDeleteAccountModal()"
                        class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button onclick="deleteAccount()"
                        class="px-6 py-3 btn-danger rounded-lg font-medium"
                        id="deleteButton" disabled>
                        <i class="fas fa-trash mr-2"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab navigation
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabId).classList.add('active');

            // Activate clicked tab button
            event.currentTarget.classList.add('active');
        }

        // Profile form reset
        function resetProfileForm() {
            if (confirm('Reset all profile changes to current values?')) {
                // In a real application, this would reload the form data from the server
                // For now, we'll just reload the page
                window.location.reload();
            }
        }

        // Notification form reset
        function resetNotificationForm() {
            if (confirm('Reset all notification preferences to default values?')) {
                document.querySelectorAll('input[name="email_notifications"]')[0].checked = true;
                document.querySelectorAll('input[name="progress_alerts"]')[0].checked = true;
                document.querySelectorAll('input[name="assignment_alerts"]')[0].checked = true;
                document.querySelectorAll('input[name="weekly_reports"]')[0].checked = false;
            }
        }

        // Privacy form reset
        function resetPrivacyForm() {
            if (confirm('Reset all privacy settings to default values?')) {
                document.getElementById('profile_students').checked = true;
                document.getElementById('contact_students').checked = true;
                document.querySelectorAll('input[name="show_online_status"]')[0].checked = true;
            }
        }

        // System form reset
        function resetSystemForm() {
            if (confirm('Reset all system preferences to default values?')) {
                document.querySelectorAll('select[name="theme"]')[0].value = 'light';
                document.querySelectorAll('select[name="language"]')[0].value = 'en';
                document.querySelectorAll('select[name="timezone"]')[0].value = 'UTC';
                document.querySelectorAll('select[name="date_format"]')[0].value = 'Y-m-d';
                document.querySelectorAll('select[name="items_per_page"]')[0].value = '10';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('strengthFill');
            let strength = 0;
            let colorClass = 'strength-weak';

            // Length check
            if (password.length >= 6) strength += 25;
            if (password.length >= 10) strength += 15;

            // Character variety checks
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;

            // Cap at 100%
            strength = Math.min(strength, 100);

            // Set color based on strength
            if (strength >= 80) {
                colorClass = 'strength-strong';
            } else if (strength >= 60) {
                colorClass = 'strength-medium';
            }

            // Update visual indicator
            strengthFill.className = `strength-fill ${colorClass}`;
            strengthFill.style.width = `${strength}%`;
        }

        // Password match checker
        function checkPasswordMatch() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('passwordMatch');

            if (newPass && confirmPass) {
                if (newPass === confirmPass) {
                    matchElement.innerHTML = '<i class="fas fa-check text-green-500"></i> <span class="text-green-600">Passwords match</span>';
                    matchElement.classList.remove('hidden');
                } else {
                    matchElement.innerHTML = '<i class="fas fa-times text-red-500"></i> <span class="text-red-600">Passwords do not match</span>';
                    matchElement.classList.remove('hidden');
                }
            } else {
                matchElement.classList.add('hidden');
            }
        }

        // Delete account modal
        function showDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('hidden');
            document.getElementById('deleteAccountModal').classList.add('flex');

            // Reset form
            document.getElementById('confirmDelete').value = '';
            document.getElementById('deletePassword').value = '';
            document.getElementById('deleteButton').disabled = true;
        }

        function closeDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.add('hidden');
            document.getElementById('deleteAccountModal').classList.remove('flex');
        }

        // Enable delete button when "DELETE" is typed
        document.getElementById('confirmDelete')?.addEventListener('input', function() {
            const deleteButton = document.getElementById('deleteButton');
            deleteButton.disabled = this.value !== 'DELETE' || !document.getElementById('deletePassword').value;
        });

        document.getElementById('deletePassword')?.addEventListener('input', function() {
            const deleteButton = document.getElementById('deleteButton');
            deleteButton.disabled = document.getElementById('confirmDelete').value !== 'DELETE' || !this.value;
        });

        // Delete account function
        function deleteAccount() {
            const confirmText = document.getElementById('confirmDelete').value;
            const password = document.getElementById('deletePassword').value;

            if (confirmText !== 'DELETE') {
                alert('Please type "DELETE" to confirm.');
                return;
            }

            if (!password) {
                alert('Please enter your password.');
                return;
            }

            if (confirm('Are you absolutely sure? This action cannot be undone!')) {
                // In a real application, this would make an AJAX call to delete the account
                alert('Account deletion would be processed here. In a real implementation, this would call the server to delete the account.');
                closeDeleteAccountModal();
            }
        }

        // Export data function
        function exportData() {
            // Show loading indicator
            const button = event.currentTarget;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Exporting...';
            button.disabled = true;

            // Simulate export process
            setTimeout(() => {
                // In a real application, this would trigger a download
                alert('Data export started. You will receive an email with your data shortly.');
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }

        // Initialize password strength check on page load
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordField = document.getElementById('new_password');
            if (newPasswordField) {
                checkPasswordStrength(newPasswordField.value);
            }

            // Set initial tab based on URL hash
            const hash = window.location.hash.substring(1);
            if (hash && ['profile', 'security', 'notifications', 'privacy', 'system', 'danger'].includes(hash)) {
                showTab(hash);
                // Update tab button
                document.querySelectorAll('.tab-button').forEach(button => {
                    if (button.onclick.toString().includes(hash)) {
                        button.click();
                    }
                });
            }
        });
    </script>

</body>

</html>
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

        .border-red-300 {
            border-color: #fca5a5;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
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
                    <h1 class="text-2xl font-bold text-gray-800">Add New Teacher</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-chalkboard-teacher text-purple-500 mr-1"></i>
                        Register a new teacher to the academy
                    </p>
                </div>
                <div>
                    <a href="teachers.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Teachers
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

            <!-- Teacher Form -->
            <div class="form-container">
                <form method="POST">
                    <!-- Personal Information Section -->
                    <div class="mb-8">
                        <h3 class="section-title">Personal Information</h3>

                        <!-- Avatar Preview -->
                        <div class="avatar-preview" id="avatarPreview">
                            <span id="avatarInitial">?</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div>
                                <label class="form-label required">Full Name</label>
                                <input type="text"
                                    name="name"
                                    class="form-input"
                                    placeholder="Enter teacher's full name"
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                    required
                                    oninput="updateAvatar()">
                            </div>

                            <!-- Qualification -->
                            <div>
                                <label class="form-label required">Qualification</label>
                                <input type="text"
                                    name="qualification"
                                    class="form-input"
                                    placeholder="e.g., M.Sc. Computer Science, B.Ed."
                                    value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Specialization -->
                            <div>
                                <label class="form-label">Specialization</label>
                                <input type="text"
                                    name="specialization"
                                    class="form-input"
                                    placeholder="e.g., Web Development, Data Science"
                                    value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
                            </div>

                            <!-- Experience Years -->
                            <div>
                                <label class="form-label required">Experience (Years)</label>
                                <input type="number"
                                    name="experience_years"
                                    class="form-input"
                                    placeholder="Years of experience"
                                    value="<?php echo isset($_POST['experience_years']) ? $_POST['experience_years'] : ''; ?>"
                                    required
                                    min="0">
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="form-label required">Phone Number</label>
                                <input type="tel"
                                    name="phone"
                                    class="form-input"
                                    placeholder="Enter phone number"
                                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="form-label required">Email</label>
                                <input type="email"
                                    name="email"
                                    class="form-input"
                                    placeholder="teacher@example.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <!-- Login Information Section -->
                    <div class="mb-8">
                        <h3 class="section-title">Login Information</h3>
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

                            <!-- Password -->
                            <div>
                                <label class="form-label required">Password</label>
                                <input type="text"
                                    name="password"
                                    class="form-input"
                                    placeholder="Enter password"
                                    value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>"
                                    required
                                    id="passwordInput">
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                    Password will be stored as plain text
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
                                    Teacher Code: <span class="font-mono font-medium">TCH-<?php echo date('Ymd'); ?>XXXX</span>
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
                                    User Type: Teacher
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

                    <!-- Important Notes -->
                    <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400 mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Important Notes:</strong>
                                </p>
                                <ul class="text-sm text-blue-700 mt-2 space-y-1">
                                    <li>• Teacher will receive login credentials via email</li>
                                    <li>• Email address must be active for communication</li>
                                    <li>• Phone number will be used for emergency contact</li>
                                    <li>• Teacher code will be generated automatically</li>
                                    <li>• Specialization helps in assigning relevant courses</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="teachers.php"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Teacher
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function updateAvatar() {
            const nameInput = document.querySelector('input[name="name"]');
            const avatarInitial = document.getElementById('avatarInitial');

            if (nameInput.value.trim()) {
                const initial = nameInput.value.trim().charAt(0).toUpperCase();
                avatarInitial.textContent = initial;
            } else {
                avatarInitial.textContent = '?';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAvatar();

            // Auto-suggest password
            const passwordInput = document.getElementById('passwordInput');
            if (!passwordInput.value) {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let password = '';
                for (let i = 0; i < 8; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                passwordInput.value = password;
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
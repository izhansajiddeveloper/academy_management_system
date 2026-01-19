<?php
require_once "../config/db.php";
require_once "../includes/functions.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!$username || !$password) {
        $error = "Please fill all fields!";
    } else {
        $stmt = $conn->prepare("SELECT id, user_type_id FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $user_type_id);

        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            // Set session
            $_SESSION['user_id'] = $user_id;

            // Set user type
            switch ($user_type_id) {
                case 1:
                    $_SESSION['user_type'] = 'admin';
                    redirect("/academy_management_system/admin/dashboard.php");
                    break;
                case 2:
                    $_SESSION['user_type'] = 'teacher';
                    redirect("/teacher/dashboard.php");
                    break;
                case 3:
                    $_SESSION['user_type'] = 'student';
                    redirect("/academy_management_system/student/dashboard.php");
                    break;
                default:
                    $error = "Invalid user type!";
            }
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduSkill Pro Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4A6FA5;
            --accent-teal: #2A9D8F;
            --accent-orange: #E76F51;
        }

        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e8f1ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
            border: 1px solid #f1f5f9;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3A5A8C 100%);
            padding: 40px;
            text-align: center;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 36px;
            color: white;
        }

        .login-body {
            padding: 40px;
        }

        .input-group {
            margin-bottom: 25px;
        }

        .input-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }

        .input-field:hover {
            border-color: #cbd5e1;
        }

        .login-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--accent-teal), #238A7C);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(42, 157, 143, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .success-message {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider-text {
            padding: 0 20px;
            color: #64748b;
            font-size: 14px;
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #f1f5f9;
        }

        .register-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(74, 111, 165, 0.1);
            color: var(--primary-blue);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 15px;
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 18px;
        }

        .input-wrapper {
            position: relative;
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-3">Welcome Back!</h1>
                <p class="text-blue-100">Sign in to your EduSkill Pro account</p>
                <div class="role-badge">
                    <i class="fas fa-user-shield"></i> Secure Login Portal
                </div>
            </div>

            <div class="login-body">
                <?php if (isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        Registration successful! Please login with your credentials.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label class="input-label">
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <input
                            type="text"
                            name="username"
                            class="input-field"
                            placeholder="Enter your username"
                            required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div class="input-group">
                        <label class="input-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                name="password"
                                class="input-field"
                                placeholder="Enter your password"
                                required
                                id="password">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="far fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <
                
    <?php include "../includes/footer.php"; ?>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add animation to form
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.input-field');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('input-focused');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('input-focused');
                });
            });
        });
    </script>
</body>

</html>
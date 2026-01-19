<?php
require_once __DIR__ . '/../config/db.php';

// Check login safely - no need to start session here as it's already started in db.php
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
$userRole = isset($_SESSION['user_type']) && !empty($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSkill Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <nav class="bg-white shadow-md border-b-4 border-teal-500 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">

                <!-- Logo Section -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-teal-500 text-white rounded-lg flex items-center justify-center shadow">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500">EduSkill Pro</div>
                        <div class="text-xs font-semibold text-orange-500 uppercase tracking-wider -mt-1">Learn • Grow • Succeed</div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="flex items-center space-x-4">
                    <a href="/academy_management_system/index.php" class="px-4 py-2 font-semibold rounded hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-teal-600 bg-teal-50' : 'text-gray-700'; ?>">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="/academy_management_system/about.php" class="px-4 py-2 font-semibold rounded hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'text-teal-600 bg-teal-50' : 'text-gray-700'; ?>">
                        <i class="fas fa-info-circle mr-1"></i> About
                    </a>
                    <a href="/academy_management_system/services.php" class="px-4 py-2 font-semibold rounded hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'text-teal-600 bg-teal-50' : 'text-gray-700'; ?>">
                        <i class="fas fa-laptop-code mr-1"></i> Courses
                    </a>
                    <a href="/academy_management_system/contact.php" class="px-4 py-2 font-semibold rounded hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'text-teal-600 bg-teal-50' : 'text-gray-700'; ?>">
                        <i class="fas fa-envelope mr-1"></i> Contact
                    </a>

                    <!-- Auth Buttons -->
                    <?php if (!$isLoggedIn): ?>
                        
                        <a href="/academy_management_system/auth/login.php" class="px-4 py-2 border-2 border-blue-600 text-blue-600 rounded hover:bg-blue-50 flex items-center gap-2">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php else: ?>
                        <!-- Show Dashboard/Logout when logged in -->
                        <div class="flex items-center gap-3">
                            <?php if ($userRole): ?>
                                <span class="px-3 py-1 bg-teal-500 text-white text-xs font-bold rounded-full flex items-center gap-1">
                                    <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars(ucfirst($userRole)); ?>
                                </span>
                            <?php endif; ?>
                            <a href="/academy_management_system/<?php echo $userRole . '/dashboard.php'; ?>" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-2">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="/academy_management_system/auth/logout.php" class="px-4 py-2 border-2 border-orange-500 text-orange-500 rounded hover:bg-orange-50 flex items-center gap-2">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</body>

</html>
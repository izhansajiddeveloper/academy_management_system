<!-- File: student/includes/sidebar.php -->
<aside class="w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white sticky top-0 h-screen flex flex-col shadow-xl">
    <!-- Header section with enhanced design -->
    <div class="p-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-user-graduate text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-purple-300 bg-clip-text text-transparent">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1 font-medium">Student Portal</p>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-400">
            <i class="fas fa-circle text-green-500 text-xs"></i>
            <span>Student Online</span>
        </div>
    </div>

    <!-- User profile mini-card -->
    <?php
    // Get current student info from session
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../includes/auth_check.php';

    $current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Student';
    $current_role = 'Student';

    // Get student details from database
    if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'student') {
        $user_id = $_SESSION['user_id'];
        $student_query = "SELECT * FROM students WHERE user_id = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $student_result = $stmt->get_result();

        if ($student_result->num_rows > 0) {
            $student = $student_result->fetch_assoc();
            $current_user = $student['name'] ?? 'Student';
            $student_code = $student['student_code'] ?? '';
        }
    }
    ?>
    <div class="p-4 border-b border-gray-700/50 bg-gray-800/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-green-500 to-blue-500 flex items-center justify-center text-white font-semibold shadow-md">
                <?php echo strtoupper(substr($current_user, 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate"><?php echo htmlspecialchars($current_user); ?></p>
                <p class="text-xs text-gray-300 truncate">
                    <?php echo htmlspecialchars($current_role); ?>
                    <?php if (!empty($student_code)): ?>
                        <span class="text-green-300 ml-1">(<?php echo htmlspecialchars($student_code); ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="/academy_management_system/student/dashboard.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-chart-line text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-300' : ''; ?>">Dashboard</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- My Courses -->
            <a href="/academy_management_system/student/my_courses.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'my_courses.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'my_courses.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-book-open text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'my_courses.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'my_courses.php') ? 'text-blue-300' : ''; ?>">My Courses</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'my_courses.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Attendance -->
            <div class="space-y-1">
                <button onclick="toggleAttendanceMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php', 'attendance_report.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php', 'attendance_report.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-calendar-check text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php', 'attendance_report.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php', 'attendance_report.php'])) ? 'text-blue-300' : ''; ?>">Attendance</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php', 'attendance_report.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="attendanceChevron"></i>
                </button>

                <div id="attendanceMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'attendance_view.php', 'attendance_report.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/student/attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-calendar-alt text-xs"></i>
                        </div>
                        <span>My Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/student/attendance_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chart-pie text-xs"></i>
                        </div>
                        <span>Attendance Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'attendance_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Fees -->
            <div class="space-y-1">
                <button onclick="toggleFeesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fees.php', 'payment_history.php', 'payment_receipts.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fees.php', 'payment_history.php', 'payment_receipts.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-rupee-sign text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fees.php', 'payment_history.php', 'payment_receipts.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fees.php', 'payment_history.php', 'payment_receipts.php'])) ? 'text-blue-300' : ''; ?>">Fees & Payments</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fees.php', 'payment_history.php', 'payment_receipts.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="feesChevron"></i>
                </button>

                <div id="feesMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fees.php', 'payment_history.php', 'payment_receipts.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/student/fees.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'fees.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'fees.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-credit-card text-xs"></i>
                        </div>
                        <span>Fee Details</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'fees.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/student/payment_history.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'payment_history.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'payment_history.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-history text-xs"></i>
                        </div>
                        <span>Payment History</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'payment_history.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    
                </div>
            </div>

            <!-- Quizzes & Tests -->
            <!-- Quizzes & Tests -->
<div class="space-y-1">
    <button onclick="toggleQuizMenu()"
        class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 
        <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_quiz.php','take_quiz.php','quiz_results.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">

        <span class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center 
                <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_quiz.php','take_quiz.php','quiz_results.php'])) ? 'bg-blue-500/20' : ''; ?>">
                <i class="fas fa-question-circle text-sm 
                    <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_quiz.php','take_quiz.php','quiz_results.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
            </div>
            <span class="font-medium 
                <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_quiz.php','take_quiz.php','quiz_results.php'])) ? 'text-blue-300' : ''; ?>">
                Quizzes & Tests
            </span>
        </span>

        <i class="fas fa-chevron-down text-xs transition-transform duration-300 
            <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_quiz.php','take_quiz.php','quiz_results.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
            id="quizChevron"></i>
    </button>

    <div id="quizMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 
        <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_quiz.php','take_quiz.php','quiz_results.php'])) ? '' : 'hidden'; ?>">

        <a href="/academy_management_system/student/student_quiz.php"
            class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm 
            <?php echo (basename($_SERVER['PHP_SELF']) == 'student_quiz.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
            <i class="fas fa-list text-xs"></i>
            <span>Quiz List</span>
        </a>

        <a href="/academy_management_system/student/take_quiz.php"
            class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm 
            <?php echo (basename($_SERVER['PHP_SELF']) == 'take_quiz.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
            <i class="fas fa-play text-xs"></i>
            <span>Take Quiz</span>
        </a>

        <a href="/academy_management_system/student/quiz_results.php"
            class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm 
            <?php echo (basename($_SERVER['PHP_SELF']) == 'quiz_results.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
            <i class="fas fa-chart-bar text-xs"></i>
            <span>Quiz Results</span>
        </a>

    </div>
</div>


            <!-- Progress & Grades -->
            <div class="space-y-1">
                <button onclick="toggleProgressMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['progress.php', 'grades.php', 'assignments.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['progress.php', 'grades.php', 'assignments.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-chart-line text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['progress.php', 'grades.php', 'assignments.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['progress.php', 'grades.php', 'assignments.php'])) ? 'text-blue-300' : ''; ?>">Progress & Grades</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['progress.php', 'grades.php', 'assignments.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="progressChevron"></i>
                </button>

                <div id="progressMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['progress.php', 'grades.php', 'assignments.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/student/student_progress.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'progress.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'progress.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chart-bar text-xs"></i>
                        </div>
                        <span>Learning Progress</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'progress.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/student/student_grades.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'grades.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'grades.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-star text-xs"></i>
                        </div>
                        <span>Grades & Results</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'grades.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                  
                     
                </div>
            </div>

            <!-- Certificates -->
            <a href="/academy_management_system/student/certificates.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'certificates.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'certificates.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-certificate text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'certificates.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'certificates.php') ? 'text-blue-300' : ''; ?>">Certificates</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'certificates.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Study Materials -->
            <div class="space-y-1">
                <button onclick="toggleMaterialsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['materials.php', 'syllabus.php', 'library.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['materials.php', 'syllabus.php', 'library.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-file-alt text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['materials.php', 'syllabus.php', 'library.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['materials.php', 'syllabus.php', 'library.php'])) ? 'text-blue-300' : ''; ?>">Study Materials</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['materials.php', 'syllabus.php', 'library.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="materialsChevron"></i>
                </button>

                <div id="materialsMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['materials.php', 'syllabus.php', 'library.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/student/materials.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'materials.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'materials.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-download text-xs"></i>
                        </div>
                        <span>Download Materials</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'materials.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                   
                </div>
            </div>

            <!-- My Profile -->
            <a href="/academy_management_system/student/profile.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-user text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'text-blue-300' : ''; ?>">My Profile</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'profile.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

        </nav>

        <!-- Quick Actions -->
        <div class="mt-8 pt-6 border-t border-gray-700/50">
            <h4 class="font-medium text-gray-300 mb-3 text-xs uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-bolt text-yellow-400"></i>
                Quick Actions
            </h4>
            <div class="space-y-2">
                <?php
                // Get student stats for quick actions
                if (isset($student_id)) {
                    // Get today's attendance status
                    $today_att_query = "SELECT COUNT(*) as today_present FROM student_attendance WHERE student_id = ? AND attendance_date = CURDATE() AND attendance_status = 'present'";
                    $stmt = $conn->prepare($today_att_query);
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $today_result = $stmt->get_result()->fetch_assoc();
                    $today_present = $today_result['today_present'] > 0;
                    
                    // Get enrolled courses count
                    $course_query = "SELECT COUNT(*) as course_count FROM student_enrollments WHERE student_id = ? AND status = 'active'";
                    $stmt2 = $conn->prepare($course_query);
                    $stmt2->bind_param("i", $student_id);
                    $stmt2->execute();
                    $course_result = $stmt2->get_result()->fetch_assoc();
                    $course_count = $course_result['course_count'] ?? 0;
                    
                    // Get pending fee amount
                    $pending_fee_query = "
                    SELECT COALESCE(SUM(fs.total_fee), 0) as total_fee,
                           COALESCE(SUM(fc.amount_paid), 0) as paid_fee
                    FROM student_enrollments se
                    LEFT JOIN fee_structures fs ON se.skill_id = fs.skill_id AND se.session_id = fs.session_id
                    LEFT JOIN fee_collections fc ON se.student_id = fc.student_id AND se.skill_id = fc.skill_id
                    WHERE se.student_id = ? AND se.status = 'active'
                    ";
                    $stmt3 = $conn->prepare($pending_fee_query);
                    $stmt3->bind_param("i", $student_id);
                    $stmt3->execute();
                    $fee_result = $stmt3->get_result()->fetch_assoc();
                    $pending_fee = max(0, ($fee_result['total_fee'] ?? 0) - ($fee_result['paid_fee'] ?? 0));
                } else {
                    $today_present = false;
                    $course_count = 0;
                    $pending_fee = 0;
                }
                ?>
                <a href="/academy_management_system/student/attendance.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded <?php echo $today_present ? 'bg-green-900/30 group-hover:bg-green-500/30' : 'bg-yellow-900/30 group-hover:bg-yellow-500/30'; ?> flex items-center justify-center transition-colors">
                        <i class="fas fa-calendar-check <?php echo $today_present ? 'text-green-400' : 'text-yellow-400'; ?> text-xs"></i>
                    </div>
                    <span><?php echo $today_present ? 'Today: Present' : 'Check Attendance'; ?></span>
                </a>
                <a href="/academy_management_system/student/my_courses.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-book-open text-blue-400 text-xs"></i>
                    </div>
                    <span>My Courses (<?php echo $course_count; ?>)</span>
                </a>
                <a href="/academy_management_system/student/fees.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded <?php echo $pending_fee > 0 ? 'bg-red-900/30 group-hover:bg-red-500/30' : 'bg-green-900/30 group-hover:bg-green-500/30'; ?> flex items-center justify-center transition-colors">
                        <i class="fas fa-rupee-sign <?php echo $pending_fee > 0 ? 'text-red-400' : 'text-green-400'; ?> text-xs"></i>
                    </div>
                    <span>Fees <?php echo $pending_fee > 0 ? '(Rs' . number_format($pending_fee) . ')' : '(Paid)'; ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-4 border-t border-gray-700/50 bg-gray-900/30">
        <div class="text-xs text-gray-400">
            <p class="mb-1">Student Portal v2.0</p>
            <p class="flex items-center gap-1">
                <i class="fas fa-user-graduate text-blue-400"></i>
                <span>Learning Platform</span>
            </p>
        </div>
    </div>
</aside>

<script>
    // Sidebar toggle functions
    function toggleAttendanceMenu() {
        const menu = document.getElementById('attendanceMenu');
        const chevron = document.getElementById('attendanceChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }
function toggleQuizMenu() {
    document.getElementById('quizMenu').classList.toggle('hidden');
    document.getElementById('quizChevron').classList.toggle('rotate-180');
}
    function toggleFeesMenu() {
        const menu = document.getElementById('feesMenu');
        const chevron = document.getElementById('feesChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleProgressMenu() {
        const menu = document.getElementById('progressMenu');
        const chevron = document.getElementById('progressChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleMaterialsMenu() {
        const menu = document.getElementById('materialsMenu');
        const chevron = document.getElementById('materialsChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    // Auto-expand menu if on a subpage
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = '<?php echo basename($_SERVER["PHP_SELF"]); ?>';
        const menus = ['attendance', 'fees', 'progress', 'materials'];

        menus.forEach(menu => {
            const menuElement = document.getElementById(`${menu}Menu`);
            const chevron = document.getElementById(`${menu}Chevron`);

            // Check if current page matches any subpage in this menu
            if (menuElement && chevron && menuElement.querySelector(`a[href*="${currentPage}"]`)) {
                menuElement.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            }
        });
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.5);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.7);
    }

    /* Smooth transitions */
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 200ms;
    }
</style>
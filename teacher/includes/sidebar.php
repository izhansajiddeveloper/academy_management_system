<!-- File: C:\xampp\htdocs\academy_management_system\teacher\includes\sidebar.php -->
<aside class="w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white sticky top-0 h-screen flex flex-col shadow-xl">
    <!-- Header section with enhanced design -->
    <div class="p-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-chalkboard-teacher text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-purple-300 bg-clip-text text-transparent">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1 font-medium">Teacher Panel</p>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-400">
            <i class="fas fa-circle text-green-500 text-xs"></i>
            <span>Online</span>
        </div>
    </div>

    <!-- User profile mini-card -->
    <?php
    // Get current teacher info from session
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../includes/auth_check.php';

    $current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Teacher';
    $current_role = 'Teacher';

    // Get teacher details from database
    if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'teacher') {
        $user_id = $_SESSION['user_id'];
        $teacher_query = "SELECT * FROM teachers WHERE user_id = ?";
        $stmt = $conn->prepare($teacher_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $teacher_result = $stmt->get_result();

        if ($teacher_result->num_rows > 0) {
            $teacher = $teacher_result->fetch_assoc();
            $current_user = $teacher['name'] ?? 'Teacher';
            $teacher_code = $teacher['teacher_code'] ?? '';
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
                    <?php if (!empty($teacher_code)): ?>
                        <span class="text-green-300 ml-1">(<?php echo htmlspecialchars($teacher_code); ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="/academy_management_system/teacher/dashboard.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-chart-line text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-300' : ''; ?>">Dashboard</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- My Batches -->
            <a href="/academy_management_system/teacher/my_batches.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'my_batches.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'my_batches.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-users text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'my_batches.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'my_batches.php') ? 'text-blue-300' : ''; ?>">My Batches</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'my_batches.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Attendance -->
            <div class="space-y-1">
                <button onclick="toggleAttendanceMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_attendance.php', 'view_attendance.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_attendance.php', 'view_attendance.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-calendar-check text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_attendance.php', 'view_attendance.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_attendance.php', 'view_attendance.php'])) ? 'text-blue-300' : ''; ?>">Attendance</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_attendance.php', 'view_attendance.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="attendanceChevron"></i>
                </button>

                <div id="attendanceMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_attendance.php', 'view_attendance.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/teacher/attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-clipboard-check text-xs"></i>
                        </div>
                        <span>Mark Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/teacher/view_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'view_attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'view_attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-eye text-xs"></i>
                        </div>
                        <span>View Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'view_attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Student Progress -->
            <a href="/academy_management_system/teacher/student_progress.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'student_progress.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'student_progress.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-chart-line text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'student_progress.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'student_progress.php') ? 'text-blue-300' : ''; ?>">Student Progress</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'student_progress.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Quizzes -->
            <div class="space-y-1">
                <button onclick="toggleQuizzesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'create_quiz.php', 'quiz_results.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'create_quiz.php', 'quiz_results.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-question-circle text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'create_quiz.php', 'quiz_results.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'create_quiz.php', 'quiz_results.php'])) ? 'text-blue-300' : ''; ?>">Quizzes & Tests</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'create_quiz.php', 'quiz_results.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="quizzesChevron"></i>
                </button>

                <div id="quizzesMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['quizzes.php', 'create_quiz.php', 'quiz_results.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/teacher/quizzes.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'quizzes.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'quizzes.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-list text-xs"></i>
                        </div>
                        <span>My Quizzes</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'quizzes.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/teacher/create_quiz.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'create_quiz.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'create_quiz.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <span>Create Quiz</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'create_quiz.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/teacher/quiz_results.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'quiz_results.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'quiz_results.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chart-bar text-xs"></i>
                        </div>
                        <span>Quiz Results</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'quiz_results.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Syllabus -->
            <a href="/academy_management_system/teacher/syllabus.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-book text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'text-blue-300' : ''; ?>">Syllabus</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'syllabus.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Reports -->
            <div class="space-y-1">
                <button onclick="toggleReportsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_reports.php', 'batch_report.php', 'student_report.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_reports.php', 'batch_report.php', 'student_report.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-file-alt text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_reports.php', 'batch_report.php', 'student_report.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_reports.php', 'batch_report.php', 'student_report.php'])) ? 'text-blue-300' : ''; ?>">Reports</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_reports.php', 'batch_report.php', 'student_report.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="reportsChevron"></i>
                </button>

                <div id="reportsMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_reports.php', 'batch_report.php', 'student_report.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/teacher/my_reports.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'my_reports.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'my_reports.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chart-pie text-xs"></i>
                        </div>
                        <span>My Reports</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'my_reports.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/teacher/batch_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'batch_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'batch_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-layer-group text-xs"></i>
                        </div>
                        <span>Batch Reports</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'batch_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/teacher/student_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'student_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'student_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-user-graduate text-xs"></i>
                        </div>
                        <span>Student Reports</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'student_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Materials -->
            <a href="/academy_management_system/teacher/materials.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'materials.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'materials.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-file-pdf text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'materials.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'materials.php') ? 'text-blue-300' : ''; ?>">Teaching Materials</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'materials.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Settings -->
            <a href="/academy_management_system/teacher/settings.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-cog text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'text-blue-300' : ''; ?>">Settings</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'settings.php'): ?>
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
                // Get teacher's assigned batches count for quick action
                if (isset($teacher)) {
                    $teacher_id = $teacher['id'];
                    $batch_count_query = "SELECT COUNT(*) as batch_count FROM teacher_assignments WHERE teacher_id = ?";
                    $stmt = $conn->prepare($batch_count_query);
                    $stmt->bind_param("i", $teacher_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $batch_data = $result->fetch_assoc();
                    $batch_count = $batch_data['batch_count'] ?? 0;
                } else {
                    $batch_count = 0;
                }
                ?>
                <a href="/academy_management_system/teacher/attendance.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-clipboard-check text-blue-400 text-xs"></i>
                    </div>
                    <span>Mark Today's Attendance</span>
                </a>
                <a href="/academy_management_system/teacher/my_batches.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-green-900/30 flex items-center justify-center group-hover:bg-green-500/30 transition-colors">
                        <i class="fas fa-users text-green-400 text-xs"></i>
                    </div>
                    <span>My Batches (<?php echo $batch_count; ?>)</span>
                </a>
                <a href="/academy_management_system/teacher/create_quiz.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-500/30 transition-colors">
                        <i class="fas fa-question-circle text-purple-400 text-xs"></i>
                    </div>
                    <span>Create New Quiz</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-4 border-t border-gray-700/50 bg-gray-900/30">
        <div class="text-xs text-gray-400">
            <p class="mb-1">Teacher Portal v2.0</p>
            <p class="flex items-center gap-1">
                <i class="fas fa-user-tie text-blue-400"></i>
                <span>Teaching Assistant</span>
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

    function toggleQuizzesMenu() {
        const menu = document.getElementById('quizzesMenu');
        const chevron = document.getElementById('quizzesChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleReportsMenu() {
        const menu = document.getElementById('reportsMenu');
        const chevron = document.getElementById('reportsChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    // Auto-expand menu if on a subpage
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = '<?php echo basename($_SERVER["PHP_SELF"]); ?>';
        const menus = ['attendance', 'quizzes', 'reports'];

        menus.forEach(menu => {
            const menuElement = document.getElementById(`${menu}Menu`);
            const chevron = document.getElementById(`${menu}Chevron`);

            // Check if current page matches any subpage in this menu
            if (menuElement && chevron && menuElement.querySelector(`a[href*="${currentPage}"]`)) {
                menuElement.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            }
        });

        // Update active batch count in real-time
        updateBatchCount();
    });

    // Function to update batch count
    function updateBatchCount() {
        fetch('/academy_management_system/includes/get_teacher_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.batch_count !== undefined) {
                    const batchElement = document.querySelector('a[href*="my_batches.php"] span:nth-child(2)');
                    if (batchElement) {
                        batchElement.textContent = `My Batches (${data.batch_count})`;
                    }
                }
            })
            .catch(error => console.error('Error updating batch count:', error));
    }

    // Update batch count every 30 seconds
    setInterval(updateBatchCount, 30000);
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
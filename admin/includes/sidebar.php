<!-- File: C:\xampp\htdocs\academy_management_system\admin\includes\sidebar.php -->
<aside class="w-64 bg-gradient-to-b from-gray-900 to-gray-800 text-white sticky top-0 h-screen flex flex-col shadow-xl">
    <!-- Header section with enhanced design -->
    <div class="p-6 border-b border-gray-700 bg-gray-900/50 backdrop-blur-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-graduation-cap text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-purple-300 bg-clip-text text-transparent">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1 font-medium">Administration Panel</p>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-400">
            <i class="fas fa-circle text-green-500 text-xs"></i>
            <span>Online</span>
        </div>
    </div>

    <!-- User profile mini-card -->
    <?php
    // Get current user info (you'll need to adjust this based on your session)
    $current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
    $current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Administrator';
    ?>
    <div class="p-4 border-b border-gray-700/50 bg-gray-800/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-semibold shadow-md">
                <?php echo strtoupper(substr($current_user, 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate"><?php echo htmlspecialchars($current_user); ?></p>
                <p class="text-xs text-gray-300 truncate"><?php echo htmlspecialchars($current_role); ?></p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="/academy_management_system/admin/dashboard.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-chart-line text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'text-blue-300' : ''; ?>">Dashboard</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>
                    <span class="ml-auto w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <?php endif; ?>
            </a>

            <!-- Users -->
            <div class="space-y-1">
                <button onclick="toggleUsersMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'teachers.php', 'inactive_users.php', 'add_student.php', 'edit_student.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'teachers.php', 'inactive_users.php', 'add_student.php', 'edit_student.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-users text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'teachers.php', 'inactive_users.php', 'add_student.php', 'edit_student.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'teachers.php', 'inactive_users.php', 'add_student.php', 'edit_student.php'])) ? 'text-blue-300' : ''; ?>">Users</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'teachers.php', 'inactive_users.php', 'add_student.php', 'edit_student.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="usersChevron"></i>
                </button>

                <div id="usersMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'teachers.php', 'inactive_users.php', 'add_student.php', 'edit_student.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/users/students.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'add_student.php', 'edit_student.php'])) ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'add_student.php', 'edit_student.php'])) ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-graduation-cap text-xs"></i>
                        </div>
                        <span>Students</span>
                        <?php if (in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'add_student.php', 'edit_student.php'])): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/users/teachers.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'teachers.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'teachers.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chalkboard-teacher text-xs"></i>
                        </div>
                        <span>Teachers</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'teachers.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/users/inactive_users.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'inactive_users.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'inactive_users.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-user-slash text-xs"></i>
                        </div>
                        <span>Inactive Users</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'inactive_users.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Announcements Button -->
            <div class="space-y-1">
                <a href="/academy_management_system/admin/announcements.php"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-bell text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'text-blue-300' : ''; ?>">Announcements</span>
                    </span>
                    <i class="fas fa-chevron-right text-xs <?php echo (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'text-blue-400' : 'text-gray-400'; ?>"></i>
                </a>
            </div>


            <!-- Skills / Courses -->
            <div class="space-y-1">
                <button onclick="toggleSkillsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['skills.php', 'progress.php', 'add_skill.php', 'edit_skill.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['skills.php', 'progress.php', 'add_skill.php', 'edit_skill.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-book-open text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['skills.php', 'progress.php', 'add_skill.php', 'edit_skill.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['skills.php', 'progress.php', 'add_skill.php', 'edit_skill.php'])) ? 'text-blue-300' : ''; ?>">Skills / Courses</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['skills.php', 'progress.php', 'add_skill.php', 'edit_skill.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="skillsChevron"></i>
                </button>

                <div id="skillsMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['skills.php', 'progress.php', 'add_skill.php', 'edit_skill.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/skills/skills.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'skills.php' || basename($_SERVER['PHP_SELF']) == 'add_skill.php' || basename($_SERVER['PHP_SELF']) == 'edit_skill.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'skills.php' || basename($_SERVER['PHP_SELF']) == 'add_skill.php' || basename($_SERVER['PHP_SELF']) == 'edit_skill.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus-circle text-xs"></i>
                        </div>
                        <span>Skills</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'skills.php' || basename($_SERVER['PHP_SELF']) == 'add_skill.php' || basename($_SERVER['PHP_SELF']) == 'edit_skill.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/skills/progress.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'progress.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'progress.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chart-line text-xs"></i>
                        </div>
                        <span>Progress</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'progress.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Sessions -->
            <div class="space-y-1">
                <button onclick="toggleSessionsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['sessions.php', 'add_session.php', 'completed_sessions.php', 'edit_session.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['sessions.php', 'add_session.php', 'completed_sessions.php', 'edit_session.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-calendar-alt text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['sessions.php', 'add_session.php', 'completed_sessions.php', 'edit_session.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['sessions.php', 'add_session.php', 'completed_sessions.php', 'edit_session.php'])) ? 'text-blue-300' : ''; ?>">Sessions</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['sessions.php', 'add_session.php', 'completed_sessions.php', 'edit_session.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="sessionsChevron"></i>
                </button>

                <div id="sessionsMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['sessions.php', 'add_session.php', 'completed_sessions.php', 'edit_session.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/sessions/sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'sessions.php' || basename($_SERVER['PHP_SELF']) == 'edit_session.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'sessions.php' || basename($_SERVER['PHP_SELF']) == 'edit_session.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-calendar-check text-xs"></i>
                        </div>
                        <span>Active Sessions</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'sessions.php' || basename($_SERVER['PHP_SELF']) == 'edit_session.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/sessions/add_session.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'add_session.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'add_session.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <span>Add Session</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'add_session.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/sessions/completed_sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'completed_sessions.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'completed_sessions.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-calendar-times text-xs"></i>
                        </div>
                        <span>Completed Sessions</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'completed_sessions.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Batches -->
            <div class="space-y-1">
                <button onclick="toggleBatchesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['batches.php', 'add_batch.php', 'completed_batches.php', 'edit_batch.php', 'assign_teacher.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['batches.php', 'add_batch.php', 'completed_batches.php', 'edit_batch.php', 'assign_teacher.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-layer-group text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['batches.php', 'add_batch.php', 'completed_batches.php', 'edit_batch.php', 'assign_teacher.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['batches.php', 'add_batch.php', 'completed_batches.php', 'edit_batch.php', 'assign_teacher.php'])) ? 'text-blue-300' : ''; ?>">Batches</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['batches.php', 'add_batch.php', 'completed_batches.php', 'edit_batch.php', 'assign_teacher.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="batchesChevron"></i>
                </button>

                <div id="batchesMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['batches.php', 'add_batch.php', 'completed_batches.php', 'edit_batch.php', 'assign_teacher.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/batches/batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'batches.php' || basename($_SERVER['PHP_SELF']) == 'edit_batch.php' || basename($_SERVER['PHP_SELF']) == 'assign_teacher.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'batches.php' || basename($_SERVER['PHP_SELF']) == 'edit_batch.php' || basename($_SERVER['PHP_SELF']) == 'assign_teacher.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-folder-open text-xs"></i>
                        </div>
                        <span>Active Batches</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'batches.php' || basename($_SERVER['PHP_SELF']) == 'edit_batch.php' || basename($_SERVER['PHP_SELF']) == 'assign_teacher.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/batches/add_batch.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'add_batch.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'add_batch.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <span>Add Batch</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'add_batch.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/batches/completed_batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'completed_batches.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'completed_batches.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-check-circle text-xs"></i>
                        </div>
                        <span>Completed Batches</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'completed_batches.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Fees -->
            <div class="space-y-1">
                <button onclick="toggleFeesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fee_structures.php', 'fee_collection.php', 'fee_history.php', 'add_fee.php', 'edit_fee.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fee_structures.php', 'fee_collection.php', 'fee_history.php', 'add_fee.php', 'edit_fee.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-money-bill-wave text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fee_structures.php', 'fee_collection.php', 'fee_history.php', 'add_fee.php', 'edit_fee.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fee_structures.php', 'fee_collection.php', 'fee_history.php', 'add_fee.php', 'edit_fee.php'])) ? 'text-blue-300' : ''; ?>">Fees</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fee_structures.php', 'fee_collection.php', 'fee_history.php', 'add_fee.php', 'edit_fee.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="feesChevron"></i>
                </button>

                <div id="feesMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['fee_structures.php', 'fee_collection.php', 'fee_history.php', 'add_fee.php', 'edit_fee.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/fees/fee_structures.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_structures.php' || basename($_SERVER['PHP_SELF']) == 'add_fee.php' || basename($_SERVER['PHP_SELF']) == 'edit_fee.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_structures.php' || basename($_SERVER['PHP_SELF']) == 'add_fee.php' || basename($_SERVER['PHP_SELF']) == 'edit_fee.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-folder-open text-xs"></i>
                        </div>
                        <span>Fee Structures</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'fee_structures.php' || basename($_SERVER['PHP_SELF']) == 'add_fee.php' || basename($_SERVER['PHP_SELF']) == 'edit_fee.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/fees/fee_collection.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_collection.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_collection.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-hand-holding-usd text-xs"></i>
                        </div>
                        <span>Fee Collection</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'fee_collection.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/fees/fee_history.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_history.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_history.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-file-invoice-dollar text-xs"></i>
                        </div>
                        <span>Fee History</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'fee_history.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Attendance -->
            <div class="space-y-1">
                <button onclick="toggleAttendanceMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_student_attendance.php', 'view_student_attendance.php', 'take_teacher_attendance.php', 'view_teacher_attendance.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_student_attendance.php', 'view_student_attendance.php', 'take_teacher_attendance.php', 'view_teacher_attendance.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-calendar-check text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_student_attendance.php', 'view_student_attendance.php', 'take_teacher_attendance.php', 'view_teacher_attendance.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_student_attendance.php', 'view_student_attendance.php', 'take_teacher_attendance.php', 'view_teacher_attendance.php'])) ? 'text-blue-300' : ''; ?>">Attendance</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_student_attendance.php', 'view_student_attendance.php', 'take_teacher_attendance.php', 'view_teacher_attendance.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="attendanceChevron"></i>
                </button>

                <div id="attendanceMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['attendance.php', 'take_student_attendance.php', 'view_student_attendance.php', 'take_teacher_attendance.php', 'view_teacher_attendance.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/attendance/attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-list text-xs"></i>
                        </div>
                        <span>All Attendances</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/attendance/take_student_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'take_student_attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'take_student_attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-user-graduate text-xs"></i>
                        </div>
                        <span>Take Student Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'take_student_attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/attendance/view_student_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'view_student_attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'view_student_attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-eye text-xs"></i>
                        </div>
                        <span>View Student Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'view_student_attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/attendance/take_teacher_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'take_teacher_attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'take_teacher_attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chalkboard-teacher text-xs"></i>
                        </div>
                        <span>Take Teacher Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'take_teacher_attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/attendance/view_teacher_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'view_teacher_attendance.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'view_teacher_attendance.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-eye text-xs"></i>
                        </div>
                        <span>View Teacher Attendance</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'view_teacher_attendance.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Expenses -->
            <div class="space-y-1">
                <button onclick="toggleExpensesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['expenses.php', 'add_expense.php', 'expense_categories.php', 'edit_expense.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['expenses.php', 'add_expense.php', 'expense_categories.php', 'edit_expense.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-wallet text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['expenses.php', 'add_expense.php', 'expense_categories.php', 'edit_expense.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['expenses.php', 'add_expense.php', 'expense_categories.php', 'edit_expense.php'])) ? 'text-blue-300' : ''; ?>">Expenses</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['expenses.php', 'add_expense.php', 'expense_categories.php', 'edit_expense.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="expensesChevron"></i>
                </button>

                <div id="expensesMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['expenses.php', 'add_expense.php', 'expense_categories.php', 'edit_expense.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/expenses/expenses.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'expenses.php' || basename($_SERVER['PHP_SELF']) == 'edit_expense.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'expenses.php' || basename($_SERVER['PHP_SELF']) == 'edit_expense.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-list text-xs"></i>
                        </div>
                        <span>All Expenses</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'expenses.php' || basename($_SERVER['PHP_SELF']) == 'edit_expense.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/expenses/add_expense.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'add_expense.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'add_expense.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <span>Add Expense</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'add_expense.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/expenses/expense_categories.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_categories.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_categories.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-tags text-xs"></i>
                        </div>
                        <span>Categories</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'expense_categories.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Donations -->
            <div class="space-y-1">
                <button onclick="toggleDonationsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['donations.php', 'add_donation.php', 'edit_donation.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['donations.php', 'add_donation.php', 'edit_donation.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-hand-holding-heart text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['donations.php', 'add_donation.php', 'edit_donation.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['donations.php', 'add_donation.php', 'edit_donation.php'])) ? 'text-blue-300' : ''; ?>">Donations</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['donations.php', 'add_donation.php', 'edit_donation.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="donationsChevron"></i>
                </button>

                <div id="donationsMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['donations.php', 'add_donation.php', 'edit_donation.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/donations/donations.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'donations.php' || basename($_SERVER['PHP_SELF']) == 'edit_donation.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'donations.php' || basename($_SERVER['PHP_SELF']) == 'edit_donation.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-list text-xs"></i>
                        </div>
                        <span>All Donations</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'donations.php' || basename($_SERVER['PHP_SELF']) == 'edit_donation.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/donations/add_donation.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'add_donation.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'add_donation.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <span>Add Donation</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'add_donation.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Profit -->
            <div class="space-y-1">
                <button onclick="toggleProfitMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profit.php', 'add_profit.php', 'edit_profit.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profit.php', 'add_profit.php', 'edit_profit.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-chart-line text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profit.php', 'add_profit.php', 'edit_profit.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profit.php', 'add_profit.php', 'edit_profit.php'])) ? 'text-blue-300' : ''; ?>">Profit</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profit.php', 'add_profit.php', 'edit_profit.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="profitChevron"></i>
                </button>

                <div id="profitMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['profit.php', 'add_profit.php', 'edit_profit.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/profit/profit.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'profit.php' || basename($_SERVER['PHP_SELF']) == 'edit_profit.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'profit.php' || basename($_SERVER['PHP_SELF']) == 'edit_profit.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-list text-xs"></i>
                        </div>
                        <span>All Profits</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'profit.php' || basename($_SERVER['PHP_SELF']) == 'edit_profit.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/profit/add_profit.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'add_profit.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'add_profit.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-plus text-xs"></i>
                        </div>
                        <span>Add Profit</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'add_profit.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Reports -->
            <div class="space-y-1">
                <button onclick="toggleReportsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_report.php', 'attendance_report.php', 'fee_report.php', 'expense_report.php', 'donation_report.php', 'profit_report.php', 'teacher_report.php', 'batch_report.php'])) ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500' : ''; ?>">
                    <span class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_report.php', 'attendance_report.php', 'fee_report.php', 'expense_report.php', 'donation_report.php', 'profit_report.php', 'teacher_report.php', 'batch_report.php'])) ? 'bg-blue-500/20' : ''; ?>">
                            <i class="fas fa-file-alt text-sm <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_report.php', 'attendance_report.php', 'fee_report.php', 'expense_report.php', 'donation_report.php', 'profit_report.php', 'teacher_report.php', 'batch_report.php'])) ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                        </div>
                        <span class="font-medium <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_report.php', 'attendance_report.php', 'fee_report.php', 'expense_report.php', 'donation_report.php', 'profit_report.php', 'teacher_report.php', 'batch_report.php'])) ? 'text-blue-300' : ''; ?>">Reports</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_report.php', 'attendance_report.php', 'fee_report.php', 'expense_report.php', 'donation_report.php', 'profit_report.php', 'teacher_report.php', 'batch_report.php'])) ? 'text-blue-400 rotate-180' : 'text-gray-400'; ?>"
                        id="reportsChevron"></i>
                </button>

                <div id="reportsMenu" class="ml-4 pl-6 border-l border-gray-700 space-y-1 <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['student_report.php', 'attendance_report.php', 'fee_report.php', 'expense_report.php', 'donation_report.php', 'profit_report.php', 'teacher_report.php', 'batch_report.php'])) ? '' : 'hidden'; ?>">
                    <a href="/academy_management_system/admin/reports/student_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'student_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'student_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-user-graduate text-xs"></i>
                        </div>
                        <span>Student Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'student_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/attendance_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-calendar-check text-xs"></i>
                        </div>
                        <span>Attendance Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'attendance_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/fee_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'fee_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-money-bill-wave text-xs"></i>
                        </div>
                        <span>Fee Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'fee_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/expense_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-receipt text-xs"></i>
                        </div>
                        <span>Expense Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'expense_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/donation_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'donation_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'donation_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-hand-holding-heart text-xs"></i>
                        </div>
                        <span>Donation Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'donation_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/profit_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'profit_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'profit_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chart-line text-xs"></i>
                        </div>
                        <span>Profit Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'profit_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/teacher_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'teacher_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'teacher_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-chalkboard-teacher text-xs"></i>
                        </div>
                        <span>Teacher Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'teacher_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/academy_management_system/admin/reports/batch_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'batch_report.php') ? 'text-blue-300 bg-gray-800/50' : 'text-gray-300'; ?>">
                        <div class="w-6 h-6 rounded flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'batch_report.php') ? 'bg-blue-500/20' : 'bg-gray-700'; ?>">
                            <i class="fas fa-layer-group text-xs"></i>
                        </div>
                        <span>Batch Report</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'batch_report.php'): ?>
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Activity Log (New Section) -->
            <a href="/academy_management_system/admin/activity_logs.php"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-800/70 hover:shadow-md transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'activity_log.php') ? 'bg-gradient-to-r from-blue-900/30 to-purple-900/30 border-l-4 border-blue-500 shadow-inner' : ''; ?>">
                <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center <?php echo (basename($_SERVER['PHP_SELF']) == 'activity_log.php') ? 'bg-blue-500/20' : ''; ?>">
                    <i class="fas fa-history text-sm <?php echo (basename($_SERVER['PHP_SELF']) == 'activity_log.php') ? 'text-blue-400' : 'text-gray-300'; ?>"></i>
                </div>
                <span class="font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'activity_log.php') ? 'text-blue-300' : ''; ?>">Activity Log</span>
                <?php if (basename($_SERVER['PHP_SELF']) == 'activity_log.php'): ?>
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
                <a href="../sessions/add_session.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-plus-circle text-blue-400 text-xs"></i>
                    </div>
                    <span>New Session</span>
                </a>
                <a href="../users/students.php?action=add"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-green-900/30 flex items-center justify-center group-hover:bg-green-500/30 transition-colors">
                        <i class="fas fa-user-plus text-green-400 text-xs"></i>
                    </div>
                    <span>Add Student</span>
                </a>
                <a href="../batches/add_batch.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gradient-to-r hover:from-blue-900/30 hover:to-purple-900/30 transition-colors group">
                    <div class="w-6 h-6 rounded bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-500/30 transition-colors">
                        <i class="fas fa-layer-group text-purple-400 text-xs"></i>
                    </div>
                    <span>New Batch</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-4 border-t border-gray-700/50 bg-gray-900/30">
        <div class="text-xs text-gray-400">
            <p class="mb-1">EduSkill Pro v2.0</p>
            <p class="flex items-center gap-1">
                <i class="fas fa-shield-alt text-green-400"></i>
                <span>Secure Admin Panel</span>
            </p>
        </div>
    </div>
</aside>

<script>
    // Sidebar toggle functions with enhanced animations
    function toggleUsersMenu() {
        const menu = document.getElementById('usersMenu');
        const chevron = document.getElementById('usersChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleAccountsMenu() {
        const menu = document.getElementById('accountsMenu');
        const chevron = document.getElementById('accountsChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleSkillsMenu() {
        const menu = document.getElementById('skillsMenu');
        const chevron = document.getElementById('skillsChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleSessionsMenu() {
        const menu = document.getElementById('sessionsMenu');
        const chevron = document.getElementById('sessionsChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleBatchesMenu() {
        const menu = document.getElementById('batchesMenu');
        const chevron = document.getElementById('batchesChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleFeesMenu() {
        const menu = document.getElementById('feesMenu');
        const chevron = document.getElementById('feesChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleAttendanceMenu() {
        const menu = document.getElementById('attendanceMenu');
        const chevron = document.getElementById('attendanceChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleExpensesMenu() {
        const menu = document.getElementById('expensesMenu');
        const chevron = document.getElementById('expensesChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleDonationsMenu() {
        const menu = document.getElementById('donationsMenu');
        const chevron = document.getElementById('donationsChevron');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }

    function toggleProfitMenu() {
        const menu = document.getElementById('profitMenu');
        const chevron = document.getElementById('profitChevron');
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
        const menus = ['users', 'accounts', 'skills', 'sessions', 'batches', 'fees', 'attendance', 'expenses', 'donations', 'profit', 'reports'];

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
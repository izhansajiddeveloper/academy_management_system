<aside class="w-64 sidebar text-white  sticky top-0 h-screen flex flex-col">
    <!-- Header section -->
    <div class="p-6 border-b border-gray-700">
        <h2 class="text-xl font-bold">🎓 EduSkill Pro</h2>
        <p class="text-sm text-gray-300 mt-1">Admin Panel</p>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto p-4">
        <nav class="space-y-1">
            <!-- Dashboard -->
            <a href="dashboard.php"
                class="flex items-center gap-3 p-3 rounded-lg bg-blue-900/30 text-white font-medium sidebar-link">
                <i class="fas fa-chart-line w-5"></i>
                <span>Dashboard</span>
            </a>

            <!-- Users -->
            <div class="space-y-1">
                <button onclick="toggleUsersMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-users w-5"></i>
                        <span>Users</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="usersChevron"></i>
                </button>

                <div id="usersMenu" class="ml-6 space-y-1 hidden">
                    <a href="users/students.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-graduation-cap w-4"></i>
                        <span>Students</span>
                    </a>
                    <a href="users/teachers.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-chalkboard-teacher w-4"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="users/inactive_users.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-user-slash w-4"></i>
                        <span>Inactive Users</span>
                    </a>
                </div>
            </div>

            <!-- Skills / Courses -->
            <div class="space-y-1">
                <button onclick="toggleSkillsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-book-open w-5"></i>
                        <span>Skills / Courses</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="skillsChevron"></i>
                </button>

                <div id="skillsMenu" class="ml-6 space-y-1 hidden">
                    <a href="skills/skills.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-plus-circle w-4"></i>
                        <span>Skills</span>
                    </a>

                    <a href="skills/progress.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-chart-line w-4"></i>
                        <span>Progress</span>
                    </a>
                </div>
            </div>

            <!-- Sessions -->
            <div class="space-y-1">
                <button onclick="toggleSessionsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span>Sessions</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="sessionsChevron"></i>
                </button>

                <div id="sessionsMenu" class="ml-6 space-y-1 hidden">
                    <a href="sessions/sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-calendar-check w-4"></i>
                        <span>Active Sessions</span>
                    </a>
                    <a href="sessions/add_session.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Session</span>
                    </a>
                    <a href="sessions/inactive_sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-calendar-times w-4"></i>
                        <span>Completed Sessions</span>
                    </a>
                </div>
            </div>

            <!-- Batches -->
            <div class="space-y-1">
                <button onclick="toggleBatchesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-layer-group w-5"></i>
                        <span>Batches</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="batchesChevron"></i>
                </button>

                <div id="batchesMenu" class="ml-6 space-y-1 hidden">
                    <a href="batches/batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-folder-open w-4"></i>
                        <span>Active Batches</span>
                    </a>
                    <a href="batches/add_batch.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Batch</span>
                    </a>
                    <a href="batches/completed_batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-check-circle w-4"></i>
                        <span>Completed Batches</span>
                    </a>
                    <!-- Assign Teacher link is **dynamic per batch**, so do NOT include batch_id here -->
                    <span class="block px-2 py-1 text-xs text-gray-400 italic">Assign Teacher link appears in batches list</span>
                </div>
            </div>
            <!--enrollments -->
            <!-- Enrollments -->
            <div class="space-y-1">
                <button onclick="toggleEnrollmentsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-user-check w-5"></i>
                        <span>Enrollments</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="enrollmentsChevron"></i>
                </button>

                <div id="enrollmentsMenu" class="ml-6 space-y-1 hidden">
                    <a href="enrollments/enroll_student.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Enroll Student</span>
                    </a>
                    <a href="enrollments/enrollment_list.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>Enrollment List</span>
                    </a>
                </div>
            </div>

            <script>
                function toggleEnrollmentsMenu() {
                    const menu = document.getElementById('enrollmentsMenu');
                    const chevron = document.getElementById('enrollmentsChevron');
                    menu.classList.toggle('hidden');
                    chevron.classList.toggle('rotate-180');
                }
            </script>

            <!-- fees -->
            <div class="space-y-1">
                <button onclick="toggleFeesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-money-bill-wave w-5"></i>
                        <span>Fees</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="feesChevron"></i>
                </button>

                <div id="feesMenu" class="ml-6 space-y-1 hidden">
                    <a href="fees/fee_structures.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-folder-open w-4"></i>
                        <span>Fee Structures</span>
                    </a>
                    <a href="fees/fee_collection.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-hand-holding-usd w-4"></i>
                        <span>Fee Collection</span>
                    </a>
                    <a href="fees/fee_history.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-file-invoice-dollar w-4"></i>
                        <span>Fee History</span>
                    </a>
                </div>
            </div>

            <script>
                function toggleFeesMenu() {
                    const menu = document.getElementById('feesMenu');
                    const chevron = document.getElementById('feesChevron');
                    menu.classList.toggle('hidden');
                    chevron.classList.toggle('rotate-180');
                }
            </script>


            <!-- Attendance Sidebar Menu -->
            <!-- Attendance Sidebar Menu -->
            <div class="space-y-1">
                <button onclick="toggleAttendanceMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-wallet w-5"></i>
                        <span>Attendance</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="attendanceChevron"></i>
                </button>

                <div id="attendanceMenu" class="ml-6 space-y-1 hidden">
                    <!-- Combined Attendance Page -->
                    <a href="attendance/attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>All Attendances</span>
                    </a>

                    <!-- Student Attendance -->
                    <a href="attendance/take_student_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-user-graduate w-4"></i>
                        <span>Take Student Attendance</span>
                    </a>
                    <a href="attendance/view_student_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-eye w-4"></i>
                        <span>View Student Attendance</span>
                    </a>

                    <!-- Teacher Attendance -->
                    <a href="attendance/take_teacher_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-chalkboard-teacher w-4"></i>
                        <span>Take Teacher Attendance</span>
                    </a>
                    <a href="attendance/view_teacher_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/30 transition-colors text-sm">
                        <i class="fas fa-eye w-4"></i>
                        <span>View Teacher Attendance</span>
                    </a>
                </div>
            </div>

            <script>
                function toggleAttendanceMenu() {
                    const menu = document.getElementById('attendanceMenu');
                    const chevron = document.getElementById('attendanceChevron');
                    menu.classList.toggle('hidden');
                    chevron.classList.toggle('rotate-180');
                }
            </script>




            <a href="expenses/expenses.php"
                class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                <i class="fas fa-wallet w-5"></i>
                <span>Expenses</span>
            </a>

            <a href="reports/student_report.php"
                class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800/50 transition-colors sidebar-link">
                <i class="fas fa-file-alt w-5"></i>
                <span>Reports</span>
            </a>
        </nav>

        <!-- Quick Actions -->
        <div class="mt-8 pt-4 border-t border-gray-700">
            <h4 class="font-medium text-gray-300 mb-3 text-xs uppercase tracking-wider">Quick Actions</h4>
            <div class="space-y-2">
                <a href="sessions/add_session.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-800/30 transition-colors">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Session</span>
                </a>
                <a href="users/students.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-800/30 transition-colors">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Student</span>
                </a>
                <a href="batches/add_batch.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-800/30 transition-colors">
                    <i class="fas fa-layer-group"></i>
                    <span>New Batch</span>
                </a>
            </div>
        </div>
    </div>
</aside>
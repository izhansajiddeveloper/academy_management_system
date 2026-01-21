<!-- File: C:\xampp\htdocs\academy_management_system\admin\includes\sidebar.php -->
<aside class="w-64 bg-gray-900 text-white sticky top-0 h-screen flex flex-col">
    <!-- Header section -->
    <div class="p-6 border-b border-gray-700">
        <h2 class="text-xl font-bold">🎓 EduSkill Pro</h2>
        <p class="text-sm text-gray-300 mt-1">Admin Panel</p>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto p-4">
        <nav class="space-y-1">
            <!-- Dashboard -->
            <a href="/academy_management_system/admin/dashboard.php"
                class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800 text-white font-medium">
                <i class="fas fa-chart-line w-5"></i>
                <span>Dashboard</span>
            </a>

            <!-- Users -->
            <div class="space-y-1">
                <button onclick="toggleUsersMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-users w-5"></i>
                        <span>Users</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="usersChevron"></i>
                </button>

                <div id="usersMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/users/students.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-graduation-cap w-4"></i>
                        <span>Students</span>
                    </a>
                    <a href="/academy_management_system/admin/users/teachers.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-chalkboard-teacher w-4"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="/academy_management_system/admin/users/inactive_users.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-user-slash w-4"></i>
                        <span>Inactive Users</span>
                    </a>
                </div>
            </div>

            <!-- Skills / Courses -->
            <div class="space-y-1">
                <button onclick="toggleSkillsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-book-open w-5"></i>
                        <span>Skills / Courses</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="skillsChevron"></i>
                </button>

                <div id="skillsMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/skills/skills.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-plus-circle w-4"></i>
                        <span>Skills</span>
                    </a>

                    <a href="/academy_management_system/admin/skills/progress.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-chart-line w-4"></i>
                        <span>Progress</span>
                    </a>
                </div>
            </div>

            <!-- Sessions -->
            <div class="space-y-1">
                <button onclick="toggleSessionsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span>Sessions</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="sessionsChevron"></i>
                </button>

                <div id="sessionsMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/sessions/sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-calendar-check w-4"></i>
                        <span>Active Sessions</span>
                    </a>
                    <a href="/academy_management_system/admin/sessions/add_session.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Session</span>
                    </a>
                    <a href="/academy_management_system/admin/sessions/completed_sessions.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-calendar-times w-4"></i>
                        <span>Completed Sessions</span>
                    </a>
                </div>
            </div>

            <!-- Batches -->
            <div class="space-y-1">
                <button onclick="toggleBatchesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-layer-group w-5"></i>
                        <span>Batches</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="batchesChevron"></i>
                </button>

                <div id="batchesMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/batches/batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-folder-open w-4"></i>
                        <span>Active Batches</span>
                    </a>
                    <a href="/academy_management_system/admin/batches/add_batch.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Batch</span>
                    </a>
                    <a href="/academy_management_system/admin/batches/completed_batches.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-check-circle w-4"></i>
                        <span>Completed Batches</span>
                    </a>
                    <span class="block px-2 py-1 text-xs text-gray-400 italic">Assign Teacher link appears in batches list</span>
                </div>
            </div>


            <!-- Fees -->
            <div class="space-y-1">
                <button onclick="toggleFeesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-money-bill-wave w-5"></i>
                        <span>Fees</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="feesChevron"></i>
                </button>

                <div id="feesMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/fees/fee_structures.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-folder-open w-4"></i>
                        <span>Fee Structures</span>
                    </a>
                    <a href="/academy_management_system/admin/fees/fee_collection.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-hand-holding-usd w-4"></i>
                        <span>Fee Collection</span>
                    </a>
                    <a href="/academy_management_system/admin/fees/fee_history.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-file-invoice-dollar w-4"></i>
                        <span>Fee History</span>
                    </a>
                </div>
            </div>

            <!-- Attendance -->
            <div class="space-y-1">
                <button onclick="toggleAttendanceMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-calendar-check w-5"></i>
                        <span>Attendance</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="attendanceChevron"></i>
                </button>

                <div id="attendanceMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/attendance/attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>All Attendances</span>
                    </a>
                    <a href="/academy_management_system/admin/attendance/take_student_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-user-graduate w-4"></i>
                        <span>Take Student Attendance</span>
                    </a>
                    <a href="/academy_management_system/admin/attendance/view_student_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-eye w-4"></i>
                        <span>View Student Attendance</span>
                    </a>
                    <a href="/academy_management_system/admin/attendance/take_teacher_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-chalkboard-teacher w-4"></i>
                        <span>Take Teacher Attendance</span>
                    </a>
                    <a href="/academy_management_system/admin/attendance/view_teacher_attendance.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-eye w-4"></i>
                        <span>View Teacher Attendance</span>
                    </a>
                </div>
            </div>

            <!-- Expenses -->
            <div class="space-y-1">
                <button onclick="toggleExpensesMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-wallet w-5"></i>
                        <span>Expenses</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="expensesChevron"></i>
                </button>

                <div id="expensesMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/expenses/expenses.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>All Expenses</span>
                    </a>
                    <a href="/academy_management_system/admin/expenses/add_expense.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Expense</span>
                    </a>
                    <a href="/academy_management_system/admin/expenses/expense_categories.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-tags w-4"></i>
                        <span>Categories</span>
                    </a>
                </div>
            </div>

            <!-- Donations -->
            <div class="space-y-1">
                <button onclick="toggleDonationsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-hand-holding-heart w-5"></i>
                        <span>Donations</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="donationsChevron"></i>
                </button>

                <div id="donationsMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/donations/donations.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>All Donations</span>
                    </a>
                    <a href="/academy_management_system/admin/donations/add_donation.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Donation</span>
                    </a>
                </div>
            </div>

            <!-- Profit -->
            <div class="space-y-1">
                <button onclick="toggleProfitMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Profit</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="profitChevron"></i>
                </button>

                <div id="profitMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/profit/profit.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>All Profits</span>
                    </a>
                    <a href="/academy_management_system/admin/profit/add_profit.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Add Profit</span>
                    </a>
                </div>
            </div>

            <!-- Reports -->
            <div class="space-y-1">
                <button onclick="toggleReportsMenu()"
                    class="flex items-center justify-between w-full p-3 rounded-lg hover:bg-gray-800 transition-colors">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-file-alt w-5"></i>
                        <span>Reports</span>
                    </span>
                    <i class="fas fa-chevron-down text-sm transition-transform" id="reportsChevron"></i>
                </button>

                <div id="reportsMenu" class="ml-6 space-y-1 hidden">
                    <a href="/academy_management_system/admin/reports/student_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-user-graduate w-4"></i>
                        <span>Student Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/attendance_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-calendar-check w-4"></i>
                        <span>Attendance Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/fee_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-money-bill-wave w-4"></i>
                        <span>Fee Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/expense_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-receipt w-4"></i>
                        <span>Expense Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/donation_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-hand-holding-heart w-4"></i>
                        <span>Donation Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/profit_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-chart-line w-4"></i>
                        <span>Profit Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/teacher_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-chalkboard-teacher w-4"></i>
                        <span>Teacher Report</span>
                    </a>
                    <a href="/academy_management_system/admin/reports/batch_report.php"
                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-800/70 transition-colors text-sm">
                        <i class="fas fa-layer-group w-4"></i>
                        <span>Batch Report</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Quick Actions -->
        <div class="mt-8 pt-4 border-t border-gray-700">
            <h4 class="font-medium text-gray-300 mb-3 text-xs uppercase tracking-wider">Quick Actions</h4>
            <div class="space-y-2">
                <a href="../sessions/add_session.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-800/70 transition-colors">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Session</span>
                </a>
                <a href="../users/students.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-800/70 transition-colors">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Student</span>
                </a>
                <a href="../batches/add_batch.php"
                    class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-800/70 transition-colors">
                    <i class="fas fa-layer-group"></i>
                    <span>New Batch</span>
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    // Sidebar toggle functions
    function toggleUsersMenu() {
        const menu = document.getElementById('usersMenu');
        const chevron = document.getElementById('usersChevron');
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

    function toggleEnrollmentsMenu() {
        const menu = document.getElementById('enrollmentsMenu');
        const chevron = document.getElementById('enrollmentsChevron');
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
</script>
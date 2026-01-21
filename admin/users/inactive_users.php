<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle Reactivate
if (isset($_GET['reactivate']) && isset($_GET['type'])) {
    $id = (int)$_GET['reactivate'];
    $type = $_GET['type'];

    if ($type === 'student') {
        $result = mysqli_query($conn, "SELECT user_id FROM students WHERE id=$id");
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $user_id = $row['user_id'];
            mysqli_query($conn, "UPDATE students SET status='active', updated_at=NOW() WHERE id=$id");
            mysqli_query($conn, "UPDATE users SET status='active', updated_at=NOW() WHERE id=$user_id");
            $_SESSION['success_message'] = "Student reactivated successfully!";
        }
    } elseif ($type === 'teacher') {
        $result = mysqli_query($conn, "SELECT user_id FROM teachers WHERE id=$id");
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $user_id = $row['user_id'];
            mysqli_query($conn, "UPDATE teachers SET status='active', updated_at=NOW() WHERE id=$id");
            mysqli_query($conn, "UPDATE users SET status='active', updated_at=NOW() WHERE id=$user_id");
            $_SESSION['success_message'] = "Teacher reactivated successfully!";
        }
    }

    header("Location: inactive_users.php?tab=$type");
    exit;
}

// Determine tab
$tab = $_GET['tab'] ?? 'students';

// Count inactive users
$student_count_query = "SELECT COUNT(*) as count FROM students s JOIN users u ON s.user_id = u.id WHERE s.status='inactive'";
$teacher_count_query = "SELECT COUNT(*) as count FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.status='inactive'";

$student_count_result = mysqli_query($conn, $student_count_query);
$teacher_count_result = mysqli_query($conn, $teacher_count_query);

$student_count = mysqli_fetch_assoc($student_count_result)['count'];
$teacher_count = mysqli_fetch_assoc($teacher_count_result)['count'];

// Store success message if exists
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inactive Users | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .table-container {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb !important;
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
                    <h1 class="text-2xl font-bold text-gray-800">Inactive Users</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-slash text-gray-500 mr-1"></i>
                        Manage inactive students and teachers
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search inactive users..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm transition-colors"
                            id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Success Message -->
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

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Inactive Students</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $student_count; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Inactive Teachers</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $teacher_count; ?></h3>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 mb-6">
                <a href="?tab=students"
                    class="px-4 py-2 rounded text-sm font-medium transition-colors <?php echo $tab === 'students' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Students
                </a>
                <a href="?tab=teachers"
                    class="px-4 py-2 rounded text-sm font-medium transition-colors <?php echo $tab === 'teachers' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Teachers
                </a>
            </div>

            <!-- Content Area -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">
                        <?php echo ucfirst($tab); ?> List
                        (<?php echo $tab === 'students' ? $student_count : $teacher_count; ?>)
                    </h3>
                </div>

                <?php if ($tab === 'students'): ?>
                    <?php
                    $query = "SELECT s.*, u.username, u.email
                              FROM students s 
                              JOIN users u ON s.user_id = u.id
                              WHERE s.status='inactive'
                              ORDER BY s.updated_at DESC";
                    $result = mysqli_query($conn, $query);
                    $total_students = mysqli_num_rows($result);
                    ?>

                    <?php if ($total_students > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Code</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Father's Name</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Phone</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-mono text-sm text-gray-600"><?php echo $row['student_code']; ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['father_name']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['phone']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex gap-1">
                                                    <a href="?tab=students&reactivate=<?= $row['id'] ?>&type=student"
                                                        class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                        onclick="return confirm('Reactivate <?php echo htmlspecialchars($row['name']); ?>?')"
                                                        title="Reactivate">
                                                        <i class="fas fa-user-check text-xs"></i>
                                                    </a>
                                                    <a href="edit_student.php?id=<?= $row['id'] ?>"
                                                        class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                        title="View Details">
                                                        <i class="fas fa-eye text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center">
                            <i class="fas fa-user-graduate text-gray-300 text-4xl mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Inactive Students</h3>
                            <p class="text-gray-500 text-sm mb-4">All students are currently active</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($tab === 'teachers'): ?>
                    <?php
                    $query = "SELECT t.*, u.username, u.email
                              FROM teachers t 
                              JOIN users u ON t.user_id = u.id
                              WHERE t.status='inactive'
                              ORDER BY t.updated_at DESC";
                    $result = mysqli_query($conn, $query);
                    $total_teachers = mysqli_num_rows($result);
                    ?>

                    <?php if ($total_teachers > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Code</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Qualification</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Experience</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-mono text-sm text-gray-600"><?php echo $row['teacher_code']; ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['qualification']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-sm text-gray-700"><?php echo $row['experience_years']; ?> years</div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex gap-1">
                                                    <a href="?tab=teachers&reactivate=<?= $row['id'] ?>&type=teacher"
                                                        class="action-btn bg-green-50 text-green-700 hover:bg-green-100"
                                                        onclick="return confirm('Reactivate <?php echo htmlspecialchars($row['name']); ?>?')"
                                                        title="Reactivate">
                                                        <i class="fas fa-user-check text-xs"></i>
                                                    </a>
                                                    <a href="edit_teacher.php?id=<?= $row['id'] ?>"
                                                        class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                        title="View Details">
                                                        <i class="fas fa-eye text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center">
                            <i class="fas fa-chalkboard-teacher text-gray-300 text-4xl mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Inactive Teachers</h3>
                            <p class="text-gray-500 text-sm mb-4">All teachers are currently active</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const tableRows = document.querySelectorAll('tbody tr');

                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = 'table-row';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

</body>

</html>
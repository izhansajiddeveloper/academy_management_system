<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$result = mysqli_query($conn, "
    SELECT s.*, u.username, u.email 
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.status='active'
    ORDER BY s.created_at DESC
");

$total_students = mysqli_num_rows($result);
$male_students = 0;
$female_students = 0;

// Reset pointer and count genders
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['gender'] == 'male') $male_students++;
    if ($row['gender'] == 'female') $female_students++;
}
mysqli_data_seek($result, 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Students Management | Academy Management System</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Students Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-user-graduate text-blue-500 mr-1"></i>
                        Manage all student records
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search students..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm"
                            id="searchInput">
                    </div>
                    <a href="add_student.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-plus mr-1"></i> Add Student
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Students</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_students; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Male</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $male_students; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Female</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $female_students; ?></h3>
                </div>
            </div>

            <!-- Students Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">All Students (<?php echo $total_students; ?>)</h3>
                </div>

                <?php if ($total_students > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Code</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Father's Name</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Gender</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Phone</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-mono text-sm text-blue-600"><?php echo $row['student_code']; ?></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['father_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($row['gender'] == 'male'): ?>
                                                <span class="text-blue-600 text-sm font-medium">Male</span>
                                            <?php elseif ($row['gender'] == 'female'): ?>
                                                <span class="text-pink-600 text-sm font-medium">Female</span>
                                            <?php else: ?>
                                                <span class="text-gray-600 text-sm font-medium">Other</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($row['phone']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-1">
                                                <a href="edit_student.php?id=<?php echo $row['id']; ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <a href="delete_student.php?id=<?php echo $row['id']; ?>"
                                                    onclick="return confirm('Delete student <?php echo htmlspecialchars($row['name']); ?>?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-user-graduate text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Students Found</h3>
                        <p class="text-gray-500 text-sm mb-4">Add your first student to get started</p>
                        <a href="add_student.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition-colors">
                            <i class="fas fa-plus"></i> Add First Student
                        </a>
                    </div>
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
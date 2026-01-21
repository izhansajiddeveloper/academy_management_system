<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch fee history per student per batch/skill/session

// 1️⃣ Fetch fee history per student
$history = mysqli_query($conn, "
    SELECT 
        st.name AS student_name,
        st.student_code,
        sk.skill_name,
        se.session_name,
        b.batch_name,
        fs.total_fee,
        IFNULL(SUM(fc.amount_paid), 0) AS total_paid,
        (fs.total_fee - IFNULL(SUM(fc.amount_paid),0)) AS due_amount
    FROM student_enrollments e
    JOIN students st ON e.student_id = st.id
    JOIN skills sk ON e.skill_id = sk.id
    JOIN sessions se ON e.session_id = se.id
    JOIN batches b ON e.batch_id = b.id
    LEFT JOIN fee_structures fs 
        ON fs.skill_id = sk.id 
        AND fs.session_id = se.id 
        AND fs.status='active'
    LEFT JOIN fee_collections fc 
        ON fc.student_id = st.id 
        AND fc.skill_id = sk.id 
        AND fc.session_id = se.id 
        AND fc.batch_id = b.id 
        AND fc.status='active'
    WHERE st.status='active'
    GROUP BY st.id, sk.id, se.id, b.id
    ORDER BY st.name, sk.skill_name, se.session_name
");

// 2️⃣ Count total active students
$total_students_query = "
    SELECT COUNT(DISTINCT e.student_id) as count 
    FROM student_enrollments e
    JOIN students st ON e.student_id = st.id 
    WHERE st.status='active'
";
$total_students_result = mysqli_query($conn, $total_students_query);
$total_students = mysqli_fetch_assoc($total_students_result)['count'] ?? 0;

// 3️⃣ Total fees expected
$total_fees_query = "
    SELECT SUM(fs.total_fee) as total 
    FROM student_enrollments e
    JOIN fee_structures fs 
        ON e.skill_id = fs.skill_id 
        AND e.session_id = fs.session_id 
        AND fs.status='active'
";
$total_fees_result = mysqli_query($conn, $total_fees_query);
$total_fees = mysqli_fetch_assoc($total_fees_result)['total'] ?? 0;
$total_fees_formatted = number_format($total_fees, 2);

// 4️⃣ Total fees collected
$total_paid_query = "
    SELECT SUM(amount_paid) as total 
    FROM fee_collections 
    WHERE status='active'
";
$total_paid_result = mysqli_query($conn, $total_paid_query);
$total_paid = mysqli_fetch_assoc($total_paid_result)['total'] ?? 0;
$total_paid_formatted = number_format($total_paid, 2);

// 5️⃣ Total due
$total_due = $total_fees - $total_paid;
$total_due_formatted = number_format($total_due, 2);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee History | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
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

        .due-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .due-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .due-pending {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Fee History & Status</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-history text-blue-500 mr-1"></i>
                        View fee payment history and due amounts for all students
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
                    <a href="fee_collection.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-cash-register mr-1"></i> Collect Fee
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-4 gap-3 mb-6">
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Students</p>
                    <h3 class="text-xl font-bold text-gray-800"><?php echo $total_students; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Fees</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $total_fees_formatted; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Paid</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $total_paid_formatted; ?></h3>
                </div>
                <div class="bg-white p-3 rounded border text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Due</p>
                    <h3 class="text-xl font-bold text-gray-800">Rs<?php echo $total_due_formatted; ?></h3>
                </div>
            </div>

            <!-- Fee History Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Fee History for All Students</h3>
                </div>

                <?php if (mysqli_num_rows($history) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Student</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Skill</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Fee</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Paid</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Due Amount</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($history)):
                                    $due_amount = $row['due_amount'];
                                    $status_class = $due_amount > 0 ? 'due-pending' : 'due-paid';
                                    $status_text = $due_amount > 0 ? 'Pending' : 'Paid';
                                    $due_color = $due_amount > 0 ? 'text-red-600' : 'text-green-600';
                                ?>
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= $row['student_code'] ?? 'N/A' ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($row['skill_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($row['session_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= htmlspecialchars($row['batch_name']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-medium text-gray-900">
                                                Rs<?= number_format($row['total_fee'] ?? 0, 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-medium text-green-600">
                                                Rs<?= number_format($row['total_paid'], 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-bold <?= $due_color ?>">
                                                Rs<?= number_format($due_amount, 2) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?= $status_class ?> due-badge">
                                                <i class="fas <?= $due_amount > 0 ? 'fa-clock' : 'fa-check-circle' ?> text-xs"></i>
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-history text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Fee History Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No fee records available for enrolled students</p>
                        <a href="fee_collection.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-cash-register"></i> Start Collecting Fees
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
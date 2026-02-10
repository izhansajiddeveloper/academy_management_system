<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Get student ID from students table using user_id from session
$user_id = $_SESSION['user_id'];

// Fetch student details
$student_query = "SELECT * FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($student_query);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit;
}

$student_id = $student['id'];

// Get course filter
$skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;

// Get enrolled courses for dropdown
$enrolled_query = "
SELECT 
    s.id as skill_id,
    s.skill_name,
    b.id as batch_id,
    b.batch_name,
    b.start_time,
    b.end_time,
    ses.session_name,
    ses.id as session_id
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
WHERE se.student_id = ? AND se.status = 'active'
ORDER BY s.skill_name, b.batch_name
";
$stmt_enrolled = $conn->prepare($enrolled_query);
$stmt_enrolled->bind_param("i", $student_id);
$stmt_enrolled->execute();
$enrolled_courses = $stmt_enrolled->get_result();

// If no skill_id specified but student has courses, use the first one
if ($skill_id == 0 && $enrolled_courses->num_rows > 0) {
    $first_course = $enrolled_courses->fetch_assoc();
    $skill_id = $first_course['skill_id'];
    mysqli_data_seek($enrolled_courses, 0); // Reset pointer
}

// Fetch fee details for selected/all courses
$fees_data = [];
$total_fee = 0;
$total_paid = 0;
$total_pending = 0;
$total_advance = 0;

// Base query for fee details
$fee_query = "
SELECT 
    se.id as enrollment_id,
    s.id as skill_id,
    s.skill_name,
    b.id as batch_id,
    b.batch_name,
    b.start_time,
    b.end_time,
    ses.id as session_id,
    ses.session_name,
    COALESCE(fs.total_fee, 0) as total_fee,
    COALESCE(fc.total_paid, 0) as paid_amount,
    COALESCE(fc.payment_count, 0) as payment_count,
    COALESCE(fc.last_payment_date, NULL) as last_payment_date,
    CASE 
        WHEN COALESCE(fc.total_paid, 0) >= COALESCE(fs.total_fee, 0) AND COALESCE(fs.total_fee, 0) > 0 THEN 'Paid'
        WHEN COALESCE(fc.total_paid, 0) > 0 AND COALESCE(fs.total_fee, 0) > 0 THEN 'Partial'
        WHEN COALESCE(fs.total_fee, 0) = 0 THEN 'No Fee'
        ELSE 'Unpaid'
    END as payment_status
FROM student_enrollments se
JOIN skills s ON se.skill_id = s.id
JOIN batches b ON se.batch_id = b.id
JOIN sessions ses ON se.session_id = ses.id
LEFT JOIN fee_structures fs ON s.id = fs.skill_id AND ses.id = fs.session_id
LEFT JOIN (
    SELECT 
        skill_id,
        session_id,
        SUM(amount_paid) as total_paid,
        MAX(payment_date) as last_payment_date,
        COUNT(*) as payment_count
    FROM fee_collections 
    WHERE student_id = ? AND status = 'active'
    GROUP BY skill_id, session_id
) fc ON s.id = fc.skill_id AND ses.id = fc.session_id
WHERE se.student_id = ? AND se.status = 'active'
";

$params = [$student_id, $student_id];
$types = "ii";

if ($skill_id > 0) {
    $fee_query .= " AND s.id = ?";
    $params[] = $skill_id;
    $types .= "i";
}

$fee_query .= " ORDER BY s.skill_name, b.batch_name";

$stmt_fee = $conn->prepare($fee_query);
$stmt_fee->bind_param($types, ...$params);
$stmt_fee->execute();
$fee_result = $stmt_fee->get_result();

// Debug: Check how many rows returned
$row_count = $fee_result->num_rows;

while ($row = $fee_result->fetch_assoc()) {
    // Calculate pending amount
    $pending_amount = max(0, $row['total_fee'] - $row['paid_amount']);
    $advance_amount = max(0, $row['paid_amount'] - $row['total_fee']);

    // If there's advance, adjust pending
    if ($advance_amount > 0) {
        $pending_amount = 0;
        $total_advance += $advance_amount;
    } else {
        $total_pending += $pending_amount;
    }

    // Add calculated fields
    $row['pending_amount'] = $pending_amount;
    $row['advance_amount'] = $advance_amount;

    $fees_data[] = $row;
    $total_fee += $row['total_fee'];
    $total_paid += $row['paid_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Fee Details | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-partial {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .status-nofee {
            background: #e5e7eb;
            color: #4b5563;
            border: 1px solid #9ca3af;
        }

        .amount-cell {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .amount-paid {
            color: #10b981;
        }

        .amount-pending {
            color: #ef4444;
        }

        .amount-total {
            color: #3b82f6;
        }

        .amount-nofee {
            color: #9ca3af;
        }

        .batch-time {
            font-size: 12px;
            color: #6b7280;
        }

        .filter-tab {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .filter-tab.active {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
            font-weight: 600;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="flex min-h-screen">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Fee Details</h1>
                        <p class="text-gray-500 mt-2">
                            <i class="fas fa-rupee-sign text-green-500 mr-2"></i>
                            View and manage your course fees
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="make_payment.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-credit-card"></i>
                            Make Payment
                        </a>
                        <a href="payment_history.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-history"></i>
                            Payment History
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Filter Fee Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Course Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Course</label>
                        <div class="flex gap-2">
                            <select id="courseSelect" onchange="changeCourse()"
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="0">All Courses</option>
                                <?php
                                $enrolled_courses->data_seek(0);
                                while ($course = $enrolled_courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['skill_id']; ?>"
                                        <?php echo $skill_id == $course['skill_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['skill_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                        <div class="flex gap-2">
                            <button onclick="filterStatus('all')" class="filter-tab active">All</button>
                            <button onclick="filterStatus('paid')" class="filter-tab">Paid</button>
                            <button onclick="filterStatus('partial')" class="filter-tab">Partial</button>
                            <button onclick="filterStatus('unpaid')" class="filter-tab">Unpaid</button>
                            <button onclick="filterStatus('nofee')" class="filter-tab">No Fee</button>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-4 flex flex-wrap gap-4 text-sm">
                    <div class="text-gray-600">
                        <span class="font-medium">Total Fee:</span> ₹<?php echo number_format($total_fee); ?>
                    </div>
                    <div class="text-green-600">
                        <span class="font-medium">Paid:</span> ₹<?php echo number_format($total_paid); ?>
                    </div>
                    <div class="text-red-600">
                        <span class="font-medium">Pending:</span> ₹<?php echo number_format($total_pending); ?>
                    </div>
                    <?php if ($total_advance > 0): ?>
                        <div class="text-purple-600">
                            <span class="font-medium">Advance:</span> ₹<?php echo number_format($total_advance); ?>
                        </div>
                    <?php endif; ?>
                    <div class="text-gray-500">
                        <span class="font-medium">Showing:</span> <?php echo $row_count; ?> course(s)
                    </div>
                </div>
            </div>

            <!-- Fee Details Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Course</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Batch & Time</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Session</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Total Fee</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Paid</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Pending</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Last Payment</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($fees_data) > 0): ?>
                                <?php foreach ($fees_data as $index => $fee):
                                    $status_class = 'status-' . strtolower($fee['payment_status']);
                                ?>
                                    <tr class="fee-row hover:bg-gray-50" data-status="<?php echo strtolower($fee['payment_status']); ?>">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($fee['skill_name']); ?></div>
                                            <div class="text-xs text-gray-500">#ENR-<?php echo str_pad($fee['enrollment_id'], 6, '0', STR_PAD_LEFT); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($fee['batch_name']); ?></div>
                                            <?php if (!empty($fee['start_time']) && !empty($fee['end_time'])): ?>
                                                <div class="batch-time">
                                                    <?php echo date('h:i A', strtotime($fee['start_time'])); ?> -
                                                    <?php echo date('h:i A', strtotime($fee['end_time'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-700"><?php echo htmlspecialchars($fee['session_name']); ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($fee['total_fee'] > 0): ?>
                                                <div class="amount-cell amount-total">₹<?php echo number_format($fee['total_fee']); ?></div>
                                            <?php else: ?>
                                                <div class="amount-cell amount-nofee">No Fee</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($fee['total_fee'] > 0): ?>
                                                <div class="amount-cell amount-paid">₹<?php echo number_format($fee['paid_amount']); ?></div>
                                                <?php if ($fee['payment_count'] > 0): ?>
                                                    <div class="text-xs text-gray-500"><?php echo $fee['payment_count']; ?> payment(s)</div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-500">-</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($fee['total_fee'] > 0): ?>
                                                <div class="amount-cell amount-pending">₹<?php echo number_format($fee['pending_amount']); ?></div>
                                                <?php if ($fee['advance_amount'] > 0): ?>
                                                    <div class="text-xs text-green-600">+₹<?php echo number_format($fee['advance_amount']); ?> advance</div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-500">-</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?php echo $status_class; ?> status-badge">
                                                <i class="fas <?php
                                                                switch (strtolower($fee['payment_status'])) {
                                                                    case 'paid':
                                                                        echo 'fa-check';
                                                                        break;
                                                                    case 'partial':
                                                                        echo 'fa-clock';
                                                                        break;
                                                                    case 'unpaid':
                                                                        echo 'fa-exclamation';
                                                                        break;
                                                                    case 'no fee':
                                                                        echo 'fa-info-circle';
                                                                        break;
                                                                    default:
                                                                        echo 'fa-question-circle';
                                                                }
                                                                ?> mr-1"></i>
                                                <?php echo $fee['payment_status']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($fee['last_payment_date']): ?>
                                                <div class="text-gray-700 text-sm"><?php echo date('d M, Y', strtotime($fee['last_payment_date'])); ?></div>
                                            <?php else: ?>
                                                <div class="text-gray-400 text-sm">No payments</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <?php if ($fee['total_fee'] > 0 && $fee['pending_amount'] > 0): ?>
                                                    <a href="make_payment.php?skill_id=<?php echo $fee['skill_id']; ?>&batch_id=<?php echo $fee['batch_id']; ?>&session_id=<?php echo $fee['session_id']; ?>"
                                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fas fa-credit-card mr-1"></i>Pay
                                                    </a>
                                                <?php endif; ?>
                                                <a href="payment_history.php?skill_id=<?php echo $fee['skill_id']; ?>&batch_id=<?php echo $fee['batch_id']; ?>&session_id=<?php echo $fee['session_id']; ?>"
                                                    class="text-green-600 hover:text-green-800 text-sm">
                                                    <i class="fas fa-history mr-1"></i>History
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="py-8 px-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <i class="fas fa-file-invoice-dollar text-gray-400 text-xl"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Fee Details Found</h3>
                                            <p class="text-gray-500">
                                                <?php if ($skill_id > 0): ?>
                                                    No fee structure found for the selected course.
                                                <?php else: ?>
                                                    You are not enrolled in any courses or no fee structure has been defined.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-sm text-blue-800">Total Fee</div>
                            <div class="text-xl font-bold text-blue-900">₹<?php echo number_format($total_fee); ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <div class="text-sm text-green-800">Amount Paid</div>
                            <div class="text-xl font-bold text-green-900">₹<?php echo number_format($total_paid); ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-circle text-red-600"></i>
                        </div>
                        <div>
                            <div class="text-sm text-red-800">Amount Pending</div>
                            <div class="text-xl font-bold text-red-900">₹<?php echo number_format($total_pending); ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($total_advance > 0): ?>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-forward text-purple-600"></i>
                            </div>
                            <div>
                                <div class="text-sm text-purple-800">Advance Amount</div>
                                <div class="text-xl font-bold text-purple-900">₹<?php echo number_format($total_advance); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Change course function
        function changeCourse() {
            const select = document.getElementById('courseSelect');
            const skill_id = select.value;
            window.location.href = `fees.php?skill_id=${skill_id}`;
        }

        // Filter by status
        function filterStatus(status) {
            const rows = document.querySelectorAll('.fee-row');
            const tabs = document.querySelectorAll('.filter-tab');

            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add filter tabs functionality
            const filterTabs = document.querySelectorAll('.filter-tab');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>

</body>

</html>
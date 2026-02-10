<?php
// payment_history
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Get student ID from session
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

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Build base query
$base_query = "
SELECT 
    fc.id as payment_id,
    fc.amount_paid,
    fc.payment_date,
    fc.payment_method,
    fc.reference_no,
    fc.remarks,
    fc.status as payment_status,
    s.skill_name,
    s.id as skill_id,
    b.batch_name,
    ses.session_name,
    fs.total_fee,
    (SELECT SUM(amount_paid) 
     FROM fee_collections fc2 
     WHERE fc2.student_id = fc.student_id 
     AND fc2.skill_id = fc.skill_id 
     AND fc2.session_id = fc.session_id 
     AND fc2.status = 'active') as total_paid_for_course
FROM fee_collections fc
JOIN skills s ON fc.skill_id = s.id
JOIN batches b ON fc.batch_id = b.id
JOIN sessions ses ON fc.session_id = ses.id
JOIN fee_structures fs ON fc.skill_id = fs.skill_id AND fc.session_id = fs.session_id
WHERE fc.student_id = ?
";

// Add filters
$params = [$student_id];
$param_types = "i";
$conditions = [];

// Search filter
if (!empty($search)) {
    $conditions[] = "(s.skill_name LIKE ? OR fc.reference_no LIKE ? OR fc.remarks LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

// Status filter
if ($status !== 'all') {
    $conditions[] = "fc.status = ?";
    $params[] = $status;
    $param_types .= "s";
}

// Payment method filter
if ($payment_method !== 'all') {
    $conditions[] = "fc.payment_method = ?";
    $params[] = $payment_method;
    $param_types .= "s";
}

// Course filter
if ($course_id > 0) {
    $conditions[] = "fc.skill_id = ?";
    $params[] = $course_id;
    $param_types .= "i";
}

// Date range filter
if (!empty($date_from)) {
    $conditions[] = "DATE(fc.payment_date) >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $conditions[] = "DATE(fc.payment_date) <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

// Add conditions to query
if (!empty($conditions)) {
    $base_query .= " AND " . implode(" AND ", $conditions);
}

// Add sorting
$base_query .= " ORDER BY fc.payment_date DESC, fc.id DESC";

// Prepare and execute query
$stmt = $conn->prepare($base_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$payments_result = $stmt->get_result();

// Calculate totals
$total_amount_paid = 0;
$total_payments = 0;
$payments_by_method = [];
$payments_by_month = [];

while ($payment = $payments_result->fetch_assoc()) {
    $total_amount_paid += $payment['amount_paid'];
    $total_payments++;
    
    // Group by payment method
    $method = $payment['payment_method'];
    if (!isset($payments_by_method[$method])) {
        $payments_by_method[$method] = 0;
    }
    $payments_by_method[$method] += $payment['amount_paid'];
    
    // Group by month
    $month = date('Y-m', strtotime($payment['payment_date']));
    if (!isset($payments_by_month[$month])) {
        $payments_by_month[$month] = 0;
    }
    $payments_by_month[$month] += $payment['amount_paid'];
}

// Reset pointer for later use
$payments_result->data_seek(0);

// Get enrolled courses for filter dropdown
$courses_query = "
SELECT DISTINCT s.id, s.skill_name
FROM fee_collections fc
JOIN skills s ON fc.skill_id = s.id
WHERE fc.student_id = ?
ORDER BY s.skill_name
";
$stmt_courses = $conn->prepare($courses_query);
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment History | Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .payment-row {
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.2s;
        }
        
        .payment-row:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active { background: #d1fae5; color: #047857; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-cancelled { background: #fee2e2; color: #ef4444; }
        
        .method-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .method-cash { background: #d1fae5; color: #047857; }
        .method-card { background: #dbeafe; color: #1d4ed8; }
        .method-cheque { background: #fef3c7; color: #d97706; }
        .method-online { background: #e0e7ff; color: #4f46e5; }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .pagination-btn {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        
        .pagination-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .export-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-btn:hover {
            background: #059669;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .chart-bar {
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .chart-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
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
                        <h1 class="text-3xl font-bold text-gray-800">Payment History</h1>
                        <p class="text-gray-600 mt-2">
                            <i class="fas fa-history text-blue-500 mr-2"></i>
                            View and manage all your payment records
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg">
                            <i class="fas fa-user-graduate mr-2"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($student['name']); ?></span>
                        </div>
                        <a href="make_payment.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2">
                            <i class="fas fa-plus mr-1"></i>
                            Make New Payment
                        </a>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="summary-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Total Paid</p>
                            <h2 class="text-2xl font-bold mt-1">₹<?php echo number_format($total_amount_paid, 2); ?></h2>
                        </div>
                        <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-rupee-sign text-xl"></i>
                        </div>
                    </div>
                    <p class="text-blue-100 text-sm mt-4"><?php echo $total_payments; ?> payment<?php echo $total_payments != 1 ? 's' : ''; ?> total</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">This Month</p>
                            <?php 
                            $this_month = date('Y-m');
                            $monthly_total = isset($payments_by_month[$this_month]) ? $payments_by_month[$this_month] : 0;
                            ?>
                            <h2 class="text-2xl font-bold text-gray-800 mt-1">₹<?php echo number_format($monthly_total, 2); ?></h2>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mt-4"><?php echo date('F Y'); ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Cash Payments</p>
                            <h2 class="text-2xl font-bold text-gray-800 mt-1">₹<?php echo number_format($payments_by_method['cash'] ?? 0, 2); ?></h2>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mt-4"><?php echo isset($payments_by_method['cash']) ? round(($payments_by_method['cash'] / $total_amount_paid) * 100, 1) : 0; ?>% of total</p>
                </div>
                
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Online Payments</p>
                            <h2 class="text-2xl font-bold text-gray-800 mt-1">₹<?php echo number_format($payments_by_method['online'] ?? 0, 2); ?></h2>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-globe text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mt-4"><?php echo isset($payments_by_method['online']) ? round(($payments_by_method['online'] / $total_amount_paid) * 100, 1) : 0; ?>% of total</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="filter-card">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Filter Payments</h2>
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Course, Reference, Remarks..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- Course Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select name="course_id" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="0">All Courses</option>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['skill_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Payment Method Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all" <?php echo $payment_method == 'all' ? 'selected' : ''; ?>>All Methods</option>
                            <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="card" <?php echo $payment_method == 'card' ? 'selected' : ''; ?>>Card</option>
                            <option value="cheque" <?php echo $payment_method == 'cheque' ? 'selected' : ''; ?>>Cheque</option>
                            <option value="online" <?php echo $payment_method == 'online' ? 'selected' : ''; ?>>Online</option>
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" 
                               name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" 
                               name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="md:col-span-2 lg:col-span-2 flex items-end gap-3">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-filter mr-2"></i>
                            Apply Filters
                        </button>
                        <a href="payment_history" 
                           class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-redo mr-2"></i>
                            Reset
                        </a>
                        <button type="button" 
                                onclick="exportPayments()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-file-export mr-2"></i>
                            Export
                        </button>
                    </div>
                </form>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Payment Methods Chart -->
                <div class="chart-container">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Methods Distribution</h3>
                    <div class="space-y-4">
                        <?php 
                        $methods = [
                            'cash' => ['icon' => 'money-bill-wave', 'color' => '#047857'],
                            'online' => ['icon' => 'globe', 'color' => '#4f46e5'],
                            'card' => ['icon' => 'credit-card', 'color' => '#1d4ed8'],
                            'cheque' => ['icon' => 'file-invoice-dollar', 'color' => '#d97706']
                        ];
                        
                        foreach ($methods as $method => $info): 
                            $amount = $payments_by_method[$method] ?? 0;
                            $percentage = $total_amount_paid > 0 ? ($amount / $total_amount_paid) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-<?php echo $info['icon']; ?> text-gray-500"></i>
                                    <span class="text-sm font-medium text-gray-700"><?php echo ucfirst($method); ?></span>
                                </div>
                                <div class="text-sm font-bold text-gray-800">₹<?php echo number_format($amount, 2); ?></div>
                            </div>
                            <div class="chart-bar">
                                <div class="chart-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $info['color']; ?>;"></div>
                            </div>
                            <div class="text-xs text-gray-500"><?php echo round($percentage, 1); ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Monthly Payments Chart -->
                <div class="chart-container">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Monthly Payments Trend</h3>
                    <div class="space-y-4">
                        <?php 
                        // Get last 6 months
                        $last_6_months = [];
                        for ($i = 5; $i >= 0; $i--) {
                            $month = date('Y-m', strtotime("-$i months"));
                            $last_6_months[$month] = $payments_by_month[$month] ?? 0;
                        }
                        
                        $max_amount = max($last_6_months) ?: 1;
                        
                        foreach ($last_6_months as $month => $amount): 
                            $percentage = ($amount / $max_amount) * 100;
                            $month_name = date('M Y', strtotime($month . '-01'));
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <div class="text-sm font-medium text-gray-700"><?php echo $month_name; ?></div>
                                <div class="text-sm font-bold text-gray-800">₹<?php echo number_format($amount, 2); ?></div>
                            </div>
                            <div class="chart-bar">
                                <div class="chart-fill" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Payment Records</h2>
                    <div class="text-sm text-gray-500">
                        Showing <?php echo $total_payments; ?> payment<?php echo $total_payments != 1 ? 's' : ''; ?>
                    </div>
                </div>
                
                <?php if ($total_payments > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($payment = $payments_result->fetch_assoc()): ?>
                            <tr class="payment-row">
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-gray-900">
                                            #PAY-<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <i class="far fa-calendar mr-1"></i>
                                            <?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="method-badge method-<?php echo $payment['payment_method']; ?>">
                                                <i class="fas fa-<?php 
                                                    switch($payment['payment_method']) {
                                                        case 'cash': echo 'money-bill-wave'; break;
                                                        case 'card': echo 'credit-card'; break;
                                                        case 'cheque': echo 'file-invoice-dollar'; break;
                                                        case 'online': echo 'globe'; break;
                                                        default: echo 'money-bill-wave';
                                                    }
                                                ?> mr-1"></i>
                                                <?php echo ucfirst($payment['payment_method']); ?>
                                            </span>
                                            <?php if (!empty($payment['reference_no'])): ?>
                                            <span class="text-xs text-gray-500">
                                                Ref: <?php echo htmlspecialchars($payment['reference_no']); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($payment['remarks'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <i class="far fa-comment mr-1"></i>
                                            <?php echo htmlspecialchars($payment['remarks']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($payment['skill_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Batch: <?php echo htmlspecialchars($payment['batch_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Session: <?php echo htmlspecialchars($payment['session_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Total Fee: ₹<?php echo number_format($payment['total_fee'], 2); ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-lg font-bold text-green-600">
                                            ₹<?php echo number_format($payment['amount_paid'], 2); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Paid: ₹<?php echo number_format($payment['total_paid_for_course'], 2); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Pending: ₹<?php echo number_format($payment['total_fee'] - $payment['total_paid_for_course'], 2); ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                        <i class="fas fa-<?php 
                                            switch($payment['payment_status']) {
                                                case 'active': echo 'check-circle'; break;
                                                case 'pending': echo 'clock'; break;
                                                case 'cancelled': echo 'times-circle'; break;
                                                default: echo 'question-circle';
                                            }
                                        ?> mr-1"></i>
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="receipt.php?payment_id=<?php echo $payment['payment_id']; ?>" 
                                           class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-receipt mr-1"></i>
                                            Receipt
                                        </a>
                                        <?php if ($payment['payment_status'] == 'pending'): ?>
                                        <button onclick="cancelPayment(<?php echo $payment['payment_id']; ?>)" 
                                                class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                            <i class="fas fa-times mr-1"></i>
                                            Cancel
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <div class="inline-block p-4 bg-gray-100 rounded-full mb-4">
                        <i class="fas fa-history text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Payment Records Found</h3>
                    <p class="text-gray-500 mb-6">You haven't made any payments yet.</p>
                    <a href="make_payment.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg inline-flex items-center gap-2">
                        <i class="fas fa-credit-card"></i>
                        Make Your First Payment
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Export Modal -->
            <div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Export Payment History</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                                <select id="exportFormat" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="csv">CSV (Excel)</option>
                                    <option value="pdf">PDF Document</option>
                                    <option value="print">Print Preview</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="date" id="exportFrom" class="border border-gray-300 rounded-lg px-3 py-2">
                                    <input type="date" id="exportTo" class="border border-gray-300 rounded-lg px-3 py-2">
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 mt-6">
                                <button onclick="closeExportModal()" 
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">
                                    Cancel
                                </button>
                                <button onclick="confirmExport()" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                    <i class="fas fa-download mr-2"></i>
                                    Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("input[type='date']", {
            dateFormat: "Y-m-d",
            allowInput: true
        });

        // Export functions
        function exportPayments() {
            document.getElementById('exportModal').classList.remove('hidden');
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.add('hidden');
        }

        function confirmExport() {
            const format = document.getElementById('exportFormat').value;
            const dateFrom = document.getElementById('exportFrom').value;
            const dateTo = document.getElementById('exportTo').value;
            
            let url = 'export_payments.php?format=' + format;
            
            // Add current filters to export
            const params = new URLSearchParams(window.location.search);
            url += '&' + params.toString();
            
            if (dateFrom) url += '&export_from=' + dateFrom;
            if (dateTo) url += '&export_to=' + dateTo;
            
            window.open(url, '_blank');
            closeExportModal();
        }

        // Cancel payment function
        function cancelPayment(paymentId) {
            if (confirm('Are you sure you want to cancel this payment? This action cannot be undone.')) {
                // In a real application, this would make an AJAX call to cancel the payment
                alert('Payment cancellation would be processed here. In a real system, this would make an API call.');
                
                // Example AJAX call:
                /*
                fetch('cancel_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ payment_id: paymentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment cancelled successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
                */
            }
        }

        // Print function
        function printPayments() {
            const printWindow = window.open('', '_blank');
            const tableContent = document.querySelector('table').outerHTML;
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Payment History - <?php echo htmlspecialchars($student['name']); ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        .total { font-weight: bold; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <h1>Payment History - <?php echo htmlspecialchars($student['name']); ?></h1>
                    <p>Generated: <?php echo date('d/m/Y H:i:s'); ?></p>
                    ${tableContent}
                    <div class="total">
                        Total Amount Paid: ₹<?php echo number_format($total_amount_paid, 2); ?>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Quick filters
        function applyQuickFilter(filter) {
            let url = 'payment_history?';
            
            switch(filter) {
                case 'this_month':
                    const today = new Date();
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    
                    url += 'date_from=' + formatDate(firstDay) + '&date_to=' + formatDate(lastDay);
                    break;
                    
                case 'last_30_days':
                    const today2 = new Date();
                    const thirtyDaysAgo = new Date();
                    thirtyDaysAgo.setDate(today2.getDate() - 30);
                    
                    url += 'date_from=' + formatDate(thirtyDaysAgo) + '&date_to=' + formatDate(today2);
                    break;
                    
                case 'online_only':
                    url += 'payment_method=online';
                    break;
                    
                case 'pending_only':
                    url += 'status=pending';
                    break;
            }
            
            window.location.href = url;
        }
        
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        // Download receipt for all payments
        function downloadAllReceipts() {
            if (confirm('This will generate receipts for all your payments. Continue?')) {
                // In a real application, this would generate a ZIP file with all receipts
                alert('In a real system, this would generate and download a ZIP file containing all receipts.');
            }
        }
    </script>
</body>
</html>
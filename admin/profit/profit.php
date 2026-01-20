<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

// Handle success messages
if (isset($_GET['generated'])) {
    $success_message = "Profit generated successfully for selected month!";
} elseif (isset($_GET['added'])) {
    $success_message = "Profit added successfully!";
} elseif (isset($_GET['updated'])) {
    $success_message = "Profit updated successfully!";
} elseif (isset($_GET['deleted'])) {
    $success_message = "Profit record deleted successfully!";
}

// Filter values
$filter = $_GET['filter'] ?? 'month'; // month, 3months, 6months, year
$filter_month = $_GET['month'] ?? date('n');
$filter_year = $_GET['year'] ?? date('Y');

// Set date condition based on filter
switch ($filter) {
    case '3months':
        $date_condition = "DATE(CONCAT(profit_year, '-', profit_month, '-01')) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        $label = "Last 3 Months";
        break;
    case '6months':
        $date_condition = "DATE(CONCAT(profit_year, '-', profit_month, '-01')) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $label = "Last 6 Months";
        break;
    case 'year':
        $date_condition = "profit_year = $filter_year";
        $label = "Year " . $filter_year;
        break;
    default: // month
        $date_condition = "profit_month = $filter_month AND profit_year = $filter_year";
        $label = date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year));
}

// Fetch profits
$profit_query = "
    SELECT *
    FROM monthly_profit
    WHERE status='active' 
      AND $date_condition
    ORDER BY profit_year DESC, profit_month DESC
";
$profit_result = mysqli_query($conn, $profit_query);

// Calculate totals for the period
$totals_query = "
    SELECT 
        SUM(total_fees) as total_fees,
        SUM(total_donations) as total_donations,
        SUM(total_expenses) as total_expenses,
        SUM(net_profit) as total_net_profit,
        COUNT(*) as record_count
    FROM monthly_profit
    WHERE status='active' 
      AND $date_condition
";
$totals_result = mysqli_query($conn, $totals_query);
$totals = mysqli_fetch_assoc($totals_result);

// Get recent months for quick filter
$recent_months_query = "
    SELECT DISTINCT profit_month, profit_year
    FROM monthly_profit
    WHERE status='active'
    ORDER BY profit_year DESC, profit_month DESC
    LIMIT 6
";
$recent_months_result = mysqli_query($conn, $recent_months_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profit Management | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            background: #111827;
            color: white;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
            color: #d1d5db;
            text-decoration: none;
        }

        .sidebar-link:hover {
            background: #374151;
            color: white;
        }

        .sidebar-link.active {
            background: #3b82f6;
            color: white;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb !important;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .filter-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .filter-btn:not(.active) {
            background: #f3f4f6;
            color: #6b7280;
        }

        .filter-btn:not(.active):hover {
            background: #e5e7eb;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .profit-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .profit-positive {
            background: #d1fae5;
            color: #065f46;
        }

        .profit-negative {
            background: #fee2e2;
            color: #991b1b;
        }

        .search-box {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-box:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 sidebar h-screen sticky top-0">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-bold text-white">🎓 EduSkill Pro</h2>
                <p class="text-xs text-gray-300 mt-1">Admin Panel</p>
            </div>

            <nav class="p-3 space-y-1">
                <a href="../dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Financial</p>
                    <a href="../fees/fee_structures.php" class="sidebar-link">
                        <i class="fas fa-calculator"></i> Fee Structures
                    </a>
                    <a href="../fees/fee_collection.php" class="sidebar-link">
                        <i class="fas fa-cash-register"></i> Fee Collection
                    </a>
                    <a href="../fees/fee_history.php" class="sidebar-link">
                        <i class="fas fa-history"></i> Fee History
                    </a>
                    <a href="../expenses/expenses.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Expenses
                    </a>
                    <a href="../donations/donations.php" class="sidebar-link">
                        <i class="fas fa-hand-holding-usd"></i> Donations
                    </a>
                    <a href="profit.php" class="sidebar-link active">
                        <i class="fas fa-chart-line"></i> Profit Management
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Student Management</p>
                    <a href="../enrollments/enrollment_list.php" class="sidebar-link">
                        <i class="fas fa-user-check"></i> Enrollments
                    </a>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Operations</p>
                    <a href="../reports/student_report.php" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </div>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Profit Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-chart-line text-green-500 mr-1"></i>
                        Track and manage academy profits
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search profits..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                    <a href="add_profit.php"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Profit
                    </a>
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

            <!-- Filters -->
            <div class="flex gap-2 mb-6">
                <a href="?filter=month&month=<?= $filter_month ?>&year=<?= $filter_year ?>" class="filter-btn <?= $filter == 'month' ? 'active' : '' ?>">This Month</a>
                <a href="?filter=3months" class="filter-btn <?= $filter == '3months' ? 'active' : '' ?>">Last 3 Months</a>
                <a href="?filter=6months" class="filter-btn <?= $filter == '6months' ? 'active' : '' ?>">Last 6 Months</a>
                <a href="?filter=year&year=<?= $filter_year ?>" class="filter-btn <?= $filter == 'year' ? 'active' : '' ?>">This Year</a>
            </div>

            <!-- Month/Year Selector -->
            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="filter" value="month">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Month</label>
                        <select name="month" class="search-box" style="width: 200px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($filter_month == $m) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Year</label>
                        <input type="number" name="year" min="2020" max="2030" value="<?php echo $filter_year; ?>" class="search-box" style="width: 120px;">
                    </div>
                    <div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                    <div class="ml-auto">
                        <a href="generate_profit.php"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-medium">
                            <i class="fas fa-calculator mr-1"></i> Generate Profit
                        </a>
                    </div>
                </form>
            </div>

            <!-- Quick Month Filter -->
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Quick Month Filter</h3>
                <div class="flex flex-wrap gap-2">
                    <?php while ($month_row = mysqli_fetch_assoc($recent_months_result)): ?>
                        <a href="?filter=month&month=<?= $month_row['profit_month'] ?>&year=<?= $month_row['profit_year'] ?>"
                            class="px-3 py-1.5 text-sm rounded-full border border-gray-300 hover:bg-gray-50 text-gray-700 hover:text-gray-900">
                            <?= date('M Y', mktime(0, 0, 0, $month_row['profit_month'], 1, $month_row['profit_year'])) ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Period</p>
                    <h3 class="text-xl font-bold text-gray-800"><?= $label ?></h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Net Profit</p>
                    <h3 class="text-2xl font-bold <?= ($totals['total_net_profit'] >= 0) ? 'text-green-600' : 'text-red-600' ?>">
                        <?= number_format($totals['total_net_profit'] ?? 0, 2) ?> PKR
                    </h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Records</p>
                    <h3 class="text-xl font-bold text-gray-800"><?= $totals['record_count'] ?? 0 ?></h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Average Monthly Profit</p>
                    <h3 class="text-xl font-bold <?= (($totals['total_net_profit'] ?? 0) / max(($totals['record_count'] ?? 1), 1) >= 0) ? 'text-green-600' : 'text-red-600' ?>">
                        <?= number_format(($totals['total_net_profit'] ?? 0) / max(($totals['record_count'] ?? 1), 1), 2) ?> PKR
                    </h3>
                </div>
            </div>

            <!-- Profit Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Fees Collected</p>
                    <h3 class="text-xl font-bold text-blue-600"><?= number_format($totals['total_fees'] ?? 0, 2) ?> PKR</h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Donations</p>
                    <h3 class="text-xl font-bold text-green-600"><?= number_format($totals['total_donations'] ?? 0, 2) ?> PKR</h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Expenses</p>
                    <h3 class="text-xl font-bold text-red-600"><?= number_format($totals['total_expenses'] ?? 0, 2) ?> PKR</h3>
                </div>
            </div>

            <!-- Profits Table -->
            <div class="table-container">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Profit Records (<?= $totals['record_count'] ?? 0 ?>)</h3>
                </div>

                <?php if (mysqli_num_rows($profit_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">#</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Period</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Fees</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Donations</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Expenses</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Net Profit</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Remarks</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $i = 1;
                                mysqli_data_seek($profit_result, 0);
                                while ($row = mysqli_fetch_assoc($profit_result)):
                                    $profit_class = $row['net_profit'] >= 0 ? 'profit-positive' : 'profit-negative';
                                ?>
                                    <tr class="profit-row">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">#<?= $row['id'] ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">
                                                <?= date('F Y', mktime(0, 0, 0, $row['profit_month'], 1, $row['profit_year'])) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Updated: <?= date('M d, Y', strtotime($row['updated_at'])) ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-semibold text-blue-600">
                                                <?= number_format($row['total_fees'], 2) ?> PKR
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-semibold text-green-600">
                                                <?= number_format($row['total_donations'], 2) ?> PKR
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-semibold text-red-600">
                                                <?= number_format($row['total_expenses'], 2) ?> PKR
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?= $profit_class ?> profit-badge">
                                                <i class="fas fa-<?= $row['net_profit'] >= 0 ? 'arrow-up' : 'arrow-down' ?> text-xs"></i>
                                                <?= number_format($row['net_profit'], 2) ?> PKR
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($row['remarks']) ?>">
                                                <?= htmlspecialchars($row['remarks']) ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <a href="edit_profit.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i> Edit
                                                </a>
                                                <a href="delete_profit.php?id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to delete this profit record?');"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i> Delete
                                                </a>
                                                <?php if (!empty($row['remarks'])): ?>
                                                    <button onclick="showRemarks('<?= htmlspecialchars(addslashes($row['remarks'])) ?>')"
                                                        class="action-btn bg-gray-50 text-gray-700 hover:bg-gray-100"
                                                        title="View Full Remarks">
                                                        <i class="fas fa-eye text-xs"></i> View
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t bg-gray-50">
                        <div class="flex justify-between items-center">
                            <p class="text-sm text-gray-600">
                                Showing <?= mysqli_num_rows($profit_result) ?> profit records for <?= $label ?>
                            </p>
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Formula:</span> Net Profit = (Fees + Donations) - Expenses
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-chart-line text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Profit Records Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No profit records found for <?= $label ?>. You can add new records or generate them automatically.</p>
                        <div class="flex gap-3 justify-center">
                            <a href="add_profit.php"
                                class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                <i class="fas fa-plus"></i> Add Profit Record
                            </a>
                            <a href="generate_profit.php"
                                class="inline-flex items-center gap-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                                <i class="fas fa-calculator"></i> Generate Automatically
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.profit-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Show remarks in modal
        function showRemarks(remarks) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Profit Remarks</h3>
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-4 bg-gray-50 rounded border border-gray-200">
                        <p class="text-sm text-gray-700">${remarks}</p>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
    </script>

</body>

</html>
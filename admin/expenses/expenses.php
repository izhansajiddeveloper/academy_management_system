<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

/*
|--------------------------------------------------------------------------
| Expense Filter (Month / 3 Months / 6 Months)
|--------------------------------------------------------------------------
*/
$filter = $_GET['filter'] ?? 'month';

switch ($filter) {
    case '3months':
        $date_condition = "e.created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        $filter_label = "Last 3 Months";
        break;

    case '6months':
        $date_condition = "e.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $filter_label = "Last 6 Months";
        break;

    case 'month':
    default:
        $date_condition = "
            MONTH(e.created_at) = MONTH(CURDATE()) 
            AND YEAR(e.created_at) = YEAR(CURDATE())
        ";
        $filter_label = "This Month";
        break;
}

/*
|--------------------------------------------------------------------------
| Fetch Expenses
|--------------------------------------------------------------------------
*/
$expenses_query = "
    SELECT 
        e.id,
        e.description,
        e.amount,
        e.created_at,
        ec.category_name
    FROM expenses e
    INNER JOIN expense_categories ec ON e.category_id = ec.id
    WHERE e.status = 'active'
      AND $date_condition
    ORDER BY e.created_at DESC
";
$expenses_result = mysqli_query($conn, $expenses_query);

/*
|--------------------------------------------------------------------------
| Total Expenses
|--------------------------------------------------------------------------
*/
$total_query = "
    SELECT SUM(e.amount) AS total_amount
    FROM expenses e
    WHERE e.status = 'active'
      AND $date_condition
";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_expenses = $total_row['total_amount'] ?? 0;

/*
|--------------------------------------------------------------------------
| Category Breakdown
|--------------------------------------------------------------------------
*/
$category_query = "
    SELECT 
        ec.category_name,
        SUM(e.amount) AS total_amount
    FROM expenses e
    INNER JOIN expense_categories ec ON e.category_id = ec.id
    WHERE e.status = 'active'
      AND $date_condition
    GROUP BY e.category_id
";
$category_result = mysqli_query($conn, $category_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expenses Management | Academy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
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

        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .category-transport {
            background: #dbeafe;
            color: #1e40af;
        }

        .category-daily {
            background: #dcfce7;
            color: #166534;
        }

        .category-rent {
            background: #fef3c7;
            color: #92400e;
        }

        .category-salaries {
            background: #fee2e2;
            color: #991b1b;
        }

        .category-utilities {
            background: #e0e7ff;
            color: #3730a3;
        }

        .category-misc {
            background: #f3e8ff;
            color: #6b21a8;
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
         <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <!-- MAIN CONTENT -->
        <main class="flex-1 p-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Expenses Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-wallet text-blue-500 mr-1"></i>
                        Track and manage academy expenses
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search expenses..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                    <a href="add_expense.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Expense
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-2 mb-6">
                <a href="?filter=month" class="filter-btn <?= $filter == 'month' ? 'active' : '' ?>">This Month</a>
                <a href="?filter=3months" class="filter-btn <?= $filter == '3months' ? 'active' : '' ?>">Last 3 Months</a>
                <a href="?filter=6months" class="filter-btn <?= $filter == '6months' ? 'active' : '' ?>">Last 6 Months</a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Period</p>
                    <h3 class="text-xl font-bold text-gray-800"><?= $filter_label ?></h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Expenses</p>
                    <h3 class="text-2xl font-bold text-red-600"><?= number_format($total_expenses, 2) ?> PKR</h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Records</p>
                    <h3 class="text-xl font-bold text-gray-800"><?= mysqli_num_rows($expenses_result) ?></h3>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="table-container mb-6">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Recent Expenses</h3>
                </div>

                <?php if (mysqli_num_rows($expenses_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">#</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Category</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Description</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Amount</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $i = 1;
                                mysqli_data_seek($expenses_result, 0);
                                while ($row = mysqli_fetch_assoc($expenses_result)):
                                ?>
                                    <tr class="expense-row">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= $i++ ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $cat_name = htmlspecialchars($row['category_name']);
                                            $color_class = $category_colors[$cat_name] ?? 'category-misc';
                                            ?>
                                            <span class="<?= $color_class ?> category-badge">
                                                <?= $cat_name ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($row['description']) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-semibold text-red-600">
                                                <?= number_format($row['amount'], 2) ?> PKR
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= date('d M, Y', strtotime($row['created_at'])) ?></div>
                                            <div class="text-xs text-gray-500"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <a href="edit_expense.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i> Edit
                                                </a>
                                                <a href="delete_expense.php?id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to delete this expense?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t bg-gray-50">
                        <p class="text-sm text-gray-600">
                            Showing <?= mysqli_num_rows($expenses_result) ?> expenses for <?= $filter_label ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-wallet text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Expenses Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No expenses found for <?= $filter_label ?>. Start by adding your first expense.</p>
                        <a href="add_expense.php"
                            class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Add First Expense
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Category Breakdown (Moved below the table) -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Category Breakdown</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    // Define category colors
                    $category_colors = [
                        'Transport' => 'category-transport',
                        'Daily Use' => 'category-daily',
                        'Rent' => 'category-rent',
                        'Salaries' => 'category-salaries',
                        'Utilities' => 'category-utilities',
                        'Miscellaneous' => 'category-misc'
                    ];

                    // Reset category pointer
                    mysqli_data_seek($category_result, 0);

                    while ($cat = mysqli_fetch_assoc($category_result)):
                        $category_name = htmlspecialchars($cat['category_name']);
                        $color_class = $category_colors[$category_name] ?? 'category-misc';
                    ?>
                        <div class="stats-card">
                            <div class="flex justify-between items-start mb-2">
                                <span class="<?= $color_class ?> category-badge">
                                    <?= $category_name ?>
                                </span>
                                <i class="fas fa-chart-pie text-gray-400"></i>
                            </div>
                            <h4 class="text-xl font-bold text-gray-800">
                                <?= number_format($cat['total_amount'], 2) ?> PKR
                            </h4>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= round(($cat['total_amount'] / max($total_expenses, 1)) * 100, 1) ?>% of total
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.expense-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltips = document.querySelectorAll('[title]');
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseenter', function(e) {
                    const title = this.getAttribute('title');
                    if (title) {
                        const tooltipEl = document.createElement('div');
                        tooltipEl.className = 'fixed bg-gray-900 text-white px-2 py-1 rounded text-xs z-50';
                        tooltipEl.textContent = title;
                        document.body.appendChild(tooltipEl);

                        const rect = this.getBoundingClientRect();
                        tooltipEl.style.top = (rect.top - 30) + 'px';
                        tooltipEl.style.left = (rect.left + rect.width / 2 - tooltipEl.offsetWidth / 2) + 'px';

                        this.setAttribute('data-tooltip', tooltipEl);
                        this.removeAttribute('title');
                    }
                });

                tooltip.addEventListener('mouseleave', function() {
                    const tooltipEl = this.getAttribute('data-tooltip');
                    if (tooltipEl) {
                        document.body.removeChild(tooltipEl);
                        this.setAttribute('title', tooltipEl.textContent);
                        this.removeAttribute('data-tooltip');
                    }
                });
            });
        });
    </script>

</body>

</html>
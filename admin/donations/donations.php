<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

// Handle success messages
if (isset($_GET['added'])) {
    $success_message = "Donation added successfully!";
} elseif (isset($_GET['updated'])) {
    $success_message = "Donation updated successfully!";
} elseif (isset($_GET['deleted'])) {
    $success_message = "Donation deleted successfully!";
}

$filter = $_GET['filter'] ?? 'month';

switch ($filter) {
    case '3months':
        $date_condition = "donation_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        $label = "Last 3 Months";
        break;
    case '6months':
        $date_condition = "donation_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        $label = "Last 6 Months";
        break;
    default:
        $date_condition = "MONTH(donation_date)=MONTH(CURDATE()) AND YEAR(donation_date)=YEAR(CURDATE())";
        $label = "This Month";
}

$donations_sql = "
    SELECT * FROM donations
    WHERE status='active' AND $date_condition
    ORDER BY donation_date DESC
";
$donations = mysqli_query($conn, $donations_sql);

// Get total donations
$total_sql = "
    SELECT SUM(amount) AS total
    FROM donations
    WHERE status='active' AND $date_condition
";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total = $total_row['total'] ?? 0;

// Get donation counts by type
$type_sql = "
    SELECT 
        donor_type,
        COUNT(*) as count,
        SUM(amount) as amount
    FROM donations
    WHERE status='active' AND $date_condition
    GROUP BY donor_type
";
$type_result = mysqli_query($conn, $type_sql);

// Get payment method breakdown
$payment_sql = "
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as amount
    FROM donations
    WHERE status='active' AND $date_condition
    GROUP BY payment_method
";
$payment_result = mysqli_query($conn, $payment_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Donations Management | Academy Management System</title>
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
        }

        .type-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .type-organization {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-individual {
            background: #dcfce7;
            color: #166534;
        }

        .payment-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .payment-cash {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-bank {
            background: #dbeafe;
            color: #1e40af;
        }

        .payment-online {
            background: #dcfce7;
            color: #166534;
        }

        .payment-cheque {
            background: #e0e7ff;
            color: #3730a3;
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
                    <p class="text-xs text-gray-400 px-3 mb-2 uppercase tracking-wider">Donations</p>
                    <a href="donations.php" class="sidebar-link active">
                        <i class="fas fa-hand-holding-usd"></i> Donations
                    </a>
                </div>

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
                    <h1 class="text-2xl font-bold text-gray-800">Donations Management</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-hand-holding-usd text-green-500 mr-1"></i>
                        Track and manage academy donations
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                            placeholder="Search donations..."
                            class="pl-9 pr-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 text-sm search-box"
                            id="searchInput">
                    </div>
                    <a href="add_donation.php"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Donation
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
                <a href="?filter=month" class="filter-btn <?= $filter == 'month' ? 'active' : '' ?>">This Month</a>
                <a href="?filter=3months" class="filter-btn <?= $filter == '3months' ? 'active' : '' ?>">Last 3 Months</a>
                <a href="?filter=6months" class="filter-btn <?= $filter == '6months' ? 'active' : '' ?>">Last 6 Months</a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Period</p>
                    <h3 class="text-xl font-bold text-gray-800"><?= $label ?></h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Donations</p>
                    <h3 class="text-2xl font-bold text-green-600"><?= number_format($total, 2) ?> PKR</h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Total Records</p>
                    <h3 class="text-xl font-bold text-gray-800"><?= mysqli_num_rows($donations) ?></h3>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-gray-500 mb-1">Average Donation</p>
                    <h3 class="text-xl font-bold text-gray-800">
                        <?= mysqli_num_rows($donations) > 0 ? number_format($total / mysqli_num_rows($donations), 2) : 0 ?> PKR
                    </h3>
                </div>
            </div>

            <!-- Donations Table -->
            <div class="table-container mb-6">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h3 class="font-medium text-gray-800">Recent Donations (<?= mysqli_num_rows($donations) ?>)</h3>
                </div>

                <?php if (mysqli_num_rows($donations) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">#</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Donor</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Amount</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Payment Method</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $i = 1;
                                mysqli_data_seek($donations, 0);
                                while ($row = mysqli_fetch_assoc($donations)):
                                ?>
                                    <tr class="donation-row">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= $i++ ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['donor_name']) ?></div>
                                            <?php if (!empty($row['contact_person'])): ?>
                                                <div class="text-xs text-gray-500">Contact: <?= htmlspecialchars($row['contact_person']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $type_class = $row['donor_type'] == 'organization' ? 'type-organization' : 'type-individual';
                                            ?>
                                            <span class="<?= $type_class ?> type-badge">
                                                <i class="fas fa-<?= $row['donor_type'] == 'organization' ? 'building' : 'user' ?> text-xs"></i>
                                                <?= ucfirst($row['donor_type']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-lg font-semibold text-green-600">
                                                <?= number_format($row['amount'], 2) ?> PKR
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm text-gray-600"><?= date('d M, Y', strtotime($row['donation_date'])) ?></div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $payment_class = 'payment-' . $row['payment_method'];
                                            $payment_icons = [
                                                'cash' => 'fa-money-bill-wave',
                                                'bank' => 'fa-university',
                                                'online' => 'fa-globe',
                                                'cheque' => 'fa-file-invoice-dollar'
                                            ];
                                            $payment_icon = $payment_icons[$row['payment_method']] ?? 'fa-money-check';
                                            ?>
                                            <span class="<?= $payment_class ?> payment-badge">
                                                <i class="fas <?= $payment_icon ?> text-xs"></i>
                                                <?= ucfirst($row['payment_method']) ?>
                                            </span>
                                            <?php if (!empty($row['reference_no'])): ?>
                                                <div class="text-xs text-gray-500 mt-1">Ref: <?= htmlspecialchars($row['reference_no']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex gap-2">
                                                <a href="edit_donation.php?id=<?= $row['id'] ?>"
                                                    class="action-btn bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-xs"></i> Edit
                                                </a>
                                                <a href="delete_donation.php?id=<?= $row['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to delete this donation?')"
                                                    class="action-btn bg-red-50 text-red-700 hover:bg-red-100"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-xs"></i> Delete
                                                </a>
                                                <?php if (!empty($row['remarks'])): ?>
                                                    <button onclick="showRemarks('<?= htmlspecialchars(addslashes($row['remarks'])) ?>')"
                                                        class="action-btn bg-gray-50 text-gray-700 hover:bg-gray-100"
                                                        title="View Remarks">
                                                        <i class="fas fa-eye text-xs"></i> Remarks
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
                        <p class="text-sm text-gray-600">
                            Showing <?= mysqli_num_rows($donations) ?> donations for <?= $label ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-hand-holding-usd text-gray-300 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Donations Found</h3>
                        <p class="text-gray-500 text-sm mb-4">No donations found for <?= $label ?>. Start by adding your first donation.</p>
                        <a href="add_donation.php"
                            class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus"></i> Add First Donation
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.donation-row');

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
                        <h3 class="text-lg font-semibold text-gray-800">Donation Remarks</h3>
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
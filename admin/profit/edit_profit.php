<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: profit.php");
    exit;
}

// Fetch profit record
$profit_query = "SELECT * FROM monthly_profit WHERE id=$id AND status='active'";
$profit_result = mysqli_query($conn, $profit_query);
$profit = mysqli_fetch_assoc($profit_result);

if (!$profit) {
    header("Location: profit.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $total_fees = floatval($_POST['total_fees'] ?? 0);
    $total_donations = floatval($_POST['total_donations'] ?? 0);
    $total_expenses = floatval($_POST['total_expenses'] ?? 0);
    $remarks = trim($_POST['remarks']);
    $net_profit = ($total_fees + $total_donations) - $total_expenses;

    $update_query = "
        UPDATE monthly_profit SET
        total_fees=$total_fees,
        total_donations=$total_donations,
        total_expenses=$total_expenses,
        net_profit=$net_profit,
        remarks='" . mysqli_real_escape_string($conn, $remarks) . "',
        updated_at=NOW()
        WHERE id=$id
    ";

    if (mysqli_query($conn, $update_query)) {
        $success_message = "Profit record updated successfully!";
    } else {
        $error_message = "Error updating profit: " . mysqli_error($conn);
    }

    // Refresh profit data
    $profit_result = mysqli_query($conn, $profit_query);
    $profit = mysqli_fetch_assoc($profit_result);
}

?>

<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profit Record | Academy Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: border 0.2s ease;
        }

        .form-input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Edit Profit Record</h1>
                    <p class="text-gray-600 mt-2">
                        Editing profit for <?php echo date('F Y', mktime(0, 0, 0, $profit['profit_month'], 1, $profit['profit_year'])); ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="add_profit.php?month=<?= $profit['profit_month'] ?>&year=<?= $profit['profit_year'] ?>"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add More to This Month
                    </a>
                    <a href="profit.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
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

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><?php echo $error_message; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="card">
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <!-- Total Fees -->
                            <div>
                                <label class="form-label">Total Fees (PKR)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="total_fees"
                                        value="<?php echo number_format($profit['total_fees'], 2, '.', ''); ?>"
                                        required class="form-input pr-10" oninput="calculateNetProfit()" id="total_fees">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500">PKR</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Donations -->
                            <div>
                                <label class="form-label">Total Donations (PKR)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="total_donations"
                                        value="<?php echo number_format($profit['total_donations'], 2, '.', ''); ?>"
                                        required class="form-input pr-10" oninput="calculateNetProfit()" id="total_donations">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500">PKR</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Expenses -->
                            <div>
                                <label class="form-label">Total Expenses (PKR)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="total_expenses"
                                        value="<?php echo number_format($profit['total_expenses'], 2, '.', ''); ?>"
                                        required class="form-input pr-10" oninput="calculateNetProfit()" id="total_expenses">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500">PKR</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Profit Display -->
                        <div class="mb-6">
                            <div class="p-4 bg-gray-50 rounded-lg border">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Calculated Net Profit:</span>
                                        <p class="text-xs text-gray-500">Formula: (Fees + Donations) - Expenses</p>
                                    </div>
                                    <div>
                                        <span id="netProfitDisplay" class="text-2xl font-bold 
                                            <?php echo ($profit['net_profit'] >= 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo number_format($profit['net_profit'], 2); ?>
                                        </span>
                                        <span class="text-gray-600">PKR</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="mb-6">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" rows="3" class="form-input"><?php echo htmlspecialchars($profit['remarks']); ?></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4">
                            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 flex items-center gap-2">
                                <i class="fas fa-save"></i> Update Profit Record
                            </button>
                            <a href="profit.php" class="px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 flex items-center gap-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="delete_profit.php?id=<?= $profit['id'] ?>"
                                onclick="return confirm('Are you sure you want to delete this profit record? This action cannot be undone.');"
                                class="px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 flex items-center gap-2">
                                <i class="fas fa-trash"></i> Delete Record
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="lg:col-span-1">
                <!-- Record Info -->
                <div class="card mb-6">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Record Information
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-gray-600">Created:</span>
                            <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($profit['created_at'])); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Last Updated:</span>
                            <p class="font-medium"><?php echo date('M d, Y H:i', strtotime($profit['updated_at'])); ?></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Record ID:</span>
                            <p class="font-medium">#<?php echo $profit['id']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- System Data -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-database mr-2"></i>System Data for This Month
                    </h3>
                    <div class="space-y-3 text-sm">
                        <?php
                        // Fetch current system data
                        $month = $profit['profit_month'];
                        $year = $profit['profit_year'];

                        // Fees
                        $current_fees_query = "SELECT COALESCE(SUM(amount_paid), 0) as fees 
                                              FROM fee_collections 
                                              WHERE MONTH(payment_date) = $month 
                                              AND YEAR(payment_date) = $year 
                                              AND status='active'";
                        $current_fees_result = mysqli_query($conn, $current_fees_query);
                        $current_fees = mysqli_fetch_assoc($current_fees_result);

                        // Donations
                        $current_donations_query = "SELECT COALESCE(SUM(amount), 0) as donations 
                                                   FROM donations 
                                                   WHERE MONTH(donation_date) = $month 
                                                   AND YEAR(donation_date) = $year 
                                                   AND status='active'";
                        $current_donations_result = mysqli_query($conn, $current_donations_query);
                        $current_donations = mysqli_fetch_assoc($current_donations_result);

                        // Expenses
                        $current_expenses_query = "SELECT COALESCE(SUM(amount), 0) as expenses 
                                                  FROM expenses 
                                                  WHERE MONTH(created_at) = $month 
                                                  AND YEAR(created_at) = $year 
                                                  AND status='active'";
                        $current_expenses_result = mysqli_query($conn, $current_expenses_query);
                        $current_expenses = mysqli_fetch_assoc($current_expenses_result);
                        ?>
                        <div class="flex justify-between">
                            <span>Current System Fees:</span>
                            <span class="font-medium"><?= number_format($current_fees['fees'], 2) ?> PKR</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Current System Donations:</span>
                            <span class="font-medium"><?= number_format($current_donations['donations'], 2) ?> PKR</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Current System Expenses:</span>
                            <span class="font-medium"><?= number_format($current_expenses['expenses'], 2) ?> PKR</span>
                        </div>
                        <div class="pt-3 border-t">
                            <button type="button" onclick="loadSystemData()"
                                class="w-full px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200">
                                <i class="fas fa-sync-alt mr-2"></i> Load System Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateNetProfit() {
            const fees = parseFloat(document.getElementById('total_fees').value) || 0;
            const donations = parseFloat(document.getElementById('total_donations').value) || 0;
            const expenses = parseFloat(document.getElementById('total_expenses').value) || 0;

            const netProfit = (fees + donations) - expenses;

            const display = document.getElementById('netProfitDisplay');
            display.textContent = netProfit.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            // Update color based on profit/loss
            if (netProfit >= 0) {
                display.className = 'text-2xl font-bold text-green-600';
            } else {
                display.className = 'text-2xl font-bold text-red-600';
            }
        }

        function loadSystemData() {
            const month = <?= $profit['profit_month'] ?>;
            const year = <?= $profit['profit_year'] ?>;

            fetch(`fetch_profit_data.php?month=${month}&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    if (confirm('Load system data? This will overwrite current values.')) {
                        document.getElementById('total_fees').value = data.total_fees || 0;
                        document.getElementById('total_donations').value = data.total_donations || 0;
                        document.getElementById('total_expenses').value = data.total_expenses || 0;
                        calculateNetProfit();
                        showNotification('System data loaded successfully!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading system data', 'error');
                });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    </script>
</body>

</html>
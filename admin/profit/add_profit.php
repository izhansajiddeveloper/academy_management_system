<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$success_message = '';
$error_message = '';

// Get current month/year if not specified
$default_month = $_GET['month'] ?? date('n');
$default_year = $_GET['year'] ?? date('Y');

// Fetch data from other tables for autofill
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $selected_month = $_GET['month'] ?? $default_month;
    $selected_year = $_GET['year'] ?? $default_year;

    // Get total fees for selected month
    $fees_query = "
        SELECT SUM(amount_paid) as total_fees 
        FROM fee_collections 
        WHERE MONTH(payment_date) = $selected_month 
        AND YEAR(payment_date) = $selected_year 
        AND status = 'active'
    ";
    $fees_result = mysqli_query($conn, $fees_query);
    $fees_row = mysqli_fetch_assoc($fees_result);
    $calculated_fees = $fees_row['total_fees'] ?? 0;

    // Get total donations for selected month
    $donations_query = "
        SELECT SUM(amount) as total_donations 
        FROM donations 
        WHERE MONTH(donation_date) = $selected_month 
        AND YEAR(donation_date) = $selected_year 
        AND status = 'active'
    ";
    $donations_result = mysqli_query($conn, $donations_query);
    $donations_row = mysqli_fetch_assoc($donations_result);
    $calculated_donations = $donations_row['total_donations'] ?? 0;

    // Get total expenses for selected month
    $expenses_query = "
        SELECT SUM(amount) as total_expenses 
        FROM expenses 
        WHERE MONTH(created_at) = $selected_month 
        AND YEAR(created_at) = $selected_year 
        AND status = 'active'
    ";
    $expenses_result = mysqli_query($conn, $expenses_query);
    $expenses_row = mysqli_fetch_assoc($expenses_result);
    $calculated_expenses = $expenses_row['total_expenses'] ?? 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profit_month = intval($_POST['profit_month']);
    $profit_year = intval($_POST['profit_year']);
    $total_fees = floatval($_POST['total_fees']);
    $total_donations = floatval($_POST['total_donations']);
    $total_expenses = floatval($_POST['total_expenses']);
    $remarks = trim($_POST['remarks']);
    $operation_type = $_POST['operation_type'] ?? 'add'; // 'add' or 'update'

    // Calculate net profit
    $net_profit = ($total_fees + $total_donations) - $total_expenses;

    if ($operation_type == 'update') {
        // Check if profit record exists for this month/year
        $check_sql = "SELECT id, total_fees, total_donations, total_expenses FROM monthly_profit 
                     WHERE profit_month = $profit_month 
                     AND profit_year = $profit_year 
                     AND status='active' 
                     LIMIT 1";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $row = mysqli_fetch_assoc($check_result);
            $profit_id = $row['id'];

            $update_sql = "
                UPDATE monthly_profit
                SET total_fees = total_fees + $total_fees,
                    total_donations = total_donations + $total_donations,
                    total_expenses = total_expenses + $total_expenses,
                    net_profit = (total_fees + $total_fees + total_donations + $total_donations - total_expenses - $total_expenses),
                    remarks = CONCAT_WS(' | ', remarks, '" . mysqli_real_escape_string($conn, $remarks) . "'),
                    updated_at = NOW()
                WHERE id = $profit_id
            ";

            if (mysqli_query($conn, $update_sql)) {
                $success_message = "Profit record updated successfully for " . date('F Y', mktime(0, 0, 0, $profit_month, 1, $profit_year));
                // Reset form values
                $calculated_fees = $calculated_donations = $calculated_expenses = 0;
            } else {
                $error_message = "Error updating profit: " . mysqli_error($conn);
            }
        } else {
            // If no record exists, create new one
            $operation_type = 'add';
        }
    }

    if ($operation_type == 'add') {
        // Insert new record
        $insert_sql = "
            INSERT INTO monthly_profit 
            (profit_month, profit_year, total_fees, total_donations, total_expenses, net_profit, remarks, status, created_at, updated_at)
            VALUES 
            ($profit_month, $profit_year, $total_fees, $total_donations, $total_expenses, $net_profit, 
            '" . mysqli_real_escape_string($conn, $remarks) . "', 'active', NOW(), NOW())
        ";

        if (mysqli_query($conn, $insert_sql)) {
            $success_message = "New profit record added successfully for " . date('F Y', mktime(0, 0, 0, $profit_month, 1, $profit_year));
            // Reset form values
            $calculated_fees = $calculated_donations = $calculated_expenses = 0;
        } else {
            $error_message = "Error adding profit: " . mysqli_error($conn);
        }
    }
}

// Check if record already exists for selected month
$existing_record = null;
if (isset($selected_month) && isset($selected_year)) {
    $existing_query = "SELECT * FROM monthly_profit 
                      WHERE profit_month = $selected_month 
                      AND profit_year = $selected_year 
                      AND status='active' 
                      LIMIT 1";
    $existing_result = mysqli_query($conn, $existing_query);
    if (mysqli_num_rows($existing_result) > 0) {
        $existing_record = mysqli_fetch_assoc($existing_result);
    }
}
?>

<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Profit Record | Academy Management</title>
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

        .btn-primary {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
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

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Add / Update Profit Record</h1>
                    <p class="text-gray-600 mt-2">Add new profit entries or update existing records for any month</p>
                </div>
                <a href="profit.php" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Back to Profit List
                </a>
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
                    <form method="POST" id="profitForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Month Selection -->
                            <div>
                                <label class="form-label">Month</label>
                                <select name="profit_month" id="profit_month" required
                                    class="form-input" onchange="updateMonthYear()">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>"
                                            <?php echo ($m == ($_POST['profit_month'] ?? $default_month)) ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Year Selection -->
                            <div>
                                <label class="form-label">Year</label>
                                <input type="number" name="profit_year" id="profit_year"
                                    value="<?php echo $_POST['profit_year'] ?? $default_year; ?>"
                                    min="2020" max="2100" required
                                    class="form-input" onchange="updateMonthYear()">
                            </div>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="mb-6">
                            <div class="flex gap-2">
                                <button type="button" onclick="fetchDataFromTables()"
                                    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200">
                                    <i class="fas fa-sync-alt mr-2"></i> Autofill from System Data
                                </button>
                                <a href="generate_profit.php?month=<?= $selected_month ?? $default_month ?>&year=<?= $selected_year ?? $default_year ?>"
                                    class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium hover:bg-purple-200">
                                    <i class="fas fa-calculator mr-2"></i> Generate Automatically
                                </a>
                            </div>
                        </div>

                        <!-- Financial Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <!-- Total Fees -->
                            <div>
                                <label class="form-label">Total Fees (PKR)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="total_fees" id="total_fees"
                                        value="<?php echo $_POST['total_fees'] ?? $calculated_fees ?? 0; ?>"
                                        required class="form-input pr-10">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500">PKR</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">From fee_collections table</p>
                            </div>

                            <!-- Total Donations -->
                            <div>
                                <label class="form-label">Total Donations (PKR)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="total_donations" id="total_donations"
                                        value="<?php echo $_POST['total_donations'] ?? $calculated_donations ?? 0; ?>"
                                        required class="form-input pr-10">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500">PKR</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">From donations table</p>
                            </div>

                            <!-- Total Expenses -->
                            <div>
                                <label class="form-label">Total Expenses (PKR)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="total_expenses" id="total_expenses"
                                        value="<?php echo $_POST['total_expenses'] ?? $calculated_expenses ?? 0; ?>"
                                        required class="form-input pr-10">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500">PKR</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">From expenses table</p>
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
                                        <span id="netProfitDisplay" class="text-2xl font-bold text-green-600">
                                            <?php
                                            $net = (($_POST['total_fees'] ?? $calculated_fees ?? 0) +
                                                ($_POST['total_donations'] ?? $calculated_donations ?? 0)) -
                                                ($_POST['total_expenses'] ?? $calculated_expenses ?? 0);
                                            echo number_format($net, 2);
                                            ?>
                                        </span>
                                        <span class="text-gray-600">PKR</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="mb-6">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" rows="3"
                                class="form-input"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                        </div>

                        <!-- Operation Type (Hidden) -->
                        <input type="hidden" name="operation_type" id="operation_type" value="add">

                        <!-- Submit Buttons -->
                        <div class="flex gap-4">
                            <?php if ($existing_record): ?>
                                <button type="submit" name="action" value="update"
                                    onclick="document.getElementById('operation_type').value='update'"
                                    class="btn-primary flex items-center gap-2">
                                    <i class="fas fa-plus-circle"></i> Add to Existing Record
                                </button>
                                <a href="edit_profit.php?id=<?= $existing_record['id'] ?>"
                                    class="px-6 py-2 bg-yellow-100 text-yellow-700 rounded-lg font-medium hover:bg-yellow-200 flex items-center gap-2">
                                    <i class="fas fa-edit"></i> Edit Full Record
                                </a>
                            <?php else: ?>
                                <button type="submit" name="action" value="add"
                                    onclick="document.getElementById('operation_type').value='add'"
                                    class="btn-primary flex items-center gap-2">
                                    <i class="fas fa-save"></i> Save New Profit Record
                                </button>
                            <?php endif; ?>

                            <a href="profit.php" class="btn-secondary flex items-center gap-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="lg:col-span-1">
                <!-- Existing Record Info -->
                <?php if ($existing_record): ?>
                    <div class="info-box">
                        <h3 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>Existing Record Found
                        </h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>Current Total Fees:</strong> <?= number_format($existing_record['total_fees'], 2) ?> PKR</p>
                            <p><strong>Current Total Donations:</strong> <?= number_format($existing_record['total_donations'], 2) ?> PKR</p>
                            <p><strong>Current Total Expenses:</strong> <?= number_format($existing_record['total_expenses'], 2) ?> PKR</p>
                            <p><strong>Current Net Profit:</strong> <span class="font-bold <?= $existing_record['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= number_format($existing_record['net_profit'], 2) ?> PKR
                                </span></p>
                            <p class="text-xs text-gray-600 mt-3">
                                <i class="fas fa-lightbulb"></i> Using "Add to Existing Record" will sum the new values with existing ones.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-box">
                        <h3 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-lightbulb mr-2"></i>No Existing Record
                        </h3>
                        <p class="text-sm text-blue-700">
                            No profit record found for the selected month. This will create a new record.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Quick Help -->
                <div class="card">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-question-circle mr-2"></i>How to Add Profit
                    </h3>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Select month and year for the profit record</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Use "Autofill" to get data from system tables automatically</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Manually adjust values if needed</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Add remarks for reference</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Choose between creating new record or updating existing</span>
                        </li>
                    </ul>
                </div>

                <!-- Quick Month Links -->
                <div class="card mt-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Quick Month Selection</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <?php
                        $months = [];
                        for ($i = 0; $i < 6; $i++) {
                            $timestamp = strtotime("-$i months");
                            $months[] = [
                                'month' => date('n', $timestamp),
                                'year' => date('Y', $timestamp),
                                'label' => date('M Y', $timestamp)
                            ];
                        }
                        foreach ($months as $month): ?>
                            <a href="?month=<?= $month['month'] ?>&year=<?= $month['year'] ?>"
                                class="text-center px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 <?= (($selected_month ?? $default_month) == $month['month'] && ($selected_year ?? $default_year) == $month['year']) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'text-gray-700' ?>">
                                <?= $month['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateMonthYear() {
            const month = document.getElementById('profit_month').value;
            const year = document.getElementById('profit_year').value;

            // Update URL without reloading the form
            const url = new URL(window.location);
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            window.history.replaceState({}, '', url);

            // Fetch new data for autofill
            fetchDataFromTables();
        }

        function fetchDataFromTables() {
            const month = document.getElementById('profit_month').value;
            const year = document.getElementById('profit_year').value;

            // Show loading state
            const feesInput = document.getElementById('total_fees');
            const donationsInput = document.getElementById('total_donations');
            const expensesInput = document.getElementById('total_expenses');

            feesInput.value = 'Loading...';
            donationsInput.value = 'Loading...';
            expensesInput.value = 'Loading...';

            // Fetch data via AJAX
            fetch(`fetch_profit_data.php?month=${month}&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    feesInput.value = data.total_fees || 0;
                    donationsInput.value = data.total_donations || 0;
                    expensesInput.value = data.total_expenses || 0;

                    // Update net profit display
                    calculateNetProfit();

                    // Show success message
                    showNotification('Data loaded successfully from system tables!', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading data. Please check manually.', 'error');
                    feesInput.value = 0;
                    donationsInput.value = 0;
                    expensesInput.value = 0;
                });
        }

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

        function showNotification(message, type) {
            // Remove existing notification
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            // Create notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 notification ${
                type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 
                'bg-red-100 text-red-800 border border-red-200'
            }`;

            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Add event listeners for real-time calculation
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['total_fees', 'total_donations', 'total_expenses'];
            inputs.forEach(id => {
                document.getElementById(id).addEventListener('input', calculateNetProfit);
            });

            // Initial calculation
            calculateNetProfit();
        });
    </script>
</body>

</html>
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Default: current month and year
$profit_month = $_POST['profit_month'] ?? date('n');
$profit_year  = $_POST['profit_year'] ?? date('Y');

// Calculate total fees collected in this month
$fees_query = "
    SELECT SUM(amount_paid) AS total_fees
    FROM fee_collections
    WHERE status='active' 
      AND MONTH(payment_date) = $profit_month 
      AND YEAR(payment_date) = $profit_year
";
$fees_result = mysqli_query($conn, $fees_query);
$fees_row = mysqli_fetch_assoc($fees_result);
$total_fees = $fees_row['total_fees'] ?? 0;

// Calculate total donations received in this month
$donations_query = "
    SELECT SUM(amount) AS total_donations
    FROM donations
    WHERE status='active'
      AND MONTH(donation_date) = $profit_month
      AND YEAR(donation_date) = $profit_year
";
$donations_result = mysqli_query($conn, $donations_query);
$donations_row = mysqli_fetch_assoc($donations_result);
$total_donations = $donations_row['total_donations'] ?? 0;

// Calculate total expenses in this month
$expenses_query = "
    SELECT SUM(amount) AS total_expenses
    FROM expenses
    WHERE status='active'
      AND MONTH(created_at) = $profit_month
      AND YEAR(created_at) = $profit_year
";
$expenses_result = mysqli_query($conn, $expenses_query);
$expenses_row = mysqli_fetch_assoc($expenses_result);
$total_expenses = $expenses_row['total_expenses'] ?? 0;

// Net profit
$net_profit = ($total_fees + $total_donations) - $total_expenses;

// Save to monthly_profit table (check if already exists)
$check_query = "
    SELECT id FROM monthly_profit
    WHERE profit_month=$profit_month AND profit_year=$profit_year
";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    // Update existing record
    $row = mysqli_fetch_assoc($check_result);
    $update_query = "
        UPDATE monthly_profit SET
        total_fees=$total_fees,
        total_donations=$total_donations,
        total_expenses=$total_expenses,
        net_profit=$net_profit,
        updated_at=NOW(),
        status='active'
        WHERE id={$row['id']}
    ";
    mysqli_query($conn, $update_query);
} else {
    // Insert new record
    $insert_query = "
        INSERT INTO monthly_profit
        (profit_month, profit_year, total_fees, total_donations, total_expenses, net_profit, status, created_at, updated_at)
        VALUES
        ($profit_month, $profit_year, $total_fees, $total_donations, $total_expenses, $net_profit, 'active', NOW(), NOW())
    ";
    mysqli_query($conn, $insert_query);
}

header("Location: profit.php?month=$profit_month&year=$profit_year");
exit;

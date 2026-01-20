<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

$data = [
    'total_fees' => 0,
    'total_donations' => 0,
    'total_expenses' => 0
];

// Get total fees
$fees_query = "
    SELECT COALESCE(SUM(amount_paid), 0) as total_fees 
    FROM fee_collections 
    WHERE MONTH(payment_date) = $month 
    AND YEAR(payment_date) = $year 
    AND status = 'active'
";
$fees_result = mysqli_query($conn, $fees_query);
if ($fees_row = mysqli_fetch_assoc($fees_result)) {
    $data['total_fees'] = floatval($fees_row['total_fees']);
}

// Get total donations
$donations_query = "
    SELECT COALESCE(SUM(amount), 0) as total_donations 
    FROM donations 
    WHERE MONTH(donation_date) = $month 
    AND YEAR(donation_date) = $year 
    AND status = 'active'
";
$donations_result = mysqli_query($conn, $donations_query);
if ($donations_row = mysqli_fetch_assoc($donations_result)) {
    $data['total_donations'] = floatval($donations_row['total_donations']);
}

// Get total expenses
$expenses_query = "
    SELECT COALESCE(SUM(amount), 0) as total_expenses 
    FROM expenses 
    WHERE MONTH(created_at) = $month 
    AND YEAR(created_at) = $year 
    AND status = 'active'
";
$expenses_result = mysqli_query($conn, $expenses_query);
if ($expenses_row = mysqli_fetch_assoc($expenses_result)) {
    $data['total_expenses'] = floatval($expenses_row['total_expenses']);
}

echo json_encode($data);
mysqli_close($conn);

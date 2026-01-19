<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$student_id = intval($_GET['student_id'] ?? 0);
$skill_id = intval($_GET['skill_id'] ?? 0);
$session_id = intval($_GET['session_id'] ?? 0);

$response = [
    'course_fee' => 0,
    'total_paid' => 0,
    'pending_amount' => 0,
    'payment_percentage' => 0
];

if ($student_id > 0 && $skill_id > 0 && $session_id > 0) {
    // Get course fee
    $fee_query = "SELECT total_fee FROM fee_structures 
                  WHERE skill_id = $skill_id AND session_id = $session_id AND status='active' 
                  LIMIT 1";
    $fee_result = mysqli_query($conn, $fee_query);
    if ($fee_result && mysqli_num_rows($fee_result) > 0) {
        $fee_row = mysqli_fetch_assoc($fee_result);
        $response['course_fee'] = floatval($fee_row['total_fee']);
    }

    // Get total paid
    $paid_query = "SELECT SUM(amount_paid) as total FROM fee_collections 
                   WHERE student_id = $student_id AND skill_id = $skill_id AND status='active'";
    $paid_result = mysqli_query($conn, $paid_query);
    if ($paid_result) {
        $paid_row = mysqli_fetch_assoc($paid_result);
        $response['total_paid'] = floatval($paid_row['total'] ?? 0);
    }

    // Calculate pending amount and percentage
    $response['pending_amount'] = max(0, $response['course_fee'] - $response['total_paid']);
    $response['payment_percentage'] = $response['course_fee'] > 0 ?
        ($response['total_paid'] / $response['course_fee']) * 100 : 0;
}

echo json_encode($response);

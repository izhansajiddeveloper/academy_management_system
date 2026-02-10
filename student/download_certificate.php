<?php
// download_certificate.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Check if certificate ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: certificates.php?error=invalid_certificate");
    exit;
}

$certificate_id = intval($_GET['id']);

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

// Fetch certificate details
$certificate_query = "
SELECT 
    c.*,
    s.skill_name,
    s.description as skill_description,
    s.level as skill_level,
    b.batch_name,
    sp.progress_percent as avg_score,
    sp.overall_performance,
    sp.last_updated as issued_date,
    t.name as trainer_name
FROM certificates c
JOIN skills s ON c.skill_id = s.id
JOIN batches b ON c.batch_id = b.id
LEFT JOIN skill_progress sp ON sp.student_id = c.student_id 
    AND sp.skill_id = c.skill_id 
    AND sp.batch_id = c.batch_id
LEFT JOIN teacher_assignments ta ON ta.batch_id = b.id
LEFT JOIN teachers t ON ta.teacher_id = t.id
WHERE c.id = ? AND c.student_id = ?
";

$stmt_certificate = $conn->prepare($certificate_query);
$stmt_certificate->bind_param("ii", $certificate_id, $student_id);
$stmt_certificate->execute();
$certificate_result = $stmt_certificate->get_result();
$certificate = $certificate_result->fetch_assoc();

if (!$certificate) {
    header("Location: certificates.php?error=certificate_not_found");
    exit;
}

// ============================================================
// TCPDF PATH FIX
// Based on your file structure, try these paths:
// ============================================================

// Option 1: If TCPDF is in student/includes/TCPDF-main/
$tcpdf_path = __DIR__ . '/includes/TCPDF-main/tcpdf.php';

// Option 2: If TCPDF is in includes/TCPDF-main/ (one level up)
if (!file_exists($tcpdf_path)) {
    $tcpdf_path = __DIR__ . '/../includes/TCPDF-main/tcpdf.php';
}

// Option 3: If TCPDF is in the root includes/TCPDF-main/
if (!file_exists($tcpdf_path)) {
    $tcpdf_path = __DIR__ . '/../../includes/TCPDF-main/tcpdf.php';
}

// Check if TCPDF exists
if (!file_exists($tcpdf_path)) {
    die("TCPDF library not found. Please check the installation path. Tried: " . $tcpdf_path);
}

// Include TCPDF library
require_once $tcpdf_path;

// Format dates
$issue_date = date('F d, Y', strtotime($certificate['issued_date']));
$expiry_date = $certificate['expiry_date'] ? date('F d, Y', strtotime($certificate['expiry_date'])) : 'No Expiry';

// Create new PDF document - Landscape orientation (11" wide x 8.5" tall)
$pdf = new TCPDF('L', 'in', array(11, 8.5), true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('EduMaster Academy');
$pdf->SetAuthor('EduMaster Academy');
$pdf->SetTitle('Certificate of Completion - ' . $certificate['skill_name']);
$pdf->SetSubject('Certificate of Achievement');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins - all to 0.5 inches
$pdf->SetMargins(0.5, 0.5, 0.5);
$pdf->SetAutoPageBreak(false, 0);

// Add a page
$pdf->AddPage();

// Set default font
$pdf->SetFont('helvetica', '', 12);

// ================== DESIGN THE CERTIFICATE ==================

// 1. BACKGROUND COLOR - White
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 0, 11, 8.5, 'F');

// 2. MAIN BORDER - Blue gradient effect
$pdf->SetLineWidth(0.02);
$pdf->SetDrawColor(30, 64, 175); // Navy Blue
$pdf->Rect(0.25, 0.25, 10.5, 8, 'D'); // Outer border

// Inner decorative border
$pdf->SetLineWidth(0.01);
$pdf->SetDrawColor(59, 130, 246); // Royal Blue
$pdf->Rect(0.35, 0.35, 10.3, 7.8, 'D');

// 3. DECORATIVE CORNERS
$corner_size = 0.4;
$pdf->SetLineWidth(0.015);
$pdf->SetDrawColor(30, 64, 175); // Navy Blue

// Top-left corner
$pdf->Line(0.25, 0.25, 0.25 + $corner_size, 0.25);
$pdf->Line(0.25, 0.25, 0.25, 0.25 + $corner_size);

// Top-right corner
$pdf->Line(10.75 - $corner_size, 0.25, 10.75, 0.25);
$pdf->Line(10.75, 0.25, 10.75, 0.25 + $corner_size);

// Bottom-left corner
$pdf->Line(0.25, 8.25 - $corner_size, 0.25, 8.25);
$pdf->Line(0.25, 8.25, 0.25 + $corner_size, 8.25);

// Bottom-right corner
$pdf->Line(10.75 - $corner_size, 8.25, 10.75, 8.25);
$pdf->Line(10.75, 8.25 - $corner_size, 10.75, 8.25);

// 4. WATERMARK BACKGROUND TEXT
$pdf->SetFont('helvetica', 'B', 72);
$pdf->SetTextColor(240, 240, 245);
$pdf->StartTransform();
$pdf->Rotate(45, 5.5, 4.25);
$pdf->SetXY(1, 3);
$pdf->Cell(9, 0, 'CERTIFICATE OF ACHIEVEMENT', 0, 0, 'C');
$pdf->StopTransform();

// Reset font and color
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(0, 0, 0);

// 5. INSTITUTION HEADER - Deep Indigo Blue
$pdf->SetFont('helvetica', 'B', 28);
$pdf->SetTextColor(37, 99, 235); // Bright Blue
$pdf->SetXY(0, 0.8);
$pdf->Cell(11, 0.3, 'EDUMASTER ACADEMY', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(79, 70, 229); // Indigo
$pdf->SetXY(0, 1.2);
$pdf->Cell(11, 0.2, 'Center for Professional Excellence', 0, 1, 'C');

// Divider line - Blue Gray
$pdf->SetLineWidth(0.005);
$pdf->SetDrawColor(100, 116, 139);
$pdf->Line(1.5, 1.5, 9.5, 1.5);

// 6. CERTIFICATE TITLE - Navy Blue
$pdf->SetFont('helvetica', 'B', 26);
$pdf->SetTextColor(30, 64, 175); // Navy Blue
$pdf->SetXY(0, 1.8);
$pdf->Cell(11, 0.3, 'Certificate of Completion', 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 14);
$pdf->SetTextColor(59, 130, 246); // Royal Blue
$pdf->SetXY(0, 2.2);
$pdf->Cell(11, 0.2, 'This Certificate is Awarded To', 0, 1, 'C');

// 7. STUDENT NAME SECTION - Dark Blue Gray
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(30, 41, 59); // Dark Blue Gray
$pdf->SetXY(0, 2.7);
$pdf->Cell(11, 0.2, 'This is to certify that', 0, 1, 'C');

// Student Name (Large and prominent) - Deep Ocean Blue
$pdf->SetFont('helvetica', 'B', 36);
$pdf->SetTextColor(2, 132, 199); // Ocean Blue
$pdf->SetXY(0, 3.0);
$pdf->Cell(11, 0.4, htmlspecialchars($student['name']), 0, 1, 'C');

// 8. COMPLETION TEXT - Steel Blue
$pdf->SetFont('helvetica', '', 14);
$pdf->SetTextColor(51, 65, 85); // Steel Blue
$pdf->SetXY(0, 3.6);
$pdf->Cell(11, 0.2, 'has successfully completed the training course in', 0, 1, 'C');

// 9. SKILL/COURSE NAME - Vibrant Blue
$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetTextColor(37, 99, 235); // Vibrant Blue
$pdf->SetXY(0, 4.0);
$pdf->Cell(11, 0.3, strtoupper(htmlspecialchars($certificate['skill_name'])), 0, 1, 'C');

// 10. DESCRIPTION - Slate Blue
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(71, 85, 105); // Slate Blue
$pdf->SetXY(1, 4.5);
$pdf->MultiCell(
    9,
    0.18,
    'Having demonstrated proficiency, commitment, and excellence throughout the program, ' .
        'achieving all required learning outcomes with outstanding performance.',
    0,
    'C',
    false,
    1,
    '',
    '',
    true,
    0,
    false,
    true,
    0,
    'T'
);

// 11. PERFORMANCE METRICS - Different blues for each box
$metrics_y = 5.2;
$box_width = 2.5;
$box_height = 0.8;
$spacing = 0.5;
$total_width = (3 * $box_width) + (2 * $spacing);
$start_x = (11 - $total_width) / 2;

// Completion Score - Teal Blue
$pdf->SetFillColor(240, 249, 255); // Light Blue background
$pdf->Rect($start_x, $metrics_y, $box_width, $box_height, 'F');
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(8, 145, 178); // Teal Blue
$pdf->SetXY($start_x, $metrics_y + 0.2);
$pdf->Cell($box_width, 0.3, round($certificate['avg_score'], 1) . '%', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(15, 118, 110); // Dark Teal
$pdf->SetXY($start_x, $metrics_y + 0.5);
$pdf->Cell($box_width, 0.2, 'COMPLETION SCORE', 0, 1, 'C');

// Performance Score - Royal Blue
$pdf->SetFillColor(239, 246, 255); // Light Royal Blue background
$pdf->Rect($start_x + $box_width + $spacing, $metrics_y, $box_width, $box_height, 'F');
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(59, 130, 246); // Royal Blue
$pdf->SetXY($start_x + $box_width + $spacing, $metrics_y + 0.2);
$pdf->Cell($box_width, 0.3, round($certificate['overall_performance'], 1) . '%', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(30, 64, 175); // Navy Blue
$pdf->SetXY($start_x + $box_width + $spacing, $metrics_y + 0.5);
$pdf->Cell($box_width, 0.2, 'PERFORMANCE SCORE', 0, 1, 'C');

// Proficiency Level - Indigo Blue
$pdf->SetFillColor(238, 242, 255); // Light Indigo background
$pdf->Rect($start_x + 2 * ($box_width + $spacing), $metrics_y, $box_width, $box_height, 'F');
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(99, 102, 241); // Indigo Blue
$pdf->SetXY($start_x + 2 * ($box_width + $spacing), $metrics_y + 0.2);
$pdf->Cell($box_width, 0.3, strtoupper($certificate['skill_level']), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(67, 56, 202); // Dark Indigo
$pdf->SetXY($start_x + 2 * ($box_width + $spacing), $metrics_y + 0.5);
$pdf->Cell($box_width, 0.2, 'PROFICIENCY LEVEL', 0, 1, 'C');

// 12. SIGNATURES
$signature_y = 6.2;
$signature_width = 3.5;

// Left signature (Instructor) - Deep Blue
$pdf->SetDrawColor(59, 130, 246); // Royal Blue line
$pdf->SetLineWidth(0.005);
$pdf->Line(1.5, $signature_y, 1.5 + $signature_width, $signature_y);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(30, 64, 175); // Navy Blue
$pdf->SetXY(1.5, $signature_y + 0.15);
$pdf->Cell($signature_width, 0.2, htmlspecialchars($certificate['trainer_name'] ?? 'Course Instructor'), 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 9);
$pdf->SetTextColor(79, 70, 229); // Indigo
$pdf->SetXY(1.5, $signature_y + 0.35);
$pdf->Cell($signature_width, 0.2, 'Certified Trainer', 0, 1, 'C');

// Right signature (Director) - Steel Blue
$pdf->SetDrawColor(100, 116, 139); // Blue Gray line
$pdf->SetLineWidth(0.005);
$pdf->Line(11 - 1.5 - $signature_width, $signature_y, 11 - 1.5, $signature_y);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 65, 85); // Steel Blue
$pdf->SetXY(11 - 1.5 - $signature_width, $signature_y + 0.15);
$pdf->Cell($signature_width, 0.2, 'Academic Director', 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 9);
$pdf->SetTextColor(71, 85, 105); // Slate Blue
$pdf->SetXY(11 - 1.5 - $signature_width, $signature_y + 0.35);
$pdf->Cell($signature_width, 0.2, 'EduMaster Academy', 0, 1, 'C');

// 13. FOOTER WITH VERIFICATION INFO - Different blues
$footer_y = 7.5;

// Certificate ID - Dark Blue Gray
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(30, 41, 59); // Dark Blue Gray
$pdf->SetXY(0.5, $footer_y);
$pdf->Cell(3, 0.2, 'Certificate ID: ' . $certificate['certificate_number'], 0, 1, 'L');

// Verification URL - Ocean Blue
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(2, 132, 199); // Ocean Blue
$pdf->SetXY(0, $footer_y);
$pdf->Cell(11, 0.2, 'Verify online: verify.edumaster.org/' . $certificate['verification_code'], 0, 1, 'C');

// Dates - Different shades
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(59, 130, 246); // Royal Blue - Issued date
$pdf->SetXY(11 - 3.5, $footer_y);
$pdf->Cell(3, 0.2, 'Issued: ' . $issue_date, 0, 1, 'R');

$pdf->SetTextColor(37, 99, 235); // Bright Blue - Valid date
$pdf->SetXY(11 - 3.5, $footer_y + 0.15);
$pdf->Cell(3, 0.2, 'Valid: ' . $expiry_date, 0, 1, 'R');

// 14. FINAL BORDER LINES - Light Blue Gray
$pdf->SetLineWidth(0.002);
$pdf->SetDrawColor(226, 232, 240); // Light Blue Gray
$pdf->Line(0.5, $footer_y + 0.4, 10.5, $footer_y + 0.4);

// ================== OUTPUT PDF ==================

// Generate filename
$filename = 'Certificate_' .
    preg_replace('/[^A-Za-z0-9_\-]/', '_', $certificate['skill_name']) . '_' .
    $certificate['certificate_number'] . '.pdf';

// Output PDF as download
$pdf->Output($filename, 'D');

exit;

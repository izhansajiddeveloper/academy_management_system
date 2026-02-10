<?php
// download_certificate.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Allow only student
if ($_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

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

// Get certificate ID
$certificate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$certificate_id) {
    header("Location: certificates.php");
    exit;
}

// Fetch certificate details
$certificate_query = "
SELECT 
    c.*,
    s.skill_name,
    s.description as skill_description,
    b.batch_name,
    st.name as student_name,
    st.email as student_email
FROM certificates c
JOIN skills s ON c.skill_id = s.id
JOIN batches b ON c.batch_id = b.id
JOIN students st ON c.student_id = st.id
WHERE c.id = ? AND c.student_id = ?
";

$stmt_certificate = $conn->prepare($certificate_query);
$stmt_certificate->bind_param("ii", $certificate_id, $student_id);
$stmt_certificate->execute();
$certificate_result = $stmt_certificate->get_result();

if ($certificate_result->num_rows === 0) {
    header("Location: certificates.php?error=certificate_not_found");
    exit;
}

$certificate = $certificate_result->fetch_assoc();

// Include TCPDF library
require_once(__DIR__ . '/../vendor/tcpdf/tcpdf.php');       

// Create new PDF document
class CertificatePDF extends TCPDF
{
    // Page header
    public function Header()
    {
        // Logo
        $image_file = __DIR__ . '/../assets/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 40);
        }

        // Set font
        $this->SetFont('helvetica', 'B', 20);

        // Title
        $this->SetY(15);
        $this->Cell(0, 10, 'ACADEMY OF EXCELLENCE', 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Line break
        $this->Ln(20);
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);

        // Set font
        $this->SetFont('helvetica', 'I', 8);

        // Page number
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');

        // Certificate ID
        $this->SetY(-25);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Certificate ID: ' . $GLOBALS['certificate_number'], 0, false, 'C', 0, '', 0, false, 'T', 'M');

        // Verification URL
        $this->SetY(-30);
        $this->Cell(0, 10, 'Verify at: ' . $GLOBALS['verification_url'], 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create PDF instance
$pdf = new CertificatePDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Academy Management System');
$pdf->SetAuthor('Academy of Excellence');
$pdf->SetTitle('Certificate of Completion - ' . $certificate['skill_name']);
$pdf->SetSubject('Certificate of Completion');
$pdf->SetKeywords('Certificate, Completion, Achievement, ' . $certificate['skill_name']);

// Set default header data
$pdf->SetHeaderData('', 0, '', '');

// Set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(20);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set global variables for footer
$GLOBALS['certificate_number'] = $certificate['certificate_number'];
$GLOBALS['verification_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/verify_certificate.php?code=' . $certificate['verification_code'];

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add decorative border
$pdf->SetLineWidth(1.5);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Rect(10, 10, 190, 277, 'D');

// Add inner decorative border
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(100, 100, 100);
$pdf->Rect(15, 15, 180, 267, 'D');

// Certificate content
$html = '
<style>
    .cert-title {
        font-size: 28px;
        font-weight: bold;
        color: #2c3e50;
        text-align: center;
        margin-bottom: 10px;
    }
    .cert-subtitle {
        font-size: 16px;
        color: #7f8c8d;
        text-align: center;
        margin-bottom: 40px;
        letter-spacing: 10px;
    }
    .cert-awarded {
        font-size: 20px;
        text-align: center;
        margin-bottom: 40px;
    }
    .cert-name {
        font-size: 36px;
        font-weight: bold;
        color: #2980b9;
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        border-top: 2px solid #ddd;
        border-bottom: 2px solid #ddd;
    }
    .cert-description {
        font-size: 16px;
        text-align: center;
        margin-bottom: 30px;
        line-height: 1.6;
    }
    .cert-details {
        font-size: 14px;
        text-align: center;
        margin-bottom: 40px;
        color: #555;
    }
    .cert-achievement {
        font-size: 16px;
        text-align: center;
        margin-bottom: 40px;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
    }
    .signature-area {
        margin-top: 60px;
        text-align: center;
    }
    .signature-line {
        border-top: 1px solid #000;
        width: 200px;
        margin: 40px auto 10px;
    }
</style>

<div class="cert-title">CERTIFICATE OF COMPLETION</div>
<div class="cert-subtitle">AWARDED TO</div>

<div class="cert-awarded">This certificate is proudly presented to</div>

<div class="cert-name">' . htmlspecialchars($student['name']) . '</div>

<div class="cert-description">
    for successfully completing the course in<br>
    <strong style="font-size: 20px;">' . htmlspecialchars($certificate['skill_name']) . '</strong><br>
    <em>' . htmlspecialchars($certificate['batch_name']) . ' Batch</em>
</div>

<div class="cert-achievement">
    <strong>Achievement Summary:</strong><br>
    • Average Score: ' . round($certificate['avg_score'], 1) . '%<br>
    • Completion Date: ' . date('F d, Y', strtotime($certificate['issued_date'])) . '<br>
    • Certificate ID: ' . $certificate['certificate_number'] . '
</div>

<div class="cert-details">
    This certificate verifies that the recipient has demonstrated proficiency in the subject matter<br>
    and has successfully met all requirements for course completion.
</div>

<div style="text-align: center; margin-bottom: 20px;">
    <img src="../assets/seal.png" width="100" style="opacity: 0.7;">
</div>

<div class="signature-area">
    <div class="signature-line"></div>
    <div style="font-size: 14px; margin-top: 5px;">Director of Academics</div>
    <div style="font-size: 12px; color: #666;">Academy of Excellence</div>
</div>

<div style="text-align: center; margin-top: 40px; font-size: 10px; color: #999;">
    This certificate can be verified online at:<br>
    https://' . $_SERVER['HTTP_HOST'] . '/verify_certificate.php?code=' . $certificate['verification_code'] . '
</div>
';

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// Add a page for transcript
$pdf->AddPage();

// Transcript content
$transcript_html = '
<style>
    .transcript-title {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
        color: #2c3e50;
    }
    .student-info {
        margin-bottom: 30px;
    }
    .table-header {
        background-color: #f1f8ff;
        font-weight: bold;
    }
    .table-row {
        border-bottom: 1px solid #ddd;
    }
</style>

<div class="transcript-title">ACADEMIC TRANSCRIPT</div>

<div class="student-info">
    <table border="0" cellpadding="5">
        <tr>
            <td width="100"><strong>Student Name:</strong></td>
            <td>' . htmlspecialchars($student['name']) . '</td>
        </tr>
        <tr>
            <td><strong>Student ID:</strong></td>
            <td>' . $student['id'] . '</td>
        </tr>
        <tr>
            <td><strong>Certificate:</strong></td>
            <td>' . htmlspecialchars($certificate['skill_name']) . '</td>
        </tr>
        <tr>
            <td><strong>Certificate ID:</strong></td>
            <td>' . $certificate['certificate_number'] . '</td>
        </tr>
        <tr>
            <td><strong>Issued Date:</strong></td>
            <td>' . date('F d, Y', strtotime($certificate['issued_date'])) . '</td>
        </tr>
    </table>
</div>

<div>
    <h3 style="font-size: 18px; margin-bottom: 10px;">Course Details</h3>
    <table border="1" cellpadding="8" style="width: 100%; border-collapse: collapse;">
        <tr class="table-header">
            <th width="60%">Description</th>
            <th width="40%">Details</th>
        </tr>
        <tr class="table-row">
            <td>Course Name</td>
            <td>' . htmlspecialchars($certificate['skill_name']) . '</td>
        </tr>
        <tr class="table-row">
            <td>Batch</td>
            <td>' . htmlspecialchars($certificate['batch_name']) . '</td>
        </tr>
        <tr class="table-row">
            <td>Overall Average Score</td>
            <td>' . round($certificate['avg_score'], 1) . '%</td>
        </tr>
        <tr class="table-row">
            <td>Certificate Status</td>
            <td>Valid until ' . date('F d, Y', strtotime($certificate['expiry_date'])) . '</td>
        </tr>
    </table>
</div>

<div style="margin-top: 40px;">
    <h3 style="font-size: 18px; margin-bottom: 10px;">Verification Information</h3>
    <table border="0" cellpadding="5">
        <tr>
            <td width="150"><strong>Verification Code:</strong></td>
            <td>' . $certificate['verification_code'] . '</td>
        </tr>
        <tr>
            <td><strong>Verification URL:</strong></td>
            <td>https://' . $_SERVER['HTTP_HOST'] . '/verify_certificate.php?code=' . $certificate['verification_code'] . '</td>
        </tr>
        <tr>
            <td><strong>Issuing Authority:</strong></td>
            <td>Academy of Excellence</td>
        </tr>
    </table>
</div>

<div style="margin-top: 60px; font-size: 11px; color: #666; text-align: justify;">
    <p><strong>Disclaimer:</strong> This transcript is an official document issued by the Academy of Excellence. 
    Any alteration or forgery of this document is strictly prohibited and may result in legal action. 
    The information contained in this transcript is accurate as of the date of issue.</p>
</div>
';

$pdf->writeHTMLCell(0, 0, '', '', $transcript_html, 0, 1, 0, true, '', true);

// Close and output PDF document
$pdf->Output('certificate_' . $certificate['certificate_number'] . '.pdf', 'D');
exit;

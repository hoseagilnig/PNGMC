<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['studentservices', 'admin', 'finance'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db_config.php';

$application_id = intval($_GET['id'] ?? 0);
$conn = getDBConnection();
$application = null;
$invoice = null;

if ($conn && $application_id) {
    // Get application details
    $result = $conn->query("SELECT a.* FROM applications a WHERE a.application_id = $application_id");
    if ($result) {
        $application = $result->fetch_assoc();
    }
    
    // Get invoice if linked
    if ($application && $application['invoice_id']) {
        $result = $conn->query("SELECT * FROM invoices WHERE invoice_id = {$application['invoice_id']}");
        if ($result) {
            $invoice = $result->fetch_assoc();
        }
    }
    
    // Try to get proforma invoice if exists
    if (!$invoice && $application) {
        $result = $conn->query("SELECT pi.* FROM proforma_invoices pi 
            WHERE pi.student_name LIKE '%{$application['first_name']}%{$application['last_name']}%' 
            ORDER BY pi.pi_id DESC LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
        }
    }
    
    // Don't close connection yet - we'll need it for program fee lookup
}

if (!$application) {
    header('Location: applications.php');
    exit;
}

// Format date as "21st Dec, 2024" (short month)
function formatInvoiceDate($date) {
    if (!$date) return date('jS M, Y');
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $suffix = 'th';
    if ($day == 1 || $day == 21 || $day == 31) $suffix = 'st';
    elseif ($day == 2 || $day == 22) $suffix = 'nd';
    elseif ($day == 3 || $day == 23) $suffix = 'rd';
    return date("j$suffix M, Y", $timestamp);
}

// Format full date as "03rd February 2025" (for course dates in invoice)
function formatFullDate($date) {
    if (!$date) return '';
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $suffix = 'th';
    if ($day == 1 || $day == 21 || $day == 31) $suffix = 'st';
    elseif ($day == 2 || $day == 22) $suffix = 'nd';
    elseif ($day == 3 || $day == 23) $suffix = 'rd';
    return date("j$suffix F Y", $timestamp);
}

// Generate invoice number (format: MCSA2025280)
$invoice_number = $invoice['invoice_number'] ?? $invoice['pi_number'] ?? 'MCSA' . date('Y') . str_pad($application_id, 3, '0', STR_PAD_LEFT);
$invoice_date = $invoice['date'] ?? $invoice['invoice_date'] ?? date('Y-m-d');
$invoice_date_formatted = formatInvoiceDate($invoice_date);

// Get program details
$program = $application['program_interest'] ?? 'Cadet Officers Program';
$commencement_date = $application['commencement_date'] ?? date('Y-m-d', strtotime('+3 months'));
$completion_date = $application['completion_date'] ?? date('Y-m-d', strtotime($commencement_date . ' +9 months'));

// Format dates
$commencement_formatted = formatInvoiceDate($commencement_date);
$completion_formatted = formatInvoiceDate($completion_date);

// Check if this is a continuing/returning student
$is_continuing_student = false;
if (isset($application['application_type'])) {
    $is_continuing_student = in_array($application['application_type'], ['continuing_student_solas', 'continuing_student_next_level']);
}

// Get course fee - Priority: 1) Application record, 2) Invoice, 3) Programs table, 4) Defaults
$course_fee = null;
$program_details = null;

// First, check if course_fee is stored directly in the application record
if ($application) {
    // Check for various possible fee field names in application
    $fee_fields = ['course_fee', 'tuition_fee', 'program_fee', 'total_fee', 'fee_amount'];
    foreach ($fee_fields as $field) {
        if (isset($application[$field]) && !empty($application[$field]) && floatval($application[$field]) > 0) {
            $course_fee = floatval($application[$field]);
            break;
        }
    }
}

// If not in application, try invoice
if ((!$course_fee || $course_fee <= 0) && $invoice) {
    if (isset($invoice['total_amount']) && floatval($invoice['total_amount']) > 0) {
        $course_fee = floatval($invoice['total_amount']);
    } elseif (isset($invoice['course_fee']) && floatval($invoice['course_fee']) > 0) {
        $course_fee = floatval($invoice['course_fee']);
    }
}

// If still not found, try to get from programs table based on program_interest
if ((!$course_fee || $course_fee <= 0) && $conn && $application) {
    $program_interest_clean = trim($program);
    $program_interest_escaped = $conn->real_escape_string($program_interest_clean);
    $result = $conn->query("SELECT * FROM programs WHERE program_name LIKE '%$program_interest_escaped%' OR program_code LIKE '%$program_interest_escaped%' LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $program_details = $result->fetch_assoc();
        $course_fee = floatval($program_details['tuition_fee']);
    } else {
        // Try partial match with common program keywords
        $keywords = ['Engineer', 'Cadet', 'Class 3', 'Class 4', 'Mate', 'Deck', 'Nautical'];
        foreach ($keywords as $keyword) {
            if (stripos($program_interest_clean, $keyword) !== false) {
                $keyword_escaped = $conn->real_escape_string($keyword);
                $result = $conn->query("SELECT * FROM programs WHERE program_name LIKE '%$keyword_escaped%' OR program_code LIKE '%$keyword_escaped%' LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $program_details = $result->fetch_assoc();
                    $course_fee = floatval($program_details['tuition_fee']);
                    break;
                }
            }
        }
    }
}

// If still not found, use defaults based on student type
if (!$course_fee || $course_fee <= 0) {
    if ($is_continuing_student) {
        $course_fee = 26811.00; // Default K 26,811.00 for continuing students
    } else {
        $course_fee = 24120.00; // Default K 24,120.00 for new students
    }
}

// Close connection after all queries
if ($conn) {
    $conn->close();
}

// Get current year for course name
$current_year = date('Y', strtotime($commencement_date));
$next_year = date('Y', strtotime($commencement_date));

// Extract program code and format course name
$program_code = 'EC3';
$course_name_for_invoice = strtoupper($program);
if (stripos($program, 'class 3') !== false) {
    $program_code = 'EC3';
} elseif (stripos($program, 'class 4') !== false) {
    $program_code = 'EC4';
} elseif (stripos($program, 'cadet') !== false) {
    $program_code = 'COP';
    $course_name_for_invoice = 'ENGINEER CADET COURSE';
}

// Format course name for invoice
if (!$is_continuing_student) {
    // For new students: "ENGINEER CADET COURSE FEES FOR 2025"
    $course_name_for_invoice = $course_name_for_invoice . ' FEES FOR ' . $next_year;
} else {
    // For continuing students: "ENGINEER CLASS 3 2026 COURSE FEES FOR"
    $course_name_for_invoice = strtoupper($program) . ' COURSE FEES FOR';
}

// Get issuing officer
$issuing_officer = 'Serah Mandengat (Ms)';
if ($invoice && isset($invoice['issuing_officer_name'])) {
    $issuing_officer = $invoice['issuing_officer_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Proforma Invoice - <?php echo htmlspecialchars($application['application_number']); ?></title>
  <style>
    @media print {
      @page {
        size: A4 portrait;
        margin: 1cm;
      }
      body { 
        margin: 0; 
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-size: 11pt;
      }
      .no-print { 
        display: none !important; 
      }
      .page-break { 
        page-break-after: always; 
      }
    }
    body {
      font-family: Arial, sans-serif;
      max-width: 21cm;
      margin: 0 auto;
      padding: 20px;
      background: white;
      font-size: 11pt;
      line-height: 1.5;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #1d4e89;
    }
    .logo-left, .logo-right {
      width: 70px;
      height: 70px;
      background: #f0f0f0;
      border: 1px solid #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 9pt;
      color: #666;
    }
    .college-name {
      text-align: center;
      flex: 1;
      padding: 0 15px;
    }
    .college-name h1 {
      font-size: 16pt;
      font-weight: bold;
      margin: 0;
      color: #1d4e89;
    }
    .contact-info {
      text-align: center;
      font-size: 9pt;
      margin-top: 8px;
      line-height: 1.3;
      color: #333;
    }
    .invoice-title {
      text-align: center;
      font-size: 18pt;
      font-weight: bold;
      margin: 20px 0;
      color: #1d4e89;
    }
    .invoice-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .invoice-number {
      text-align: right;
      font-size: 11pt;
    }
    .bill-to {
      margin-bottom: 20px;
    }
    .bill-to h3 {
      font-size: 12pt;
      margin-bottom: 10px;
      color: #1d4e89;
    }
    .bill-to p {
      margin: 3px 0;
      font-size: 11pt;
    }
    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    .invoice-table th {
      background: #1d4e89;
      color: white;
      padding: 10px;
      text-align: left;
      font-size: 11pt;
      font-weight: bold;
    }
    .invoice-table td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      font-size: 11pt;
    }
    .invoice-table tr:last-child td {
      border-bottom: none;
    }
    .amount-col {
      text-align: right;
    }
    .total-amount {
      margin-top: 20px;
      font-size: 13pt;
      font-weight: bold;
      text-align: right;
      border-top: 2px solid #1d4e89;
      padding-top: 10px;
    }
    .total-amount .amount {
      text-decoration: underline;
    }
    .quoted-by {
      margin-top: 30px;
      font-size: 11pt;
    }
    .bank-details-box {
      margin-top: 30px;
      padding: 15px;
      border: 2px solid #1d4e89;
      border-radius: 5px;
      background: #f0f7ff;
      font-size: 10pt;
    }
    .bank-details-box strong {
      color: #1d4e89;
    }
    .btn {
      background: #1d4e89;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      margin: 10px 5px;
    }
  </style>
</head>
<body>
  <div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" class="btn">🖨️ Print Invoice</button>
    <a href="application_details.php?id=<?php echo $application_id; ?>" class="btn">← Back to Application</a>
  </div>

  <div class="header">
    <div class="logo-left">
      <img src="../images/pnmc.png" alt="PNG Maritime College Logo" style="max-width: 100%; max-height: 100%; height: auto; width: auto;">
    </div>
    <div class="college-name">
      <h1>PAPUA NEW GUINEA MARITIME COLLEGE</h1>
      <div class="contact-info">
        Kusbau Road PO Box 1040 Madang 511 Papua New Guinea<br>
        Ph: +675 422 2615 Fax: +675 422 3113<br>
        Email: info@pngmc.ac.pg; Web: www.pngmc.ac.pg
      </div>
    </div>
    <div class="logo-right">
      <img src="../images/bird%20of%20paradise.png" alt="Papua New Guinea Emblem" style="max-width: 100%; max-height: 100%; height: auto; width: auto;">
    </div>
  </div>

  <div class="invoice-title">PROFORMA INVOICE</div>

  <div class="invoice-info">
    <div></div>
    <div class="invoice-number">
      <strong>Number:</strong> <?php echo htmlspecialchars($invoice_number); ?><br>
      <strong>Date:</strong> <?php echo $invoice_date_formatted; ?>
    </div>
  </div>

  <div class="bill-to">
    <h3>Bill to:</h3>
    <p><strong><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></strong></p>
    <?php if ($application['address']): ?>
      <p><?php echo nl2br(htmlspecialchars($application['address'])); ?></p>
    <?php endif; ?>
    <?php if ($application['city']): ?>
      <p><?php echo htmlspecialchars($application['city']); ?><?php if ($application['province']): ?>, <?php echo htmlspecialchars($application['province']); ?><?php endif; ?></p>
    <?php endif; ?>
  </div>

  <table class="invoice-table">
    <thead>
      <tr>
        <th>Details/Description</th>
        <th class="amount-col">Amount (K)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <?php if ($is_continuing_student): ?>
            <strong><?php echo $course_name_for_invoice; ?>;</strong><br>
            <strong><?php echo strtoupper(htmlspecialchars($application['first_name'] . ' ' . $application['last_name'])); ?> - <?php echo $program_code; ?></strong><br>
            Commencing: <?php echo $commencement_formatted; ?><br>
            Completes: <?php echo $completion_formatted; ?>
          <?php else: ?>
            <strong><?php echo $course_name_for_invoice; ?></strong><br>
            <strong><?php echo strtoupper(htmlspecialchars($application['first_name'] . ' ' . $application['last_name'])); ?></strong><br>
            Course commencing: <?php echo formatFullDate($commencement_date); ?><br>
            Course End: <?php echo formatFullDate($completion_date); ?><br>
            Quoted by: <?php echo htmlspecialchars($issuing_officer); ?>
          <?php endif; ?>
        </td>
        <td class="amount-col"><strong>K <?php echo number_format($course_fee, 2, '.', ', '); ?></strong></td>
      </tr>
    </tbody>
  </table>

  <div class="total-amount">
    <strong>Total Amount:</strong> <span class="amount">K <?php echo number_format($course_fee, 2, '.', ', '); ?></span>
  </div>

  <?php if ($is_continuing_student): ?>
  <div class="quoted-by">
    <strong>Quoted by:</strong> <?php echo htmlspecialchars($issuing_officer); ?>
  </div>
  <?php endif; ?>

  <div class="bank-details-box">
    <p><strong>Bank details:</strong></p>
    <p><strong>Bank:</strong> Bank South Pacific - Madang</p>
    <p><strong>Acc. No.:</strong> 1000 151911</p>
    <p><strong>Acc. Name:</strong> PNGMC Enrolment Account</p>
  </div>
</body>
</html>


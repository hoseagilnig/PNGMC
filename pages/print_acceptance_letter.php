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
    // Get application details - using prepared statement
    $stmt = $conn->prepare("SELECT a.*, u.full_name as hod_name 
        FROM applications a 
        LEFT JOIN users u ON a.hod_decision_by = u.user_id 
        WHERE a.application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $application = $result->fetch_assoc();
    }
    $stmt->close();
    
    // Get invoice if linked
    if ($application && $application['invoice_id']) {
        $invoice_id = intval($application['invoice_id']);
        $stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $invoice = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // Try to get proforma invoice if exists
    if (!$invoice && $application) {
        // Check if there's a proforma invoice linked via application number or student
        $first_name = $application['first_name'];
        $last_name = $application['last_name'];
        $search_pattern = "%{$first_name}%{$last_name}%";
        $stmt = $conn->prepare("SELECT pi.* FROM proforma_invoices pi 
            WHERE pi.student_name LIKE ? 
            ORDER BY pi.pi_id DESC LIMIT 1");
        $stmt->bind_param("s", $search_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
        }
        $stmt->close();
    }
    
    // Don't close connection yet - we'll need it for program fee lookup
}

if (!$application) {
    header('Location: applications.php');
    exit;
}

// Format date as "18th October, 2025"
function formatDate($date) {
    if (!$date) return date('jS F, Y');
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $suffix = 'th';
    if ($day == 1 || $day == 21 || $day == 31) $suffix = 'st';
    elseif ($day == 2 || $day == 22) $suffix = 'nd';
    elseif ($day == 3 || $day == 23) $suffix = 'rd';
    return date("j$suffix F, Y", $timestamp);
}

// Check if this is a continuing/returning student
$is_continuing_student = false;
if (isset($application['application_type'])) {
    $is_continuing_student = in_array($application['application_type'], ['continuing_student_solas', 'continuing_student_next_level']);
}

// Get program details
$program = $application['program_interest'] ?? 'Cadet Officers Program';
$commencement_date = $application['commencement_date'] ?? date('Y-m-d', strtotime('+3 months'));
$completion_date = $application['completion_date'] ?? date('Y-m-d', strtotime($commencement_date . ' +9 months'));

// Format commencement date
$commencement_formatted = formatDate($commencement_date);

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

// Get current year for subject line
$current_year = date('Y', strtotime($commencement_date));
$next_year = date('Y', strtotime($commencement_date));

// Extract program level (e.g., "Class 3 Engineer" from "Engineer Class 3" or similar)
$program_short = $program;
if (stripos($program, 'class 3') !== false) {
    $program_short = 'CLASS 3 ENGINEER';
} elseif (stripos($program, 'class 4') !== false) {
    $program_short = 'CLASS 4 ENGINEER';
} elseif (stripos($program, 'cadet') !== false) {
    $program_short = 'CADET OFFICERS PROGRAM';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceptance Letter - <?php echo htmlspecialchars($application['application_number']); ?></title>
  <style>
    @media print {
      @page {
        size: A4 portrait;
        margin: 1.5cm;
        /* Remove browser headers and footers */
        margin-header: 0;
        margin-footer: 0;
      }
      body { 
        margin: 0; 
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-size: 12pt;
      }
      .no-print { 
        display: none !important; 
      }
      .page-break { 
        page-break-after: always; 
      }
      /* Hide any browser-added content */
      @page {
        @top-center { content: ""; }
        @bottom-center { content: ""; }
        @top-left { content: ""; }
        @top-right { content: ""; }
        @bottom-left { content: ""; }
        @bottom-right { content: ""; }
      }
    }
    body {
      font-family: 'Times New Roman', serif;
      max-width: 21cm;
      margin: 0 auto;
      padding: 20px;
      background: white;
      font-size: 12pt;
      line-height: 1.6;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 2px solid #000;
    }
    .logo-left, .logo-right {
      width: 80px;
      height: 80px;
      background: #f0f0f0;
      border: 1px solid #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10pt;
      color: #666;
    }
    .college-name {
      text-align: center;
      flex: 1;
      padding: 0 20px;
    }
    .college-name h1 {
      font-size: 18pt;
      font-weight: bold;
      margin: 0;
      letter-spacing: 1px;
    }
    .contact-info {
      text-align: center;
      font-size: 10pt;
      margin-top: 10px;
      line-height: 1.4;
    }
    .letter-date {
      text-align: right;
      margin-bottom: 20px;
      font-size: 12pt;
    }
    .recipient-info {
      margin-bottom: 20px;
      font-size: 12pt;
    }
    .subject-line {
      font-weight: bold;
      margin: 20px 0;
      font-size: 12pt;
    }
    .letter-body {
      text-align: justify;
      font-size: 12pt;
      line-height: 1.8;
      margin-bottom: 20px;
    }
    .letter-body p {
      margin-bottom: 15px;
    }
    .fees-section {
      margin: 20px 0;
      padding: 15px;
      background: #f9f9f9;
      border-left: 4px solid #1d4e89;
    }
    .fees-section strong {
      color: #1d4e89;
    }
    .bank-details {
      margin: 20px 0;
      padding: 15px;
      background: #f0f7ff;
      border: 1px solid #1d4e89;
      border-radius: 5px;
    }
    .bank-details strong {
      color: #1d4e89;
    }
    .requirements-list {
      margin: 20px 0;
      padding-left: 20px;
    }
    .requirements-list li {
      margin-bottom: 10px;
    }
    .signature-block {
      margin-top: 40px;
    }
    .signature-line {
      border-top: 1px solid #000;
      width: 300px;
      margin-top: 60px;
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
    <button onclick="window.print()" class="btn">🖨️ Print Acceptance Letter</button>
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

  <div class="letter-date">
    <?php echo formatDate(date('Y-m-d')); ?>
  </div>

  <div class="recipient-info">
    <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?><br>
    <?php if ($application['address']): ?>
      <?php echo nl2br(htmlspecialchars($application['address'])); ?><br>
    <?php endif; ?>
    <?php if ($application['city']): ?>
      <?php echo htmlspecialchars($application['city']); ?><?php if ($application['province']): ?>, <?php echo htmlspecialchars($application['province']); ?><?php endif; ?><br>
    <?php endif; ?>
  </div>

  <div style="margin-top: 20px;">
    <strong>Dear <?php echo htmlspecialchars($application['first_name']); ?>,</strong>
  </div>

  <div class="subject-line">
    RE: <?php if ($is_continuing_student): ?>
      LETTER OF ACCEPTANCE - <?php echo $program_short; ?> IN <?php echo $next_year; ?>
    <?php else: ?>
      <strong>ACCEPTANCE FOR THE <?php echo strtoupper($program_short); ?> IN <?php echo $next_year; ?></strong>
    <?php endif; ?>
  </div>

  <div class="letter-body">
    <?php if ($is_continuing_student): ?>
      <p>
        We are pleased to inform you that your application has been approved by the <strong>HOD Engine</strong> and you have been accepted to study the <strong><?php echo htmlspecialchars($program); ?></strong> at Papua New Guinea Maritime College.
      </p>
      <p>
        Your course will commence on <strong><?php echo $commencement_formatted; ?></strong>. Please note that your acceptance is conditional once your full course fees are settled.
      </p>
    <?php else: ?>
      <p>
        This is to advise that your application for the <strong><?php echo htmlspecialchars($program); ?></strong> has been approved by the HoD Engine, and you are accepted for the above training. Course starts on <strong><?php echo $commencement_formatted; ?></strong>, once your full course fees is settled by your sponsor.
      </p>
    <?php endif; ?>

    <div class="fees-section">
      <p><strong>COURSE FEES AND ACCOMMODATION:</strong></p>
      <?php if ($is_continuing_student): ?>
        <p>
          Your full course fee is <strong>K <?php echo number_format($course_fee, 2, '.', ','); ?></strong>. This covers messing, lodging, tuition, uniform, ID card, a calculator.
        </p>
        <p>
          <strong>Please bring your own bed sheets, pillow cases and towel.</strong> The College will provide a shared room in the dormitories, single mattress, pillow and eating utensils.
        </p>
        <p>
          A room key security deposit of <strong>K 20.00</strong> (cash) is required at registration. This deposit is refundable upon vacating the room in good order and returning the key.
        </p>
      <?php else: ?>
        <p>
          The full fee of <strong>K <?php echo number_format($course_fee, 2, '.', ', '); ?></strong> includes boarding and lodging, tuition, uniform (short trousers, shirts, a pair of shoe and socks) and ID Card. You must provide your own bed sheets, towels and pillow cases. The College will provide a shared dormitory type room, bed, single mattress, pillow and eating utensils. At the time of registration, you are required to pay <strong>K20.00</strong> in cash as Room Key deposit which will be refunded when you vacate the room in good order and return the Room Key to the Administration up on clearing out from the College accommodation.
        </p>
      <?php endif; ?>
    </div>

    <div class="bank-details">
      <p><strong>PAYMENT INSTRUCTIONS:</strong></p>
      <p>
        Please make payment deposit to "<strong>PNG Maritime College Enrolment Account</strong>" number <strong>1000 151911</strong> (Cheque A/C - Madang Branch) at any BSP branch near you. Once a fee deposit had been made, please email a copy of the bank deposit slip to the College on email address: 
        <?php if ($is_continuing_student): ?>
          Jimmy Raimbas on <strong>jraimbas@pngmc.ac.pg</strong> or Serah Mandengat on <strong>smandengat@pngmc.ac.pg</strong> (Attention - Student Admin) as proof of payment.
        <?php else: ?>
          Sandy Pisae on <strong>spisae@pngmc.ac.pg</strong> or Pius Narol on <strong>pnarol@pngmc.ac.pg</strong> (Attention - Student Admin) as proof of payment.
        <?php endif; ?>
        Please ensure you or your sponsor inform us of your intended travel date so that we can arrange your room key and have someone meet you on arrival at the airport, if you are travelling by plane.
      </p>
    </div>

    <p><strong>PRE-COLLEGE REQUIREMENTS:</strong></p>
    <?php if ($is_continuing_student): ?>
      <ul class="requirements-list">
        <li><strong>Medical Fitness Certificate:</strong> Must be valid and sighted by the Examiner prior to sitting for Orals (towards the end of the course).</li>
        <li><strong>CERB:</strong> You must bring your CERB (Certificate of Eligibility for Recruitment and Billeting).</li>
      </ul>
    <?php else: ?>
      <p>
        It is also a requirement under the Merchant Shipping Act that before you undertake this course of study, you must be certified by a National Maritime Safety Authority (NMSA) appointed medical doctor to be medically fit. The examination can be arranged in Madang at the <strong>Madang Private Surgery Clinic</strong> which will be at your own cost.
      </p>
    <?php endif; ?>

    <?php if ($is_continuing_student): ?>
    <p>
      As a continuing student, please be aware of and observe the College's Student Code of Conduct and Campus Rules & Regulations from enrollment to completion. <strong>Breach of any of these rules & regulations could result in disciplinary action including expulsion from the College being taken against offending students.</strong>
    </p>
    <p style="font-weight: bold; color: #d32f2f;">
      PLEASE NOTE: ORALS EXAM FEES ARE NOT INCLUDED IN THE MAIN COURSE FEES.
    </p>
    <?php else: ?>
    <p>
      You must be made aware that the College has strict Rules & Regulations governing the conduct of students on and off campus so you must observe all these rules & regulations at all times from the time you register to the time you finish your training here. <strong>Breach of any of these Rules & Regulations could result in disciplinary action including instant expulsion from the College being taken against you.</strong>
    </p>
    <?php endif; ?>

    <p>
      <?php if ($is_continuing_student): ?>
        We look forward to seeing you soon.
      <?php else: ?>
        We look forward to see you soon.
      <?php endif; ?>
    </p>
  </div>

  <div class="signature-block">
    <p>Yours Sincerely,</p>
    <div class="signature-line"></div>
    <p style="margin-top: 5px;">
      <strong>Terence Sisu (Mr)</strong><br>
      A/ PRINCIPAL
    </p>
  </div>

  <script>
    // Prevent browser from adding URL/date to print
    window.onbeforeprint = function() {
      // Remove any potential extra content
      var noPrintElements = document.querySelectorAll('.no-print');
      noPrintElements.forEach(function(el) {
        el.style.display = 'none';
      });
    };
    window.onafterprint = function() {
      // Restore elements after printing
      var noPrintElements = document.querySelectorAll('.no-print');
      noPrintElements.forEach(function(el) {
        el.style.display = '';
      });
    };
  </script>
</body>
</html>


<?php
session_start();
// Allow finance and studentservices roles
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['finance', 'studentservices', 'admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/finance_sas_helper.php';

$conn = getDBConnection();
$red_green_days = [];

// Get Red & Green Days schedule
if ($conn) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'student_schedules'")->num_rows > 0;
    
    if ($tables_exist) {
        $schedules = getStudentSchedules('red_green_days');
        
        // Calculate Red & Green Days for each schedule
        foreach ($schedules as $schedule) {
            if ($schedule['program_course_start_date'] && $schedule['program_course_ending_date']) {
                $calc = calculateRedGreenDays(
                    $schedule['student_id'],
                    $schedule['program_course_tuition_fee'],
                    $schedule['program_course_start_date'],
                    $schedule['program_course_ending_date'],
                    $schedule['amount_paid']
                );
                
                // Update schedule with calculated values
                $update_conn = getDBConnection();
                if ($update_conn) {
                    $update_conn->query("UPDATE student_schedules SET 
                        daily_rate = {$calc['daily_rate']},
                        days_paid = {$calc['days_paid']},
                        days_non_paid = {$calc['days_non_paid']},
                        overstayed_days = {$calc['overstayed_days']},
                        unpaid_days = {$calc['unpaid_days']},
                        alert_flag = '{$calc['alert_flag']}'
                        WHERE schedule_id = {$schedule['schedule_id']}");
                    $update_conn->close();
                }
                
                $schedule = array_merge($schedule, $calc);
            }
            $red_green_days[] = $schedule;
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Red & Green Days Monitor - Finance/SAS</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .alert-red {
      background: #dc3545;
      color: white;
      padding: 4px 8px;
      border-radius: 3px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    .alert-yellow {
      background: #ffc107;
      color: #000;
      padding: 4px 8px;
      border-radius: 3px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    .alert-green {
      background: #28a745;
      color: white;
      padding: 4px 8px;
      border-radius: 3px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    body {
      overflow-x: hidden;
      max-width: 100vw;
    }
    .dashboard-wrap {
      max-width: 100%;
      overflow-x: hidden;
    }
    .content {
      max-width: 100%;
      overflow-x: hidden;
      box-sizing: border-box;
    }
    .main-card {
      max-width: 100%;
      overflow-x: hidden;
      box-sizing: border-box;
    }
    .table-container {
      width: 100%;
      max-width: 100%;
      overflow-x: auto;
      overflow-y: visible;
      -webkit-overflow-scrolling: touch;
      margin-top: 20px;
      box-sizing: border-box;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    .table-container table {
      width: 100%;
      min-width: 1200px;
      border-collapse: collapse;
      margin: 0;
    }
    .table-container th,
    .table-container td {
      white-space: nowrap;
      font-size: 0.9rem;
      padding: 10px 8px;
    }
    .table-container th {
      position: sticky;
      top: 0;
      z-index: 10;
      background: #1d4e89;
      color: white;
    }
    .table-container td {
      background: white;
    }
    .table-container tr:nth-child(even) td {
      background: #f8f9fa;
    }
    @media (max-width: 1400px) {
      .table-container th,
      .table-container td {
        padding: 8px 6px;
        font-size: 0.85rem;
      }
    }
    @media (max-width: 1200px) {
      .table-container table {
        min-width: 1000px;
      }
      .table-container th,
      .table-container td {
        padding: 8px 5px;
        font-size: 0.8rem;
      }
    }
    @media (max-width: 768px) {
      .table-container table {
        min-width: 900px;
      }
      .table-container th,
      .table-container td {
        padding: 6px 4px;
        font-size: 0.75rem;
      }
      .content {
        padding: 15px;
        max-width: 100vw;
      }
      .main-card {
        padding: 15px;
        max-width: 100%;
      }
    }
    @media (max-width: 480px) {
      .table-container table {
        min-width: 800px;
      }
      .table-container th,
      .table-container td {
        padding: 5px 3px;
        font-size: 0.7rem;
      }
    }
    @media print {
      body {
        margin: 0;
        padding: 10px;
        font-size: 10px;
      }
      .sidebar,
      .btn,
      .no-print {
        display: none !important;
      }
      .content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
      .main-card {
        padding: 10px !important;
        margin-bottom: 10px !important;
        page-break-inside: avoid;
      }
      .table-container {
        overflow: visible !important;
        width: 100% !important;
      }
      .table-container table {
        min-width: 100% !important;
        width: 100% !important;
        font-size: 8px !important;
        border-collapse: collapse;
      }
      .table-container th,
      .table-container td {
        padding: 4px 6px !important;
        font-size: 8px !important;
        white-space: nowrap;
        border: 1px solid #ddd;
      }
      .table-container th {
        background: #1d4e89 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .table-container tr {
        page-break-inside: avoid;
      }
      .alert-red,
      .alert-yellow,
      .alert-green {
        padding: 2px 4px !important;
        font-size: 7px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      header {
        margin-bottom: 10px !important;
      }
      header h1 {
        font-size: 18px !important;
        margin: 0 !important;
      }
      header .small {
        font-size: 10px !important;
        margin: 5px 0 !important;
      }
      @page {
        size: A4 landscape;
        margin: 0.5cm;
      }
    }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="<?php echo $_SESSION['role'] === 'finance' ? 'finance_dashboard.php' : 'student_service_dashboard.php'; ?>" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <?php if ($_SESSION['role'] === 'finance'): ?>
          <a class="menu-item" href="finance_dashboard.php">Dashboard</a>
          <div class="menu-section">Workflow</div>
          <a class="menu-item" href="proforma_invoices.php">Proforma Invoices</a>
          <a class="menu-item" href="student_schedules.php">Student Schedules</a>
          <a class="menu-item" href="fees_monitor.php">Fees Monitor</a>
          <a class="menu-item active" href="red_green_days.php">Red & Green Days</a>
          <a class="menu-item" href="finance_to_sas.php">Finance to SAS</a>
        <?php else: ?>
          <a class="menu-item" href="student_service_dashboard.php">Dashboard</a>
          <div class="menu-section">Workflow</div>
          <a class="menu-item" href="proforma_invoices.php">Proforma Invoices</a>
          <a class="menu-item" href="withdrawal_advice.php">Withdrawal Advice</a>
          <a class="menu-item" href="disciplinary_advice.php">Disciplinary Advice</a>
          <a class="menu-item" href="student_schedules.php">Student Schedules</a>
          <a class="menu-item active" href="red_green_days.php">Red & Green Days</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Red & Green Days Monitor</h1>
        <p class="small">
          <?php if ($_SESSION['role'] === 'studentservices' || $_SESSION['role'] === 'admin'): ?>
            Monitor daily rates, days paid/non-paid, and flag overstayed/unpaid days for non-attending students. Finance will be notified when schedule is generated.
          <?php else: ?>
            View Red & Green Days Schedule generated by Student Admin Service. This schedule is sent to Finance for review and MYOB synchronization.
          <?php endif; ?>
        </p>
      </header>

      <div class="main-card" style="margin-bottom: 20px; background: #e7f3ff; border-left: 4px solid #1d4e89; padding: 20px;">
        <h3 style="margin: 0 0 10px 0; color: #1d4e89;">About Red & Green Days</h3>
        <ul style="margin: 0; padding-left: 20px; color: #333;">
          <li><strong>Daily Rate:</strong> Calculated from course fee divided by total course days</li>
          <li><strong>Days Paid:</strong> Number of days covered by payments made</li>
          <li><strong>Days Non Paid:</strong> Number of days not covered by payments</li>
          <li><strong>Overstayed Days:</strong> Days beyond course ending date with unpaid fees</li>
          <li><strong>Unpaid Days:</strong> Days within course period with unpaid fees</li>
          <li><strong>Alert Flags:</strong> GREEN (0-14 days), YELLOW (15-30 days), RED (30+ days unpaid)</li>
        </ul>
      </div>

      <div class="main-card">
        <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
          <a href="student_schedules.php?type=red_green_days" class="btn btn-primary no-print" style="text-decoration: none; display: inline-block;">Generate/Update Red & Green Days Schedule</a>
          <button onclick="window.print()" class="btn btn-primary no-print" style="background: #6c757d; border: none; padding: 10px 20px; border-radius: 5px; color: white; cursor: pointer; font-weight: 600;">🖨️ Print</button>
        </div>

        <?php if (!empty($red_green_days)): ?>
          <div class="table-container">
            <table>
              <thead>
                <tr style="background: #1d4e89; color: white;">
                  <th style="padding: 10px 6px; text-align: center; width: 40px;">#</th>
                  <th style="padding: 10px 6px; text-align: left; width: 100px;">Student #</th>
                  <th style="padding: 10px 6px; text-align: left; min-width: 120px;">Student Name</th>
                  <th style="padding: 10px 6px; text-align: left; min-width: 120px;">Program/Course</th>
                  <th style="padding: 10px 6px; text-align: left; width: 100px;">Start Date</th>
                  <th style="padding: 10px 6px; text-align: left; width: 100px;">End Date</th>
                  <th style="padding: 10px 6px; text-align: right; width: 100px;">Course Fee</th>
                  <th style="padding: 10px 6px; text-align: right; width: 90px;">Daily Rate</th>
                  <th style="padding: 10px 6px; text-align: right; width: 110px;">Payments</th>
                  <th style="padding: 10px 6px; text-align: center; width: 70px;">Days Paid</th>
                  <th style="padding: 10px 6px; text-align: center; width: 80px;">Days Non Paid</th>
                  <th style="padding: 10px 6px; text-align: center; width: 70px;">Overstayed</th>
                  <th style="padding: 10px 6px; text-align: center; width: 80px;">Unpaid Days</th>
                  <th style="padding: 10px 6px; text-align: center; width: 70px;">Alert</th>
                  <th style="padding: 10px 6px; text-align: right; width: 110px;">Outstanding</th>
                </tr>
              </thead>
              <tbody>
                <?php $row_num = 1; foreach ($red_green_days as $schedule): ?>
                  <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px 6px; text-align: center;"><?php echo $row_num++; ?></td>
                    <td style="padding: 10px 6px;"><?php echo htmlspecialchars($schedule['student_number']); ?></td>
                    <td style="padding: 10px 6px;"><?php echo htmlspecialchars($schedule['student_name']); ?></td>
                    <td style="padding: 10px 6px;"><?php echo htmlspecialchars($schedule['program_course_name']); ?></td>
                    <td style="padding: 10px 6px;"><?php echo $schedule['program_course_start_date'] ? date('M d, Y', strtotime($schedule['program_course_start_date'])) : 'N/A'; ?></td>
                    <td style="padding: 10px 6px;"><?php echo $schedule['program_course_ending_date'] ? date('M d, Y', strtotime($schedule['program_course_ending_date'])) : 'N/A'; ?></td>
                    <td style="padding: 10px 6px; text-align: right;">PGK <?php echo number_format($schedule['program_course_tuition_fee'], 2); ?></td>
                    <td style="padding: 10px 6px; text-align: right; font-size: 0.85rem;">PGK <?php echo number_format($schedule['daily_rate'] ?? 0, 2); ?>/day</td>
                    <td style="padding: 10px 6px; text-align: right; color: #28a745;">PGK <?php echo number_format($schedule['amount_paid'], 2); ?></td>
                    <td style="padding: 10px 6px; text-align: center; color: #28a745; font-weight: 600;"><?php echo $schedule['days_paid'] ?? 0; ?></td>
                    <td style="padding: 10px 6px; text-align: center; color: #ffc107; font-weight: 600;"><?php echo $schedule['days_non_paid'] ?? 0; ?></td>
                    <td style="padding: 10px 6px; text-align: center; color: #dc3545; font-weight: 600;"><?php echo $schedule['overstayed_days'] ?? 0; ?></td>
                    <td style="padding: 10px 6px; text-align: center; color: #dc3545; font-weight: 600;"><?php echo $schedule['unpaid_days'] ?? 0; ?></td>
                    <td style="padding: 10px 6px; text-align: center;">
                      <?php if ($schedule['alert_flag']): ?>
                        <span class="alert-<?php echo $schedule['alert_flag']; ?>">
                          <?php echo strtoupper($schedule['alert_flag']); ?>
                        </span>
                      <?php else: ?>
                        <span class="alert-green">GREEN</span>
                      <?php endif; ?>
                    </td>
                    <td style="padding: 10px 6px; text-align: right; font-weight: bold; color: <?php echo $schedule['balance'] > 0 ? '#dc3545' : '#28a745'; ?>;">PGK <?php echo number_format($schedule['balance'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="no-print" style="color: #666; margin-top: 15px; font-size: 0.9rem; font-style: italic;">
            <strong>Note:</strong> On smaller screens, scroll horizontally to view all columns.
          </p>
        <?php else: ?>
          <p style="color: #666; margin-top: 20px;">No Red & Green Days data found. <a href="student_schedules.php?type=red_green_days">Generate Red & Green Days Schedule</a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>


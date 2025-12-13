<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['finance', 'studentservices', 'admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db_config.php';

$pi_id = intval($_GET['id'] ?? 0);
$conn = getDBConnection();
$pi = null;
$payments = [];

if ($conn && $pi_id) {
    $tables_exist = $conn->query("SHOW TABLES LIKE 'proforma_invoices'")->num_rows > 0;
    
    if ($tables_exist) {
        // Get PI details
        $result = $conn->query("SELECT pi.*, 
            u1.full_name as issuing_officer_name,
            u2.full_name as registrar_name
            FROM proforma_invoices pi
            LEFT JOIN users u1 ON pi.pi_issuing_officer = u1.user_id
            LEFT JOIN users u2 ON pi.approval_by_registrar = u2.user_id
            WHERE pi.pi_id = $pi_id");
        if ($result) {
            $pi = $result->fetch_assoc();
        }
        
        // Get payments
        if ($pi) {
            $result = $conn->query("SELECT * FROM proforma_invoice_payments WHERE pi_id = $pi_id ORDER BY payment_date ASC");
            if ($result) {
                $payments = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
    
    $conn->close();
}

if (!$pi) {
    header('Location: proforma_invoices.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pi['pi_number']); ?></title>
  <style>
    @media print {
      @page {
        size: A4 portrait;
        margin: 0.5cm;
      }
      body { 
        margin: 0; 
        padding: 15px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-size: 12px;
      }
      .no-print { 
        display: none !important; 
      }
      .myob-note {
        display: none !important;
      }
      .page-break { 
        page-break-after: always; 
      }
      .header {
        margin-bottom: 15px;
        padding-bottom: 10px;
      }
      .header h1 {
        font-size: 22px;
      }
      .header p {
        font-size: 11px;
        margin: 3px 0;
      }
      .invoice-info {
        gap: 15px;
        margin-bottom: 15px;
      }
      .info-section {
        padding: 10px;
      }
      .info-section h3 {
        font-size: 14px;
        padding-bottom: 5px;
      }
      .info-row {
        padding: 5px 0;
        font-size: 11px;
      }
      table {
        margin: 10px 0;
        font-size: 11px;
      }
      table th {
        padding: 8px;
        font-size: 11px;
      }
      table td {
        padding: 6px 8px;
        font-size: 11px;
      }
      .summary-box {
        padding: 15px;
        margin-top: 15px;
      }
      .summary-row {
        padding: 6px 0;
        font-size: 12px;
      }
      .summary-row.total {
        padding-top: 10px;
        font-size: 16px;
      }
      .footer {
        margin-top: 20px;
        padding-top: 15px;
        font-size: 10px;
      }
    }
    body {
      font-family: Arial, sans-serif;
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
      background: white;
    }
    .header {
      text-align: center;
      border-bottom: 3px solid #1d4e89;
      padding-bottom: 20px;
      margin-bottom: 30px;
    }
    .header h1 {
      color: #1d4e89;
      margin: 0;
      font-size: 28px;
    }
    .header p {
      color: #666;
      margin: 5px 0;
    }
    .invoice-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-bottom: 30px;
    }
    .info-section {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
    }
    .info-section h3 {
      margin-top: 0;
      color: #1d4e89;
      font-size: 16px;
      border-bottom: 2px solid #1d4e89;
      padding-bottom: 8px;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #ddd;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      font-weight: 600;
      color: #666;
    }
    .info-value {
      color: #333;
      text-align: right;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    table th {
      background: #1d4e89;
      color: white;
      padding: 12px;
      text-align: left;
      font-weight: 600;
    }
    table td {
      padding: 10px 12px;
      border-bottom: 1px solid #ddd;
    }
    table tr:last-child td {
      border-bottom: none;
    }
    .text-right {
      text-align: right;
    }
    .text-center {
      text-align: center;
    }
    .summary-box {
      background: #e7f3ff;
      border: 2px solid #1d4e89;
      padding: 20px;
      border-radius: 5px;
      margin-top: 20px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      font-size: 16px;
    }
    .summary-row.total {
      border-top: 2px solid #1d4e89;
      margin-top: 10px;
      padding-top: 15px;
      font-size: 20px;
      font-weight: bold;
    }
    .footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 2px solid #ddd;
      font-size: 12px;
      color: #666;
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
    <a href="proforma_invoice_details.php?id=<?php echo $pi['pi_id']; ?>" class="btn">← Back to Details</a>
  </div>

  <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <div style="flex: 0 0 auto;">
      <img src="../images/pnmc.png" alt="PNG Maritime College Logo" style="max-height: 80px; max-width: 200px; height: auto;">
    </div>
    <div style="flex: 1; text-align: center;">
      <h1>PROFORMA INVOICE</h1>
      <p>PNG Maritime College</p>
      <p>Updated Record of Student Throughout the Year</p>
    </div>
    <div style="flex: 0 0 auto;">
      <img src="../images/bird%20of%20paradise.png" alt="Papua New Guinea Emblem" style="max-height: 80px; max-width: 200px; height: auto;">
    </div>
  </div>

  <div class="invoice-info">
    <div class="info-section">
      <h3>Invoice Information</h3>
      <div class="info-row">
        <span class="info-label">PI Number:</span>
        <span class="info-value"><strong><?php echo htmlspecialchars($pi['pi_number']); ?></strong></span>
      </div>
      <div class="info-row">
        <span class="info-label">Date:</span>
        <span class="info-value"><?php echo date('d/m/Y', strtotime($pi['date'])); ?></span>
      </div>
      <?php if ($pi['revised_pi_number']): ?>
      <div class="info-row">
        <span class="info-label">Revised PI Number:</span>
        <span class="info-value"><strong><?php echo htmlspecialchars($pi['revised_pi_number']); ?></strong></span>
      </div>
      <div class="info-row">
        <span class="info-label">Revised Date:</span>
        <span class="info-value"><?php echo date('d/m/Y', strtotime($pi['revised_date'])); ?></span>
      </div>
      <?php endif; ?>
      <div class="info-row">
        <span class="info-label">Course Name:</span>
        <span class="info-value"><?php echo htmlspecialchars($pi['course_name']); ?></span>
      </div>
    </div>

    <div class="info-section">
      <h3>Student Information</h3>
      <div class="info-row">
        <span class="info-label">Student Name:</span>
        <span class="info-value"><?php echo htmlspecialchars($pi['student_name']); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Address:</span>
        <span class="info-value"><?php echo htmlspecialchars($pi['forwarding_address'] ?? 'N/A'); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Telephone:</span>
        <span class="info-value"><?php echo htmlspecialchars($pi['telephone'] ?? 'N/A'); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Mobile:</span>
        <span class="info-value"><?php echo htmlspecialchars($pi['mobile_number'] ?? 'N/A'); ?></span>
      </div>
    </div>
  </div>

  <div style="margin-top: 30px;">
    <h3 style="color: #1d4e89; border-bottom: 2px solid #1d4e89; padding-bottom: 10px;">Course Fee Details</h3>
    <table>
      <tr>
        <th>Description</th>
        <th class="text-right">Amount (PGK)</th>
      </tr>
      <tr>
        <td><strong>Course Fee</strong></td>
        <td class="text-right"><strong>PGK <?php echo number_format($pi['course_fee'], 2); ?></strong></td>
      </tr>
      <?php if ($pi['amendment_amount'] != 0): ?>
      <tr>
        <td>Amendment (<?php echo ucfirst($pi['amendment_reason']); ?>)</td>
        <td class="text-right" style="color: <?php echo $pi['amendment_amount'] > 0 ? '#dc3545' : '#28a745'; ?>;">
          <?php echo $pi['amendment_amount'] > 0 ? '+' : ''; ?>PGK <?php echo number_format($pi['amendment_amount'], 2); ?>
        </td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <?php if (!empty($payments)): ?>
  <div style="margin-top: 30px;">
    <h3 style="color: #1d4e89; border-bottom: 2px solid #1d4e89; padding-bottom: 10px;">Payments</h3>
    <table>
      <thead>
        <tr>
          <th>Receipt Number</th>
          <th>Payment Date</th>
          <th>Payment Method</th>
          <th class="text-right">Amount (PGK)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $payment): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($payment['receipt_number']); ?></strong></td>
            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
            <td class="text-right"><strong>PGK <?php echo number_format($payment['amount'], 2); ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background: #f8f9fa;">
          <td colspan="3" style="text-align: right; font-weight: bold; padding: 12px;">Total Payments:</td>
          <td class="text-right" style="font-weight: bold; padding: 12px;">PGK <?php echo number_format($pi['total_payments'], 2); ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>

  <div class="summary-box">
    <div class="summary-row">
      <span>Course Fee:</span>
      <span>PGK <?php echo number_format($pi['course_fee'], 2); ?></span>
    </div>
    <?php if ($pi['amendment_amount'] != 0): ?>
    <div class="summary-row">
      <span>Amendment (<?php echo ucfirst($pi['amendment_reason']); ?>):</span>
      <span style="color: <?php echo $pi['amendment_amount'] > 0 ? '#dc3545' : '#28a745'; ?>;">
        <?php echo $pi['amendment_amount'] > 0 ? '+' : ''; ?>PGK <?php echo number_format($pi['amendment_amount'], 2); ?>
      </span>
    </div>
    <?php endif; ?>
    <div class="summary-row">
      <span>Total Payments:</span>
      <span style="color: #28a745;">PGK <?php echo number_format($pi['total_payments'], 2); ?></span>
    </div>
    <div class="summary-row total">
      <span>Balance:</span>
      <span style="color: <?php echo $pi['balance'] > 0 ? '#dc3545' : '#28a745'; ?>;">
        <?php echo $pi['balance'] > 0 ? 'Outstanding: ' : 'Refund Due: '; ?>
        PGK <?php echo number_format(abs($pi['balance']), 2); ?>
      </span>
    </div>
  </div>

  <div class="footer">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
      <div>
        <strong>PI Issuing Officer:</strong><br>
        <?php echo htmlspecialchars($pi['issuing_officer_name'] ?? 'N/A'); ?><br>
        Date: <?php echo date('d/m/Y', strtotime($pi['date'])); ?>
      </div>
      <?php if ($pi['approval_by_registrar']): ?>
      <div>
        <strong>Approval by Registrar:</strong><br>
        <?php echo htmlspecialchars($pi['registrar_name']); ?><br>
        Date: <?php echo date('d/m/Y', strtotime($pi['approval_date'])); ?>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($pi['remarks']): ?>
    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
      <strong>Remarks:</strong><br>
      <?php echo nl2br(htmlspecialchars($pi['remarks'])); ?>
    </div>
    <?php endif; ?>
    <div class="myob-note" style="margin-top: 30px; text-align: center; color: #999; font-size: 11px;">
      <p><strong>Note:</strong> This Proforma Invoice should be reflected in MYOB accounting system. 
      The invoice represents an updated record of the student throughout the year and must be synchronized with MYOB for accurate financial reporting.</p>
    </div>
  </div>
</body>
</html>


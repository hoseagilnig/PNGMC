<?php
/**
 * Finance Dashboard
 * Standardized authentication and error handling
 */

// Use standardized auth guard
require_once __DIR__ . '/includes/auth_guard.php';
requireRole('finance');

// Load required files with __DIR__ for Linux compatibility
require_once __DIR__ . '/includes/menu_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/fee_helper.php';
require_once __DIR__ . '/includes/workflow_helper.php';

// Get comprehensive fee statistics
$stats = getFeeStatistics();

// Get workflow notifications for Finance
$notification_count = 0;
$workflow_notifications = [];
$workflow_tables_exist = false;
$conn_wf = getDBConnection();
if ($conn_wf) {
    $table_check = $conn_wf->prepare("SHOW TABLES LIKE 'workflow_notifications'");
    $table_check->execute();
    $workflow_tables_exist = $table_check->get_result()->num_rows > 0;
    $table_check->close();
    if ($workflow_tables_exist) {
        $notification_count = getNotificationCount('finance');
        $workflow_notifications = getUnreadNotifications('finance', 5);
    }
    $conn_wf->close();
}

// Get recent outstanding fees
$conn = getDBConnection();
$recent_outstanding = [];
$payment_mode_analysis = [];
$invoice_receipt_analysis = [];

if ($conn) {
    $table_check = $conn->prepare("SHOW TABLES LIKE 'student_fees'");
    $table_check->execute();
    $tables_exist = $table_check->get_result()->num_rows > 0;
    $table_check->close();
    
    if ($tables_exist) {
        // Get top 10 outstanding fees
        $result = $conn->query("SELECT 
            sf.*,
            s.first_name,
            s.last_name,
            s.student_number,
            p.program_name,
            DATEDIFF(CURDATE(), sf.due_date) as days_overdue
        FROM student_fees sf
        JOIN students s ON sf.student_id = s.student_id
        LEFT JOIN enrollments e ON s.student_id = e.student_id
        LEFT JOIN programs p ON e.program_id = p.program_id
        WHERE sf.outstanding_amount > 0
        ORDER BY sf.due_date ASC
        LIMIT 10");
        
        if ($result) {
            $recent_outstanding = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get payment mode analysis
        $payment_mode_analysis = getPaymentModeAnalysis('week');
        
        // Get invoice vs receipt analysis
        $invoice_receipt_analysis = getInvoiceReceiptAnalysis('week');
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finance Dashboard</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .user-dropdown {
      animation: fadeIn 0.2s ease-in;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
  <script>
    function toggleUserDropdown() {
      const dropdown = document.getElementById('userDropdown');
      const trigger = document.querySelector('.user-dropdown-trigger');
      
      if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';
        
        // On mobile, position dropdown relative to viewport at bottom
        if (window.innerWidth <= 767 && trigger) {
          dropdown.style.position = 'fixed';
          dropdown.style.right = '10px';
          dropdown.style.bottom = '80px';
          dropdown.style.top = 'auto';
          dropdown.style.left = 'auto';
          dropdown.style.zIndex = '99999';
          dropdown.style.maxHeight = (window.innerHeight - 100) + 'px';
          dropdown.style.overflow = 'visible';
        } else {
          // Desktop: position normally
          dropdown.style.position = 'absolute';
          dropdown.style.top = '100%';
          dropdown.style.right = '0';
          dropdown.style.bottom = 'auto';
          dropdown.style.left = 'auto';
        }
      } else {
        dropdown.style.display = 'none';
      }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const userInfo = document.querySelector('.user-info');
      const dropdown = document.getElementById('userDropdown');
      if (userInfo && dropdown && !userInfo.contains(event.target)) {
        dropdown.style.display = 'none';
      }
    });
    
    function markAsReadAndNavigate(notificationId, url) {
      // Mark notification as read via AJAX
      fetch('workflow_manager.php?mark_read=' + notificationId, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(() => {
        // Navigate to the action URL
        if (url) {
          window.location.href = url;
        } else {
          // Reload page to refresh notifications
          window.location.reload();
        }
      }).catch(() => {
        // If AJAX fails, still navigate
        if (url) {
          window.location.href = url;
        } else {
          window.location.reload();
        }
      });
    }
  </script>
</head>
<body>
    <header>
        <div class="logo">
            <a href="finance_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">Finance Dashboard</span>
            </a>
        </div>
        <div class="user-info" style="position: relative; display: flex; align-items: center; gap: 15px;">
            <?php if ($notification_count > 0): ?>
              <a href="workflow_manager.php" class="notification-bubble workflow-bubble" style="position: relative; display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #f57c00 0%, #e65100 100%); border-radius: 50%; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); animation: pulse-bubble 2s infinite; z-index: 100;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(245, 124, 0, 0.6), 0 2px 6px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2)';" onclick="event.preventDefault(); window.location.href='workflow_manager.php'; return false;">
                <span style="font-size: 1.5rem; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2)); pointer-events: none;">📬</span>
                <span class="bubble-count" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; font-size: 0.7rem; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.5); border: 2px solid white; animation: bounce-count 1s infinite; pointer-events: none;"><?php echo $notification_count; ?></span>
              </a>
            <?php endif; ?>
            
            <!-- User Profile Dropdown -->
            <div style="position: relative;">
                <div class="user-dropdown-trigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 5px; transition: background 0.2s;" onclick="toggleUserDropdown()" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='transparent'">
                    <span>👤</span>
                    <span>Logged in as <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    <span style="font-size: 0.8rem;">▼</span>
                </div>
            <div id="userDropdown" class="user-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 180px; z-index: 1000;">
                <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div style="font-size: 0.85rem; color: #666; margin-top: 4px;"><?php echo ucfirst($_SESSION['role']); ?> User</div>
                </div>
                <a href="logout.php" style="display: block; padding: 12px 16px; color: #dc3545; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'">
                    🚪 Logout
                </a>
            </div>
        </div>
        <?php echo getMobileMenuToggle(); ?>
    </header>

    <?php echo getSidebarOverlay(); ?>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="finance_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item <?php echo isActive('finance_dashboard.php'); ?>" href="finance_dashboard.php">Dashboard</a>
        <div class="menu-section">Fee Management</div>
        <a class="menu-item <?php echo isActive('fee_management.php'); ?>" href="fee_management.php">Fee Plans Setup</a>
        <a class="menu-item <?php echo isActive('automated_triggers.php'); ?>" href="automated_triggers.php">Automated Triggers</a>
        <a class="menu-item <?php echo isActive('payment_reminders.php'); ?>" href="payment_reminders.php">Payment Reminders</a>
        <div class="menu-section">Billing & Invoices</div>
        <a class="menu-item <?php echo isActive('billing.php'); ?>" href="billing.php">Billing</a>
        <a class="menu-item <?php echo isActive('invoices.php'); ?>" href="invoices.php">Invoices</a>
        <a class="menu-item <?php echo isActive('student_fees.php'); ?>" href="student_fees.php">Student Fees</a>
        <div class="menu-section">Workflow</div>
        <a class="menu-item <?php echo isActive('proforma_invoices.php'); ?>" href="proforma_invoices.php">Proforma Invoices</a>
        <a class="menu-item <?php echo isActive('student_schedules.php'); ?>" href="student_schedules.php">Student Schedules</a>
        <a class="menu-item <?php echo isActive('fees_monitor.php'); ?>" href="fees_monitor.php">Fees Monitor</a>
        <a class="menu-item <?php echo isActive('red_green_days.php'); ?>" href="red_green_days.php">Red & Green Days</a>
        <a class="menu-item <?php echo isActive('finance_to_sas.php'); ?>" href="finance_to_sas.php">Finance to SAS</a>
        <div class="menu-section">Reports</div>
        <a class="menu-item <?php echo isActive('financial_reports.php'); ?>" href="financial_reports.php">Financial Reports</a>
        <a class="menu-item <?php echo isActive('fee_reports.php'); ?>" href="fee_reports.php">Fee Reports & Analysis</a>
        <a class="menu-item <?php echo isActive('workflow_manager.php'); ?>" href="workflow_manager.php">Workflow Manager</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Finance Dashboard</h1>
        <p class="small">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>! Manage fees, billing, and financial operations.</p>
      </header>

      <!-- Recent Notifications Section -->
      <?php if (!empty($workflow_notifications)): ?>
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Recent Notifications</h2>
        <div class="main-card">
          <?php foreach ($workflow_notifications as $notif): ?>
            <div style="padding: 15px; border-left: 4px solid #1d4e89; margin-bottom: 10px; background: <?php echo $notif['status'] === 'unread' ? '#e7f3ff' : '#f8f9fa'; ?>; border-radius: 5px; cursor: pointer; transition: all 0.2s;" 
                 onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'" 
                 onmouseout="this.style.transform=''; this.style.boxShadow=''"
                 onclick="markAsReadAndNavigate(<?php echo $notif['notification_id']; ?>, '<?php echo htmlspecialchars($notif['action_url'] ?? 'workflow_manager.php', ENT_QUOTES); ?>')">
              <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                <strong style="color: #1d4e89;">
                  <?php if ($notif['notification_type'] === 'urgent'): ?>
                    ⚠️ 
                  <?php elseif ($notif['notification_type'] === 'information'): ?>
                    ℹ️ 
                  <?php elseif ($notif['notification_type'] === 'approval_request'): ?>
                    ✓ 
                  <?php else: ?>
                    📬 
                  <?php endif; ?>
                  <?php echo htmlspecialchars($notif['title']); ?>
                </strong>
                <span style="font-size: 12px; color: #666;"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></span>
              </div>
              <p style="color: #555; margin: 0; font-size: 14px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
              <div style="margin-top: 8px; font-size: 12px; color: #999;">
                From: <?php echo ucfirst(str_replace('studentservices', 'Student Services', $notif['from_department'])); ?>
                <?php if ($notif['application_number']): ?>
                  | Application: <?php echo htmlspecialchars($notif['application_number']); ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <div style="text-align: center; margin-top: 15px;">
            <a href="workflow_manager.php" style="color: #1d4e89; text-decoration: underline; font-weight: 600;">View All Notifications →</a>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- Key Statistics Cards -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Overview</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='student_fees.php?filter=outstanding'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Total Outstanding</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #dc3545;">PGK <?php echo number_format($stats['total_outstanding'], 2); ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Unpaid fees</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">💰</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='fee_reports.php?view=receipts'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Receipts Today</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #28a745;">PGK <?php echo number_format($stats['total_receipts_today'], 2); ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Today's payments</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📊</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='fee_reports.php?view=receipts&period=week'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Weekly Receipts</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #17a2b8;">PGK <?php echo number_format($stats['total_receipts_week'], 2); ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Last 7 days</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📈</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='student_fees.php?filter=overdue'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Overdue Fees</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;">PGK <?php echo number_format($stats['overdue_fees'], 2); ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Past due date</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">⚠️</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='student_fees.php?filter=holds'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Financial Holds</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #dc3545;"><?php echo $stats['students_with_holds']; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Students on hold</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">🔒</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='payment_reminders.php'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Pending Reminders</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #ffc107;"><?php echo $stats['pending_reminders']; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">To be sent</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📧</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Recent Outstanding Fees -->
      <?php if (!empty($recent_outstanding)): ?>
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Recent Outstanding Fees</h2>
        <div class="main-card">
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background: #1d4e89; color: white;">
                <th style="padding: 12px; text-align: left;">Student</th>
                <th style="padding: 12px; text-align: left;">Program</th>
                <th style="padding: 12px; text-align: left;">Fee Type</th>
                <th style="padding: 12px; text-align: right;">Amount</th>
                <th style="padding: 12px; text-align: right;">Outstanding</th>
                <th style="padding: 12px; text-align: left;">Due Date</th>
                <th style="padding: 12px; text-align: center;">Days Overdue</th>
                <th style="padding: 12px; text-align: left;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_outstanding as $fee): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                  <td style="padding: 12px;"><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?><br><small style="color: #666;"><?php echo htmlspecialchars($fee['student_number']); ?></small></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($fee['program_name'] ?? 'N/A'); ?></td>
                  <td style="padding: 12px;"><?php echo ucfirst(str_replace('_', ' ', $fee['fee_type'])); ?></td>
                  <td style="padding: 12px; text-align: right;">PGK <?php echo number_format($fee['net_amount'], 2); ?></td>
                  <td style="padding: 12px; text-align: right; font-weight: bold; color: #dc3545;">PGK <?php echo number_format($fee['outstanding_amount'], 2); ?></td>
                  <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                  <td style="padding: 12px; text-align: center;">
                    <?php if ($fee['days_overdue'] > 0): ?>
                      <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 3px; font-size: 0.85rem;">
                        <?php echo $fee['days_overdue']; ?> days
                      </span>
                    <?php else: ?>
                      <span style="color: #28a745;">Due soon</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding: 12px;">
                    <span style="padding: 4px 8px; border-radius: 3px; background: <?php 
                      echo $fee['status'] === 'overdue' ? '#dc3545' : ($fee['status'] === 'partial' ? '#ffc107' : '#17a2b8');
                    ?>; color: white; font-size: 0.85rem;">
                      <?php echo ucfirst($fee['status']); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div style="text-align: center; margin-top: 15px;">
            <a href="student_fees.php?filter=outstanding" style="color: #1d4e89; text-decoration: underline;">View All Outstanding Fees →</a>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- Payment Mode Analysis -->
      <?php if (!empty($payment_mode_analysis)): ?>
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Payment Mode Analysis (Last 7 Days)</h2>
        <div class="main-card">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($payment_mode_analysis as $mode): ?>
              <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; text-transform: uppercase;"><?php echo ucfirst(str_replace('_', ' ', $mode['payment_method'])); ?></h3>
                <div style="font-size: 2rem; font-weight: bold; color: #1d4e89;">PGK <?php echo number_format($mode['total_amount'], 2); ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;"><?php echo $mode['count']; ?> transactions</p>
              </div>
            <?php endforeach; ?>
          </div>
          <div style="text-align: center; margin-top: 15px;">
            <a href="fee_reports.php?view=payment_mode" style="color: #1d4e89; text-decoration: underline;">View Detailed Analysis →</a>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <!-- Quick Actions -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Quick Actions</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
          <a href="fee_management.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">⚙️</div>
            <h3 style="margin: 0; color: #1d4e89;">Fee Plans Setup</h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Configure fee plans</p>
          </a>

          <a href="automated_triggers.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">🔄</div>
            <h3 style="margin: 0; color: #1d4e89;">Automated Triggers</h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Set RED/GREEN DAYS</p>
          </a>

          <a href="payment_reminders.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">📧</div>
            <h3 style="margin: 0; color: #1d4e89;">Payment Reminders</h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Manage reminders</p>
          </a>

          <a href="fee_reports.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">📊</div>
            <h3 style="margin: 0; color: #1d4e89;">Fee Reports</h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">View all reports</p>
          </a>

          <a href="student_fees.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">👥</div>
            <h3 style="margin: 0; color: #1d4e89;">Student Fees</h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Manage student fees</p>
          </a>

          <a href="invoices.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">🧾</div>
            <h3 style="margin: 0; color: #1d4e89;">Generate Invoice</h3>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Create new invoice</p>
          </a>
        </div>
      </section>
    </div>
  </div>

  <?php require_once 'includes/chatbot_simple.php'; ?>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>

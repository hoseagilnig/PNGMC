<?php
/**
 * Student Services Dashboard
 * Standardized authentication and error handling
 */

// Use standardized auth guard
require_once __DIR__ . '/includes/auth_guard.php';
requireRole(['studentservices', 'sas']);

// Load required files with __DIR__ for Linux compatibility
require_once __DIR__ . '/includes/menu_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/workflow_helper.php';

// Get statistics
$conn = getDBConnection();
$stats = [];
if ($conn) {
    // Check if application_type column exists
    // Note: SHOW COLUMNS doesn't support placeholders, so we use direct query with escaped column name
    $col_name = $conn->real_escape_string('application_type');
    $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE '{$col_name}'");
    $has_application_type = $col_check && $col_check->num_rows > 0;
    if ($col_check) $col_check->close();
    
    // Check if requirements_met column exists
    $col_name = $conn->real_escape_string('requirements_met');
    $req_check = $conn->query("SHOW COLUMNS FROM applications LIKE '{$col_name}'");
    $has_requirements_met = $req_check && $req_check->num_rows > 0;
    if ($req_check) $req_check->close();
    
    if ($has_application_type) {
        // School Leaver Applications (new students - Grade 10/12)
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE (application_type = 'new_student' OR application_type IS NULL) AND status = 'submitted'");
        $stats['school_leaver_pending'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE (application_type = 'new_student' OR application_type IS NULL) AND status = 'under_review'");
        $stats['school_leaver_review'] = $result->fetch_assoc()['count'];
        
        // Candidates Returning Applications (after sea service)
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE (application_type = 'continuing_student_solas' OR application_type = 'continuing_student_next_level') AND status = 'submitted'");
        $stats['continuing_pending'] = $result->fetch_assoc()['count'];
        
        if ($has_requirements_met) {
            $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE (application_type = 'continuing_student_solas' OR application_type = 'continuing_student_next_level') AND requirements_met = FALSE AND status != 'ineligible'");
            $stats['requirements_check'] = $result->fetch_assoc()['count'];
        } else {
            $stats['requirements_check'] = 0;
        }
    } else {
        // Fallback: treat all applications as new students if column doesn't exist
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'submitted'");
        $stats['school_leaver_pending'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'under_review'");
        $stats['school_leaver_review'] = $result->fetch_assoc()['count'];
        
        $stats['continuing_pending'] = 0;
        $stats['requirements_check'] = 0;
    }
    
    // Applications ready for HOD
    if ($has_requirements_met) {
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE requirements_met = TRUE AND status = 'hod_review'");
        $stats['hod_review'] = $result->fetch_assoc()['count'];
    } else {
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'hod_review'");
        $stats['hod_review'] = $result->fetch_assoc()['count'];
    }
    
    // Accepted applications
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'");
    $stats['accepted'] = $result->fetch_assoc()['count'];
    
    // Correspondence pending
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'correspondence_sent'");
    $stats['correspondence_pending'] = $result->fetch_assoc()['count'];
    
    // Ready for enrollment
    $enroll_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'enrollment_ready'");
    if ($enroll_check && $enroll_check->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE enrollment_ready = TRUE AND enrolled = FALSE");
        $stats['ready_enroll'] = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
        if ($enroll_check) $enroll_check->close();
    } else {
        $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE enrolled = FALSE AND status = 'accepted'");
        $stats['ready_enroll'] = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
        if ($enroll_check) $enroll_check->close();
    }
    
    // Support tickets
    $result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'");
    $stats['open_tickets'] = $result->fetch_assoc()['count'];
    
    $conn->close();
}

// Check if workflow tables exist
$conn = getDBConnection();
$workflow_tables_exist = false;
if ($conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'workflow_notifications'");
    $workflow_tables_exist = $table_check->num_rows > 0;
    $conn->close();
}

// Get workflow notifications (only if tables exist)
$workflow_notifications = [];
$notification_count = 0;
if ($workflow_tables_exist) {
    $workflow_notifications = getUnreadNotifications('studentservices', 5);
    $notification_count = getNotificationCount('studentservices');
}

// Get count of new Finance transfers (only those newer than last viewed)
$finance_transfer_count = 0;
$conn = getDBConnection();
if ($conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'finance_to_sas_transfers'");
    $tables_exist = $table_check && $table_check->num_rows > 0;
    if ($tables_exist) {
        // Get last viewed timestamp from session
        $last_viewed = isset($_SESSION['last_viewed_transfers']) ? $_SESSION['last_viewed_transfers'] : 0;
        
        if ($last_viewed > 0) {
            // Only count transfers newer than last viewed timestamp
            $last_viewed_date = date('Y-m-d H:i:s', $last_viewed);
            $result = $conn->query("SELECT COUNT(*) as count FROM finance_to_sas_transfers WHERE transferred_at > '" . $conn->real_escape_string($last_viewed_date) . "'");
        } else {
            // If never viewed, count transfers from last 7 days as "new"
            $result = $conn->query("SELECT COUNT(*) as count FROM finance_to_sas_transfers WHERE transferred_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        }
        
        if ($result) {
            $row = $result->fetch_assoc();
            $finance_transfer_count = intval($row['count'] ?? 0);
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
  <title>Student Services Dashboard</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.05); }
    }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .notification-badge {
      animation: pulse 2s infinite;
      display: inline-block;
    }
    .highlight-menu-item {
      background: #e7f3ff !important;
      border-left: 4px solid #1d4e89 !important;
      font-weight: 600 !important;
      color: #1d4e89 !important;
    }
    .alert-banner {
      animation: blink 3s infinite;
    }
    .user-dropdown {
      animation: fadeIn 0.2s ease-in;
    }
    
    /* Ensure dropdown and logout button are always visible */
    header {
      overflow: visible !important;
    }
    
    .user-info {
      overflow: visible !important;
    }
    
    .user-dropdown {
      overflow: visible !important;
      z-index: 99999 !important;
    }
    
    .user-dropdown a[href*="logout"] {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
      pointer-events: auto !important;
      z-index: 100000 !important;
      min-height: 44px !important;
      padding: 14px 16px !important;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @keyframes slideInBounce {
      0% {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
      }
      60% {
        opacity: 1;
        transform: translateY(5px) scale(1.02);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-8px);
      }
    }
    .notification-banner {
      transition: all 0.3s ease;
    }
    .notification-banner:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.15), 0 2px 6px rgba(0,0,0,0.1) !important;
    }
    
    /* Responsive styles for notification bubbles */
    @media (max-width: 768px) {
      .user-info {
        gap: 10px !important;
      }
      .notification-bubble {
        width: 40px !important;
        height: 40px !important;
      }
      .notification-bubble span:first-child {
        font-size: 1.3rem !important;
      }
      .bubble-count {
        min-width: 18px !important;
        height: 18px !important;
        font-size: 0.65rem !important;
        top: -3px !important;
        right: -3px !important;
      }
      .user-dropdown-trigger span:nth-child(2) {
        display: none;
      }
    }
    
    @media (max-width: 480px) {
      .notification-bubble {
        width: 38px !important;
        height: 38px !important;
      }
      .notification-bubble span:first-child {
        font-size: 1.2rem !important;
      }
      .bubble-count {
        min-width: 16px !important;
        height: 16px !important;
        font-size: 0.6rem !important;
        padding: 0 4px !important;
      }
    }
    
    /* Responsive styles for notification banners */
    @media (max-width: 768px) {
      .notification-banner {
        padding: 15px !important;
        margin-bottom: 15px !important;
        border-radius: 10px !important;
      }
      .notification-banner .banner-content {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
      }
      .notification-banner .banner-icon {
        font-size: 2.5rem !important;
      }
      .notification-banner .banner-text {
        width: 100% !important;
      }
      .notification-banner .banner-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
        margin-bottom: 10px !important;
      }
      .notification-banner .banner-text strong {
        font-size: 1.1rem !important;
        line-height: 1.4 !important;
      }
      .notification-banner .banner-text p {
        font-size: 0.9rem !important;
      }
      .notification-banner .banner-button {
        width: 100% !important;
        justify-content: center !important;
        padding: 12px 20px !important;
        font-size: 0.9rem !important;
      }
    }
    
    @media (max-width: 480px) {
      .notification-banner {
        padding: 12px !important;
        border-radius: 8px !important;
        border-left-width: 4px !important;
      }
      .notification-banner .banner-icon {
        font-size: 2rem !important;
      }
      .notification-banner .banner-text strong {
        font-size: 1rem !important;
      }
      .notification-banner .banner-text p {
        font-size: 0.85rem !important;
      }
      .notification-banner .banner-button {
        padding: 10px 16px !important;
        font-size: 0.85rem !important;
      }
      .notification-banner .banner-header span {
        font-size: 0.7rem !important;
        padding: 3px 8px !important;
      }
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
  </script>
  <style>
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-3px); }
    }
    @keyframes pulse-bubble {
      0%, 100% { 
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4), 0 2px 4px rgba(0,0,0,0.2);
      }
      50% { 
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(25, 118, 210, 0.6), 0 2px 6px rgba(0,0,0,0.3);
      }
    }
    @keyframes bounce-count {
      0%, 100% { 
        transform: translateY(0) scale(1);
      }
      50% { 
        transform: translateY(-3px) scale(1.1);
      }
    }
    .notification-badge {
      animation: pulse 2s infinite;
    }
    .notification-bubble {
      cursor: pointer;
    }
    .notification-bubble:hover {
      transform: scale(1.1) !important;
    }
    .bubble-count {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
  </style>
</head>
<body>
    <header style="overflow: visible !important; z-index: 1000;">
        <div class="logo">
            <a href="student_service_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">Student Services Dashboard</span>
            </a>
        </div>
        <div class="user-info" style="position: relative; display: flex; align-items: center; gap: 15px; overflow: visible !important; z-index: 10000;">
            <!-- Notification Indicators -->
            <?php if ($finance_transfer_count > 0 || $notification_count > 0): ?>
              <div style="display: flex; align-items: center; gap: 10px;">
                <?php if ($finance_transfer_count > 0): ?>
                  <a href="sas_received_data.php" class="notification-bubble finance-bubble" style="position: relative; display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%); border-radius: 50%; text-decoration: none; box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4), 0 2px 4px rgba(0,0,0,0.2); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); animation: pulse-bubble 2s infinite;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(25, 118, 210, 0.6), 0 2px 6px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(25, 118, 210, 0.4), 0 2px 4px rgba(0,0,0,0.2)';">
                    <span style="font-size: 1.5rem; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));">💰</span>
                    <span class="bubble-count" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; font-size: 0.7rem; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.5); border: 2px solid white; animation: bounce-count 1s infinite;"><?php echo $finance_transfer_count; ?></span>
                  </a>
                <?php endif; ?>
                <?php if ($notification_count > 0): ?>
                  <a href="workflow_manager.php" class="notification-bubble workflow-bubble" style="position: relative; display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #f57c00 0%, #e65100 100%); border-radius: 50%; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); animation: pulse-bubble 2s infinite 0.5s;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(245, 124, 0, 0.6), 0 2px 6px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2)';">
                    <span style="font-size: 1.5rem; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));">📬</span>
                    <span class="bubble-count" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; font-size: 0.7rem; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.5); border: 2px solid white; animation: bounce-count 1s infinite 0.3s;"><?php echo $notification_count; ?></span>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
            <!-- User Profile Dropdown -->
            <div style="position: relative; z-index: 10000; overflow: visible;">
                <div class="user-dropdown-trigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 5px; transition: background 0.2s;" onclick="toggleUserDropdown()" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='transparent'">
                    <span>👤</span>
                    <span>Logged in as <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    <span style="font-size: 0.8rem;">▼</span>
                </div>
                <div id="userDropdown" class="user-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.25); min-width: 200px; z-index: 99999; overflow: visible;">
                    <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
                        <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 4px;"><?php echo ucfirst($_SESSION['role']); ?> User</div>
                    </div>
                    <a href="logout.php" style="display: block; padding: 14px 16px; color: #dc3545; text-decoration: none; transition: background 0.2s; min-height: 44px; line-height: 1.4; position: relative; z-index: 1;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'">
                        🚪 Logout
                    </a>
                </div>
            </div>
        </div>
        <?php echo getMobileMenuToggle(); ?>
    </header>

    <?php echo getSidebarOverlay(); ?>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="student_service_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item <?php echo isActive('student_service_dashboard.php'); ?>" href="student_service_dashboard.php">Dashboard</a>
        <div class="menu-section">Application Processing</div>
        <a class="menu-item <?php echo isActive('applications.php'); ?>" href="applications.php">School Leavers</a>
        <a class="menu-item <?php echo isActive('continuing_students.php'); ?>" href="continuing_students.php">Candidates Returning</a>
        <div class="menu-section">Student Management</div>
        <a class="menu-item <?php echo isActive('student_records.php'); ?>" href="student_records.php">Student Records</a>
        <a class="menu-item <?php echo isActive('advising.php'); ?>" href="advising.php">Advising</a>
        <a class="menu-item <?php echo isActive('support_tickets.php'); ?>" href="support_tickets.php">Support Tickets</a>
        <div class="menu-section">Workflow</div>
        <a class="menu-item <?php echo isActive('proforma_invoices.php'); ?>" href="proforma_invoices.php">Proforma Invoices</a>
        <a class="menu-item <?php echo isActive('withdrawal_advice.php'); ?>" href="withdrawal_advice.php">Withdrawal Advice</a>
        <a class="menu-item <?php echo isActive('disciplinary_advice.php'); ?>" href="disciplinary_advice.php">Disciplinary Advice</a>
        <a class="menu-item <?php echo isActive('student_schedules.php'); ?>" href="student_schedules.php">Student Schedules</a>
        <a class="menu-item <?php echo isActive('fees_monitor.php'); ?>" href="fees_monitor.php">Fees Monitor</a>
        <a class="menu-item <?php echo isActive('red_green_days.php'); ?>" href="red_green_days.php">Red & Green Days</a>
        <a class="menu-item <?php echo isActive('sas_received_data.php'); ?> <?php echo $finance_transfer_count > 0 ? 'highlight-menu-item' : ''; ?>" href="sas_received_data.php">
          💰 Received from Finance
        </a>
          <?php if ($finance_transfer_count > 0): ?>
            <span class="notification-badge" style="background: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: 8px; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);"><?php echo $finance_transfer_count; ?> NEW</span>
          <?php endif; ?>
        </a>
        <a class="menu-item <?php echo isActive('workflow_manager.php'); ?>" href="workflow_manager.php">
          Workflow Manager
          <?php if ($notification_count > 0): ?>
            <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;"><?php echo $notification_count; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Student Services Dashboard</h1>
        <p class="small">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>! Process applications, manage student records, and handle student services.</p>
        <?php if (!$workflow_tables_exist): ?>
          <div style="background: #d1ecf1; border-left: 4px solid #0c5460; padding: 12px; margin-top: 15px; border-radius: 5px;">
            <strong>ℹ️ Workflow System Not Set Up</strong> 
            <p style="margin: 5px 0 0 0;">To enable cross-department workflow and notifications, please run the setup script: 
            <a href="../database/create_workflow_tables.php" style="color: #1d4e89; text-decoration: underline; font-weight: bold;">Create Workflow Tables</a></p>
          </div>
        <?php endif; ?>
      </header>

      <!-- School Leavers Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">School Leavers (New Students)</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=submitted'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Pending Applications</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1d4e89;"><?php echo $stats['school_leaver_pending'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">New submissions</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📋</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=under_review'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Under Review</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?php echo $stats['school_leaver_review'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Being processed</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">🔍</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Candidates Returning Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Candidates Returning (After Sea Service)</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='continuing_students.php?status=submitted'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Pending Applications</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1d4e89;"><?php echo $stats['continuing_pending'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Awaiting processing</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">🔄</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='continuing_students.php'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Check Requirements</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?php echo $stats['requirements_check'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Verify documents</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">✅</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Application Workflow Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Application Workflow</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=hod_review'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">HOD Review</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?php echo $stats['hod_review'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Awaiting HOD decision</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">👨‍💼</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=accepted'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Accepted</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #388e3c;"><?php echo $stats['accepted'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Ready for correspondence</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">✉️</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=correspondence_sent'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Correspondence Sent</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1976d2;"><?php echo $stats['correspondence_pending'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Awaiting response</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📧</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?enrollment_ready=1'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Ready to Enroll</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #7b1fa2;"><?php echo $stats['ready_enroll'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Complete enrollment</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">🎓</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Student Management Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Student Management</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <a href="student_records.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">📚</div>
            <strong>Student Records</strong>
            <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Manage student information</p>
          </a>

          <a href="advising.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">💬</div>
            <strong>Advising</strong>
            <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Student advising & appointments</p>
          </a>

          <a href="support_tickets.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">🎫</div>
            <strong>Support Tickets</strong>
            <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;"><?php echo $stats['open_tickets'] ?? 0; ?> open tickets</p>
          </a>
        </div>
      </section>
    </div>
  </div>

  <?php require_once 'includes/chatbot_simple.php'; ?>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>

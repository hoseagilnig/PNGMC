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
      padding: 10px 20px !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      width: 100% !important;
      box-sizing: border-box !important;
      gap: 15px !important;
    }
    /* Force header layout with !important to override external CSS and inline styles */
    body > header,
    header[style] {
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      gap: 15px !important;
      width: 100% !important;
      max-width: 100vw !important;
      box-sizing: border-box !important;
      overflow: visible !important;
      padding: 10px 20px !important;
      position: relative !important;
      min-height: 50px !important;
    }
    
    /* CRITICAL: Force user-info to always be visible - override ALL possible hiding rules */
    .user-info,
    header .user-info,
    body > header .user-info,
    header .user-info[style],
    body > header .user-info[style],
    div.user-info,
    header div.user-info,
    body > header div.user-info {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 10000 !important;
      flex: 0 0 auto !important;
      flex-shrink: 0 !important;
      min-width: fit-content !important;
      max-width: none !important;
      width: auto !important;
      margin-left: auto !important;
      overflow: visible !important;
      clip: auto !important;
      clip-path: none !important;
      transform: none !important;
      left: auto !important;
      right: auto !important;
      top: auto !important;
      bottom: auto !important;
      height: auto !important;
      min-height: fit-content !important;
      max-height: none !important;
    }
    
    /* CRITICAL: Force user-dropdown-trigger to always be visible */
    .user-dropdown-trigger,
    header .user-dropdown-trigger,
    body > header .user-dropdown-trigger,
    header .user-dropdown-trigger[style],
    body > header .user-dropdown-trigger[style],
    div.user-dropdown-trigger,
    header div.user-dropdown-trigger,
    body > header div.user-dropdown-trigger {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      width: auto !important;
      min-width: fit-content !important;
      flex-shrink: 0 !important;
      position: relative !important;
      clip: auto !important;
      clip-path: none !important;
      transform: none !important;
      height: auto !important;
      min-height: fit-content !important;
      max-height: none !important;
    }
    
    /* CRITICAL: Force all spans inside user-dropdown-trigger to be visible */
    .user-dropdown-trigger span,
    header .user-dropdown-trigger span,
    body > header .user-dropdown-trigger span {
      display: inline-block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }
    
    /* RESPONSIVE: Ensure user-dropdown-trigger is visible on ALL screen sizes */
    /* Large Desktop (1400px+) */
    @media (min-width: 1400px) {
      .user-dropdown-trigger,
      header .user-dropdown-trigger,
      body > header .user-dropdown-trigger {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 6px 10px !important;
        gap: 6px !important;
        font-size: 0.9rem !important;
      }
      .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2) {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.9rem !important;
        max-width: none !important;
        overflow: visible !important;
      }
    }
    
    /* Desktop (1200px - 1399px) */
    @media (min-width: 1200px) and (max-width: 1399px) {
      .user-dropdown-trigger,
      header .user-dropdown-trigger,
      body > header .user-dropdown-trigger {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 5px 9px !important;
        gap: 5px !important;
        font-size: 0.85rem !important;
      }
      .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2) {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.85rem !important;
        max-width: 350px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
    }
    
    /* Tablet Landscape (992px - 1199px) */
    @media (min-width: 992px) and (max-width: 1199px) {
      .user-dropdown-trigger,
      header .user-dropdown-trigger,
      body > header .user-dropdown-trigger {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 5px 8px !important;
        gap: 5px !important;
        font-size: 0.8rem !important;
      }
      .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2) {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.8rem !important;
        max-width: 280px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
    }
    
    /* Tablet Portrait (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991px) {
      .user-dropdown-trigger,
      header .user-dropdown-trigger,
      body > header .user-dropdown-trigger {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 4px 7px !important;
        gap: 4px !important;
        font-size: 0.75rem !important;
      }
      .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2) {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.75rem !important;
        max-width: 220px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
    }
    
    /* Mobile (480px - 767px) */
    @media (min-width: 480px) and (max-width: 767px) {
      .user-dropdown-trigger,
      header .user-dropdown-trigger,
      body > header .user-dropdown-trigger {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 4px 6px !important;
        gap: 3px !important;
        font-size: 0.75rem !important;
      }
      .user-dropdown-trigger span:first-child,
      header .user-dropdown-trigger span:first-child {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 1.1rem !important;
      }
      .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2) {
        display: none !important; /* Hide text on mobile to save space */
      }
      .user-dropdown-trigger span:last-child,
      header .user-dropdown-trigger span:last-child {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.7rem !important;
      }
    }
    
    /* Small Mobile (below 480px) */
    @media (max-width: 479px) {
      .user-dropdown-trigger,
      header .user-dropdown-trigger,
      body > header .user-dropdown-trigger {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 3px 5px !important;
        gap: 2px !important;
        font-size: 0.7rem !important;
      }
      .user-dropdown-trigger span:first-child,
      header .user-dropdown-trigger span:first-child {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 1rem !important;
      }
      .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2) {
        display: none !important; /* Hide text on small mobile */
      }
      .user-dropdown-trigger span:last-child,
      header .user-dropdown-trigger span:last-child {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.6rem !important;
      }
    }
    body > header .logo,
    header .logo[style] {
      min-width: 0 !important;
      flex: 0 1 auto !important;
      max-width: 30% !important;
      overflow: visible !important;
    }
    body > header .logo a,
    header .logo a[style] {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      min-width: 0 !important;
      overflow: visible !important;
    }
    body > header .logo img,
    header .logo img[style] {
      flex-shrink: 0 !important;
      width: auto !important;
      height: 35px !important;
      max-width: 50px !important;
      object-fit: contain !important;
    }
    body > header .logo span,
    header .logo span[style] {
      overflow: visible !important;
      text-overflow: clip !important;
      white-space: nowrap !important;
      font-size: 0.9rem !important;
      flex-shrink: 1 !important;
      display: inline-block !important;
      max-width: none !important;
    }
    body > header .user-info,
    header .user-info[style] {
      flex: 0 0 auto !important;
      min-width: fit-content !important;
      flex-shrink: 0 !important;
      overflow: visible !important;
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 10000 !important;
      width: auto !important;
      max-width: none !important;
      margin-left: auto !important;
    }
    body > header .user-dropdown-trigger,
    header .user-dropdown-trigger[style] {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      width: auto !important;
      min-width: fit-content !important;
      flex-shrink: 0 !important;
    }
    
    /* Ensure user-info is always visible on desktop/laptop screens */
    @media (min-width: 768px) {
      body > header,
      header[style] {
        overflow: visible !important;
        max-width: 100vw !important;
        display: flex !important;
        justify-content: space-between !important;
      }
      body > header .user-info,
      header .user-info[style],
      body > header div.user-info,
      header div.user-info[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 10000 !important;
        flex: 0 0 auto !important;
        flex-shrink: 0 !important;
        min-width: fit-content !important;
        max-width: none !important;
        width: auto !important;
        margin-left: auto !important;
        order: 999 !important;
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: auto !important;
        min-width: fit-content !important;
        flex-shrink: 0 !important;
      }
      body > header .logo,
      header .logo[style] {
        flex: 0 1 auto !important;
        max-width: 30% !important;
        min-width: 0 !important;
        order: 1 !important;
      }
    }
    
    /* Laptop L (1440px) - Ensure profile is always visible */
    @media (min-width: 1440px) {
      body > header .user-info,
      header .user-info[style],
      body > header div.user-info,
      header div.user-info[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 10000 !important;
        flex: 0 0 auto !important;
        flex-shrink: 0 !important;
        min-width: fit-content !important;
        max-width: none !important;
        width: auto !important;
        margin-left: auto !important;
        margin-right: 340px !important;
        order: 999 !important;
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: auto !important;
        min-width: fit-content !important;
        flex-shrink: 0 !important;
        padding: 6px 10px !important;
        gap: 6px !important;
      }
      body > header .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2)[style] {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        overflow: visible !important;
        white-space: nowrap !important;
        max-width: none !important;
      }
    }
    
    /* Desktop/Laptop (1200px - 1399px) - Add margin for profile */
    @media (min-width: 1200px) and (max-width: 1399px) {
      body > header .user-info,
      header .user-info[style],
      body > header div.user-info,
      header div.user-info[style] {
        margin-right: 220px !important;
      }
    }
    
    /* Tablet sizes (768px - 1199px) - Ensure profile is always visible */
    @media (min-width: 768px) and (max-width: 1199px) {
      body > header .user-info,
      header .user-info[style],
      body > header div.user-info,
      header div.user-info[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 10000 !important;
        flex: 0 0 auto !important;
        flex-shrink: 0 !important;
        min-width: fit-content !important;
        max-width: none !important;
        width: auto !important;
        margin-left: auto !important;
        order: 999 !important;
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: auto !important;
        min-width: fit-content !important;
        flex-shrink: 0 !important;
      }
    }
    body > header .user-dropdown-trigger span:nth-child(2),
    header .user-dropdown-trigger span:nth-child(2)[style] {
      overflow: visible !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      min-width: fit-content !important;
      max-width: none !important;
      display: inline-block !important;
    }
    /* Responsive breakpoints for header */
    
    /* Large Desktop (1400px and above) */
    @media (min-width: 1400px) {
      body > header,
      header[style] {
        padding: 12px 25px !important;
        gap: 20px !important;
      }
      body > header .logo,
      header .logo[style] {
        max-width: 50% !important;
      }
      body > header .logo span,
      header .logo span[style] {
        font-size: 0.95rem !important;
      }
      body > header .user-info,
      header .user-info[style],
      body > header div.user-info,
      header div.user-info[style] {
        margin-right: 280px !important;
      }
    }
    
    /* Desktop (1200px - 1399px) */
    @media (max-width: 1399px) and (min-width: 1200px) {
      body > header,
      header[style] {
        padding: 10px 20px !important;
        gap: 15px !important;
      }
      body > header .logo,
      header .logo[style] {
        max-width: 45% !important;
      }
      body > header .logo span,
      header .logo span[style] {
        font-size: 0.9rem !important;
      }
      body > header .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2)[style] {
        max-width: 300px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
      body > header .user-info,
      header .user-info[style],
      body > header div.user-info,
      header div.user-info[style] {
        margin-right: 220px !important;
      }
    }
    
    /* Tablet Landscape (992px - 1199px) */
    @media (max-width: 1199px) and (min-width: 992px) {
      body > header,
      header[style] {
        padding: 10px 15px !important;
        gap: 12px !important;
      }
      body > header .logo,
      header .logo[style] {
        max-width: 30% !important;
      }
      body > header .logo img,
      header .logo img[style] {
        height: 30px !important;
        max-width: 40px !important;
      }
      body > header .logo span,
      header .logo span[style] {
        font-size: 0.85rem !important;
      }
      body > header .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2)[style] {
        max-width: 250px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.8rem !important;
      }
    }
    
    /* Tablet Portrait (768px - 991px) */
    @media (max-width: 991px) and (min-width: 768px) {
      body > header,
      header[style] {
        padding: 8px 12px !important;
        gap: 10px !important;
      }
      body > header .logo,
      header .logo[style] {
        max-width: 35% !important;
      }
      body > header .logo img,
      header .logo img[style] {
        height: 28px !important;
        max-width: 35px !important;
      }
      body > header .logo span,
      header .logo span[style] {
        font-size: 0.75rem !important;
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        padding: 4px 6px !important;
        gap: 4px !important;
      }
      body > header .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2)[style] {
        max-width: 200px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 0.75rem !important;
      }
    }
    
    /* Mobile (480px - 767px) */
    @media (max-width: 767px) and (min-width: 480px) {
      body > header,
      header[style] {
        padding: 8px 10px !important;
        gap: 8px !important;
        flex-wrap: wrap !important;
      }
      body > header .logo,
      header .logo[style] {
        max-width: 60% !important;
        flex: 0 1 auto !important;
      }
      body > header .logo img,
      header .logo img[style] {
        height: 26px !important;
        max-width: 30px !important;
      }
      body > header .logo span,
      header .logo span[style] {
        display: none !important;
      }
      body > header .user-info,
      header .user-info[style] {
        flex: 0 0 auto !important;
        width: auto !important;
        margin-left: auto !important;
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        padding: 4px 6px !important;
        gap: 3px !important;
      }
      body > header .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2)[style] {
        display: none !important;
      }
      body > header .user-dropdown-trigger span:first-child,
      header .user-dropdown-trigger span:first-child[style] {
        font-size: 1.1rem !important;
      }
    }
    
    /* Small Mobile (below 480px) */
    @media (max-width: 479px) {
      body > header,
      header[style] {
        padding: 6px 8px !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
      }
      body > header .logo,
      header .logo[style] {
        max-width: 50% !important;
        flex: 0 1 auto !important;
      }
      body > header .logo img,
      header .logo img[style] {
        height: 24px !important;
        max-width: 28px !important;
      }
      body > header .logo span,
      header .logo span[style] {
        display: none !important;
      }
      body > header .user-info,
      header .user-info[style] {
        flex: 0 0 auto !important;
        width: auto !important;
        margin-left: auto !important;
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        padding: 3px 5px !important;
        gap: 2px !important;
      }
      body > header .user-dropdown-trigger span:nth-child(2),
      header .user-dropdown-trigger span:nth-child(2)[style] {
        display: none !important;
      }
      body > header .user-dropdown-trigger span:first-child,
      header .user-dropdown-trigger span:first-child[style] {
        font-size: 1rem !important;
      }
      body > header .user-dropdown-trigger span:last-child,
      header .user-dropdown-trigger span:last-child[style] {
        font-size: 0.6rem !important;
      }
    }
    header .user-info {
      flex: 0 0 auto;
      min-width: fit-content;
      flex-shrink: 0;
      overflow: visible !important;
    }
    .user-dropdown-trigger {
      min-width: fit-content;
      flex-shrink: 0;
      white-space: nowrap;
      overflow: visible;
    }
    .user-dropdown-trigger span:nth-child(2) {
      overflow: visible !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      min-width: fit-content !important;
      max-width: none !important;
    }
    @media (max-width: 1400px) {
      header .logo {
        max-width: 35%;
      }
      .user-dropdown-trigger span:nth-child(2) {
        max-width: 350px;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
    @media (max-width: 1200px) {
      header .logo {
        max-width: 30%;
      }
      header .logo span {
        font-size: 0.85rem;
      }
      .user-dropdown-trigger span:nth-child(2) {
        max-width: 280px;
      }
    }
    @media (max-width: 992px) {
      header .logo {
        max-width: 25%;
      }
      .user-dropdown-trigger span:nth-child(2) {
        max-width: 220px;
      }
    }
    @media (max-width: 768px) {
      header .logo span {
        display: none;
      }
      .user-dropdown-trigger span:nth-child(2) {
        display: none;
      }
    }
    
    header .logo {
      order: 1 !important;
      flex-shrink: 0 !important;
      margin-right: auto !important;
    }
    
    header .user-info {
      overflow: visible !important;
      flex-shrink: 0 !important;
      order: 3 !important;
      margin-left: auto !important;
    }
    
    header .menu-toggle {
      order: 2 !important;
      flex-shrink: 0 !important;
    }
    
    /* Prevent notification bubbles from taking too much space */
    .user-info > div:first-child {
      flex-shrink: 0 !important;
    }
    
    .notification-bubble {
      flex-shrink: 0 !important;
      width: 40px !important;
      height: 40px !important;
    }
    
    /* Ensure dropdown has space and doesn't overlap */
    .user-dropdown {
      overflow: visible !important;
      z-index: 99999 !important;
      right: 0 !important;
      left: auto !important;
    }
    
    /* On smaller screens, position dropdown to the left if needed */
    @media (max-width: 1200px) {
      .user-dropdown {
        right: auto !important;
        left: 0 !important;
        transform: translateX(-100%) !important;
      }
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
    
    /* Mobile: Ensure dropdown and logout button are always visible */
    @media (max-width: 767px) {
      .user-dropdown {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        z-index: 99999 !important;
        position: fixed !important;
        overflow: visible !important;
        max-height: none !important;
        min-width: 200px !important;
        background: white !important;
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.25) !important;
      }
      
      .user-dropdown[style*="display: none"] {
        display: none !important;
      }
      
      .user-dropdown[style*="display: block"] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
      }
      
      /* Profile details section - ensure it's always visible on mobile */
      .user-dropdown > div:first-child {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 12px 16px !important;
        border-bottom: 1px solid #eee !important;
        background: white !important;
      }
      
      .user-dropdown > div:first-child > div:first-child {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-weight: 600 !important;
        color: #333 !important;
        font-size: 0.95rem !important;
      }
      
      .user-dropdown > div:first-child > div:last-child {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 0.85rem !important;
        color: #666 !important;
        margin-top: 4px !important;
      }
      
      /* Logout button - ensure it's always visible and clickable on mobile */
      .user-dropdown a[href*="logout"],
      .user-dropdown #logout-link {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        z-index: 100000 !important;
        min-height: 44px !important;
        padding: 14px 16px !important;
        color: #dc3545 !important;
        text-decoration: none !important;
        position: relative !important;
        width: 100% !important;
        box-sizing: border-box !important;
        cursor: pointer !important;
        -webkit-tap-highlight-color: rgba(220, 53, 69, 0.2) !important;
      }
      
      .user-dropdown > div {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
      }
    }
    
    @media (max-width: 480px) {
      .user-dropdown {
        min-width: 180px !important;
        max-width: calc(100vw - 20px) !important;
      }
      
      /* Profile details on small mobile */
      .user-dropdown > div:first-child {
        padding: 10px 14px !important;
      }
      
      .user-dropdown > div:first-child > div:first-child {
        font-size: 0.9rem !important;
      }
      
      .user-dropdown > div:first-child > div:last-child {
        font-size: 0.8rem !important;
      }
      
      /* Logout button on small mobile */
      .user-dropdown a[href*="logout"],
      .user-dropdown #logout-link {
        padding: 12px 14px !important;
        font-size: 0.9rem !important;
        min-height: 44px !important;
      }
    }
    
    /* Ensure menu toggle doesn't overlap */
    .menu-toggle {
      flex-shrink: 0 !important;
      margin-left: 10px !important;
    }
    
    /* Make menu toggle button visible and blue on mobile */
    @media (max-width: 767px) {
      .menu-toggle,
      #menuToggle {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        background: #007bff !important;
        border: 2px solid #0056b3 !important;
        border-radius: 6px !important;
        padding: 8px 10px !important;
        cursor: pointer !important;
        z-index: 10001 !important;
        min-width: 44px !important;
        min-height: 44px !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3) !important;
        transition: all 0.2s ease !important;
      }
      
      .menu-toggle:hover,
      .menu-toggle:active,
      #menuToggle:hover,
      #menuToggle:active {
        background: #0056b3 !important;
        border-color: #004085 !important;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.5) !important;
        transform: scale(1.05) !important;
      }
      
      .menu-toggle svg,
      #menuToggle svg {
        width: 24px !important;
        height: 24px !important;
        fill: white !important;
        stroke: white !important;
        color: white !important;
      }
      
      .menu-toggle path,
      #menuToggle path {
        fill: white !important;
        stroke: white !important;
      }
    }
    
    @media (max-width: 480px) {
      .menu-toggle,
      #menuToggle {
        padding: 10px 12px !important;
        min-width: 48px !important;
        min-height: 48px !important;
      }
      
      .menu-toggle svg,
      #menuToggle svg {
        width: 26px !important;
        height: 26px !important;
      }
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
    function toggleUserDropdown(event) {
      if (event) {
        event.stopPropagation();
        event.preventDefault();
      }
      
      const dropdown = document.getElementById('userDropdown');
      const trigger = document.querySelector('.user-dropdown-trigger');
      
      if (!dropdown || !trigger) {
        console.error('Dropdown or trigger not found');
        return false;
      }
      
      const isVisible = dropdown.style.display === 'block' || 
                       (dropdown.style.display === '' && window.getComputedStyle(dropdown).display !== 'none');
      
      if (!isVisible) {
        // Show dropdown - use setProperty with !important to override inline !important
        dropdown.style.setProperty('display', 'block', 'important');
        dropdown.style.setProperty('visibility', 'visible', 'important');
        dropdown.style.setProperty('opacity', '1', 'important');
        dropdown.style.setProperty('z-index', '99999', 'important');
        dropdown.style.setProperty('overflow', 'visible', 'important');
        dropdown.style.setProperty('max-height', 'none', 'important');
        
        // Force logout button to be visible
        const logoutLink = dropdown.querySelector('a[href*="logout"]') || document.getElementById('logout-link');
        if (logoutLink) {
          logoutLink.style.setProperty('display', 'block', 'important');
          logoutLink.style.setProperty('visibility', 'visible', 'important');
          logoutLink.style.setProperty('opacity', '1', 'important');
          logoutLink.style.setProperty('pointer-events', 'auto', 'important');
          logoutLink.style.setProperty('z-index', '100000', 'important');
          logoutLink.style.setProperty('position', 'relative', 'important');
        }
        
        // Ensure dropdown content is visible
        const dropdownContent = dropdown.querySelector('div');
        if (dropdownContent) {
          dropdownContent.style.setProperty('display', 'block', 'important');
          dropdownContent.style.setProperty('visibility', 'visible', 'important');
        }
        
        // Position dropdown
        const rect = trigger.getBoundingClientRect();
        const dropdownWidth = 220;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        if (window.innerWidth <= 767) {
          // Mobile: position at bottom
          dropdown.style.setProperty('position', 'fixed', 'important');
          dropdown.style.setProperty('right', '10px', 'important');
          dropdown.style.setProperty('bottom', '80px', 'important');
          dropdown.style.setProperty('top', 'auto', 'important');
          dropdown.style.setProperty('left', 'auto', 'important');
          dropdown.style.setProperty('display', 'block', 'important');
          dropdown.style.setProperty('visibility', 'visible', 'important');
          dropdown.style.setProperty('opacity', '1', 'important');
          dropdown.style.setProperty('z-index', '99999', 'important');
          dropdown.style.setProperty('overflow', 'visible', 'important');
          dropdown.style.setProperty('max-height', 'none', 'important');
          
          // Force logout button visibility on mobile
          const logoutLinkMobile = dropdown.querySelector('a[href*="logout"]') || document.getElementById('logout-link');
          if (logoutLinkMobile) {
            logoutLinkMobile.style.setProperty('display', 'block', 'important');
            logoutLinkMobile.style.setProperty('visibility', 'visible', 'important');
            logoutLinkMobile.style.setProperty('opacity', '1', 'important');
            logoutLinkMobile.style.setProperty('pointer-events', 'auto', 'important');
            logoutLinkMobile.style.setProperty('z-index', '100000', 'important');
            logoutLinkMobile.style.setProperty('position', 'relative', 'important');
            logoutLinkMobile.style.setProperty('width', '100%', 'important');
            logoutLinkMobile.style.setProperty('box-sizing', 'border-box', 'important');
          }
          
          // Ensure dropdown content is visible on mobile
          const dropdownContentMobile = dropdown.querySelector('div');
          if (dropdownContentMobile) {
            dropdownContentMobile.style.setProperty('display', 'block', 'important');
            dropdownContentMobile.style.setProperty('visibility', 'visible', 'important');
            dropdownContentMobile.style.setProperty('opacity', '1', 'important');
          }
        } else {
          // Desktop: position below trigger, adjust if goes off-screen
          dropdown.style.position = 'fixed';
          dropdown.style.top = (rect.bottom + 5) + 'px';
          
          // Check if dropdown would go off right edge
          if (rect.right + dropdownWidth > viewportWidth - 10) {
            // Position to the left of trigger
            dropdown.style.left = Math.max(10, rect.left - dropdownWidth) + 'px';
            dropdown.style.right = 'auto';
          } else {
            // Position to the right of trigger
            dropdown.style.left = rect.right + 'px';
            dropdown.style.right = 'auto';
          }
          
          // Check if dropdown would go off bottom
          const dropdownHeight = dropdown.offsetHeight || 100;
          if (rect.bottom + dropdownHeight + 10 > viewportHeight) {
            dropdown.style.top = Math.max(10, rect.top - dropdownHeight) + 'px';
          }
        }
        
        dropdown.style.transform = 'none';
        dropdown.style.marginTop = '0';
      } else {
        // Hide dropdown
        dropdown.style.display = 'none';
        dropdown.style.visibility = 'hidden';
        dropdown.style.opacity = '0';
      }
      
      return false;
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('userDropdown');
      const trigger = document.querySelector('.user-dropdown-trigger');
      const logoutLink = document.getElementById('logout-link');
      
      if (!dropdown) return;
      
      // Don't close if clicking on trigger, dropdown, or logout link
      const clickedInside = (trigger && trigger.contains(event.target)) || 
                           dropdown.contains(event.target) ||
                           (logoutLink && (event.target === logoutLink || logoutLink.contains(event.target)));
      
      if (!clickedInside && dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
        dropdown.style.visibility = 'hidden';
        dropdown.style.opacity = '0';
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
    <header style="overflow: visible !important; z-index: 9999 !important; position: relative !important; padding: 10px 15px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; max-width: 100vw !important; box-sizing: border-box !important; gap: 10px !important;">
        <div class="logo" style="flex-shrink: 1 !important; order: 1 !important; min-width: 0 !important; flex: 0 1 auto !important; max-width: 30% !important; overflow: hidden !important;">
            <a href="student_service_dashboard.php" style="display: flex !important; align-items: center !important; text-decoration: none !important; color: inherit !important; gap: 6px !important; min-width: 0 !important;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img" style="width: auto !important; height: 32px !important; max-width: 45px !important; object-fit: contain !important; flex-shrink: 0 !important;">
                <span style="white-space: nowrap !important; font-size: 0.85rem !important; overflow: hidden !important; text-overflow: ellipsis !important; min-width: 0 !important; flex-shrink: 1 !important;">Student Services Dashboard</span>
            </a>
        </div>
        <div class="user-info" style="position: relative !important; display: flex !important; align-items: center !important; gap: 8px !important; overflow: visible !important; z-index: 10000 !important; flex-shrink: 0 !important; order: 3 !important; margin-left: auto !important; min-width: fit-content !important; flex: 0 0 auto !important;">
            <!-- Notification Indicators -->
            <?php if ($finance_transfer_count > 0 || $notification_count > 0): ?>
              <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
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
            <div style="position: relative !important; z-index: 10000 !important; overflow: visible !important; flex-shrink: 0 !important;">
                <div class="user-dropdown-trigger" style="cursor: pointer !important; display: flex !important; align-items: center !important; gap: 5px !important; padding: 5px 8px !important; border-radius: 5px !important; transition: background 0.2s !important; white-space: nowrap !important; flex-shrink: 0 !important;" onclick="toggleUserDropdown(event); return false;" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='transparent'">
                    <span style="flex-shrink: 0 !important;">👤</span>
                    <span style="white-space: nowrap !important; overflow: visible !important; flex-shrink: 0 !important; min-width: fit-content !important; font-size: 0.85rem !important;">Logged in as <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    <span style="font-size: 0.7rem !important; flex-shrink: 0 !important;">▼</span>
                </div>
                <div id="userDropdown" class="user-dropdown" style="display: none !important; position: fixed !important; background: white !important; border: 1px solid #ddd !important; border-radius: 8px !important; box-shadow: 0 8px 24px rgba(0,0,0,0.25) !important; min-width: 200px !important; z-index: 99999 !important; overflow: visible !important; max-height: none !important;">
                    <div style="padding: 12px 16px !important; border-bottom: 1px solid #eee !important;">
                        <div style="font-weight: 600 !important; color: #333 !important;"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                        <div style="font-size: 0.85rem !important; color: #666 !important; margin-top: 4px !important;"><?php echo ucfirst($_SESSION['role']); ?> User</div>
                    </div>
                    <a href="logout.php" id="logout-link" style="display: block !important; padding: 14px 16px !important; color: #dc3545 !important; text-decoration: none !important; transition: background 0.2s !important; min-height: 44px !important; line-height: 1.4 !important; position: relative !important; z-index: 100000 !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'" onclick="event.stopPropagation();">
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

  <?php require_once __DIR__ . '/includes/chatbot_simple.php'; ?>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>

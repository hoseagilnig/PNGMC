<?php
/**
 * Admin Dashboard
 * Standardized authentication and error handling
 */

// Use standardized auth guard
require_once __DIR__ . '/includes/auth_guard.php';
requireRole('admin');

// Load required files with __DIR__ for Linux compatibility
require_once __DIR__ . '/includes/menu_helper.php';
require_once __DIR__ . '/includes/db_config.php';

// Get statistics with prepared statements and error handling
$conn = getDBConnection();
$stats = [
    'pending_applications' => 0,
    'hod_review' => 0,
    'accepted' => 0,
    'correspondence_pending' => 0,
    'admins' => 0,
    'student_services' => 0,
    'finance_staff' => 0,
    'active_students' => 0,
    'enrolled' => 0,
    'open_tickets' => 0
];

if ($conn) {
    try {
        // Application Statistics - using prepared statements
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = ?");
        $status = 'submitted';
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending_applications'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        $status = 'hod_review';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['hod_review'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        $status = 'accepted';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['accepted'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        $status = 'correspondence_sent';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['correspondence_pending'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        // Staff Statistics
        $role = 'admin';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['admins'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        $role = 'studentservices';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['student_services'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        $role = 'finance';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['finance_staff'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        // Student Statistics
        $status = 'active';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['active_students'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE enrolled = TRUE");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['enrolled'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        // System Statistics
        $status = 'open';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['open_tickets'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
    } catch (Exception $e) {
        // Log error but don't expose to user
        error_log("Admin Dashboard statistics error: " . $e->getMessage());
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administration Dashboard</title>
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
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    /* Header layout fixes */
    header {
      width: 100% !important;
      box-sizing: border-box !important;
      overflow: visible !important;
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
        margin-right: 180px !important;
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
        margin-right: 180px !important;
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
        max-width: 40% !important;
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
      }
      
      .user-dropdown > div {
        display: block !important;
        visibility: visible !important;
      }
    }
    
    @media (max-width: 480px) {
      .user-dropdown {
        min-width: 180px !important;
        max-width: calc(100vw - 20px) !important;
      }
      
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
        // Show dropdown
        dropdown.style.display = 'block';
        dropdown.style.visibility = 'visible';
        dropdown.style.opacity = '1';
        dropdown.style.zIndex = '99999';
        dropdown.style.overflow = 'visible';
        dropdown.style.maxHeight = 'none';
        
        // Force logout button to be visible
        const logoutLink = dropdown.querySelector('a[href*="logout"]') || document.getElementById('logout-link');
        if (logoutLink) {
          logoutLink.style.display = 'block';
          logoutLink.style.visibility = 'visible';
          logoutLink.style.opacity = '1';
          logoutLink.style.pointerEvents = 'auto';
          logoutLink.style.zIndex = '100000';
          logoutLink.style.position = 'relative';
        }
        
        // Position dropdown
        const rect = trigger.getBoundingClientRect();
        const dropdownWidth = 220;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        if (window.innerWidth <= 767) {
          // Mobile: position at bottom
          dropdown.style.position = 'fixed';
          dropdown.style.right = '10px';
          dropdown.style.bottom = '80px';
          dropdown.style.top = 'auto';
          dropdown.style.left = 'auto';
          dropdown.style.display = 'block';
          dropdown.style.visibility = 'visible';
          dropdown.style.opacity = '1';
          dropdown.style.zIndex = '99999';
          dropdown.style.overflow = 'visible';
          dropdown.style.maxHeight = 'none';
          
          // Force logout button visibility on mobile
          const logoutLink = dropdown.querySelector('a[href*="logout"]') || document.getElementById('logout-link');
          if (logoutLink) {
            logoutLink.style.display = 'block';
            logoutLink.style.visibility = 'visible';
            logoutLink.style.opacity = '1';
            logoutLink.style.pointerEvents = 'auto';
            logoutLink.style.zIndex = '100000';
            logoutLink.style.position = 'relative';
            logoutLink.style.width = '100%';
            logoutLink.style.boxSizing = 'border-box';
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
    
    // Ensure logout link is always clickable - add dedicated event listener
    document.addEventListener('DOMContentLoaded', function() {
      const logoutLink = document.getElementById('logout-link');
      if (logoutLink) {
        // Remove any existing listeners
        const newLogoutLink = logoutLink.cloneNode(true);
        logoutLink.parentNode.replaceChild(newLogoutLink, logoutLink);
        
        // Add click listener
        newLogoutLink.addEventListener('click', function(e) {
          e.stopPropagation();
          e.stopImmediatePropagation();
          e.preventDefault();
          window.location.href = 'logout.php';
          return false;
        }, true); // Use capture phase to ensure it fires first
      }
    });
  </script>
</head>
<body>
    <header style="overflow: visible !important; z-index: 9999 !important; position: relative !important; padding: 10px 15px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; max-width: 100vw !important; box-sizing: border-box !important; gap: 10px !important;">
        <div class="logo" style="flex-shrink: 1 !important; order: 1 !important; min-width: 0 !important; flex: 0 1 auto !important; max-width: 30% !important; overflow: hidden !important;">
            <a href="admin_dashboard.php" style="display: flex !important; align-items: center !important; text-decoration: none !important; color: inherit !important; gap: 6px !important; min-width: 0 !important;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img" style="width: auto !important; height: 32px !important; max-width: 45px !important; object-fit: contain !important; flex-shrink: 0 !important;">
                <span style="white-space: nowrap !important; font-size: 0.85rem !important; overflow: hidden !important; text-overflow: ellipsis !important; min-width: 0 !important; flex-shrink: 1 !important;">Administration Dashboard</span>
            </a>
        </div>
        <?php echo getMobileMenuToggle(); ?>
        <div class="user-info" style="position: relative !important; display: flex !important; align-items: center !important; gap: 8px !important; overflow: visible !important; z-index: 10000 !important; flex-shrink: 0 !important; order: 3 !important; margin-left: auto !important; min-width: fit-content !important; flex: 0 0 auto !important;">
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
    </header>

    <?php echo getSidebarOverlay(); ?>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="admin_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item <?php echo isActive('admin_dashboard.php'); ?>" href="admin_dashboard.php">Dashboard</a>
        <div class="menu-section">Monitoring</div>
        <a class="menu-item <?php echo isActive('workflow_monitor.php'); ?>" href="workflow_monitor.php">Workflow Monitor</a>
        <a class="menu-item <?php echo isActive('applications.php'); ?>" href="applications.php">View Applications</a>
        <a class="menu-item <?php echo isActive('continuing_students.php'); ?>" href="continuing_students.php">Candidates Returning</a>
        <div class="menu-section">Administration</div>
        <a class="menu-item <?php echo isActive('manage_staff.php'); ?>" href="manage_staff.php">Manage Staff</a>
        <a class="menu-item <?php echo isActive('system_settings.php'); ?>" href="system_settings.php">System Settings</a>
        <a class="menu-item <?php echo isActive('reports.php'); ?>" href="reports.php">Reports & Analytics</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Administration Dashboard</h1>
        <p class="small">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>! Monitor workflow progress, track applications, and manage system operations.</p>
      </header>

      <!-- Workflow Monitoring Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Workflow Monitoring</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='workflow_monitor.php'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Workflow Monitor</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1d4e89;">📊</div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Track overall progress</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📊</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=submitted'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Pending Applications</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1d4e89;"><?php echo $stats['pending_applications'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">View all pending</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📋</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=hod_review'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">HOD Review</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?php echo $stats['hod_review'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">View HOD reviews</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">✅</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=accepted'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Accepted</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #388e3c;"><?php echo $stats['accepted'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">View accepted</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">✉️</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Staff & System Management Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Staff & System Management</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='manage_staff.php'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Total Staff</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1d4e89;">
                  <?php echo ($stats['admins'] ?? 0) + ($stats['student_services'] ?? 0) + ($stats['finance_staff'] ?? 0); ?>
                </div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">
                  <?php echo $stats['admins'] ?? 0; ?> Admin, <?php echo $stats['student_services'] ?? 0; ?> Student Services, <?php echo $stats['finance_staff'] ?? 0; ?> Finance
                </p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">👥</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='system_settings.php'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">System Settings</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #7b1fa2;">⚙️</div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Configure system</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">⚙️</div>
            </div>
          </div>

          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='reports.php'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Reports & Analytics</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #d32f2f;">📊</div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">View reports</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📊</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Student Statistics Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Student Statistics</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Active Students</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #388e3c;"><?php echo $stats['active_students'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Currently enrolled</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">🎓</div>
            </div>
          </div>

          <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Enrolled from Applications</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1976d2;"><?php echo $stats['enrolled'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Successfully enrolled</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">✅</div>
            </div>
          </div>

          <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Open Support Tickets</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?php echo $stats['open_tickets'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Require attention</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">🎫</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick Access -->
      <section>
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Quick Access</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
          <a href="workflow_monitor.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2rem; margin-bottom: 10px;">📊</div>
            <strong>Workflow Monitor</strong>
          </a>
          <a href="applications.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
            <strong>View All Applications</strong>
          </a>
          <a href="continuing_students.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2rem; margin-bottom: 10px;">🔄</div>
            <strong>Continuing Students</strong>
          </a>
          <a href="manage_staff.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2rem; margin-bottom: 10px;">👥</div>
            <strong>Manage Staff</strong>
          </a>
          <a href="reports.php" class="main-card" style="text-decoration: none; color: inherit; display: block; padding: 20px; text-align: center; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="font-size: 2rem; margin-bottom: 10px;">📊</div>
            <strong>Generate Reports</strong>
          </a>
        </div>
      </section>
    </div>
  </div>

  <?php require_once __DIR__ . '/includes/chatbot_simple.php'; ?>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>

<?php
/**
 * HOD Dashboard
 * Standardized authentication and error handling
 */

// Use standardized auth guard
require_once __DIR__ . '/includes/auth_guard.php';
requireRole('hod');

// Load required files with __DIR__ for Linux compatibility
require_once __DIR__ . '/includes/menu_helper.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/workflow_helper.php';

// Get statistics with prepared statements and error handling
$conn = getDBConnection();
$stats = [
    'pending_review' => 0,
    'approved_today' => 0,
    'rejected_today' => 0,
    'total_pending' => 0
];

if ($conn) {
    try {
        // Check if hod_decision columns exist
        $col_check = $conn->query("SHOW COLUMNS FROM applications LIKE 'hod_decision'");
        $has_hod_decision = $col_check->num_rows > 0;
        
        // Applications pending HOD review
        $status = 'hod_review';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending_review'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
        // Applications approved today (only if hod_decision column exists)
        if ($has_hod_decision) {
            $decision = 'approved';
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE hod_decision = ? AND hod_decision_date = CURDATE()");
            $stmt->bind_param("s", $decision);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['approved_today'] = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
            
            // Applications rejected today
            $decision = 'rejected';
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE hod_decision = ? AND hod_decision_date = CURDATE()");
            $stmt->bind_param("s", $decision);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['rejected_today'] = $result->fetch_assoc()['count'] ?? 0;
            $stmt->close();
        } else {
            // If columns don't exist, set to 0
            $stats['approved_today'] = 0;
            $stats['rejected_today'] = 0;
        }
        
        // Total pending
        $status1 = 'ineligible';
        $status2 = 'enrolled';
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status != ? AND status != ?");
        $stmt->bind_param("ss", $status1, $status2);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_pending'] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        
    } catch (Exception $e) {
        // Log error but don't expose to user
        error_log("HOD Dashboard statistics error: " . $e->getMessage());
    }
    
    $conn->close();
}

// Get workflow notifications
$workflow_notifications = getUnreadNotifications('hod', 5);
$notification_count = getNotificationCount('hod');

// Get pending applications for HOD
$pending_applications = getPendingApplicationsForDepartment('hod', 'hod_review');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HOD Dashboard - PNG Maritime College</title>
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
      box-sizing: border-box !important;
      overflow: visible !important;
      padding: 10px 20px !important;
    }
    body > header .logo,
    header .logo[style] {
      min-width: 0 !important;
      flex: 1 1 auto !important;
      max-width: 50% !important;
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
      body > header .user-info,
      header .user-info[style] {
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
      }
      body > header .user-dropdown-trigger,
      header .user-dropdown-trigger[style] {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: auto !important;
        min-width: fit-content !important;
      }
      body > header .logo,
      header .logo[style] {
        flex: 0 1 auto !important;
        max-width: 50% !important;
        min-width: 0 !important;
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
    @keyframes pulse-bubble {
      0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2);
      }
      50% {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(245, 124, 0, 0.6), 0 2px 6px rgba(0,0,0,0.3);
      }
    }
    @keyframes bounce-count {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-3px);
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
</head>
<body>
    <header style="overflow: visible !important; z-index: 9999 !important; position: relative !important; padding: 10px 15px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; max-width: 100vw !important; box-sizing: border-box !important; gap: 10px !important;">
        <div class="logo" style="flex-shrink: 1 !important; order: 1 !important; min-width: 0 !important; flex: 0 1 auto !important; max-width: calc(50% - 20px) !important; overflow: hidden !important;">
            <a href="hod_dashboard.php" style="display: flex !important; align-items: center !important; text-decoration: none !important; color: inherit !important; gap: 6px !important; min-width: 0 !important;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img" style="width: auto !important; height: 32px !important; max-width: 45px !important; object-fit: contain !important; flex-shrink: 0 !important;">
                <span style="white-space: nowrap !important; font-size: 0.85rem !important; overflow: hidden !important; text-overflow: ellipsis !important; min-width: 0 !important; flex-shrink: 1 !important;">HOD Dashboard</span>
            </a>
        </div>
        <div class="user-info" style="position: relative !important; display: flex !important; align-items: center !important; gap: 8px !important; z-index: 10000 !important; flex-shrink: 0 !important; order: 3 !important; margin-left: auto !important; min-width: fit-content !important; flex: 0 0 auto !important;">
            <?php if ($notification_count > 0): ?>
              <a href="workflow_manager.php" class="notification-bubble workflow-bubble" style="position: relative; display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #f57c00 0%, #e65100 100%); border-radius: 50%; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); animation: pulse-bubble 2s infinite; z-index: 100;" onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(245, 124, 0, 0.6), 0 2px 6px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(245, 124, 0, 0.4), 0 2px 4px rgba(0,0,0,0.2)';" onclick="event.preventDefault(); window.location.href='workflow_manager.php'; return false;">
                <span style="font-size: 1.5rem; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2)); pointer-events: none;">📬</span>
                <span class="bubble-count" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; font-size: 0.7rem; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.5); border: 2px solid white; animation: bounce-count 1s infinite; pointer-events: none;"><?php echo $notification_count; ?></span>
              </a>
            <?php endif; ?>
            
            <!-- User Profile Dropdown -->
            <div style="position: relative;">
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
        <a href="hod_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item <?php echo isActive('hod_dashboard.php'); ?>" href="hod_dashboard.php">Dashboard</a>
        <div class="menu-section">Applications</div>
        <a class="menu-item <?php echo isActive('applications.php'); ?>" href="applications.php?status=hod_review">Pending Review</a>
        <a class="menu-item <?php echo isActive('workflow_manager.php'); ?>" href="workflow_manager.php">
          Workflow Manager
          <?php if ($notification_count > 0): ?>
            <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;"><?php echo $notification_count; ?></span>
          <?php endif; ?>
        </a>
        <div class="menu-section">Reports</div>
        <a class="menu-item <?php echo isActive('reports.php'); ?>" href="reports.php">Reports</a>
      </div>
    </nav>

    <div class="content">
      <header style="margin-bottom: 30px;">
        <h1>Head of Department Dashboard</h1>
        <p class="small">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>! Review and approve applications.</p>
      </header>

      <!-- Statistics Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Overview</h2>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          <div class="main-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onclick="window.location.href='applications.php?status=hod_review'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Pending Review</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #1d4e89;"><?php echo $stats['pending_review'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Awaiting your decision</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📋</div>
            </div>
          </div>

          <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Approved Today</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #28a745;"><?php echo $stats['approved_today'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Applications approved</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">✅</div>
            </div>
          </div>

          <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Rejected Today</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #dc3545;"><?php echo $stats['rejected_today'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">Applications rejected</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">❌</div>
            </div>
          </div>

          <div class="main-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 0.9rem; font-weight: 600;">Total Pending</h3>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?php echo $stats['total_pending'] ?? 0; ?></div>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 0.85rem;">In your queue</p>
              </div>
              <div style="font-size: 3rem; opacity: 0.2;">📊</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Pending Applications Section -->
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Applications Pending Review</h2>
        <?php if (empty($pending_applications)): ?>
          <div class="main-card" style="text-align: center; padding: 40px;">
            <p style="color: #666; font-size: 1.1rem;">No applications pending review at this time.</p>
          </div>
        <?php else: ?>
          <div class="main-card">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="background: #1d4e89; color: white;">
                  <th style="padding: 12px; text-align: left;">Application #</th>
                  <th style="padding: 12px; text-align: left;">Name</th>
                  <th style="padding: 12px; text-align: left;">Program</th>
                  <th style="padding: 12px; text-align: left;">Status</th>
                  <th style="padding: 12px; text-align: left;">Submitted</th>
                  <th style="padding: 12px; text-align: left;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pending_applications as $app): ?>
                  <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px;"><?php echo htmlspecialchars($app['application_number']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($app['program_interest'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px;">
                      <span style="padding: 4px 8px; border-radius: 3px; background: #ffc107; color: #000; font-size: 0.85rem;">
                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                      </span>
                    </td>
                    <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($app['submitted_at'])); ?></td>
                    <td style="padding: 12px;">
                      <a href="application_details.php?id=<?php echo $app['application_id']; ?>" 
                         style="background: #1d4e89; color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 0.9rem;">
                        Review
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <!-- Recent Notifications Section -->
      <?php if (!empty($workflow_notifications)): ?>
      <section style="margin-bottom: 30px;">
        <h2 style="color: #1d4e89; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1d4e89;">Recent Notifications</h2>
        <div class="main-card">
          <?php foreach (array_slice($workflow_notifications, 0, 5) as $notif): ?>
            <div style="padding: 15px; border-left: 4px solid #1d4e89; margin-bottom: 10px; background: #f8f9fa; border-radius: 5px;">
              <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                <strong style="color: #1d4e89;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                <span style="font-size: 12px; color: #666;"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></span>
              </div>
              <p style="color: #555; margin: 0; font-size: 14px;"><?php echo htmlspecialchars($notif['message']); ?></p>
              <div style="margin-top: 8px; font-size: 12px; color: #999;">
                From: <?php echo ucfirst($notif['from_department']); ?> | 
                Application: <?php echo htmlspecialchars($notif['application_number']); ?>
              </div>
            </div>
          <?php endforeach; ?>
          <div style="text-align: center; margin-top: 15px;">
            <a href="workflow_manager.php" style="color: #1d4e89; text-decoration: underline;">View All Notifications →</a>
          </div>
        </div>
      </section>
      <?php endif; ?>
    </div>
  </div>

  <?php require_once __DIR__ . '/includes/chatbot_simple.php'; ?>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>


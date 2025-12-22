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
    @media (max-width: 1400px) {
      header .logo span {
        font-size: 0.85rem !important;
      }
    }
    @media (max-width: 1200px) {
      header .logo {
        max-width: 45% !important;
      }
      header .logo span {
        font-size: 0.8rem !important;
      }
    }
    @media (max-width: 992px) {
      header .logo {
        max-width: 40% !important;
      }
      header .logo span {
        font-size: 0.75rem !important;
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
  </style>
  <script>
    function toggleUserDropdown() {
      const dropdown = document.getElementById('userDropdown');
      const trigger = document.querySelector('.user-dropdown-trigger');
      
      if (!dropdown || !trigger) {
        console.error('Dropdown or trigger not found');
        return;
      }
      
      const isVisible = dropdown.style.display === 'block' || dropdown.style.display === '';
      const currentDisplay = window.getComputedStyle(dropdown).display;
      
      if (!isVisible || currentDisplay === 'none') {
        // Show dropdown
        dropdown.style.display = 'block';
        dropdown.style.visibility = 'visible';
        dropdown.style.opacity = '1';
        dropdown.style.zIndex = '99999';
        dropdown.style.overflow = 'visible';
        dropdown.style.maxHeight = 'none';
        
        // Ensure logout button is visible
        const logoutLink = dropdown.querySelector('a[href*="logout"]');
        if (logoutLink) {
          logoutLink.style.display = 'block';
          logoutLink.style.visibility = 'visible';
          logoutLink.style.opacity = '1';
          logoutLink.style.pointerEvents = 'auto';
          logoutLink.style.zIndex = '100000';
        }
        
        // On mobile, position dropdown relative to viewport at bottom
        if (window.innerWidth <= 767 && trigger) {
          dropdown.style.position = 'fixed';
          dropdown.style.right = '10px';
          dropdown.style.bottom = '80px';
          dropdown.style.top = 'auto';
          dropdown.style.left = 'auto';
          dropdown.style.maxHeight = 'none';
          dropdown.style.overflow = 'visible';
        } else {
          // Desktop/Workstation: use fixed positioning to ensure it's above all content
          const rect = trigger.getBoundingClientRect();
          const dropdownWidth = 180;
          const viewportWidth = window.innerWidth;
          
          // Always position dropdown to the right of the trigger
          dropdown.style.position = 'fixed';
          dropdown.style.top = (rect.bottom + 8) + 'px';
          
          // Check if dropdown would go off-screen, if so position to the left
          if (rect.right + dropdownWidth > viewportWidth - 20) {
            // Position to the left of trigger
            dropdown.style.left = (rect.left - dropdownWidth) + 'px';
            dropdown.style.right = 'auto';
          } else {
            // Position to the right of trigger
            dropdown.style.left = rect.right + 'px';
            dropdown.style.right = 'auto';
          }
          
          dropdown.style.transform = 'none';
          dropdown.style.bottom = 'auto';
          dropdown.style.marginTop = '0';
          dropdown.style.visibility = 'visible';
          dropdown.style.opacity = '1';
          dropdown.style.maxHeight = 'none';
          dropdown.style.overflow = 'visible';
          
          // Force show with important styles
          dropdown.setAttribute('style', dropdown.getAttribute('style') + ' !important');
        }
      } else {
        // Hide dropdown
        dropdown.style.display = 'none';
        dropdown.style.visibility = 'hidden';
        dropdown.style.opacity = '0';
      }
    }
    
    // Close dropdown when clicking outside (but not on logout link)
    document.addEventListener('click', function(event) {
      const userInfo = document.querySelector('.user-info');
      const dropdown = document.getElementById('userDropdown');
      const logoutLink = document.getElementById('logout-link');
      
      // Don't close if clicking inside dropdown or on logout link
      if (userInfo && dropdown) {
        const clickedInside = userInfo.contains(event.target);
        const clickedLogout = logoutLink && (event.target === logoutLink || logoutLink.contains(event.target) || event.target.closest('#logout-link'));
        
        // Allow logout link to work - don't interfere
        if (clickedLogout) {
          return true; // Let the link work
        }
        
        if (!clickedInside) {
          dropdown.style.display = 'none';
          dropdown.style.visibility = 'hidden';
        }
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
    <header style="overflow: visible !important; z-index: 9999 !important; position: relative !important; display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; width: 100%; box-sizing: border-box; gap: 15px;">
        <div class="logo" style="flex-shrink: 1; order: 1; min-width: 0; flex: 1 1 auto; max-width: 50%; overflow: visible;">
            <a href="admin_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit; gap: 8px; min-width: 0;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img" style="width: auto; height: 35px; max-width: 50px; object-fit: contain; flex-shrink: 0;">
                <span style="white-space: nowrap; font-size: 0.9rem; overflow: visible; text-overflow: clip; min-width: 0; flex-shrink: 1;">Administration Dashboard</span>
            </a>
        </div>
        <?php echo getMobileMenuToggle(); ?>
        <div class="user-info" style="position: relative; z-index: 10000; flex-shrink: 0; order: 3; margin-left: auto; display: flex; align-items: center; gap: 10px; min-width: fit-content; flex: 0 0 auto;">
            <div class="user-dropdown-trigger" style="cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 5px; transition: background 0.2s; white-space: nowrap; flex-shrink: 0;" onclick="toggleUserDropdown()" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='transparent'">
                <span style="flex-shrink: 0;">👤</span>
                <span style="white-space: nowrap; overflow: visible; flex-shrink: 0; min-width: fit-content;">Logged in as <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                <span style="font-size: 0.8rem; flex-shrink: 0;">▼</span>
            </div>
            <div id="userDropdown" class="user-dropdown" style="display: none; position: fixed; background: white !important; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 180px; z-index: 99999 !important; overflow: visible !important;">
                <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div style="font-size: 0.85rem; color: #666; margin-top: 4px;"><?php echo ucfirst($_SESSION['role']); ?> User</div>
                </div>
                <a href="logout.php" id="logout-link" style="display: block !important; padding: 12px 16px; color: #dc3545; text-decoration: none !important; transition: background 0.2s; position: relative; z-index: 100000 !important; pointer-events: auto !important; cursor: pointer !important; background: white;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'" onclick="event.stopPropagation(); event.stopImmediatePropagation(); window.location.href='logout.php'; return false;">
                    🚪 Logout
                </a>
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

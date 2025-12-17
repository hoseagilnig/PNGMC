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
      if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';
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
</head>
<body>
    <header>
        <div class="logo">
            <a href="admin_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">Administration Dashboard</span>
            </a>
        </div>
        <?php echo getMobileMenuToggle(); ?>
        <div class="user-info" style="position: relative;">
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

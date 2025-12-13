<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'hod') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/workflow_helper.php';

// Get statistics
$conn = getDBConnection();
$stats = [];
if ($conn) {
    // Applications pending HOD review
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE current_department = 'hod' AND status = 'hod_review'");
    $stats['pending_review'] = $result->fetch_assoc()['count'];
    
    // Applications approved today
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE hod_decision = 'approved' AND hod_decision_date = CURDATE()");
    $stats['approved_today'] = $result->fetch_assoc()['count'];
    
    // Applications rejected today
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE hod_decision = 'rejected' AND hod_decision_date = CURDATE()");
    $stats['rejected_today'] = $result->fetch_assoc()['count'];
    
    // Total pending
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE current_department = 'hod' AND status != 'ineligible' AND status != 'enrolled'");
    $stats['total_pending'] = $result->fetch_assoc()['count'];
    
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
  <style>
    .user-dropdown {
      animation: fadeIn 0.2s ease-in;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
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
            <a href="hod_dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="../images/pnmc.png" alt="PNG Maritime College Logo" class="logo-img">
                <span style="margin-left: 10px;">HOD Dashboard</span>
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

  <?php require_once 'includes/chatbot_simple.php'; ?>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>


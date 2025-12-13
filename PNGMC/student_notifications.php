<?php
session_start();
if (!isset($_SESSION['student_loggedin']) || $_SESSION['student_loggedin'] !== true) {
    header('Location: student_login.php');
    exit;
}

require_once 'pages/includes/db_config.php';
require_once 'pages/includes/student_account_helper.php';

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    markStudentNotificationRead($notification_id);
    header('Location: student_notifications.php');
    exit;
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn = getDBConnection();
    if ($conn) {
        $student_id = $_SESSION['student_id'];
        $table_check = $conn->query("SHOW TABLES LIKE 'student_notifications'");
        if ($table_check->num_rows > 0) {
            $conn->query("UPDATE student_notifications SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE student_id = $student_id AND status = 'unread'");
        }
        $conn->close();
    }
    header('Location: student_notifications.php');
    exit;
}

$notifications = [];
$unread_count = 0;
$filter = $_GET['filter'] ?? 'all'; // all, unread, read

$notifications = getStudentNotifications($_SESSION['student_id'], 100);
$unread_count = getStudentNotificationCount($_SESSION['student_id']);

// Filter notifications
if ($filter === 'unread') {
    $notifications = array_filter($notifications, function($n) { return $n['status'] === 'unread'; });
} elseif ($filter === 'read') {
    $notifications = array_filter($notifications, function($n) { return $n['status'] === 'read'; });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - PNG Maritime College</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #1d4e89;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 1.5rem;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }
        .filter-tab.active {
            background: #1d4e89;
            color: white;
            border-color: #1d4e89;
        }
        .notification-item {
            padding: 20px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-left: 4px solid #1d4e89;
            border-radius: 5px;
        }
        .notification-item.unread {
            background: #e3f2fd;
            border-left-color: #1976d2;
        }
        .notification-item.important {
            border-left-color: #dc3545;
        }
        .notification-item.warning {
            border-left-color: #ffc107;
        }
        .notification-item.payment {
            border-left-color: #28a745;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .notification-title {
            font-weight: 600;
            color: #1d4e89;
            font-size: 1.1rem;
        }
        .notification-meta {
            font-size: 0.85rem;
            color: #666;
            margin-top: 8px;
        }
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn {
            padding: 6px 12px;
            background: #1d4e89;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .btn:hover {
            background: #163c6a;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .mark-all-read {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>My Notifications</h1>
            <p style="opacity: 0.9; margin-top: 5px;">View all notifications from Administration, Finance, and Student Admin Service</p>
        </div>
        <div class="user-info">
            <a href="student_dashboard.php" style="color: white; text-decoration: none; margin-right: 15px;">← Dashboard</a>
            <a href="student_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Filter Tabs -->
        <div class="card">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All (<?php echo count(getStudentNotifications($_SESSION['student_id'], 1000)); ?>)
                </a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                    Unread (<?php echo $unread_count; ?>)
                </a>
                <a href="?filter=read" class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                    Read
                </a>
            </div>
            
            <?php if ($unread_count > 0 && $filter !== 'read'): ?>
            <form method="POST" class="mark-all-read">
                <button type="submit" name="mark_all_read" class="btn btn-secondary">Mark All as Read</button>
            </form>
            <?php endif; ?>
        </div>
        
        <!-- Notifications List -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #1d4e89;">Notifications</h2>
            
            <?php if (empty($notifications)): ?>
                <div class="no-data">
                    <p>No notifications found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['status'] === 'unread' ? 'unread' : ''; ?> <?php echo $notif['notification_type']; ?>">
                        <div class="notification-header">
                            <div style="flex: 1;">
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                    <?php if ($notif['status'] === 'unread'): ?>
                                        <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 10px;">New</span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 10px; color: #333;">
                                    <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                                </div>
                                <div class="notification-meta">
                                    From: <strong><?php echo ucfirst(str_replace('studentservices', 'Student Admin Service', $notif['from_department'])); ?></strong> • 
                                    <?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?>
                                    <?php if ($notif['created_by_name']): ?>
                                        • By: <?php echo htmlspecialchars($notif['created_by_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if ($notif['action_url']): ?>
                                <a href="<?php echo htmlspecialchars($notif['action_url']); ?>" class="btn">View Details →</a>
                            <?php endif; ?>
                            <?php if ($notif['status'] === 'unread'): ?>
                                <a href="?mark_read=<?php echo $notif['notification_id']; ?>" class="btn btn-secondary">Mark as Read</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


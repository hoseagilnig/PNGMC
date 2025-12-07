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
    header('Location: student_dashboard.php');
    exit;
}

$conn = getDBConnection();
$student = null;
$course_history = [];
$current_course = null;
$notifications = [];
$unread_count = 0;

if ($conn) {
    $student_id = $_SESSION['student_id'];
    
    // Get student information
    $stmt = $conn->prepare("SELECT s.*, sa.username, sa.account_status, sa.last_login 
        FROM students s 
        LEFT JOIN student_accounts sa ON s.student_id = sa.student_id 
        WHERE s.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    // Get course history
    $table_check = $conn->query("SHOW TABLES LIKE 'student_course_history'");
    if ($table_check->num_rows > 0) {
        $result = $conn->query("SELECT * FROM student_course_history WHERE student_id = $student_id ORDER BY enrollment_date DESC");
        $course_history = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get current course (most recent enrolled or active)
        foreach ($course_history as $course) {
            if ($course['status'] === 'enrolled') {
                $current_course = $course;
                break;
            }
        }
    }
    
    // Get notifications
    $notifications = getStudentNotifications($student_id, 20);
    $unread_count = getStudentNotificationCount($student_id);
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - PNG Maritime College</title>
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
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1200px;
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
        .card h2 {
            color: #1d4e89;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1d4e89;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-on-hold { background: #fff3cd; color: #856404; }
        .status-suspended { background: #f8d7da; color: #721c24; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1d4e89;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>PNG Maritime College - Student Portal</h1>
            <p style="opacity: 0.9; margin-top: 5px;">Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?></p>
        </div>
        <div class="user-info">
            <span>Student Number: <strong><?php echo htmlspecialchars($_SESSION['student_number']); ?></strong></span>
            <a href="student_notifications.php" style="position: relative; text-decoration: none; color: white; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; margin-right: 10px;">
                🔔 Notifications
                <?php if ($unread_count > 0): ?>
                    <span style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">
                        <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="student_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Student Profile Photo -->
        <?php if (!empty($student['profile_photo_path']) && file_exists(__DIR__ . '/' . $student['profile_photo_path'])): ?>
        <div class="card" style="text-align: center;">
            <img src="<?php echo htmlspecialchars($student['profile_photo_path']); ?>" 
                 alt="Profile Photo" 
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #1d4e89; margin-bottom: 10px;">
            <p style="color: #666; font-size: 0.9rem;">
                <a href="student_profile.php" style="color: #1d4e89; text-decoration: none;">Update Photo →</a>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Account Status -->
        <div class="card">
            <h2>Account Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Account Status</div>
                    <div class="info-value">
                        <?php 
                        $status = $student['account_status'] ?? 'active';
                        $status_class = 'status-' . str_replace('_', '-', $status);
                        $status_text = ucfirst(str_replace('_', ' ', $status));
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['username'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Login</div>
                    <div class="info-value">
                        <?php 
                        if ($student['last_login']) {
                            echo date('M d, Y H:i', strtotime($student['last_login']));
                        } else {
                            echo 'First login';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></div>
                </div>
            </div>
            
            <?php if ($status === 'on_hold'): ?>
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                    <strong>Account On Hold:</strong> Your account is currently on hold. This typically happens after your course has ended. 
                    If you are returning to study, please submit a continuing student application to reactivate your account.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Current Course -->
        <?php if ($current_course): ?>
        <div class="card">
            <h2>Current Course</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Course Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($current_course['course_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Enrollment Date</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($current_course['enrollment_date'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Course End Date</div>
                    <div class="info-value">
                        <?php 
                        if ($current_course['course_end_date']) {
                            echo date('M d, Y', strtotime($current_course['course_end_date']));
                        } else {
                            echo 'Not set';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-active"><?php echo ucfirst($current_course['status']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Course History -->
        <div class="card">
            <h2>Course History</h2>
            <?php if (empty($course_history)): ?>
                <div class="no-data">
                    <p>No course history available.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Course Type</th>
                            <th>Enrollment Date</th>
                            <th>End Date</th>
                            <th>Completion Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($course_history as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_type'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></td>
                                <td><?php echo $course['course_end_date'] ? date('M d, Y', strtotime($course['course_end_date'])) : 'N/A'; ?></td>
                                <td><?php echo $course['completion_date'] ? date('M d, Y', strtotime($course['completion_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace('_', '-', $course['status']); ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Notifications Section -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">Notifications</h2>
                <a href="student_notifications.php" style="color: #1d4e89; text-decoration: none; font-weight: 600;">View All →</a>
            </div>
            <?php if (empty($notifications)): ?>
                <div class="no-data">
                    <p>No notifications at this time.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
                        <div style="padding: 15px; margin-bottom: 10px; background: <?php echo $notif['status'] === 'unread' ? '#e3f2fd' : '#f8f9fa'; ?>; border-left: 4px solid <?php 
                            $colors = [
                                'info' => '#17a2b8',
                                'warning' => '#ffc107',
                                'important' => '#dc3545',
                                'action_required' => '#fd7e14',
                                'payment' => '#28a745',
                                'academic' => '#1d4e89',
                                'general' => '#6c757d'
                            ];
                            echo $colors[$notif['notification_type']] ?? '#6c757d';
                        ?>; border-radius: 5px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <strong style="color: #1d4e89;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                        <?php if ($notif['status'] === 'unread'): ?>
                                            <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="margin: 5px 0; color: #666; font-size: 0.9rem;"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                                    <div style="margin-top: 8px; font-size: 0.85rem; color: #999;">
                                        From: <strong><?php echo ucfirst(str_replace('studentservices', 'Student Admin Service', $notif['from_department'])); ?></strong> • 
                                        <?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?>
                                    </div>
                                </div>
                                <div style="margin-left: 15px;">
                                    <?php if ($notif['action_url']): ?>
                                        <a href="<?php echo htmlspecialchars($notif['action_url']); ?>" style="color: #1d4e89; text-decoration: none; font-size: 0.85rem;">View →</a>
                                    <?php endif; ?>
                                    <?php if ($notif['status'] === 'unread'): ?>
                                        <a href="?mark_read=<?php echo $notif['notification_id']; ?>" style="color: #6c757d; text-decoration: none; font-size: 0.85rem; margin-left: 10px;">Mark Read</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($notifications) > 5): ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="student_notifications.php" style="color: #1d4e89; text-decoration: none; font-weight: 600;">
                            View All <?php echo count($notifications); ?> Notifications →
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h2>Quick Actions</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <?php if ($status === 'on_hold'): ?>
                    <a href="apply_continuing.php" style="display: inline-block; padding: 12px 20px; background: #1d4e89; color: white; text-decoration: none; border-radius: 5px;">
                        📝 Apply to Return (Continuing Student)
                    </a>
                <?php endif; ?>
                <a href="student_profile.php" style="display: inline-block; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;">
                    👤 View Profile
                </a>
                <a href="student_change_password.php" style="display: inline-block; padding: 12px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px;">
                    🔒 Change Password
                </a>
            </div>
        </div>
    </div>
</body>
</html>


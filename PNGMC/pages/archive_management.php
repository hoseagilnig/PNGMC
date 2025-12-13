<?php
session_start();
// Only admin can access archive management
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';
require_once 'includes/archive_helper.php';
require_once 'includes/security_helper.php';

$message = '';
$message_type = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'applications';

// Handle archive actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please refresh the page and try again.';
        $message_type = "error";
    } else {
        $conn = getDBConnection();
        
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'update_settings') {
                // Update archive settings
                $settings = [
                    'auto_archive_applications' => $_POST['auto_archive_applications'] ?? 'false',
                    'archive_applications_after_days' => intval($_POST['archive_applications_after_days'] ?? 365),
                    'auto_archive_students' => $_POST['auto_archive_students'] ?? 'false',
                    'archive_students_after_days' => intval($_POST['archive_students_after_days'] ?? 730),
                    'auto_archive_invoices' => $_POST['auto_archive_invoices'] ?? 'false',
                    'archive_invoices_after_days' => intval($_POST['archive_invoices_after_days'] ?? 180),
                ];
                
                foreach ($settings as $key => $value) {
                    updateArchiveSetting($key, $value);
                }
                
                $message = "Archive settings updated successfully!";
                $message_type = "success";
            }
        }
    }
}

// Get archive statistics
$archive_stats = getArchiveStatistics();

// Get archive settings
$auto_archive_apps = getArchiveSetting('auto_archive_applications', 'false');
$archive_apps_days = getArchiveSetting('archive_applications_after_days', '365');
$auto_archive_students = getArchiveSetting('auto_archive_students', 'false');
$archive_students_days = getArchiveSetting('archive_students_after_days', '730');
$auto_archive_invoices = getArchiveSetting('auto_archive_invoices', 'false');
$archive_invoices_days = getArchiveSetting('archive_invoices_after_days', '180');

// Get archived records based on active tab
$conn = getDBConnection();
$archived_records = [];
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($active_tab === 'applications') {
    $query = "SELECT aa.*, u.full_name as archived_by_name 
              FROM archived_applications aa 
              LEFT JOIN users u ON aa.archived_by = u.user_id";
    if ($search_term) {
        $query .= " WHERE aa.application_number LIKE ? OR aa.first_name LIKE ? OR aa.last_name LIKE ? OR aa.email LIKE ?";
    }
    $query .= " ORDER BY aa.archived_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    if ($search_term) {
        $search_param = "%$search_term%";
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $archived_records[] = $row;
    }
    $stmt->close();
} elseif ($active_tab === 'students') {
    $query = "SELECT as.*, u.full_name as archived_by_name 
              FROM archived_students as 
              LEFT JOIN users u ON as.archived_by = u.user_id";
    if ($search_term) {
        $query .= " WHERE as.student_number LIKE ? OR as.first_name LIKE ? OR as.last_name LIKE ? OR as.email LIKE ?";
    }
    $query .= " ORDER BY as.archived_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    if ($search_term) {
        $search_param = "%$search_term%";
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $archived_records[] = $row;
    }
    $stmt->close();
} elseif ($active_tab === 'invoices') {
    $query = "SELECT ai.*, u.full_name as archived_by_name 
              FROM archived_invoices ai 
              LEFT JOIN users u ON ai.archived_by = u.user_id";
    if ($search_term) {
        $query .= " WHERE ai.invoice_number LIKE ? OR ai.payment_reference LIKE ?";
    }
    $query .= " ORDER BY ai.archived_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    if ($search_term) {
        $search_param = "%$search_term%";
        $stmt->bind_param("ss", $search_param, $search_param);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $archived_records[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Management - Admin</title>
    <link rel="stylesheet" href="../css/d_styles.css">
    <style>
        .archive-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            font-weight: 600;
            color: #666;
        }
        .tab.active {
            background: var(--primary);
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .archive-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .archive-table th {
            background: var(--primary);
            color: white;
            padding: 12px;
            text-align: left;
        }
        .archive-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .archive-table tr:hover {
            background: #f8f9fa;
        }
        .settings-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input[type="checkbox"] {
            margin-right: 8px;
        }
        .form-group input[type="number"] {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php echo getMobileMenuToggle(); ?>
    <?php echo getSidebarOverlay(); ?>
    
    <div class="dashboard-wrap container">
        <nav class="sidebar">
            <?php echo getSidebarMenu($_SESSION['role']); ?>
        </nav>
        
        <div class="content">
            <h1>Archive Management</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Archive Statistics -->
            <div class="archive-stats">
                <div class="stat-card">
                    <h3>Archived Applications</h3>
                    <p class="number"><?php echo number_format($archive_stats['applications'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Archived Students</h3>
                    <p class="number"><?php echo number_format($archive_stats['students'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Archived Invoices</h3>
                    <p class="number"><?php echo number_format($archive_stats['invoices'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Archived Documents</h3>
                    <p class="number"><?php echo number_format($archive_stats['documents'] ?? 0); ?></p>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab <?php echo $active_tab === 'applications' ? 'active' : ''; ?>" onclick="switchTab('applications')">Applications</button>
                <button class="tab <?php echo $active_tab === 'students' ? 'active' : ''; ?>" onclick="switchTab('students')">Students</button>
                <button class="tab <?php echo $active_tab === 'invoices' ? 'active' : ''; ?>" onclick="switchTab('invoices')">Invoices</button>
                <button class="tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" onclick="switchTab('settings')">Settings</button>
            </div>
            
            <!-- Applications Tab -->
            <div class="tab-content <?php echo $active_tab === 'applications' ? 'active' : ''; ?>">
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                        <input type="hidden" name="tab" value="applications">
                        <input type="text" name="search" placeholder="Search by application number, name, or email..." value="<?php echo htmlspecialchars($search_term); ?>" style="flex: 1;">
                        <button type="submit" class="cta-btn">Search</button>
                        <?php if ($search_term): ?>
                            <a href="?tab=applications" class="cta-btn" style="background: #6c757d;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Application #</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Archived By</th>
                            <th>Archived Date</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archived_records)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    No archived applications found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archived_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['application_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><span class="badge badge-<?php 
                                        echo $record['status'] === 'enrolled' ? 'success' : 
                                            ($record['status'] === 'rejected' || $record['status'] === 'ineligible' ? 'danger' : 'info'); 
                                    ?>"><?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($record['archived_by_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($record['archived_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['archive_reason'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Students Tab -->
            <div class="tab-content <?php echo $active_tab === 'students' ? 'active' : ''; ?>">
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                        <input type="hidden" name="tab" value="students">
                        <input type="text" name="search" placeholder="Search by student number, name, or email..." value="<?php echo htmlspecialchars($search_term); ?>" style="flex: 1;">
                        <button type="submit" class="cta-btn">Search</button>
                        <?php if ($search_term): ?>
                            <a href="?tab=students" class="cta-btn" style="background: #6c757d;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Student #</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Archived By</th>
                            <th>Archived Date</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archived_records)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    No archived students found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archived_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['student_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><span class="badge badge-<?php 
                                        echo $record['status'] === 'graduated' ? 'success' : 
                                            ($record['status'] === 'withdrawn' ? 'danger' : 'warning'); 
                                    ?>"><?php echo ucfirst($record['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($record['archived_by_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($record['archived_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['archive_reason'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Invoices Tab -->
            <div class="tab-content <?php echo $active_tab === 'invoices' ? 'active' : ''; ?>">
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                        <input type="hidden" name="tab" value="invoices">
                        <input type="text" name="search" placeholder="Search by invoice number or payment reference..." value="<?php echo htmlspecialchars($search_term); ?>" style="flex: 1;">
                        <button type="submit" class="cta-btn">Search</button>
                        <?php if ($search_term): ?>
                            <a href="?tab=invoices" class="cta-btn" style="background: #6c757d;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Archived By</th>
                            <th>Archived Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archived_records)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                    No archived invoices found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archived_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['invoice_number']); ?></td>
                                    <td>K<?php echo number_format($record['amount'], 2); ?></td>
                                    <td><span class="badge badge-<?php 
                                        echo $record['status'] === 'paid' ? 'success' : 'warning'; 
                                    ?>"><?php echo ucfirst($record['status']); ?></span></td>
                                    <td><?php echo $record['payment_date'] ? date('Y-m-d', strtotime($record['payment_date'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($record['archived_by_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($record['archived_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Settings Tab -->
            <div class="tab-content <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                <div class="settings-form">
                    <h2>Auto-Archive Settings</h2>
                    <form method="POST">
                        <?php echo generateCSRFToken(); ?>
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_archive_applications" value="true" <?php echo $auto_archive_apps === 'true' ? 'checked' : ''; ?>>
                                Automatically archive applications
                            </label>
                            <p style="margin: 5px 0 0 25px; color: #666; font-size: 0.9rem;">
                                Archive applications that are completed/rejected and older than:
                                <input type="number" name="archive_applications_after_days" value="<?php echo $archive_apps_days; ?>" min="30" max="3650"> days
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_archive_students" value="true" <?php echo $auto_archive_students === 'true' ? 'checked' : ''; ?>>
                                Automatically archive students
                            </label>
                            <p style="margin: 5px 0 0 25px; color: #666; font-size: 0.9rem;">
                                Archive students that are inactive/graduated and older than:
                                <input type="number" name="archive_students_after_days" value="<?php echo $archive_students_days; ?>" min="30" max="3650"> days
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_archive_invoices" value="true" <?php echo $auto_archive_invoices === 'true' ? 'checked' : ''; ?>>
                                Automatically archive invoices
                            </label>
                            <p style="margin: 5px 0 0 25px; color: #666; font-size: 0.9rem;">
                                Archive paid invoices older than:
                                <input type="number" name="archive_invoices_after_days" value="<?php echo $archive_invoices_days; ?>" min="30" max="3650"> days
                            </p>
                        </div>
                        
                        <button type="submit" class="cta-btn">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }
    </script>
    <?php echo getMobileMenuScript(); ?>
</body>
</html>


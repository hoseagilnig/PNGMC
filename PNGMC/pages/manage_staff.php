<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'includes/menu_helper.php';
require_once 'includes/db_config.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    if ($conn) {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $role = $_POST['role'];
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $username, $password_hash, $full_name, $email, $phone, $role);
                
                if ($stmt->execute()) {
                    $message = "Staff member added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } elseif ($_POST['action'] === 'edit') {
                $user_id = $_POST['user_id'];
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $role = $_POST['role'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $full_name, $email, $phone, $role, $status, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Staff member updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } elseif ($_POST['action'] === 'delete') {
                $user_id = $_POST['user_id'];
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $message = "Staff member deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
        $conn->close();
    }
}

// Get all staff
$conn = getDBConnection();
$staff = [];
if ($conn) {
    $result = $conn->query("SELECT user_id, username, full_name, email, phone, role, status, created_at, last_login FROM users ORDER BY created_at DESC");
    if ($result) {
        $staff = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Staff - Admin</title>
  <link rel="stylesheet" href="../css/d_styles.css">
  <style>
    .message { padding: 12px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .form-section { background: var(--card-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .form-row.full { grid-template-columns: 1fr; }
    label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--primary); }
    input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .btn-group { display: flex; gap: 10px; margin-top: 15px; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: var(--primary); color: white; }
    .badge { padding: 4px 8px; border-radius: 3px; font-size: 0.85rem; }
    .badge-active { background: #28a745; color: white; }
    .badge-inactive { background: #dc3545; color: white; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { background: white; margin: 50px auto; padding: 20px; width: 90%; max-width: 600px; border-radius: 10px; }
  </style>
</head>
<body>
    <div class="dashboard-wrap container">
    <nav class="sidebar" aria-label="Main navigation">
      <div class="brand">
        <a href="admin_dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
          <img src="../images/pnmc.png" alt="logo"> 
          <strong>PNGMC</strong>
        </a>
      </div>
      <div class="menu">
        <a class="menu-item" href="admin_dashboard.php">Dashboard</a>
        <a class="menu-item active" href="manage_staff.php">Manage Staff</a>
        <a class="menu-item" href="system_settings.php">System Settings</a>
        <a class="menu-item" href="reports.php">Reports</a>
      </div>
    </nav>

    <div class="content">
      <div class="main-card">
        <h1>Manage Staff</h1>
        
        <?php if ($message): ?>
          <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
          <h2>Add New Staff Member</h2>
          <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
              <div>
                <label>Username *</label>
                <input type="text" name="username" required>
              </div>
              <div>
                <label>Password *</label>
                <input type="password" name="password" required>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Full Name *</label>
                <input type="text" name="full_name" required>
              </div>
              <div>
                <label>Role *</label>
                <select name="role" required>
                  <option value="admin">Administration</option>
                  <option value="finance">Finance</option>
                  <option value="studentservices">Student Services</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Email</label>
                <input type="email" name="email">
              </div>
              <div>
                <label>Phone</label>
                <input type="text" name="phone">
              </div>
            </div>
            <div class="btn-group">
              <button type="submit" class="btn btn-primary">Add Staff Member</button>
            </div>
          </form>
        </div>

        <div class="form-section">
          <h2>All Staff Members</h2>
          <table>
            <thead>
              <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($staff)): ?>
                <tr><td colspan="8" style="text-align: center;">No staff members found.</td></tr>
              <?php else: ?>
                <?php foreach ($staff as $member): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($member['phone'] ?? '-'); ?></td>
                    <td><?php echo ucfirst($member['role']); ?></td>
                    <td><span class="badge <?php echo $member['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo ucfirst($member['status']); ?></span></td>
                    <td><?php echo $member['last_login'] ? date('Y-m-d H:i', strtotime($member['last_login'])) : 'Never'; ?></td>
                    <td>
                      <button onclick="editStaff(<?php echo htmlspecialchars(json_encode($member)); ?>)" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.9rem;">Edit</button>
                      <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.9rem;">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <h2>Edit Staff Member</h2>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="form-row">
          <div>
            <label>Full Name *</label>
            <input type="text" name="full_name" id="edit_full_name" required>
          </div>
          <div>
            <label>Role *</label>
            <select name="role" id="edit_role" required>
              <option value="admin">Administration</option>
              <option value="finance">Finance</option>
              <option value="studentservices">Student Services</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Email</label>
            <input type="email" name="email" id="edit_email">
          </div>
          <div>
            <label>Phone</label>
            <input type="text" name="phone" id="edit_phone">
          </div>
        </div>
        <div class="form-row">
          <div>
            <label>Status *</label>
            <select name="status" id="edit_status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="btn-group">
          <button type="submit" class="btn btn-primary">Update</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function editStaff(member) {
      document.getElementById('edit_user_id').value = member.user_id;
      document.getElementById('edit_full_name').value = member.full_name;
      document.getElementById('edit_email').value = member.email || '';
      document.getElementById('edit_phone').value = member.phone || '';
      document.getElementById('edit_role').value = member.role;
      document.getElementById('edit_status').value = member.status;
      document.getElementById('editModal').style.display = 'block';
    }
    
    function closeModal() {
      document.getElementById('editModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target == modal) {
        closeModal();
      }
    }
  </script>
</body>
</html>


<?php
/**
 * Manage Chatbot Knowledge Base
 * Admin page to add, edit, and manage chatbot responses
 */

session_start();
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/auth_check.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $role = $_POST['role'] ?? '';
            $keyword = trim($_POST['keyword'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $priority = intval($_POST['priority'] ?? 0);
            
            if ($role && $keyword && $title && $content) {
                $stmt = $conn->prepare("INSERT INTO chatbot_knowledge (role, keyword, title, content, priority) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $role, $keyword, $title, $content, $priority);
                if ($stmt->execute()) {
                    $message = "Knowledge entry added successfully!";
                } else {
                    $error = "Error adding entry: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "All fields are required!";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $role = $_POST['role'] ?? '';
            $keyword = trim($_POST['keyword'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $priority = intval($_POST['priority'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id && $role && $keyword && $title && $content) {
                $stmt = $conn->prepare("UPDATE chatbot_knowledge SET role = ?, keyword = ?, title = ?, content = ?, priority = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("ssssiii", $role, $keyword, $title, $content, $priority, $is_active, $id);
                if ($stmt->execute()) {
                    $message = "Knowledge entry updated successfully!";
                } else {
                    $error = "Error updating entry: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM chatbot_knowledge WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Knowledge entry deleted successfully!";
                } else {
                    $error = "Error deleting entry: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get all knowledge entries
$result = $conn->query("SELECT * FROM chatbot_knowledge ORDER BY role, priority DESC, keyword");
$knowledge_entries = [];
while ($row = $result->fetch_assoc()) {
    $knowledge_entries[] = $row;
}

// Get entry for editing
$edit_entry = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM chatbot_knowledge WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_entry = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>Manage Chatbot Knowledge Base</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $edit_entry ? 'Edit' : 'Add'; ?> Knowledge Entry</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_entry ? 'edit' : 'add'; ?>">
                        <?php if ($edit_entry): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_entry['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>User Role:</label>
                            <select name="role" class="form-control" required>
                                <option value="admin" <?php echo ($edit_entry && $edit_entry['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="finance" <?php echo ($edit_entry && $edit_entry['role'] === 'finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="studentservices" <?php echo ($edit_entry && $edit_entry['role'] === 'studentservices') ? 'selected' : ''; ?>>Student Admin Service</option>
                                <option value="hod" <?php echo ($edit_entry && $edit_entry['role'] === 'hod') ? 'selected' : ''; ?>>HOD</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Keyword/Topic:</label>
                            <input type="text" name="keyword" class="form-control" value="<?php echo htmlspecialchars($edit_entry['keyword'] ?? ''); ?>" required>
                            <small class="form-text text-muted">Keyword that triggers this response (e.g., "invoice", "enroll", "workflow")</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Title:</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_entry['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Content (HTML allowed):</label>
                            <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($edit_entry['content'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">You can use HTML tags like &lt;p&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;strong&gt;, etc.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Priority:</label>
                            <input type="number" name="priority" class="form-control" value="<?php echo $edit_entry['priority'] ?? 0; ?>" min="0" max="100">
                            <small class="form-text text-muted">Higher priority entries are matched first (0-100)</small>
                        </div>
                        
                        <?php if ($edit_entry): ?>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" <?php echo $edit_entry['is_active'] ? 'checked' : ''; ?>>
                                    Active
                                </label>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary"><?php echo $edit_entry ? 'Update' : 'Add'; ?> Entry</button>
                        <?php if ($edit_entry): ?>
                            <a href="manage_chatbot_knowledge.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Knowledge Entries</h3>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Keyword</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($knowledge_entries as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['role']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['keyword']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['title']); ?></td>
                                    <td><?php echo $entry['priority']; ?></td>
                                    <td>
                                        <?php if ($entry['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $entry['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <div class="card">
            <div class="card-header">
                <h3>Configuration</h3>
            </div>
            <div class="card-body">
                <p><strong>Current Mode:</strong> <?php 
                    require_once __DIR__ . '/includes/chatbot_config.php';
                    echo CHATBOT_MODE === 'database' ? 'Database' : 'AI API';
                ?></p>
                <p>To change the chatbot mode, edit <code>pages/includes/chatbot_config.php</code></p>
                <p>To use AI API, set your API keys in the config file.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


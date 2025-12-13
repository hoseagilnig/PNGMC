<?php
session_start();
if (!isset($_SESSION['student_loggedin']) || $_SESSION['student_loggedin'] !== true) {
    header('Location: student_login.php');
    exit;
}

require_once 'pages/includes/db_config.php';

$message = '';
$message_type = '';
$student = null;
$conn = getDBConnection();

if ($conn) {
    $student_id = $_SESSION['student_id'];
    
    // Handle photo upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
        $file = $_FILES['profile_photo'];
        
        // Check if file was uploaded
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $file['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $message = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
                $message_type = "error";
            } else {
                // Validate file size (max 5MB)
                $max_size = 5 * 1024 * 1024; // 5MB in bytes
                if ($file['size'] > $max_size) {
                    $message = "File size exceeds 5MB limit. Please upload a smaller image.";
                    $message_type = "error";
                } else {
                    // Create upload directory if it doesn't exist
                    $upload_dir = __DIR__ . '/uploads/student_photos';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $student_number = $_SESSION['student_number'];
                    $unique_filename = 'student_' . $student_number . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . '/' . $unique_filename;
                    $relative_path = 'uploads/student_photos/' . $unique_filename;
                    
                    // Delete old photo if exists
                    $stmt = $conn->prepare("SELECT profile_photo_path FROM students WHERE student_id = ?");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $old_photo = $result->fetch_assoc()) {
                        if (!empty($old_photo['profile_photo_path'])) {
                            $old_file_path = __DIR__ . '/' . $old_photo['profile_photo_path'];
                            if (file_exists($old_file_path)) {
                                @unlink($old_file_path);
                            }
                        }
                    }
                    $stmt->close();
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        // Update database
                        $stmt = $conn->prepare("UPDATE students SET profile_photo_path = ? WHERE student_id = ?");
                        $stmt->bind_param("si", $relative_path, $student_id);
                        
                        if ($stmt->execute()) {
                            $message = "Profile photo uploaded successfully!";
                            $message_type = "success";
                        } else {
                            $message = "Error updating database: " . $stmt->error;
                            $message_type = "error";
                            @unlink($file_path); // Remove file if DB update fails
                        }
                        $stmt->close();
                    } else {
                        $message = "Error uploading file. Please try again.";
                        $message_type = "error";
                    }
                }
            }
        } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $message = "File size exceeds the maximum allowed size.";
            $message_type = "error";
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $message = "Error uploading file. Error code: " . $file['error'];
            $message_type = "error";
        }
    }
    
    // Handle photo deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photo'])) {
        $stmt = $conn->prepare("SELECT profile_photo_path FROM students WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $photo_data = $result->fetch_assoc()) {
            if (!empty($photo_data['profile_photo_path'])) {
                $file_path = __DIR__ . '/' . $photo_data['profile_photo_path'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        }
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE students SET profile_photo_path = NULL WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        if ($stmt->execute()) {
            $message = "Profile photo deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting photo: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
    
    // Get student information
    $stmt = $conn->prepare("SELECT s.*, sa.username, sa.account_status, sa.last_login, p.program_name 
        FROM students s 
        LEFT JOIN student_accounts sa ON s.student_id = sa.student_id 
        LEFT JOIN programs p ON s.program_id = p.program_id
        WHERE s.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PNG Maritime College</title>
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
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #1d4e89;
            margin-bottom: 20px;
            border-bottom: 2px solid #1d4e89;
            padding-bottom: 10px;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .profile-header {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        .photo-section {
            text-align: center;
        }
        .photo-preview {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #1d4e89;
            margin-bottom: 15px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 3rem;
        }
        .photo-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 3rem;
            margin: 0 auto 15px;
            border: 4px solid #1d4e89;
        }
        .upload-form {
            margin-top: 20px;
        }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: #1d4e89;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .file-input-label:hover {
            background: #153d6b;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #1d4e89;
            color: white;
        }
        .btn-primary:hover {
            background: #153d6b;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
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
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .info-value {
            color: #333;
            font-size: 1rem;
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
        .form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .photo-preview, .photo-placeholder {
                margin: 0 auto 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PNG Maritime College - Student Portal</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
            <a href="student_dashboard.php" class="logout-btn">Dashboard</a>
            <a href="student_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>My Profile</h2>
            
            <div class="profile-header">
                <div class="photo-section">
                    <?php if (!empty($student['profile_photo_path']) && file_exists(__DIR__ . '/' . $student['profile_photo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_photo_path']); ?>" 
                             alt="Profile Photo" 
                             class="photo-preview"
                             id="photoPreview">
                    <?php else: ?>
                        <div class="photo-placeholder">👤</div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="file-input-wrapper">
                            <input type="file" 
                                   name="profile_photo" 
                                   id="profilePhoto" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif"
                                   onchange="previewPhoto(this)">
                            <label for="profilePhoto" class="file-input-label">📷 Choose Photo</label>
                        </div>
                        <div class="form-help">
                            Accepted formats: JPEG, PNG, GIF (Max 5MB)
                        </div>
                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                            <?php if (!empty($student['profile_photo_path'])): ?>
                                <button type="submit" 
                                        name="delete_photo" 
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete your profile photo?');">
                                    Delete Photo
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div style="flex: 1;">
                    <h3 style="color: #1d4e89; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['student_number']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Program</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['program_name'] ?? 'Not assigned'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Enrollment Date</div>
                            <div class="info-value">
                                <?php echo $student['enrollment_date'] ? date('M d, Y', strtotime($student['enrollment_date'])) : 'Not enrolled'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span style="padding: 4px 8px; background: #28a745; color: white; border-radius: 3px;">
                                    <?php echo ucfirst($student['status'] ?? 'active'); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($student['date_of_birth']): ?>
                        <div class="info-item">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($student['gender']): ?>
                        <div class="info-item">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['gender']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($student['address']): ?>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($student['address']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="student_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('photoPreview');
                    if (!preview) {
                        // Create preview element if it doesn't exist
                        const placeholder = document.querySelector('.photo-placeholder');
                        if (placeholder) {
                            placeholder.outerHTML = '<img src="' + e.target.result + '" alt="Profile Photo" class="photo-preview" id="photoPreview">';
                        }
                    } else {
                        preview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>


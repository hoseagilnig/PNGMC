<?php
session_start();
// Allow admin, studentservices, hod, and finance roles to view documents
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod', 'finance'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_config.php';

$document_id = $_GET['id'] ?? 0;
$conn = getDBConnection();

if ($conn && $document_id) {
    // Get document details
    $stmt = $conn->prepare("SELECT d.*, a.application_id, a.application_number FROM application_documents d 
                            INNER JOIN applications a ON d.application_id = a.application_id 
                            WHERE d.document_id = ?");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    $stmt->close();
    
    if ($document && !empty($document['file_path'])) {
        // Resolve file path - handle both absolute and relative paths
        $file_path = $document['file_path'];
        $original_path = $file_path;
        
        // Normalize path separators
        $file_path = str_replace('\\', '/', $file_path);
        
        // If path is relative, make it relative to the project root
        if (!file_exists($file_path) || !is_file($file_path)) {
            // Try relative to project root (one level up from pages/)
            $project_root = dirname(__DIR__);
            
            // Remove leading slash if present for relative paths
            $relative_path = ltrim($file_path, '/');
            $absolute_path = $project_root . '/' . $relative_path;
            
            if (file_exists($absolute_path) && is_file($absolute_path)) {
                $file_path = $absolute_path;
            } else {
                // Try with original path structure
                $absolute_path = $project_root . '/' . $file_path;
                if (file_exists($absolute_path) && is_file($absolute_path)) {
                    $file_path = $absolute_path;
                } else {
                    // Try Windows-style path
                    $absolute_path = $project_root . '\\' . str_replace('/', '\\', $relative_path);
                    if (file_exists($absolute_path) && is_file($absolute_path)) {
                        $file_path = $absolute_path;
                    }
                }
            }
        }
        
        // Final check if file exists
        if (file_exists($file_path) && is_file($file_path)) {
            $file_name = $document['document_name'];
            $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Determine content type
        $content_types = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $content_type = $content_types[$file_ext] ?? 'application/octet-stream';
        
        // Check if download requested
        if (isset($_GET['download']) && $_GET['download'] == '1') {
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($file_name) . '"');
        } else {
            // For PDFs, always use inline to display in browser
            if ($file_ext === 'pdf') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . htmlspecialchars($file_name) . '"');
            } else {
                header('Content-Type: ' . $content_type);
                header('Content-Disposition: inline; filename="' . htmlspecialchars($file_name) . '"');
            }
        }
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
            readfile($file_path);
            exit;
        } else {
            // File doesn't exist - show debugging info
            header('Content-Type: text/html; charset=utf-8');
            echo "<html><body>";
            echo "<h2>Document File Not Found</h2>";
            echo "<p><strong>Document ID:</strong> " . htmlspecialchars($document_id) . "</p>";
            echo "<p><strong>Original Path:</strong> " . htmlspecialchars($original_path) . "</p>";
            echo "<p><strong>Tried Path:</strong> " . htmlspecialchars($file_path) . "</p>";
            echo "<p><strong>Project Root:</strong> " . htmlspecialchars(dirname(__DIR__)) . "</p>";
            echo "<p><strong>File Exists Check:</strong> " . (file_exists($file_path) ? 'Yes' : 'No') . "</p>";
            if (file_exists($file_path)) {
                echo "<p><strong>Is File:</strong> " . (is_file($file_path) ? 'Yes' : 'No') . "</p>";
            }
            echo "<p><a href='application_details.php?id=" . $document['application_id'] . "'>Back to Application</a></p>";
            echo "</body></html>";
            $conn->close();
            exit;
        }
    } else {
        // Document not found in database
        header('HTTP/1.0 404 Not Found');
        echo "Document not found in database.";
        $conn->close();
        exit;
    }
}

// If document not found or access denied
header('HTTP/1.0 404 Not Found');
echo "Document not found or access denied.";
if ($conn) {
    $conn->close();
}
?>


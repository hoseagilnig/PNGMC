<?php
session_start();
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'studentservices', 'hod', 'finance'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db_config.php';

$application_id = $_GET['application_id'] ?? 0;
$conn = getDBConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Document Debug</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #1d4e89; color: white; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Document Debug Information</h1>
    
    <?php if ($application_id): ?>
        <h2>Application ID: <?php echo $application_id; ?></h2>
        
        <?php
        // Get application info
        $app_result = $conn->query("SELECT application_number, first_name, last_name FROM applications WHERE application_id = $application_id");
        $app = $app_result->fetch_assoc();
        if ($app) {
            echo "<p><strong>Application:</strong> {$app['application_number']} - {$app['first_name']} {$app['last_name']}</p>";
        }
        
        // Get documents
        $doc_result = $conn->query("SELECT * FROM application_documents WHERE application_id = $application_id");
        $documents = $doc_result->fetch_all(MYSQLI_ASSOC);
        
        echo "<h3>Documents in Database: " . count($documents) . "</h3>";
        
        if (empty($documents)) {
            echo "<p class='error'>No documents found in database for this application.</p>";
            echo "<p>Documents need to be uploaded by the applicant. Check if there's a document upload interface.</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Document ID</th><th>Type</th><th>Name</th><th>File Path</th><th>File Exists</th><th>Actions</th></tr>";
            
            foreach ($documents as $doc) {
                $file_path = $doc['file_path'];
                $file_exists = false;
                $resolved_path = '';
                
                if (!empty($file_path)) {
                    // Try direct path
                    if (file_exists($file_path)) {
                        $file_exists = true;
                        $resolved_path = $file_path;
                    } else {
                        // Try relative to project root
                        $project_root = dirname(__DIR__);
                        $absolute_path = $project_root . '/' . ltrim($file_path, '/');
                        if (file_exists($absolute_path)) {
                            $file_exists = true;
                            $resolved_path = $absolute_path;
                        }
                    }
                }
                
                echo "<tr>";
                echo "<td>{$doc['document_id']}</td>";
                echo "<td>{$doc['document_type']}</td>";
                echo "<td>{$doc['document_name']}</td>";
                echo "<td>" . htmlspecialchars($file_path) . "</td>";
                echo "<td class='" . ($file_exists ? 'success' : 'error') . "'>" . ($file_exists ? 'YES' : 'NO') . "</td>";
                if ($file_exists) {
                    echo "<td><a href='view_document.php?id={$doc['document_id']}' target='_blank'>View</a></td>";
                } else {
                    echo "<td>Cannot view - file not found</td>";
                }
                echo "</tr>";
                
                if (!$file_exists && !empty($file_path)) {
                    echo "<tr><td colspan='6' style='font-size: 0.9em; color: #666;'>";
                    echo "Tried paths:<br>";
                    echo "1. " . htmlspecialchars($file_path) . "<br>";
                    echo "2. " . htmlspecialchars($project_root . '/' . ltrim($file_path, '/')) . "<br>";
                    echo "Project root: " . htmlspecialchars($project_root);
                    echo "</td></tr>";
                }
            }
            
            echo "</table>";
        }
        ?>
        
        <p><a href="application_details.php?id=<?php echo $application_id; ?>">Back to Application Details</a></p>
    <?php else: ?>
        <p>Please provide application_id parameter: ?application_id=123</p>
    <?php endif; ?>
    
    <?php $conn->close(); ?>
</body>
</html>


<?php
/**
 * Document Path Fix Script
 * This script diagnoses and fixes document file path issues
 * 
 * Access via: http://localhost/sms2/database/fix_document_paths.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$message = '';
$message_type = '';
$fixed_count = 0;
$not_found_count = 0;
$already_correct_count = 0;
$issues = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_paths'])) {
    $conn = getDBConnection();
    if (!$conn) {
        $message = "Database connection failed!";
        $message_type = "error";
    } else {
        // Get all documents
        $result = $conn->query("SELECT document_id, application_id, file_path, document_name FROM application_documents WHERE file_path IS NOT NULL AND file_path != ''");
        $documents = $result->fetch_all(MYSQLI_ASSOC);
        
        $project_root = dirname(__DIR__);
        
        foreach ($documents as $doc) {
            $original_path = $doc['file_path'];
            $document_id = $doc['document_id'];
            $resolved_path = null;
            
            // Normalize path separators
            $normalized_path = str_replace('\\', '/', $original_path);
            
            // Check if file exists as-is
            if (file_exists($original_path) && is_file($original_path)) {
                $already_correct_count++;
                continue;
            }
            
            // Try different path resolutions
            $attempts = [
                $original_path,
                $normalized_path,
                $project_root . '/' . ltrim($normalized_path, '/'),
                $project_root . '/' . $normalized_path,
                str_replace('pages/', '', $normalized_path), // Remove pages/ if present
                str_replace('../', '', $normalized_path), // Remove ../ if present
            ];
            
            // Also try Windows-style paths
            $windows_path = str_replace('/', '\\', $normalized_path);
            $attempts[] = $project_root . '\\' . ltrim($windows_path, '\\');
            $attempts[] = $project_root . '\\' . $windows_path;
            
            foreach ($attempts as $attempt) {
                if (file_exists($attempt) && is_file($attempt)) {
                    $resolved_path = $attempt;
                    break;
                }
            }
            
            if ($resolved_path) {
                // File found - update path in database if different
                if ($resolved_path !== $original_path) {
                    // Store relative path if possible, otherwise absolute
                    $relative_path = str_replace($project_root . '/', '', $resolved_path);
                    $relative_path = str_replace($project_root . '\\', '', $relative_path);
                    $relative_path = ltrim($relative_path, '/\\');
                    
                    // Use relative path if it's shorter and makes sense
                    $new_path = (strlen($relative_path) < strlen($resolved_path) && !strpos($relative_path, ':')) 
                        ? $relative_path 
                        : $resolved_path;
                    
                    $stmt = $conn->prepare("UPDATE application_documents SET file_path = ? WHERE document_id = ?");
                    $stmt->bind_param("si", $new_path, $document_id);
                    if ($stmt->execute()) {
                        $fixed_count++;
                        $issues[] = [
                            'document_id' => $document_id,
                            'original' => $original_path,
                            'fixed' => $new_path,
                            'status' => 'fixed'
                        ];
                    }
                    $stmt->close();
                } else {
                    $already_correct_count++;
                }
            } else {
                // File not found
                $not_found_count++;
                $issues[] = [
                    'document_id' => $document_id,
                    'original' => $original_path,
                    'status' => 'not_found',
                    'attempts' => $attempts
                ];
            }
        }
        
        $message = "Scan complete! Fixed: $fixed_count, Already correct: $already_correct_count, Not found: $not_found_count";
        $message_type = "success";
        $conn->close();
    }
} else {
    // Just show diagnostics
    $conn = getDBConnection();
    if ($conn) {
        $result = $conn->query("SELECT COUNT(*) as total FROM application_documents WHERE file_path IS NOT NULL AND file_path != ''");
        $total_docs = $result->fetch_assoc()['total'];
        
        $result = $conn->query("SELECT document_id, application_id, file_path, document_name FROM application_documents WHERE file_path IS NOT NULL AND file_path != '' LIMIT 10");
        $sample_docs = $result->fetch_all(MYSQLI_ASSOC);
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Document Paths</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9rem; }
        th { background: #1d4e89; color: white; }
        .btn { padding: 10px 20px; background: #1d4e89; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #163c6a; }
        .fixed { color: green; }
        .not-found { color: red; }
        .path-info { font-size: 0.85rem; color: #666; }
    </style>
</head>
<body>
    <h1>Document Path Fix Script</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="message info">
        <h3>What this script does:</h3>
        <ul>
            <li>Scans all documents in the database</li>
            <li>Checks if files exist at the stored paths</li>
            <li>Attempts to find files using different path resolutions</li>
            <li>Updates database with correct paths if files are found</li>
            <li>Reports files that cannot be found</li>
        </ul>
    </div>
    
    <?php if (isset($total_docs)): ?>
        <div class="message info">
            <p><strong>Total documents in database:</strong> <?php echo $total_docs; ?></p>
            <p><strong>Project root:</strong> <?php echo htmlspecialchars(dirname(__DIR__)); ?></p>
        </div>
        
        <?php if (!empty($sample_docs)): ?>
            <h3>Sample Documents (First 10):</h3>
            <table>
                <thead>
                    <tr>
                        <th>Document ID</th>
                        <th>Application ID</th>
                        <th>Document Name</th>
                        <th>File Path</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $project_root = dirname(__DIR__);
                    foreach ($sample_docs as $doc): 
                        $file_exists = false;
                        $resolved = '';
                        
                        if (!empty($doc['file_path'])) {
                            if (file_exists($doc['file_path'])) {
                                $file_exists = true;
                                $resolved = $doc['file_path'];
                            } else {
                                $normalized = str_replace('\\', '/', $doc['file_path']);
                                $absolute = $project_root . '/' . ltrim($normalized, '/');
                                if (file_exists($absolute)) {
                                    $file_exists = true;
                                    $resolved = $absolute;
                                }
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo $doc['document_id']; ?></td>
                            <td><?php echo $doc['application_id']; ?></td>
                            <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                            <td class="path-info"><?php echo htmlspecialchars($doc['file_path']); ?></td>
                            <td class="<?php echo $file_exists ? 'fixed' : 'not-found'; ?>">
                                <?php echo $file_exists ? '✓ Found' : '✗ Not Found'; ?>
                                <?php if ($file_exists && $resolved !== $doc['file_path']): ?>
                                    <br><small>Resolved to: <?php echo htmlspecialchars($resolved); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (!empty($issues)): ?>
        <h3>Issues Found:</h3>
        <table>
            <thead>
                <tr>
                    <th>Document ID</th>
                    <th>Status</th>
                    <th>Original Path</th>
                    <th>Fixed/Resolved Path</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?php echo $issue['document_id']; ?></td>
                        <td class="<?php echo $issue['status'] === 'fixed' ? 'fixed' : 'not-found'; ?>">
                            <?php echo ucfirst($issue['status']); ?>
                        </td>
                        <td class="path-info"><?php echo htmlspecialchars($issue['original']); ?></td>
                        <td>
                            <?php if ($issue['status'] === 'fixed'): ?>
                                <span class="fixed"><?php echo htmlspecialchars($issue['fixed']); ?></span>
                            <?php else: ?>
                                <span class="not-found">File not found</span>
                                <?php if (isset($issue['attempts'])): ?>
                                    <br><small>Tried <?php echo count($issue['attempts']); ?> different paths</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <form method="POST" style="margin-top: 30px;">
        <button type="submit" name="fix_paths" class="btn" onclick="return confirm('This will scan all documents and update paths where files are found. Continue?');">
            🔧 Scan and Fix Document Paths
        </button>
    </form>
    
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <h3>Manual Check:</h3>
        <p>To check a specific application's documents, use:</p>
        <p><code>pages/debug_documents.php?application_id=123</code></p>
    </div>
</body>
</html>


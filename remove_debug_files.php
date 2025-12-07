<?php
/**
 * Remove Debug and Test Files Script
 * Removes test and debug files that should not be in production
 * 
 * Usage: php remove_debug_files.php
 */

$files_to_remove = [
    // Test files
    'pages/test_requirements_api.php',
    'pages/test_gemini_api.php',
    'pages/test_chatbot_api.php',
    'pages/test_finance_sas_workflow.php',
    'test_student_login.php',
    'database/test_connection.php',
    
    // Debug files
    'pages/debug_documents.php',
    
    // Test student account creation
    'database/create_test_student_account.php',
    
    // List models (debugging tool)
    'pages/list_gemini_models.php',
];

$removed = [];
$not_found = [];
$errors = [];

echo "Removing debug and test files...\n\n";

foreach ($files_to_remove as $file) {
    $full_path = __DIR__ . '/' . $file;
    
    if (file_exists($full_path)) {
        if (unlink($full_path)) {
            $removed[] = $file;
            echo "✓ Removed: $file\n";
        } else {
            $errors[] = $file;
            echo "✗ Error removing: $file (check permissions)\n";
        }
    } else {
        $not_found[] = $file;
        echo "- Not found: $file (already removed?)\n";
    }
}

echo "\n";
echo "Summary:\n";
echo "  Removed: " . count($removed) . " files\n";
echo "  Not found: " . count($not_found) . " files\n";
echo "  Errors: " . count($errors) . " files\n";

if (!empty($removed)) {
    echo "\n✓ Successfully removed " . count($removed) . " debug/test files.\n";
}

if (!empty($errors)) {
    echo "\n⚠ Warning: Could not remove some files. Check file permissions.\n";
    echo "  Files: " . implode(', ', $errors) . "\n";
}

echo "\nDone!\n";

?>


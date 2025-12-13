<?php
/**
 * Production .htaccess Setup Script
 * Merges production security settings into existing .htaccess
 * 
 * Usage: php setup_production_htaccess.php
 */

$htaccess_file = __DIR__ . '/.htaccess';
$production_file = __DIR__ . '/.htaccess_production';

if (!file_exists($production_file)) {
    die("Error: .htaccess_production file not found!\n");
}

// Read existing .htaccess
$existing_content = '';
if (file_exists($htaccess_file)) {
    $existing_content = file_get_contents($htaccess_file);
}

// Read production settings
$production_content = file_get_contents($production_file);

// Merge: Keep existing PHP settings, add production security settings
$merged_content = "# Production Security Settings\n";
$merged_content .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Add production security settings
$merged_content .= $production_content . "\n\n";

// Preserve existing custom settings (if any)
if (!empty($existing_content)) {
    $merged_content .= "# Existing Custom Settings\n";
    $merged_content .= $existing_content;
}

// Backup existing file
if (file_exists($htaccess_file)) {
    $backup_file = $htaccess_file . '.backup.' . date('YmdHis');
    copy($htaccess_file, $backup_file);
    echo "Backed up existing .htaccess to: $backup_file\n";
}

// Write merged content
if (file_put_contents($htaccess_file, $merged_content)) {
    echo "✓ Successfully updated .htaccess with production security settings!\n";
    echo "  File: $htaccess_file\n";
} else {
    die("Error: Could not write to .htaccess file. Check permissions.\n");
}

echo "\nDone! Review the .htaccess file to ensure all settings are correct.\n";

?>


<?php
/**
 * Check and Fix Database Migration
 * This page automatically checks and fixes missing columns
 * Access via: http://localhost/sms2/database/check_and_fix.php
 */

require_once __DIR__ . '/../pages/includes/db_config.php';

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed!");
}

// Check if application_type exists
$check = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_type'");
$needs_fix = ($check->num_rows === 0);

if ($needs_fix) {
    // Run migration automatically
    header('Location: fix_migration.php');
    exit;
} else {
    // Already fixed
    header('Location: ../enroll_engine_rating1.php?migration=complete');
    exit;
}


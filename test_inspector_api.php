<?php
/**
 * Test Inspector API
 * Simulate the API call to get assigned projects
 */

// Start session to simulate logged-in inspector
session_start();

// Set up inspector session (simulate login)
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = 1001; // Inspector user ID
$_SESSION['admin_email'] = 'inspector@buildhub.com';
$_SESSION['admin_scope'] = 'INSPECTOR';
$_SESSION['admin_role'] = 'contractor';

echo "🧪 Testing Inspector API...\n\n";

// Include the API file and capture output
ob_start();
include 'backend/api/inspector/get_assigned_projects.php';
$apiOutput = ob_get_clean();

echo "API Response:\n";
echo $apiOutput . "\n";

// Also test without session to see error handling
echo "\n🧪 Testing without session...\n";
session_destroy();
session_start();

ob_start();
include 'backend/api/inspector/get_assigned_projects.php';
$noSessionOutput = ob_get_clean();

echo "API Response (no session):\n";
echo $noSessionOutput . "\n";
?>
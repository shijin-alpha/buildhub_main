<?php
echo "=== TESTING HOMEOWNER PROGRESS API ENDPOINT ===\n";

// Start session like the API does
session_start();
$_SESSION['user_id'] = 28;
$_SESSION['role'] = 'homeowner';

// Capture the API output
ob_start();
include 'backend/api/homeowner/get_progress_updates.php';
$homeowner_output = ob_get_clean();

echo "Homeowner API Output:\n";
echo $homeowner_output;
echo "\n\n";

echo "=== TESTING CONTRACTOR PROGRESS API ENDPOINT ===\n";

// Reset session for contractor
session_destroy();
session_start();
$_SESSION['user_id'] = 29;
$_SESSION['role'] = 'contractor';

// Capture the API output
ob_start();
include 'backend/api/contractor/get_progress_updates.php';
$contractor_output = ob_get_clean();

echo "Contractor API Output:\n";
echo $contractor_output;
echo "\n\n";

echo "=== TESTING SESSION BRIDGE ===\n";

// Test session bridge
ob_start();
include 'backend/api/homeowner/session_bridge.php';
$session_output = ob_get_clean();

echo "Session Bridge Output:\n";
echo $session_output;
?>
<?php
require_once 'config/database.php';

// Simulate architect 31 session
session_start();
$_SESSION['user_id'] = 31;

echo "=== TESTING ARCHITECT 31 GET_ASSIGNED_REQUESTS ===\n";

// Include the actual API file
ob_start();
include 'api/architect/get_assigned_requests.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output;
?>
<?php
session_start();

// Set session for contractor 27 (who created the report)
$_SESSION['user_id'] = 27;
$_SESSION['role'] = 'contractor';

echo "=== Testing Sent Reports API ===\n";
echo "Session User ID: " . $_SESSION['user_id'] . "\n";
echo "Session Role: " . $_SESSION['role'] . "\n\n";

// Include the API file
ob_start();
include 'backend/api/contractor/get_sent_reports.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output;
?>
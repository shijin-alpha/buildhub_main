<?php
session_start();

echo "=== HOMEOWNER SESSION TEST ===\n\n";

// Check current session
echo "Current Session Data:\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
echo "Email: " . ($_SESSION['email'] ?? 'Not set') . "\n";

// Set homeowner session for testing
$_SESSION['user_id'] = 28;
$_SESSION['role'] = 'homeowner';
$_SESSION['username'] = 'SHIJIN THOMAS MCA2024-2026';
$_SESSION['email'] = 'shijinthomas2026@mca.ajce.in';

echo "\nAfter setting homeowner session:\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Role: " . $_SESSION['role'] . "\n";
echo "Username: " . $_SESSION['username'] . "\n";
echo "Email: " . $_SESSION['email'] . "\n";

// Test the session bridge
echo "\n=== TESTING SESSION BRIDGE ===\n";
ob_start();
include 'backend/api/homeowner/session_bridge.php';
$bridgeResponse = ob_get_clean();
echo "Session Bridge Response:\n" . $bridgeResponse . "\n";

echo "\n=== SESSION TEST COMPLETE ===\n";
?>
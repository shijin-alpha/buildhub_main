<?php
/**
 * Simple script to establish homeowner session for testing
 */

session_start();

// Set homeowner session
$_SESSION['user_id'] = 28;
$_SESSION['user_type'] = 'homeowner';
$_SESSION['username'] = 'test_homeowner';

echo "<h1>🏠 Homeowner Session Established</h1>\n";
echo "<p>Session variables set:</p>\n";
echo "<ul>\n";
echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>\n";
echo "<li><strong>User Type:</strong> " . $_SESSION['user_type'] . "</li>\n";
echo "<li><strong>Username:</strong> " . $_SESSION['username'] . "</li>\n";
echo "</ul>\n";

echo "<p>✅ You can now test the homeowner receipt upload functionality.</p>\n";
echo "<p><a href='test_homeowner_receipt_frontend.html'>🧪 Go to Receipt Upload Test</a></p>\n";
?>
<?php
/**
 * Simple script to establish contractor session for testing
 * In production, this would be handled by proper login system
 */

session_start();

// Set contractor session
$_SESSION['user_id'] = 29;
$_SESSION['user_type'] = 'contractor';
$_SESSION['username'] = 'test_contractor';

echo "<h1>🔧 Contractor Session Established</h1>\n";
echo "<p>Session variables set:</p>\n";
echo "<ul>\n";
echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>\n";
echo "<li><strong>User Type:</strong> " . $_SESSION['user_type'] . "</li>\n";
echo "<li><strong>Username:</strong> " . $_SESSION['username'] . "</li>\n";
echo "</ul>\n";

echo "<p>✅ You can now test the contractor receipt upload functionality.</p>\n";
echo "<p><a href='contractor_receipt_upload_test.html'>🧪 Go to Receipt Upload Test</a></p>\n";
?>
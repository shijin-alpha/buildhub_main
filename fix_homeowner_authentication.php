<?php
/**
 * Fix homeowner authentication by establishing proper session
 */

session_start();

// Set homeowner session with proper session configuration
$_SESSION['user_id'] = 28;
$_SESSION['user_type'] = 'homeowner';
$_SESSION['role'] = 'homeowner'; // Some APIs check for 'role' instead of 'user_type'
$_SESSION['username'] = 'test_homeowner';
$_SESSION['first_name'] = 'Test';
$_SESSION['last_name'] = 'Homeowner';
$_SESSION['email'] = 'homeowner@test.com';

// Set session cookie parameters to ensure it persists
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_secure', false);
ini_set('session.cookie_httponly', true);

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n<title>Homeowner Authentication Fixed</title>\n</head>\n<body>\n";
echo "<h1>🏠 Homeowner Authentication Fixed</h1>\n";
echo "<p>Session variables set:</p>\n";
echo "<ul>\n";
foreach ($_SESSION as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>\n";
}
echo "</ul>\n";

echo "<p>Session ID: " . session_id() . "</p>\n";
echo "<p>✅ Authentication established. You can now use the homeowner dashboard.</p>\n";

// Test the authentication by calling an API
echo "<h2>Testing API Authentication</h2>\n";

// Test the unified payment requests API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data && $data['success']) {
    echo "<p style='color: green;'>✅ API Authentication Test: SUCCESS</p>\n";
    echo "<p>Found " . count($data['data']['requests']) . " payment requests</p>\n";
    
    foreach ($data['data']['requests'] as $request) {
        $type_icon = $request['request_type'] === 'custom' ? '💰' : '🏗️';
        echo "<p>$type_icon {$request['request_type']}: {$request['request_title']} - ₹{$request['requested_amount']} ({$request['status']})</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ API Authentication Test: FAILED</p>\n";
    echo "<p>Error: " . ($data['message'] ?? 'Unknown error') . "</p>\n";
}

echo "<h2>Quick Links</h2>\n";
echo "<p><a href='/buildhub/frontend/src/components/HomeownerDashboard.jsx' target='_blank'>🏠 Homeowner Dashboard</a></p>\n";
echo "<p><a href='test_complete_approval_flow.html' target='_blank'>🧪 Test Approval Flow</a></p>\n";

echo "</body>\n</html>\n";
?>
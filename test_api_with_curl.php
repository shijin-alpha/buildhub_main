<?php
// Start session and login as contractor
session_start();
$_SESSION['user_id'] = 29;
$_SESSION['role'] = 'contractor';
$_SESSION['email'] = 'shijinthomas248@gmail.com';

echo "=== Testing API with cURL ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n\n";

// Get the session cookie
$sessionName = session_name();
$sessionId = session_id();
$cookie = "{$sessionName}={$sessionId}";

// Make API request
$url = 'http://localhost/buildhub/backend/api/contractor/get_sent_reports.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}

echo "Response:\n";
echo $response . "\n";

// Try to parse JSON
$data = json_decode($response, true);
if ($data) {
    echo "\n=== Parsed Response ===\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if ($data['success']) {
        echo "Reports found: " . count($data['data']['reports']) . "\n";
        echo "Daily reports: " . count($data['data']['grouped_reports']['daily']) . "\n";
        echo "Statistics: " . json_encode($data['data']['statistics']) . "\n";
    } else {
        echo "Error: " . $data['message'] . "\n";
    }
} else {
    echo "Failed to parse JSON response\n";
}
?>
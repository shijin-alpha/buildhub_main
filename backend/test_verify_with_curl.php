<?php
/**
 * Test verify payment API with proper session
 */

echo "=== Testing Verify Payment API ===\n\n";

// Start session and set contractor
session_start();
$_SESSION['user_id'] = 29;
$_SESSION['user_type'] = 'contractor';

echo "Session set:\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n";
echo "- User Type: " . $_SESSION['user_type'] . "\n\n";

// Prepare the request
$url = 'http://localhost/buildhub/backend/api/contractor/verify_payment_receipt.php';
$data = [
    'payment_id' => 13,
    'verification_status' => 'verified',
    'verification_notes' => 'Test verification from script'
];

echo "Request:\n";
echo "URL: $url\n";
echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// Get session cookie
$sessionName = session_name();
$sessionId = session_id();
$cookie = "$sessionName=$sessionId";

echo "Cookie: $cookie\n\n";

// Make the request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . $cookie
]);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "Response:\n";
echo "HTTP Code: $httpCode\n";
echo "Headers:\n$headers\n";
echo "Body:\n$body\n\n";

// Try to parse JSON
$json = json_decode($body, true);
if ($json) {
    echo "Parsed JSON:\n";
    print_r($json);
} else {
    echo "Failed to parse JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

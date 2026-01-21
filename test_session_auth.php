<?php
session_start();

echo "Session Authentication Test:\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";

// Test if we can establish a homeowner session
$_SESSION['user_id'] = 28;
$_SESSION['user_type'] = 'homeowner';

echo "\nAfter setting session:\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Type: " . $_SESSION['user_type'] . "\n";

// Test the custom payment API with session
echo "\nTesting custom payment API with session:\n";

$testData = [
    'request_id' => 1,
    'action' => 'approve',
    'homeowner_notes' => 'Test approval with session'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/homeowner/respond_to_custom_payment.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$data = json_decode($response, true);
if ($data) {
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Message: " . $data['message'] . "\n";
}
?>
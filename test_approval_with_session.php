<?php
// First establish the session
session_start();
$_SESSION['user_id'] = 28;
$_SESSION['user_type'] = 'homeowner';
$_SESSION['username'] = 'test_homeowner';

echo "Testing Custom Payment Approval with Session:\n";
echo "Session User ID: " . $_SESSION['user_id'] . "\n\n";

// Test data for the custom payment request we know exists (ID: 1)
$testData = [
    'request_id' => 1,
    'action' => 'approve',
    'homeowner_notes' => 'Approved for overtime work - test with session',
    'approved_amount' => 2000
];

// Use cURL to maintain session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/homeowner/respond_to_custom_payment.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');

// First, establish session by calling the session establishment
$sessionResponse = file_get_contents('http://localhost/buildhub/establish_homeowner_session.php');

// Now make the API call
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$data = json_decode($response, true);
if ($data) {
    echo "\nParsed Response:\n";
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Message: " . $data['message'] . "\n";
    
    if ($data['success'] && isset($data['data'])) {
        echo "New Status: " . $data['data']['status'] . "\n";
        echo "Request Title: " . $data['data']['request_title'] . "\n";
        echo "Approved Amount: ₹" . $data['data']['approved_amount'] . "\n";
    }
}

// Check the database to verify the change
echo "\nVerifying database update:\n";
$pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $pdo->prepare("SELECT * FROM custom_payment_requests WHERE id = ?");
$stmt->execute([1]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo "Database Status: {$request['status']}\n";
    echo "Response Date: " . ($request['response_date'] ?? 'NULL') . "\n";
    echo "Homeowner Notes: " . ($request['homeowner_notes'] ?? 'NULL') . "\n";
    echo "Approved Amount: ₹" . ($request['approved_amount'] ?? 'NULL') . "\n";
}
?>
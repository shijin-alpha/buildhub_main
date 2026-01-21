<?php
// Test custom payment approval flow
session_start();
$_SESSION['user_id'] = 28; // Set homeowner session

// Test data for the custom payment request we know exists (ID: 1)
$testData = [
    'request_id' => 1,
    'action' => 'approve',
    'homeowner_notes' => 'Approved for overtime work',
    'approved_amount' => 2000
];

echo "Testing Custom Payment Approval Flow:\n";
echo "Request ID: {$testData['request_id']}\n";
echo "Action: {$testData['action']}\n\n";

// Test the custom payment response API directly
$url = 'http://localhost/buildhub/backend/api/homeowner/respond_to_custom_payment.php';
$postData = json_encode($testData);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$response = file_get_contents($url, false, $context);
$data = json_decode($response, true);

echo "API Response:\n";
echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
echo "Message: " . $data['message'] . "\n";

if (!$data['success']) {
    echo "\nDebugging - Let's check the custom payment request:\n";
    
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $stmt = $pdo->prepare("SELECT * FROM custom_payment_requests WHERE id = ? AND homeowner_id = ?");
    $stmt->execute([1, 28]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "Request found:\n";
        echo "- ID: {$request['id']}\n";
        echo "- Status: {$request['status']}\n";
        echo "- Homeowner ID: {$request['homeowner_id']}\n";
        echo "- Contractor ID: {$request['contractor_id']}\n";
        echo "- Amount: ₹{$request['requested_amount']}\n";
    } else {
        echo "Request not found in database!\n";
    }
}
?>
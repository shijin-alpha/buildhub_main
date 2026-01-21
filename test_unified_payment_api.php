<?php
session_start();
$_SESSION['user_id'] = 28; // Set homeowner session

// Test the unified payment requests API
$response = file_get_contents('http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php');
$data = json_decode($response, true);

echo "Unified Payment Requests API Test:\n";
echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";

if ($data['success']) {
    echo "Total requests: " . count($data['data']['requests']) . "\n";
    echo "\nRequests:\n";
    foreach ($data['data']['requests'] as $request) {
        echo "- ID: {$request['id']}, Type: {$request['request_type']}, Title: " . ($request['request_title'] ?? $request['stage_name']) . ", Amount: ₹{$request['requested_amount']}, Status: {$request['status']}\n";
    }
    
    echo "\nSummary:\n";
    echo "- Total: {$data['data']['summary']['total_requests']}\n";
    echo "- Pending: {$data['data']['summary']['pending_requests']}\n";
    echo "- Approved: {$data['data']['summary']['approved_requests']}\n";
} else {
    echo "Error: " . $data['message'] . "\n";
}
?>
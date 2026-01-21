<?php
// Test the structure of payment requests returned by the unified API
$response = file_get_contents('http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php?homeowner_id=28');
$data = json_decode($response, true);

echo "Testing Payment Request Structure:\n\n";

if ($data['success']) {
    foreach ($data['data']['requests'] as $request) {
        echo "Request ID: {$request['id']}\n";
        echo "Request Type: " . ($request['request_type'] ?? 'MISSING') . "\n";
        echo "Request Title: " . ($request['request_title'] ?? 'MISSING') . "\n";
        echo "Status: {$request['status']}\n";
        
        if ($request['request_type'] === 'custom') {
            echo "Category: " . ($request['category'] ?? 'MISSING') . "\n";
            echo "Urgency: " . ($request['urgency_level'] ?? 'MISSING') . "\n";
        }
        
        echo "Has all required fields for frontend: " . 
             (isset($request['request_type'], $request['request_title'], $request['status']) ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} else {
    echo "API Error: " . $data['message'] . "\n";
}
?>
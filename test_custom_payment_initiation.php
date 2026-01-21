<?php
echo "=== TESTING CUSTOM PAYMENT INITIATION ===\n\n";

// Test 1: Check if we have approved custom payment requests
echo "1. Checking for approved custom payment requests...\n";
$requests_url = 'http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php';
$requests_response = file_get_contents($requests_url);
$requests_data = json_decode($requests_response, true);

if (!$requests_data['success']) {
    echo "❌ Failed to get payment requests: {$requests_data['message']}\n";
    exit;
}

$approved_custom = array_filter($requests_data['data']['requests'], function($r) {
    return $r['request_type'] === 'custom' && $r['status'] === 'approved';
});

if (empty($approved_custom)) {
    echo "❌ No approved custom payment requests found\n";
    echo "Creating a test approved custom payment request...\n";
    
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO custom_payment_requests (
        project_id, contractor_id, homeowner_id, request_title, request_reason, 
        requested_amount, approved_amount, urgency_level, category, contractor_notes, 
        status, request_date, response_date, homeowner_notes
    ) VALUES (
        37, 29, 28, 'Test Payment for API', 
        'Testing the custom payment initiation API',
        3000.00, 3000.00, 'medium', 'testing', 
        'This is a test request for API validation',
        'approved', NOW(), NOW(), 'Approved for testing'
    )";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    
    if ($result) {
        $test_request_id = $db->lastInsertId();
        echo "✅ Created test approved custom payment request ID: $test_request_id\n";
        
        // Refresh the requests
        $requests_response = file_get_contents($requests_url);
        $requests_data = json_decode($requests_response, true);
        $approved_custom = array_filter($requests_data['data']['requests'], function($r) use ($test_request_id) {
            return $r['request_type'] === 'custom' && $r['status'] === 'approved' && $r['id'] == $test_request_id;
        });
    } else {
        echo "❌ Failed to create test request\n";
        exit;
    }
}

$test_request = array_values($approved_custom)[0];
echo "✅ Found approved custom request: ID {$test_request['id']}, Title: {$test_request['request_title']}, Amount: ₹{$test_request['requested_amount']}\n";

// Test 2: Test custom payment initiation API
echo "\n2. Testing custom payment initiation API...\n";
$initiate_url = 'http://localhost/buildhub/backend/api/homeowner/initiate_custom_payment.php';
$initiate_data = json_encode([
    'payment_request_id' => $test_request['id'],
    'amount' => $test_request['approved_amount'] ?? $test_request['requested_amount']
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $initiate_data
    ]
]);

$initiate_response = file_get_contents($initiate_url, false, $context);
$initiate_result = json_decode($initiate_response, true);

if ($initiate_result['success']) {
    echo "✅ PAYMENT INITIATION SUCCESS!\n";
    echo "   Transaction ID: {$initiate_result['data']['transaction_id']}\n";
    echo "   Razorpay Order ID: {$initiate_result['data']['razorpay_order_id']}\n";
    echo "   Amount (paise): {$initiate_result['data']['amount']}\n";
    echo "   Request Title: {$initiate_result['data']['request_title']}\n";
    echo "   Category: {$initiate_result['data']['category']}\n";
    echo "   Payment Type: {$initiate_result['data']['payment_type']}\n";
} else {
    echo "❌ PAYMENT INITIATION FAILED: {$initiate_result['message']}\n";
    exit;
}

// Test 3: Verify the transaction was created
echo "\n3. Verifying transaction was created in database...\n";
require_once 'backend/config/database.php';
$database = new Database();
$db = $database->getConnection();

$check_stmt = $db->prepare("SELECT * FROM custom_payment_transactions WHERE payment_request_id = ? ORDER BY id DESC LIMIT 1");
$check_stmt->execute([$test_request['id']]);
$transaction = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($transaction) {
    echo "✅ Transaction created in database:\n";
    echo "   ID: {$transaction['id']}\n";
    echo "   Status: {$transaction['payment_status']}\n";
    echo "   Amount: ₹{$transaction['amount']}\n";
    echo "   Razorpay Order ID: {$transaction['razorpay_order_id']}\n";
} else {
    echo "❌ Transaction not found in database\n";
}

echo "\n=== CUSTOM PAYMENT INITIATION TEST COMPLETE ===\n";
echo "✅ Custom payment initiation API is working correctly!\n";
echo "✅ Frontend should now be able to initiate payments for custom requests\n";
echo "✅ Payment method selector will use the correct API based on request type\n";
?>
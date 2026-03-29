<?php
require_once 'config/database.php';

echo "Testing Alternative Payment API...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, let's check if we have any stage payment requests
    echo "1. Checking existing stage payment requests...\n";
    $stmt = $db->query("SELECT * FROM stage_payment_requests LIMIT 5");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "   No stage payment requests found. Creating a test request...\n";
        
        // Create a test payment request
        $insertStmt = $db->prepare("
            INSERT INTO stage_payment_requests 
            (homeowner_id, contractor_id, stage_name, requested_amount, work_description, status) 
            VALUES (1, 1, 'Foundation Work', 1500000, 'Foundation and structural work completed', 'approved')
        ");
        $insertStmt->execute();
        $testRequestId = $db->lastInsertId();
        echo "   ✓ Created test payment request with ID: $testRequestId\n";
    } else {
        $testRequestId = $requests[0]['id'];
        echo "   ✓ Found existing payment request with ID: $testRequestId\n";
    }
    
    // Test the payment methods API
    echo "\n2. Testing payment methods API...\n";
    $amount = 1500000; // ₹15 lakhs
    
    $postData = json_encode(['amount' => $amount]);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents('http://localhost/buildhub/backend/api/homeowner/get_payment_methods.php', false, $context);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "   ✓ Payment methods API working\n";
        echo "   Available methods: " . count($data['data']['available_methods']) . "\n";
        foreach ($data['data']['available_methods'] as $key => $method) {
            echo "   - $key: {$method['name']}\n";
        }
    } else {
        echo "   ✗ Payment methods API failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
    // Test alternative payment initiation
    echo "\n3. Testing alternative payment initiation...\n";
    
    // Simulate session
    session_start();
    $_SESSION['user_id'] = 1; // Test homeowner ID
    
    $paymentData = [
        'payment_type' => 'stage_payment',
        'reference_id' => $testRequestId,
        'payment_method' => 'bank_transfer',
        'amount' => $amount,
        'notes' => 'Test bank transfer payment'
    ];
    
    $postData = json_encode($paymentData);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents('http://localhost/buildhub/backend/api/homeowner/initiate_alternative_payment.php', false, $context);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "   ✓ Alternative payment initiation working\n";
        echo "   Payment ID: {$data['data']['payment_id']}\n";
        echo "   Method: {$data['data']['payment_method']}\n";
        echo "   Amount: ₹" . number_format($data['data']['amount'], 2) . "\n";
        
        if (isset($data['data']['bank_details'])) {
            echo "   Bank details provided: Yes\n";
        }
        
        if (isset($data['data']['instructions'])) {
            echo "   Instructions provided: Yes\n";
        }
    } else {
        echo "   ✗ Alternative payment initiation failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
    // Check if alternative payment was created
    echo "\n4. Verifying database records...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM alternative_payments");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Alternative payments in database: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT * FROM alternative_payments ORDER BY id DESC LIMIT 1");
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Latest payment: ID {$payment['id']}, Method: {$payment['payment_method']}, Status: {$payment['payment_status']}\n";
    }
    
    echo "\n✅ Alternative Payment API Test Completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>
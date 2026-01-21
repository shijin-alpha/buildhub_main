<?php
echo "=== TESTING PAYMENT APPROVAL FLOW DIRECTLY ===\n\n";

// Test 1: Session Bridge
echo "1. Testing Session Bridge...\n";
$session_url = 'http://localhost/buildhub/backend/api/homeowner/session_bridge.php';
$session_response = file_get_contents($session_url);
$session_data = json_decode($session_response, true);

if ($session_data['success']) {
    echo "✅ Session established: User ID {$session_data['user']['id']}\n";
} else {
    echo "❌ Session failed: {$session_data['message']}\n";
}

// Test 2: Get All Payment Requests
echo "\n2. Testing Get All Payment Requests...\n";
$requests_url = 'http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php';
$requests_response = file_get_contents($requests_url);
$requests_data = json_decode($requests_response, true);

if ($requests_data['success']) {
    echo "✅ Found {$requests_data['data']['summary']['total_requests']} payment requests\n";
    
    // Find pending custom requests
    $pending_custom = array_filter($requests_data['data']['requests'], function($r) {
        return $r['request_type'] === 'custom' && $r['status'] === 'pending';
    });
    
    if (!empty($pending_custom)) {
        $test_request = array_values($pending_custom)[0];
        echo "✅ Found pending custom request: ID {$test_request['id']}, Title: {$test_request['request_title']}\n";
        
        // Test 3: Approve the Custom Payment Request
        echo "\n3. Testing Custom Payment Approval...\n";
        
        $approval_url = 'http://localhost/buildhub/backend/api/homeowner/respond_to_custom_payment.php';
        $approval_data = json_encode([
            'request_id' => $test_request['id'],
            'action' => 'approve',
            'homeowner_notes' => 'Approved via direct test',
            'approved_amount' => $test_request['requested_amount']
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $approval_data
            ]
        ]);
        
        $approval_response = file_get_contents($approval_url, false, $context);
        $approval_result = json_decode($approval_response, true);
        
        if ($approval_result['success']) {
            echo "✅ SUCCESS: {$approval_result['message']}\n";
            echo "New Status: {$approval_result['data']['status']}\n";
        } else {
            echo "❌ APPROVAL FAILED: {$approval_result['message']}\n";
        }
        
        // Test 4: Verify the change
        echo "\n4. Verifying the change...\n";
        $verify_response = file_get_contents($requests_url);
        $verify_data = json_decode($verify_response, true);
        
        if ($verify_data['success']) {
            $updated_request = array_filter($verify_data['data']['requests'], function($r) use ($test_request) {
                return $r['id'] == $test_request['id'];
            });
            
            if (!empty($updated_request)) {
                $updated = array_values($updated_request)[0];
                echo "✅ Request updated: Status is now '{$updated['status']}'\n";
                
                if ($updated['status'] === 'approved') {
                    echo "🎉 COMPLETE SUCCESS: Custom payment request approved!\n";
                }
            }
        }
        
    } else {
        echo "❌ No pending custom requests found for testing\n";
    }
} else {
    echo "❌ Failed to get payment requests: {$requests_data['message']}\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
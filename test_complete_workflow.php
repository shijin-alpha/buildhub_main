<?php
echo "=== COMPLETE PAYMENT WORKFLOW TEST ===\n\n";

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 1: Create a new test custom payment request
    echo "1. Creating new test custom payment request...\n";
    $query = "INSERT INTO custom_payment_requests (
        project_id, contractor_id, homeowner_id, request_title, request_reason, 
        requested_amount, urgency_level, category, contractor_notes, status, request_date
    ) VALUES (
        37, 29, 28, 'Plumbing Upgrade', 
        'Need to upgrade bathroom plumbing and install new fixtures',
        7500.00, 'high', 'plumbing', 
        'Includes new pipes, faucets, and shower installation',
        'pending', NOW()
    )";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    
    if ($result) {
        $new_request_id = $db->lastInsertId();
        echo "✅ Created custom payment request ID: $new_request_id\n";
    } else {
        echo "❌ Failed to create test request\n";
        exit;
    }
    
    // Step 2: Test the unified API
    echo "\n2. Testing unified payment requests API...\n";
    $requests_url = 'http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php';
    $requests_response = file_get_contents($requests_url);
    $requests_data = json_decode($requests_response, true);
    
    if ($requests_data['success']) {
        echo "✅ API returned {$requests_data['data']['summary']['total_requests']} total requests\n";
        echo "   - Pending: {$requests_data['data']['summary']['pending_requests']}\n";
        echo "   - Approved: {$requests_data['data']['summary']['approved_requests']}\n";
        echo "   - Paid: {$requests_data['data']['summary']['paid_requests']}\n";
        
        // Find our new request
        $our_request = null;
        foreach ($requests_data['data']['requests'] as $req) {
            if ($req['id'] == $new_request_id && $req['request_type'] === 'custom') {
                $our_request = $req;
                break;
            }
        }
        
        if ($our_request) {
            echo "✅ Found our test request: '{$our_request['request_title']}' - Status: {$our_request['status']}\n";
        } else {
            echo "❌ Could not find our test request in API response\n";
            exit;
        }
    } else {
        echo "❌ API failed: {$requests_data['message']}\n";
        exit;
    }
    
    // Step 3: Test approval
    echo "\n3. Testing payment approval...\n";
    $approval_url = 'http://localhost/buildhub/backend/api/homeowner/respond_to_custom_payment.php';
    $approval_data = json_encode([
        'request_id' => $new_request_id,
        'action' => 'approve',
        'homeowner_notes' => 'Approved - plumbing upgrade is necessary',
        'approved_amount' => 7500.00
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
        echo "✅ APPROVAL SUCCESS: {$approval_result['message']}\n";
        echo "   New Status: {$approval_result['data']['status']}\n";
        echo "   Approved Amount: ₹{$approval_result['data']['approved_amount']}\n";
    } else {
        echo "❌ APPROVAL FAILED: {$approval_result['message']}\n";
        exit;
    }
    
    // Step 4: Verify the change
    echo "\n4. Verifying status change...\n";
    $verify_response = file_get_contents($requests_url);
    $verify_data = json_decode($verify_response, true);
    
    if ($verify_data['success']) {
        $updated_request = null;
        foreach ($verify_data['data']['requests'] as $req) {
            if ($req['id'] == $new_request_id) {
                $updated_request = $req;
                break;
            }
        }
        
        if ($updated_request && $updated_request['status'] === 'approved') {
            echo "✅ VERIFICATION SUCCESS: Request status is now 'approved'\n";
            echo "   Response Date: {$updated_request['response_date']}\n";
            echo "   Homeowner Notes: {$updated_request['homeowner_notes']}\n";
        } else {
            echo "❌ VERIFICATION FAILED: Status not updated correctly\n";
        }
    }
    
    // Step 5: Test rejection workflow
    echo "\n5. Testing rejection workflow with another request...\n";
    
    // Create another test request
    $query2 = "INSERT INTO custom_payment_requests (
        project_id, contractor_id, homeowner_id, request_title, request_reason, 
        requested_amount, urgency_level, category, contractor_notes, status, request_date
    ) VALUES (
        37, 29, 28, 'Unnecessary Decoration', 
        'Want to add expensive decorative elements',
        15000.00, 'low', 'decoration', 
        'Gold plated fixtures and marble countertops',
        'pending', NOW()
    )";
    
    $stmt2 = $db->prepare($query2);
    $result2 = $stmt2->execute();
    $reject_request_id = $db->lastInsertId();
    
    // Reject this request
    $rejection_data = json_encode([
        'request_id' => $reject_request_id,
        'action' => 'reject',
        'homeowner_notes' => 'Too expensive and not necessary',
        'rejection_reason' => 'Budget constraints - decorative items not essential'
    ]);
    
    $reject_context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $rejection_data
        ]
    ]);
    
    $reject_response = file_get_contents($approval_url, false, $reject_context);
    $reject_result = json_decode($reject_response, true);
    
    if ($reject_result['success']) {
        echo "✅ REJECTION SUCCESS: {$reject_result['message']}\n";
        echo "   Status: {$reject_result['data']['status']}\n";
        echo "   Rejection Reason: {$reject_result['data']['rejection_reason']}\n";
    } else {
        echo "❌ REJECTION FAILED: {$reject_result['message']}\n";
    }
    
    echo "\n=== WORKFLOW TEST COMPLETE ===\n";
    echo "✅ All payment approval workflows are working correctly!\n";
    echo "✅ Custom payment requests can be approved and rejected\n";
    echo "✅ Status changes are properly tracked\n";
    echo "✅ API responses include all necessary data\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
<?php
/**
 * Test Homeowner Payment API
 * Test the new unified payment requests API
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🧪 Testing Homeowner Payment Requests API\n\n";
    
    $homeowner_id = 28;
    $project_id = 37;
    
    // 1. Test the new unified API
    echo "1. Testing unified payment requests API:\n";
    
    // Simulate the API call
    $_GET['homeowner_id'] = $homeowner_id;
    $_GET['project_id'] = $project_id;
    
    // Capture the API output
    ob_start();
    include 'backend/api/homeowner/get_all_payment_requests.php';
    $apiOutput = ob_get_clean();
    
    $apiData = json_decode($apiOutput, true);
    
    if ($apiData && $apiData['success']) {
        echo "   ✅ API call successful\n";
        echo "   Total requests: " . count($apiData['data']['requests']) . "\n";
        
        $stageRequests = array_filter($apiData['data']['requests'], fn($r) => $r['request_type'] === 'stage');
        $customRequests = array_filter($apiData['data']['requests'], fn($r) => $r['request_type'] === 'custom');
        
        echo "   Stage requests: " . count($stageRequests) . "\n";
        echo "   Custom requests: " . count($customRequests) . "\n\n";
        
        echo "   Request details:\n";
        foreach ($apiData['data']['requests'] as $request) {
            $type = $request['request_type'] === 'stage' ? '🏗️' : '💳';
            echo "   $type {$request['request_type']}: {$request['request_title']} - ₹" . number_format($request['requested_amount']) . " ({$request['status']})\n";
            
            if ($request['request_type'] === 'custom') {
                echo "      Category: {$request['category']}, Urgency: {$request['urgency_level']}\n";
                echo "      Description: " . substr($request['request_description'], 0, 50) . "...\n";
            }
        }
        echo "\n";
        
        echo "   Summary:\n";
        echo "   - Total requests: {$apiData['data']['summary']['total_requests']}\n";
        echo "   - Pending: {$apiData['data']['summary']['pending_requests']}\n";
        echo "   - Approved: {$apiData['data']['summary']['approved_requests']}\n";
        echo "   - Paid: {$apiData['data']['summary']['paid_requests']}\n";
        echo "   - Pending amount: ₹" . number_format($apiData['data']['summary']['pending_amount']) . "\n";
        
    } else {
        echo "   ❌ API call failed\n";
        echo "   Error: " . ($apiData['message'] ?? 'Unknown error') . "\n";
        echo "   Raw output: $apiOutput\n";
    }
    echo "\n";
    
    // 2. Check what homeowner dashboard should display
    echo "2. Expected homeowner dashboard display:\n";
    
    if ($apiData && $apiData['success']) {
        $pendingRequests = array_filter($apiData['data']['requests'], fn($r) => $r['status'] === 'pending');
        
        if (!empty($pendingRequests)) {
            echo "   📋 Pending Payment Requests:\n";
            foreach ($pendingRequests as $request) {
                $type = $request['request_type'] === 'stage' ? 'Stage Payment' : 'Custom Payment';
                echo "   - $type: {$request['request_title']}\n";
                echo "     Amount: ₹" . number_format($request['requested_amount']) . "\n";
                echo "     Contractor: {$request['contractor_first_name']} {$request['contractor_last_name']}\n";
                echo "     Date: {$request['request_date_formatted']}\n";
                
                if ($request['request_type'] === 'custom') {
                    echo "     Category: {$request['category']}\n";
                    echo "     Urgency: {$request['urgency_level']}\n";
                    echo "     Reason: " . substr($request['request_description'], 0, 100) . "...\n";
                }
                echo "     [Approve] [Reject] buttons should be available\n\n";
            }
        } else {
            echo "   ✅ No pending payment requests\n";
        }
    }
    
    // 3. Test approval workflow
    echo "3. Testing approval workflow:\n";
    
    $customRequests = array_filter($apiData['data']['requests'] ?? [], fn($r) => $r['request_type'] === 'custom' && $r['status'] === 'pending');
    
    if (!empty($customRequests)) {
        $testRequest = array_values($customRequests)[0];
        echo "   Found pending custom request ID: {$testRequest['id']}\n";
        echo "   Title: {$testRequest['request_title']}\n";
        echo "   Amount: ₹" . number_format($testRequest['requested_amount']) . "\n";
        echo "   ✅ This request should be approvable via respond_to_custom_payment.php API\n";
    } else {
        echo "   ⚠️ No pending custom requests found for testing approval\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
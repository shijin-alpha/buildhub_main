<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Testing Payment History Project Fix...\n\n";
    
    // Test project ID 1 (from the debug info)
    $project_id = 1;
    $contractor_id = 29;
    
    echo "1. Testing project existence check for project ID: $project_id\n";
    
    // Check if project exists in construction_projects table first, then layout_requests
    $project_exists_query = "
        SELECT 
            CASE 
                WHEN cp.id IS NOT NULL THEN 'construction_projects'
                WHEN lr.id IS NOT NULL THEN 'layout_requests'
                ELSE NULL
            END as project_source,
            COALESCE(cp.id, lr.id) as project_id,
            COALESCE(cp.homeowner_id, lr.user_id) as homeowner_id
        FROM (SELECT :project_id as search_id) s
        LEFT JOIN construction_projects cp ON cp.id = s.search_id
        LEFT JOIN layout_requests lr ON lr.id = s.search_id
        WHERE cp.id IS NOT NULL OR lr.id IS NOT NULL
    ";
    
    $project_stmt = $db->prepare($project_exists_query);
    $project_stmt->execute([':project_id' => $project_id]);
    $project_result = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project_result) {
        echo "   ✅ Project found in: " . $project_result['project_source'] . "\n";
        echo "   📋 Project ID: " . $project_result['project_id'] . "\n";
        echo "   👤 Homeowner ID: " . $project_result['homeowner_id'] . "\n";
    } else {
        echo "   ❌ Project not found in either table\n";
    }
    
    echo "\n2. Testing stage_payment_requests table for project $project_id:\n";
    
    // Check if there are any payment requests for this project
    $payment_query = "
        SELECT 
            spr.*,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = :project_id 
        AND spr.contractor_id = :contractor_id
        ORDER BY spr.request_date DESC
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $payment_requests = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payment_requests)) {
        echo "   ⚠️  No payment requests found for this project\n";
        echo "   💡 This is normal for new projects - payment requests are created when contractor submits them\n";
    } else {
        echo "   ✅ Found " . count($payment_requests) . " payment requests:\n";
        foreach ($payment_requests as $request) {
            echo "      - ID: {$request['id']}, Stage: {$request['stage_name']}, Amount: ₹{$request['requested_amount']}, Status: {$request['status']}\n";
        }
    }
    
    echo "\n3. Testing the complete payment history API call:\n";
    
    // Simulate the API call
    $_GET['project_id'] = $project_id;
    $_SESSION['user_id'] = $contractor_id;
    
    // Capture output
    ob_start();
    include 'api/contractor/get_payment_history.php';
    $api_output = ob_get_clean();
    
    $api_data = json_decode($api_output, true);
    
    if ($api_data && $api_data['success']) {
        echo "   ✅ API call successful\n";
        echo "   📊 Payment requests: " . count($api_data['data']['payment_requests']) . "\n";
        echo "   💰 Total requested: ₹" . number_format($api_data['data']['summary']['total_requested']) . "\n";
    } else {
        echo "   ❌ API call failed: " . ($api_data['message'] ?? 'Unknown error') . "\n";
        echo "   📄 Raw output: " . substr($api_output, 0, 200) . "...\n";
    }
    
    echo "\n🎉 Payment history project fix test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
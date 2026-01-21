<?php
// Test the fixed payment history API
session_start();
$_SESSION['user_id'] = 29; // Set contractor ID

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=buildhub;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PAYMENT HISTORY API TEST ===\n\n";
    
    // Test 1: Check project 37 exists
    echo "1. Checking if project 37 exists:\n";
    $project_check = "
        SELECT 
            CASE 
                WHEN cp.id IS NOT NULL THEN 'construction_projects'
                WHEN lr.id IS NOT NULL THEN 'layout_requests'
                ELSE NULL
            END as project_source,
            COALESCE(cp.id, lr.id) as project_id,
            COALESCE(cp.homeowner_id, lr.user_id) as homeowner_id
        FROM (SELECT ? as search_id) s
        LEFT JOIN construction_projects cp ON (cp.id = s.search_id OR cp.estimate_id = s.search_id)
        LEFT JOIN layout_requests lr ON lr.id = s.search_id
        WHERE cp.id IS NOT NULL OR lr.id IS NOT NULL
    ";
    
    $stmt = $pdo->prepare($project_check);
    $stmt->execute([37]);
    $project_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project_result) {
        echo "✅ Project 37 found in {$project_result['project_source']} table\n";
        echo "   Project ID: {$project_result['project_id']}\n";
        echo "   Homeowner ID: {$project_result['homeowner_id']}\n\n";
    } else {
        echo "❌ Project 37 not found\n\n";
    }
    
    // Test 2: Check payment requests for project 37
    echo "2. Checking payment requests for project 37:\n";
    $payment_query = "
        SELECT 
            spr.*,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = ? 
        AND spr.contractor_id = ?
        ORDER BY spr.request_date DESC
    ";
    
    $stmt = $pdo->prepare($payment_query);
    $stmt->execute([37, 29]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($payments) . " payment requests\n";
    
    foreach ($payments as $payment) {
        echo "- ID: {$payment['id']}, Stage: {$payment['stage_name']}, Amount: ₹{$payment['requested_amount']}, Status: {$payment['status']}\n";
    }
    echo "\n";
    
    // Test 3: Call the actual API
    echo "3. Testing actual API call:\n";
    $_GET['project_id'] = 37;
    
    ob_start();
    include 'backend/api/contractor/get_payment_history.php';
    $api_response = ob_get_clean();
    
    echo "API Response:\n";
    echo $api_response . "\n\n";
    
    $api_data = json_decode($api_response, true);
    if ($api_data && $api_data['success']) {
        echo "✅ Payment history API working!\n";
        echo "Total requests: " . count($api_data['data']['payment_requests']) . "\n";
        echo "Summary: " . json_encode($api_data['data']['summary'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Payment history API failed: " . ($api_data['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
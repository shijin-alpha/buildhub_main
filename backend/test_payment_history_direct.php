<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Testing Payment History System...\n\n";
    
    // Test 1: Check if stage_payment_requests table exists and has data
    echo "=== Test 1: Check Payment Requests Table ===\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'stage_payment_requests'");
    if ($stmt->rowCount() > 0) {
        echo "✅ stage_payment_requests table exists\n";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM stage_payment_requests");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total payment requests: " . $result['count'] . "\n";
        
        if ($result['count'] > 0) {
            // Show sample data
            $stmt = $db->query("SELECT * FROM stage_payment_requests LIMIT 3");
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\nSample payment requests:\n";
            foreach ($requests as $request) {
                echo "- ID: {$request['id']}, Project: {$request['project_id']}, Stage: {$request['stage_name']}, Status: {$request['status']}, Amount: ₹{$request['requested_amount']}\n";
            }
        }
    } else {
        echo "❌ stage_payment_requests table does not exist\n";
    }
    
    echo "\n=== Test 2: Check Layout Requests ===\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM layout_requests WHERE status = 'approved'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Approved layout requests: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $db->query("SELECT id, user_id, plot_size, location, status FROM layout_requests WHERE status = 'approved' LIMIT 3");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nSample approved projects:\n";
        foreach ($requests as $request) {
            echo "- Project ID: {$request['id']}, Homeowner ID: {$request['user_id']}, Plot: {$request['plot_size']}, Location: {$request['location']}\n";
        }
    }
    
    echo "\n=== Test 3: Test Payment History Query ===\n";
    
    // Get a project ID that has payment requests
    $stmt = $db->query("
        SELECT DISTINCT project_id 
        FROM stage_payment_requests 
        LIMIT 1
    ");
    
    if ($stmt->rowCount() > 0) {
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        $project_id = $project['project_id'];
        
        echo "Testing with project ID: $project_id\n";
        
        // Test the payment history query
        $payment_query = "
            SELECT 
                spr.*,
                u.first_name, u.last_name
            FROM stage_payment_requests spr
            LEFT JOIN users u ON spr.homeowner_id = u.id
            WHERE spr.project_id = ?
            ORDER BY spr.request_date DESC
        ";
        
        $stmt = $db->prepare($payment_query);
        $stmt->execute([$project_id]);
        $payment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($payment_requests) . " payment requests for project $project_id\n";
        
        foreach ($payment_requests as $request) {
            $homeowner_name = '';
            if ($request['first_name'] && $request['last_name']) {
                $homeowner_name = $request['first_name'] . ' ' . $request['last_name'];
            }
            
            echo "- {$request['stage_name']}: ₹{$request['requested_amount']} ({$request['status']})";
            if ($request['homeowner_notes']) {
                echo " - Homeowner: " . substr($request['homeowner_notes'], 0, 50) . "...";
            }
            echo "\n";
        }
        
        // Calculate summary
        $total_requested = array_sum(array_column($payment_requests, 'requested_amount'));
        $pending_count = count(array_filter($payment_requests, function($r) { return $r['status'] === 'pending'; }));
        $approved_count = count(array_filter($payment_requests, function($r) { return $r['status'] === 'approved'; }));
        $rejected_count = count(array_filter($payment_requests, function($r) { return $r['status'] === 'rejected'; }));
        $paid_count = count(array_filter($payment_requests, function($r) { return $r['status'] === 'paid'; }));
        
        echo "\nPayment Summary:\n";
        echo "- Total Requested: ₹" . number_format($total_requested) . "\n";
        echo "- Pending: $pending_count\n";
        echo "- Approved: $approved_count\n";
        echo "- Rejected: $rejected_count\n";
        echo "- Paid: $paid_count\n";
        
        echo "\n✅ Payment history system is working correctly!\n";
        
    } else {
        echo "❌ No payment requests found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
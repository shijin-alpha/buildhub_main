<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== TESTING HOMEOWNER 32 PAYMENT REQUESTS ===\n";
    
    // Simulate the same query as the API
    $homeowner_id = 32;
    
    $query = "
        (SELECT 
            spr.id,
            spr.project_id,
            spr.contractor_id,
            spr.homeowner_id,
            spr.stage_name as request_title,
            spr.requested_amount,
            spr.completion_percentage,
            spr.status,
            spr.request_date,
            'stage' as request_type
        FROM stage_payment_requests spr
        WHERE spr.homeowner_id = ?)
        
        UNION ALL
        
        (SELECT 
            cpr.id,
            cpr.project_id,
            cpr.contractor_id,
            cpr.homeowner_id,
            cpr.request_title,
            cpr.requested_amount,
            100 as completion_percentage,
            cpr.status,
            cpr.request_date,
            'custom' as request_type
        FROM custom_payment_requests cpr
        WHERE cpr.homeowner_id = ?)
        
        ORDER BY request_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$homeowner_id, $homeowner_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($requests) . " payment requests for homeowner $homeowner_id:\n\n";
    
    foreach ($requests as $request) {
        echo "Payment ID: {$request['id']}\n";
        echo "  Type: {$request['request_type']}\n";
        echo "  Project: {$request['project_id']}\n";
        echo "  Amount: ₹{$request['requested_amount']}\n";
        echo "  Status: {$request['status']}\n";
        echo "  Title: {$request['request_title']}\n";
        echo "  Date: {$request['request_date']}\n";
        echo "  ---\n";
    }
    
    // Check if payment ID 16 exists anywhere
    echo "\n=== CHECKING PAYMENT ID 16 GLOBALLY ===\n";
    $stmt = $db->prepare("SELECT id, homeowner_id, requested_amount, status FROM stage_payment_requests WHERE id = 16");
    $stmt->execute();
    $payment16 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment16) {
        echo "Payment ID 16 found: Homeowner {$payment16['homeowner_id']}, Amount {$payment16['requested_amount']}, Status {$payment16['status']}\n";
    } else {
        echo "Payment ID 16 does NOT exist in the database\n";
    }
    
    // Check custom payments too
    $stmt = $db->prepare("SELECT id, homeowner_id, requested_amount, status FROM custom_payment_requests WHERE id = 16");
    $stmt->execute();
    $customPayment16 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customPayment16) {
        echo "Custom Payment ID 16 found: Homeowner {$customPayment16['homeowner_id']}, Amount {$customPayment16['requested_amount']}, Status {$customPayment16['status']}\n";
    } else {
        echo "Custom Payment ID 16 does NOT exist in the database\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
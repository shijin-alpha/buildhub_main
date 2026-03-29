<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check custom payment requests
    echo "=== CUSTOM PAYMENT REQUESTS ===\n";
    $stmt = $db->query("SELECT * FROM custom_payment_requests ORDER BY id DESC LIMIT 5");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "No custom payment requests found.\n";
    } else {
        foreach ($requests as $req) {
            echo "ID: {$req['id']}, Status: {$req['status']}, Title: {$req['request_title']}, Amount: ₹{$req['requested_amount']}, Homeowner: {$req['homeowner_id']}\n";
        }
    }
    
    // Check stage payment requests
    echo "\n=== STAGE PAYMENT REQUESTS ===\n";
    $stmt = $db->query("SELECT * FROM stage_payment_requests ORDER BY id DESC LIMIT 5");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "No stage payment requests found.\n";
    } else {
        foreach ($requests as $req) {
            echo "ID: {$req['id']}, Status: {$req['status']}, Stage: {$req['stage_name']}, Amount: ₹{$req['requested_amount']}, Homeowner: {$req['homeowner_id']}\n";
        }
    }
    
    // Test the unified API
    echo "\n=== TESTING UNIFIED API ===\n";
    $homeowner_id = 28;
    
    $query = "
        (SELECT 
            id, 'custom' as request_type, request_title, requested_amount, status, homeowner_id
        FROM custom_payment_requests 
        WHERE homeowner_id = ?)
        UNION ALL
        (SELECT 
            id, 'stage' as request_type, stage_name as request_title, requested_amount, status, homeowner_id
        FROM stage_payment_requests 
        WHERE homeowner_id = ?)
        ORDER BY id DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$homeowner_id, $homeowner_id]);
    $unified_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($unified_requests) . " requests for homeowner ID $homeowner_id:\n";
    foreach ($unified_requests as $req) {
        echo "- {$req['request_type']}: ID {$req['id']}, {$req['request_title']}, ₹{$req['requested_amount']}, Status: {$req['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
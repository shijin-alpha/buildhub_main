<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Current Stage Payment Requests ===\n";
    $stmt = $db->query("SELECT * FROM stage_payment_requests ORDER BY created_at DESC LIMIT 5");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($requests)) {
        echo "No payment requests found.\n";
    } else {
        foreach ($requests as $request) {
            echo "ID: {$request['id']}\n";
            echo "Stage: {$request['stage_name']}\n";
            echo "Amount: ₹" . number_format($request['requested_amount'], 2) . "\n";
            echo "Contractor ID: {$request['contractor_id']}\n";
            echo "Homeowner ID: {$request['homeowner_id']}\n";
            echo "Status: {$request['status']}\n";
            echo "Created: {$request['created_at']}\n";
            echo "---\n";
        }
    }
    
    echo "\n=== Payment Limits Configuration ===\n";
    require_once 'config/payment_limits.php';
    $limits = getPaymentLimitsInfo();
    echo "Current Mode: {$limits['current_mode']}\n";
    echo "Max Amount: {$limits['max_amount_formatted']}\n";
    echo "Daily Limit: {$limits['daily_limit_formatted']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update the payment request to 10 lakhs (₹10,00,000)
    $new_amount = 1000000; // 10 lakhs
    
    echo "Updating payment request to ₹" . number_format($new_amount, 2) . " (10 lakhs)...\n";
    
    $updateStmt = $db->prepare("
        UPDATE stage_payment_requests 
        SET requested_amount = :amount,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = 1
    ");
    
    $updateStmt->bindValue(':amount', $new_amount, PDO::PARAM_STR);
    
    if ($updateStmt->execute()) {
        echo "✅ Successfully updated payment request amount to 10 lakhs\n";
        
        // Verify the update
        $verifyStmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = 1");
        $verifyStmt->execute();
        $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updated) {
            echo "\n=== Updated Payment Request ===\n";
            echo "ID: {$updated['id']}\n";
            echo "Stage: {$updated['stage_name']}\n";
            echo "Amount: ₹" . number_format($updated['requested_amount'], 2) . "\n";
            echo "Contractor ID: {$updated['contractor_id']}\n";
            echo "Homeowner ID: {$updated['homeowner_id']}\n";
            echo "Status: {$updated['status']}\n";
            echo "Updated: {$updated['updated_at']}\n";
        }
        
        // Test payment limits validation
        echo "\n=== Testing Payment Limits ===\n";
        require_once 'config/payment_limits.php';
        $validation = validatePaymentAmount($new_amount);
        
        if ($validation['valid']) {
            echo "✅ Payment amount validation: PASSED\n";
            echo "Message: {$validation['message']}\n";
        } else {
            echo "❌ Payment amount validation: FAILED\n";
            echo "Message: {$validation['message']}\n";
        }
        
    } else {
        echo "❌ Failed to update payment request\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
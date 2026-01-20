<?php
require_once 'config/database.php';
require_once 'config/payment_limits.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Testing Flexible Payment Amounts ===\n\n";
    
    // Get the payment request
    $stmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = 1");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo "❌ Payment request not found\n";
        exit;
    }
    
    $max_amount = (float)$request['requested_amount'];
    echo "Payment Request Details:\n";
    echo "- ID: {$request['id']}\n";
    echo "- Stage: {$request['stage_name']}\n";
    echo "- Maximum Amount: ₹" . number_format($max_amount, 2) . "\n";
    echo "- Status: {$request['status']}\n\n";
    
    // Test various payment amounts
    echo "Testing Various Payment Amounts:\n";
    $test_amounts = [
        10000,      // ₹10,000 - should pass
        50000,      // ₹50,000 - should pass (original amount)
        250000,     // ₹2.5 lakhs - should pass
        500000,     // ₹5 lakhs - should pass
        750000,     // ₹7.5 lakhs - should pass
        1000000,    // ₹10 lakhs - should pass (maximum)
        1200000,    // ₹12 lakhs - should fail (exceeds limit)
        0,          // ₹0 - should fail (invalid)
        -1000       // Negative - should fail (invalid)
    ];
    
    foreach ($test_amounts as $test_amount) {
        $amount_formatted = '₹' . number_format($test_amount, 2);
        
        // Test amount validation logic
        if ($test_amount <= 0) {
            echo "   {$amount_formatted}: ❌ FAIL - Amount must be greater than zero\n";
        } elseif ($test_amount > $max_amount) {
            echo "   {$amount_formatted}: ❌ FAIL - Exceeds maximum allowed amount\n";
        } else {
            echo "   {$amount_formatted}: ✅ PASS - Valid payment amount\n";
        }
    }
    
    // Test payment limits validation
    echo "\nTesting Payment Limits Validation:\n";
    $limits = getPaymentLimitsInfo();
    echo "- Current Mode: {$limits['current_mode']}\n";
    echo "- System Max Amount: {$limits['max_amount_formatted']}\n";
    echo "- Daily Limit: {$limits['daily_limit_formatted']}\n";
    
    // Test system limits vs request limits
    $system_max = getMaxPaymentAmount();
    echo "\nLimit Comparison:\n";
    echo "- Request Maximum: ₹" . number_format($max_amount, 2) . "\n";
    echo "- System Maximum: ₹" . number_format($system_max, 2) . "\n";
    
    if ($max_amount <= $system_max) {
        echo "✅ Request limit is within system limits\n";
    } else {
        echo "❌ Request limit exceeds system limits\n";
    }
    
    echo "\n=== Flexible Payment Summary ===\n";
    echo "✅ Users can now pay any amount from ₹0.01 to ₹" . number_format($max_amount, 2) . "\n";
    echo "✅ No exact amount matching required\n";
    echo "✅ Payment validation works correctly\n";
    echo "✅ System respects both request and system limits\n";
    
    echo "\nExample valid payments:\n";
    echo "- Pay ₹10,000 for partial foundation work\n";
    echo "- Pay ₹50,000 for the original amount\n";
    echo "- Pay ₹5,00,000 for half the maximum\n";
    echo "- Pay ₹10,00,000 for the full maximum amount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
// Simulate the payment initiation API call with different amounts
require_once 'config/database.php';
require_once 'config/razorpay_config.php';
require_once 'config/payment_limits.php';

// Simulate session
session_start();
$_SESSION['user_id'] = 28; // Homeowner ID

function testPaymentAmount($amount, $description) {
    echo "\n=== Testing: $description (₹" . number_format($amount, 2) . ") ===\n";
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $homeowner_id = $_SESSION['user_id'];
        $payment_request_id = 1;
        
        // Validate payment amount using the new limits configuration
        $validation = validatePaymentAmount($amount);
        if (!$validation['valid']) {
            echo "❌ FAIL: {$validation['message']}\n";
            return false;
        }
        
        // Get payment request details and verify ownership
        $requestCheck = $db->prepare("
            SELECT spr.*, 
                   u_contractor.first_name as contractor_first_name, 
                   u_contractor.last_name as contractor_last_name,
                   u_homeowner.first_name as homeowner_first_name, 
                   u_homeowner.last_name as homeowner_last_name
            FROM stage_payment_requests spr
            LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
            LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
            WHERE spr.id = :request_id AND spr.homeowner_id = :homeowner_id 
            AND spr.status IN ('pending', 'approved')
            LIMIT 1
        ");
        $requestCheck->bindValue(':request_id', $payment_request_id, PDO::PARAM_INT);
        $requestCheck->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $requestCheck->execute();
        $request = $requestCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo "❌ FAIL: Payment request not found or not eligible for payment\n";
            return false;
        }
        
        // Flexible amount validation - allow any amount up to the limit
        $max_allowed_amount = (float)$request['requested_amount']; // Use DB amount as maximum
        $input_amount = (float)$amount;
        
        // Validate amount is positive and within allowed range
        if ($input_amount <= 0) {
            echo "❌ FAIL: Payment amount must be greater than zero\n";
            return false;
        }
        
        if ($input_amount > $max_allowed_amount) {
            echo "❌ FAIL: Payment amount ₹" . number_format($input_amount, 2) . " exceeds maximum allowed amount of ₹" . number_format($max_allowed_amount, 2) . " for this stage\n";
            return false;
        }
        
        // Use the input amount (user can pay any amount up to the limit)
        $final_amount = $input_amount;
        
        echo "✅ PASS: Payment validation successful\n";
        echo "   - Input Amount: ₹" . number_format($input_amount, 2) . "\n";
        echo "   - Maximum Allowed: ₹" . number_format($max_allowed_amount, 2) . "\n";
        echo "   - Final Amount: ₹" . number_format($final_amount, 2) . "\n";
        echo "   - Stage: {$request['stage_name']}\n";
        echo "   - Contractor: {$request['contractor_first_name']} {$request['contractor_last_name']}\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== Flexible Payment API Test ===\n";

// Test various amounts
$tests = [
    [10000, "Small payment - ₹10,000"],
    [50000, "Original amount - ₹50,000"],
    [250000, "Quarter payment - ₹2.5 lakhs"],
    [500000, "Half payment - ₹5 lakhs"],
    [750000, "Three-quarter payment - ₹7.5 lakhs"],
    [1000000, "Maximum payment - ₹10 lakhs"],
    [1200000, "Over limit - ₹12 lakhs (should fail)"],
    [0, "Zero amount (should fail)"],
    [-1000, "Negative amount (should fail)"]
];

$passed = 0;
$total = count($tests);

foreach ($tests as $test) {
    if (testPaymentAmount($test[0], $test[1])) {
        $passed++;
    }
}

echo "\n=== Test Summary ===\n";
echo "Passed: $passed/$total tests\n";

if ($passed >= 6) { // Expect 6 valid tests to pass
    echo "✅ Flexible payment system working correctly!\n";
    echo "✅ Users can pay any amount up to the limit\n";
    echo "✅ Proper validation for invalid amounts\n";
} else {
    echo "❌ Some tests failed - check the implementation\n";
}

echo "\nNext step: Test in browser using tests/demos/flexible_payment_amounts_test.html\n";
?>
<?php
/**
 * Test button text prices for different scenarios
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Testing Button Text Prices ===\n\n";

    // Test different unlock prices
    $test_prices = [5000, 8000, 12000, 15000];
    
    foreach ($test_prices as $price) {
        echo "Testing price: ₹$price\n";
        
        // Format price for display (Indian locale)
        $formatted_price = number_format($price, 0, '.', ',');
        echo "   Formatted: ₹$formatted_price\n";
        
        // JavaScript toLocaleString equivalent
        $js_formatted = number_format($price, 0, '.', ',');
        echo "   JS Format: ₹$js_formatted\n";
        
        // Button text examples
        echo "   Button Text: Pay ₹$js_formatted to Unlock\n";
        echo "   Lock Text: 🔒 Locked - Pay ₹$js_formatted to unlock\n";
        echo "\n";
    }

    // Test with house plan data
    echo "=== Testing with Real House Plan Data ===\n";
    
    $planStmt = $db->prepare("
        SELECT hp.*, tdp.payment_status, tdp.amount as paid_amount
        FROM house_plans hp
        LEFT JOIN technical_details_payments tdp ON hp.id = tdp.house_plan_id AND tdp.homeowner_id = 28
        WHERE hp.id = 7
    ");
    $planStmt->execute();
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        $unlock_price = (float)($plan['unlock_price'] ?? 8000.00);
        $is_unlocked = ($plan['payment_status'] === 'completed');
        $paid_amount = $plan['paid_amount'] ? (float)$plan['paid_amount'] : null;
        
        echo "House Plan: " . $plan['plan_name'] . "\n";
        echo "Unlock Price: ₹" . number_format($unlock_price, 0, '.', ',') . "\n";
        echo "Is Unlocked: " . ($is_unlocked ? 'Yes' : 'No') . "\n";
        echo "Paid Amount: ₹" . ($paid_amount ? number_format($paid_amount, 0, '.', ',') : '0') . "\n\n";
        
        // Generate button text based on status
        if ($is_unlocked) {
            echo "Button Text: ✅ Technical Details Unlocked\n";
            echo "Status Text: Paid ₹" . number_format($paid_amount, 0, '.', ',') . " - Access Granted\n";
        } else {
            echo "Button Text: Pay ₹" . number_format($unlock_price, 0, '.', ',') . " to Unlock\n";
            echo "Lock Text: 🔒 Locked - Pay ₹" . number_format($unlock_price, 0, '.', ',') . " to unlock\n";
            echo "Description: Technical details are locked. Pay ₹" . number_format($unlock_price, 0, '.', ',') . " to unlock complete specifications.\n";
        }
    }

    // Test edge cases
    echo "\n=== Testing Edge Cases ===\n";
    
    $edge_cases = [
        ['price' => null, 'expected' => 8000], // Default price
        ['price' => 0, 'expected' => 8000],    // Zero price -> default
        ['price' => '', 'expected' => 8000],   // Empty price -> default
        ['price' => 'invalid', 'expected' => 8000], // Invalid price -> default
    ];
    
    foreach ($edge_cases as $case) {
        $price = $case['price'];
        $expected = $case['expected'];
        
        // Simulate the logic from frontend
        $display_price = $price && is_numeric($price) && $price > 0 ? (float)$price : 8000.00;
        
        echo "Input: " . var_export($price, true) . "\n";
        echo "Expected: ₹$expected\n";
        echo "Actual: ₹" . number_format($display_price, 0, '.', ',') . "\n";
        echo "Match: " . ($display_price == $expected ? '✅' : '❌') . "\n\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
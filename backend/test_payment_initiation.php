<?php
/**
 * Test payment initiation API
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Simulate session
    session_start();
    $_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026
    $homeowner_id = $_SESSION['user_id'];

    echo "=== Testing Payment Initiation API ===\n";
    echo "Homeowner ID: $homeowner_id\n\n";

    $house_plan_id = 7;
    echo "Testing payment initiation for house plan ID: $house_plan_id\n\n";

    // Get house plan details and verify access
    $planStmt = $db->prepare("
        SELECT hp.*, lr.user_id as request_owner_id
        FROM house_plans hp
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        WHERE hp.id = :plan_id AND hp.status IN ('submitted', 'approved', 'rejected')
    ");
    $planStmt->execute([':plan_id' => $house_plan_id]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo "❌ House plan not found or not accessible\n";
        exit;
    }

    echo "✅ House plan found:\n";
    echo "   Plan Name: " . $plan['plan_name'] . "\n";
    echo "   Request Owner ID: " . $plan['request_owner_id'] . "\n";
    echo "   Unlock Price: ₹" . ($plan['unlock_price'] ?? 8000.00) . "\n\n";

    // Verify homeowner has access to this plan
    if ($plan['request_owner_id'] != $homeowner_id) {
        echo "❌ Access denied to this house plan\n";
        exit;
    }

    echo "✅ Access verified\n\n";

    // Check if already paid
    $paymentStmt = $db->prepare("
        SELECT * FROM technical_details_payments 
        WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id AND payment_status = 'completed'
    ");
    $paymentStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $homeowner_id
    ]);
    
    if ($paymentStmt->fetch()) {
        echo "❌ Technical details already unlocked\n";
        exit;
    }

    echo "✅ Payment required - proceeding with initiation\n\n";

    $amount = $plan['unlock_price'] ?? 8000.00;
    $amount_paise = $amount * 100; // Convert to paise for Razorpay

    // Create Razorpay order (simplified)
    $razorpay_order_id = 'order_' . uniqid() . '_' . time();
    
    echo "Payment details:\n";
    echo "   Amount: ₹$amount ($amount_paise paise)\n";
    echo "   Razorpay Order ID: $razorpay_order_id\n\n";
    
    // Insert payment record
    $insertStmt = $db->prepare("
        INSERT INTO technical_details_payments 
        (house_plan_id, homeowner_id, amount, payment_status, razorpay_order_id, created_at)
        VALUES (:plan_id, :homeowner_id, :amount, 'pending', :order_id, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE 
        amount = VALUES(amount), 
        payment_status = 'pending', 
        razorpay_order_id = VALUES(razorpay_order_id),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $result = $insertStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $homeowner_id,
        ':amount' => $amount,
        ':order_id' => $razorpay_order_id
    ]);

    if ($result) {
        $payment_id = $db->lastInsertId();
        echo "✅ Payment record created successfully\n";
        echo "   Payment ID: $payment_id\n";
        
        // Simulate API response
        $response = [
            'success' => true,
            'payment_id' => $payment_id,
            'razorpay_order_id' => $razorpay_order_id,
            'amount' => $amount_paise,
            'currency' => 'INR',
            'razorpay_key_id' => 'rzp_test_your_key_here',
            'plan_name' => $plan['plan_name'],
            'description' => 'Unlock Technical Details for ' . $plan['plan_name']
        ];
        
        echo "\n=== API Response ===\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
    } else {
        echo "❌ Failed to create payment record\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
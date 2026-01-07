<?php
/**
 * Fresh test of payment initiation API
 */

// Clear any existing sessions
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

require_once 'config/database.php';
require_once 'config/razorpay_config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Start fresh session
    session_start();
    $_SESSION['user_id'] = 28;
    $homeowner_id = $_SESSION['user_id'];

    echo "=== Fresh Payment API Test ===\n";
    echo "Homeowner ID: $homeowner_id\n";
    echo "Razorpay Key: " . getRazorpayKeyId() . "\n\n";

    $house_plan_id = 7;

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
        echo "❌ House plan not found\n";
        exit;
    }

    if ($plan['request_owner_id'] != $homeowner_id) {
        echo "❌ Access denied\n";
        exit;
    }

    echo "✅ House plan found: " . $plan['plan_name'] . "\n";
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
        echo "ℹ️ Already paid - resetting for test\n";
        $resetStmt = $db->prepare("DELETE FROM technical_details_payments WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id");
        $resetStmt->execute([':plan_id' => $house_plan_id, ':homeowner_id' => $homeowner_id]);
    }

    $amount = $plan['unlock_price'] ?? 8000.00;
    $amount_paise = $amount * 100;

    echo "Creating real Razorpay order...\n";
    
    // Create real Razorpay order
    $receipt = 'technical_details_' . $house_plan_id . '_' . $homeowner_id . '_' . time();
    $razorpay_order = createRazorpayOrder($amount_paise, 'INR', $receipt);
    $razorpay_order_id = $razorpay_order['id'];
    
    echo "✅ Real order created: $razorpay_order_id\n\n";
    
    // Check if payment record exists and update/insert accordingly
    $existingStmt = $db->prepare("
        SELECT id FROM technical_details_payments 
        WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
    ");
    $existingStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $homeowner_id
    ]);
    
    if ($existingStmt->fetch()) {
        echo "ℹ️ Updating existing payment record\n";
        $updateStmt = $db->prepare("
            UPDATE technical_details_payments 
            SET amount = :amount, payment_status = 'pending', razorpay_order_id = :order_id, updated_at = CURRENT_TIMESTAMP
            WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
        ");
        $updateStmt->execute([
            ':plan_id' => $house_plan_id,
            ':homeowner_id' => $homeowner_id,
            ':amount' => $amount,
            ':order_id' => $razorpay_order_id
        ]);
        
        // Get the existing payment ID
        $idStmt = $db->prepare("SELECT id FROM technical_details_payments WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id");
        $idStmt->execute([':plan_id' => $house_plan_id, ':homeowner_id' => $homeowner_id]);
        $payment_id = $idStmt->fetchColumn();
    } else {
        echo "ℹ️ Creating new payment record\n";
        // Insert payment record
        $insertStmt = $db->prepare("
            INSERT INTO technical_details_payments 
            (house_plan_id, homeowner_id, amount, payment_status, razorpay_order_id, created_at)
            VALUES (:plan_id, :homeowner_id, :amount, 'pending', :order_id, CURRENT_TIMESTAMP)
        ");
        
        $insertStmt->execute([
            ':plan_id' => $house_plan_id,
            ':homeowner_id' => $homeowner_id,
            ':amount' => $amount,
            ':order_id' => $razorpay_order_id
        ]);

        $payment_id = $db->lastInsertId();
    }

    // Create API response
    $api_response = [
        'success' => true,
        'payment_id' => $payment_id,
        'razorpay_order_id' => $razorpay_order_id,
        'amount' => $amount_paise,
        'currency' => 'INR',
        'razorpay_key_id' => getRazorpayKeyId(), // This should be your real key
        'plan_name' => $plan['plan_name'],
        'description' => 'Unlock Technical Details for ' . $plan['plan_name']
    ];

    echo "API Response:\n";
    echo json_encode($api_response, JSON_PRETTY_PRINT) . "\n\n";

    // Verify the key
    if ($api_response['razorpay_key_id'] === 'rzp_test_RP6aD2gNdAuoRE') {
        echo "✅ Correct Razorpay Key ID in response\n";
        echo "✅ Real order ID from Razorpay API\n";
        echo "✅ 400 Bad Request error should be fixed!\n";
    } else {
        echo "❌ Unexpected key: " . $api_response['razorpay_key_id'] . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
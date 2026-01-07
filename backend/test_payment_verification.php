<?php
/**
 * Test payment verification API
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Simulate session
    session_start();
    $_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026
    $homeowner_id = $_SESSION['user_id'];

    echo "=== Testing Payment Verification API ===\n";
    echo "Homeowner ID: $homeowner_id\n\n";

    // Get the latest payment record
    $paymentStmt = $db->prepare("
        SELECT * FROM technical_details_payments 
        WHERE homeowner_id = :homeowner_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $paymentStmt->execute([':homeowner_id' => $homeowner_id]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        echo "❌ No payment record found\n";
        exit;
    }

    echo "Found payment record:\n";
    echo "   Payment ID: " . $payment['id'] . "\n";
    echo "   House Plan ID: " . $payment['house_plan_id'] . "\n";
    echo "   Amount: ₹" . $payment['amount'] . "\n";
    echo "   Status: " . $payment['payment_status'] . "\n";
    echo "   Razorpay Order ID: " . $payment['razorpay_order_id'] . "\n\n";

    // Simulate successful payment verification
    $razorpay_payment_id = 'pay_' . uniqid() . '_test';
    $razorpay_signature = 'signature_' . uniqid() . '_test';

    echo "Simulating payment verification with:\n";
    echo "   Razorpay Payment ID: $razorpay_payment_id\n";
    echo "   Razorpay Signature: $razorpay_signature\n\n";

    // Update payment status to completed
    $updateStmt = $db->prepare("
        UPDATE technical_details_payments 
        SET payment_status = 'completed', 
            razorpay_payment_id = :payment_id,
            razorpay_signature = :signature,
            payment_method = 'razorpay',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    
    $result = $updateStmt->execute([
        ':id' => $payment['id'],
        ':payment_id' => $razorpay_payment_id,
        ':signature' => $razorpay_signature
    ]);

    if ($result) {
        echo "✅ Payment status updated to completed\n\n";

        // Get house plan details for notification
        $planStmt = $db->prepare("
            SELECT hp.plan_name, hp.architect_id 
            FROM house_plans hp
            WHERE hp.id = :plan_id
        ");
        $planStmt->execute([':plan_id' => $payment['house_plan_id']]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if ($plan) {
            echo "House plan details:\n";
            echo "   Plan Name: " . $plan['plan_name'] . "\n";
            echo "   Architect ID: " . $plan['architect_id'] . "\n\n";

            // Create notification for successful payment
            $notificationStmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                VALUES (:user_id, 'payment_success', 'Technical Details Unlocked', :message, :plan_id, CURRENT_TIMESTAMP)
            ");
            
            $message = sprintf(
                'Payment of ₹%.2f successful! Technical details for "%s" are now unlocked and available for viewing.',
                $payment['amount'],
                $plan['plan_name']
            );
            
            $notificationResult = $notificationStmt->execute([
                ':user_id' => $homeowner_id,
                ':message' => $message,
                ':plan_id' => $payment['house_plan_id']
            ]);

            if ($notificationResult) {
                echo "✅ Homeowner notification created\n";
            }

            // Notify architect about the payment
            if ($plan['architect_id']) {
                $architectNotificationStmt = $db->prepare("
                    INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                    VALUES (:user_id, 'technical_details_purchased', 'Technical Details Purchased', :message, :plan_id, CURRENT_TIMESTAMP)
                ");
                
                $architectMessage = sprintf(
                    'Homeowner has purchased technical details for "%s" (₹%.2f). They now have full access to your technical specifications.',
                    $plan['plan_name'],
                    $payment['amount']
                );
                
                $architectNotificationResult = $architectNotificationStmt->execute([
                    ':user_id' => $plan['architect_id'],
                    ':message' => $architectMessage,
                    ':plan_id' => $payment['house_plan_id']
                ]);

                if ($architectNotificationResult) {
                    echo "✅ Architect notification created\n";
                }
            }
        }

        // Test the updated received designs API
        echo "\n=== Testing Updated Received Designs API ===\n";
        
        $housePlanSql = "SELECT 
                            hp.*,
                            a.first_name AS architect_first_name, 
                            a.last_name AS architect_last_name, 
                            a.email AS architect_email,
                            lr.selected_layout_id AS selected_layout_id,
                            tdp.payment_status,
                            tdp.amount as paid_amount,
                            'house_plan' as source_type
                         FROM house_plans hp
                         INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
                         INNER JOIN users a ON hp.architect_id = a.id
                         LEFT JOIN technical_details_payments tdp ON hp.id = tdp.house_plan_id AND tdp.homeowner_id = :homeowner_id_payment
                         WHERE lr.user_id = :homeowner_id 
                           AND hp.status IN ('submitted', 'approved', 'rejected')
                           AND hp.technical_details IS NOT NULL 
                           AND hp.technical_details != ''
                         ORDER BY hp.updated_at DESC";

        $housePlanStmt = $db->prepare($housePlanSql);
        $housePlanStmt->bindParam(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $housePlanStmt->bindParam(':homeowner_id_payment', $homeowner_id, PDO::PARAM_INT);
        $housePlanStmt->execute();

        $housePlanRows = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($housePlanRows as $row) {
            echo "House Plan: " . $row['plan_name'] . "\n";
            echo "   Unlock Price: ₹" . ($row['unlock_price'] ?? 8000.00) . "\n";
            echo "   Payment Status: " . ($row['payment_status'] ?? 'None') . "\n";
            echo "   Is Unlocked: " . (($row['payment_status'] === 'completed') ? 'Yes' : 'No') . "\n";
            echo "   Paid Amount: ₹" . ($row['paid_amount'] ?? '0') . "\n";
        }

    } else {
        echo "❌ Failed to update payment status\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
<?php
/**
 * Reset payment status for testing
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Resetting Payment Status for Testing ===\n\n";

    // Delete payment records for testing
    $deleteStmt = $db->prepare("DELETE FROM technical_details_payments WHERE homeowner_id = 28");
    $result = $deleteStmt->execute();

    if ($result) {
        echo "✅ Payment records reset for homeowner ID 28\n";
        echo "Technical details are now locked again for testing\n\n";
        
        // Verify the reset
        $checkStmt = $db->prepare("
            SELECT hp.*, tdp.payment_status
            FROM house_plans hp
            LEFT JOIN technical_details_payments tdp ON hp.id = tdp.house_plan_id AND tdp.homeowner_id = 28
            WHERE hp.id = 7
        ");
        $checkStmt->execute();
        $plan = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo "House Plan Status:\n";
            echo "   Plan Name: " . $plan['plan_name'] . "\n";
            echo "   Unlock Price: ₹" . ($plan['unlock_price'] ?? 8000.00) . "\n";
            echo "   Payment Status: " . ($plan['payment_status'] ?? 'None (Locked)') . "\n";
            echo "   Is Unlocked: " . (($plan['payment_status'] === 'completed') ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "❌ Failed to reset payment records\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
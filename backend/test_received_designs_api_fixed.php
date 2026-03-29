<?php
/**
 * Test the received designs API with correct paths
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Simulate session
    session_start();
    $_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026
    $user_id = $_SESSION['user_id'];

    echo "=== Testing Received Designs API ===\n";
    echo "User ID: $user_id\n\n";

    // Test the house plans query with payment information
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
    $housePlanStmt->bindParam(':homeowner_id', $user_id, PDO::PARAM_INT);
    $housePlanStmt->bindParam(':homeowner_id_payment', $user_id, PDO::PARAM_INT);
    $housePlanStmt->execute();

    $housePlanRows = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($housePlanRows) . " house plans with technical details\n\n";

    $designs = [];
    foreach ($housePlanRows as $row) {
        $plan_data = json_decode($row['plan_data'], true) ?? [];
        $technical_details = json_decode($row['technical_details'], true) ?? [];
        
        $designs[] = [
            'id' => 'hp_' . $row['id'],
            'house_plan_id' => (int)$row['id'],
            'design_title' => $row['plan_name'] . ' (House Plan)',
            'unlock_price' => (float)($row['unlock_price'] ?? 8000.00),
            'is_technical_details_unlocked' => ($row['payment_status'] === 'completed'),
            'payment_status' => $row['payment_status'],
            'paid_amount' => $row['paid_amount'] ? (float)$row['paid_amount'] : null,
            'source_type' => 'house_plan',
            'architect' => [
                'name' => trim(($row['architect_first_name'] ?? '') . ' ' . ($row['architect_last_name'] ?? '')),
                'email' => $row['architect_email'] ?? null
            ]
        ];
    }

    echo "Processed designs:\n";
    foreach ($designs as $design) {
        echo "- " . $design['design_title'] . "\n";
        echo "  Unlock Price: ₹" . $design['unlock_price'] . "\n";
        echo "  Is Unlocked: " . ($design['is_technical_details_unlocked'] ? 'Yes' : 'No') . "\n";
        echo "  Payment Status: " . ($design['payment_status'] ?? 'None') . "\n";
        echo "\n";
    }

    // Test payment initiation
    echo "=== Testing Payment Initiation ===\n";
    $house_plan_id = 7; // From our test data
    
    // Check if already paid
    $paymentStmt = $db->prepare("
        SELECT * FROM technical_details_payments 
        WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id AND payment_status = 'completed'
    ");
    $paymentStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $user_id
    ]);
    
    if ($paymentStmt->fetch()) {
        echo "✅ Technical details already unlocked for house plan $house_plan_id\n";
    } else {
        echo "🔒 Technical details locked for house plan $house_plan_id\n";
        
        // Get house plan details
        $planStmt = $db->prepare("
            SELECT hp.*, lr.user_id as request_owner_id
            FROM house_plans hp
            LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
            WHERE hp.id = :plan_id AND hp.status IN ('submitted', 'approved', 'rejected')
        ");
        $planStmt->execute([':plan_id' => $house_plan_id]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan && $plan['request_owner_id'] == $user_id) {
            echo "✅ User has access to house plan $house_plan_id\n";
            echo "   Plan Name: " . $plan['plan_name'] . "\n";
            echo "   Unlock Price: ₹" . ($plan['unlock_price'] ?? 8000.00) . "\n";
        } else {
            echo "❌ User does not have access to house plan $house_plan_id\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
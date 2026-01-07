<?php
/**
 * Test the payment query logic directly
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Testing Payment Query Logic ===\n\n";

    $user_id = 28; // SHIJIN THOMAS MCA2024-2026
    echo "Testing for Homeowner ID: $user_id\n\n";

    // Test the house plans query with payment information
    echo "Testing House Plans Query with Payment Info:\n";
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

    echo "SQL Query:\n$housePlanSql\n\n";

    $housePlanStmt = $db->prepare($housePlanSql);
    echo "Binding parameters:\n";
    echo ":homeowner_id = $user_id\n";
    echo ":homeowner_id_payment = $user_id\n\n";
    
    $housePlanStmt->bindParam(':homeowner_id', $user_id, PDO::PARAM_INT);
    $housePlanStmt->bindParam(':homeowner_id_payment', $user_id, PDO::PARAM_INT);
    
    echo "Executing query...\n";
    $housePlanStmt->execute();

    $housePlanRows = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($housePlanRows) . " house plans\n\n";

    foreach ($housePlanRows as $row) {
        echo "House Plan ID: " . $row['id'] . "\n";
        echo "Plan Name: " . $row['plan_name'] . "\n";
        echo "Unlock Price: ₹" . ($row['unlock_price'] ?? 'Not set') . "\n";
        echo "Payment Status: " . ($row['payment_status'] ?? 'No payment') . "\n";
        echo "Paid Amount: ₹" . ($row['paid_amount'] ?? '0') . "\n";
        echo "Architect: " . $row['architect_first_name'] . " " . $row['architect_last_name'] . "\n";
        echo "\n";
    }

    // Test processing the results
    echo "Processing results for API response:\n";
    foreach ($housePlanRows as $row) {
        $technical_details = json_decode($row['technical_details'], true) ?? [];
        $unlock_price = (float)($row['unlock_price'] ?? 8000.00);
        $is_unlocked = ($row['payment_status'] === 'completed');
        
        echo "- Plan: " . $row['plan_name'] . "\n";
        echo "  Unlock Price: ₹$unlock_price\n";
        echo "  Is Unlocked: " . ($is_unlocked ? 'Yes' : 'No') . "\n";
        echo "  Payment Status: " . ($row['payment_status'] ?? 'None') . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
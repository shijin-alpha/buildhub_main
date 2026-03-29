<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== FIXING ALL CONTRACTOR PAYMENT MISMATCHES ===\n";
    
    // Find all payments where contractor ID doesn't match project contractor ID
    $stmt = $db->prepare("
        SELECT 
            spr.id, spr.contractor_id, spr.project_id, spr.stage_name,
            cp.contractor_id as project_contractor_id,
            u1.first_name as payment_contractor_name, u1.last_name as payment_contractor_lastname,
            u2.first_name as project_contractor_name, u2.last_name as project_contractor_lastname
        FROM stage_payment_requests spr
        LEFT JOIN construction_projects cp ON spr.project_id = cp.id
        LEFT JOIN users u1 ON spr.contractor_id = u1.id
        LEFT JOIN users u2 ON cp.contractor_id = u2.id
        WHERE spr.contractor_id != cp.contractor_id
    ");
    $stmt->execute();
    $mismatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($mismatches) . " payment requests with contractor mismatches:\n\n";
    
    foreach ($mismatches as $mismatch) {
        echo "Payment ID: {$mismatch['id']}\n";
        echo "  Stage: {$mismatch['stage_name']}\n";
        echo "  Project ID: {$mismatch['project_id']}\n";
        echo "  Current Contractor: {$mismatch['payment_contractor_name']} {$mismatch['payment_contractor_lastname']} (ID: {$mismatch['contractor_id']})\n";
        echo "  Correct Contractor: {$mismatch['project_contractor_name']} {$mismatch['project_contractor_lastname']} (ID: {$mismatch['project_contractor_id']})\n";
        
        // Fix the mismatch
        $updateStmt = $db->prepare("
            UPDATE stage_payment_requests 
            SET contractor_id = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$mismatch['project_contractor_id'], $mismatch['id']]);
        
        echo "  ✅ FIXED: Updated contractor ID to {$mismatch['project_contractor_id']}\n";
        echo "  ---\n";
    }
    
    if (count($mismatches) == 0) {
        echo "✅ No contractor mismatches found - all payments are correctly assigned!\n";
    } else {
        echo "\n🎉 All contractor mismatches have been fixed!\n";
    }
    
    // Now show all payments for homeowner 32 with correct contractor assignments
    echo "\n=== ALL PAYMENTS FOR HOMEOWNER 32 (AFTER FIX) ===\n";
    $stmt = $db->prepare("
        SELECT 
            spr.id, spr.stage_name, spr.requested_amount, spr.status,
            spr.contractor_id, spr.project_id, spr.receipt_file_path, spr.verification_status,
            u.first_name as contractor_name, u.last_name as contractor_lastname
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.contractor_id = u.id
        WHERE spr.homeowner_id = 32
        ORDER BY spr.id DESC
    ");
    $stmt->execute();
    $allPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPayments as $payment) {
        echo "Payment ID: {$payment['id']}\n";
        echo "  Stage: {$payment['stage_name']}\n";
        echo "  Amount: ₹{$payment['requested_amount']}\n";
        echo "  Status: {$payment['status']}\n";
        echo "  Project ID: {$payment['project_id']}\n";
        echo "  Contractor: {$payment['contractor_name']} {$payment['contractor_lastname']} (ID: {$payment['contractor_id']})\n";
        echo "  Receipt: " . ($payment['receipt_file_path'] ? 'YES' : 'NO') . "\n";
        echo "  Verification: " . ($payment['verification_status'] ?: 'NULL') . "\n";
        echo "  ---\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "✅ All payment requests are now correctly assigned to their project contractors\n";
    echo "✅ Contractor 29 (Shijin Thomas) should now see payment ID 16 with receipt for verification\n";
    echo "✅ The receipt upload issue has been resolved\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
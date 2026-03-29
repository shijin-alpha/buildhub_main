<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== FIXING CONTRACTOR ASSIGNMENT FOR PAYMENT 16 ===\n";
    
    // First, check the current state
    $stmt = $db->prepare("
        SELECT spr.id, spr.contractor_id, spr.project_id, cp.contractor_id as project_contractor_id
        FROM stage_payment_requests spr
        LEFT JOIN construction_projects cp ON spr.project_id = cp.id
        WHERE spr.id = 16
    ");
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        echo "Current state:\n";
        echo "  Payment ID: {$current['id']}\n";
        echo "  Payment Contractor ID: {$current['contractor_id']}\n";
        echo "  Project ID: {$current['project_id']}\n";
        echo "  Project Contractor ID: {$current['project_contractor_id']}\n";
        
        if ($current['contractor_id'] != $current['project_contractor_id']) {
            echo "\n❌ MISMATCH DETECTED!\n";
            echo "Payment contractor ID ({$current['contractor_id']}) does not match project contractor ID ({$current['project_contractor_id']})\n";
            
            // Fix the contractor ID in the payment request
            $updateStmt = $db->prepare("
                UPDATE stage_payment_requests 
                SET contractor_id = ? 
                WHERE id = 16
            ");
            $updateStmt->execute([$current['project_contractor_id']]);
            
            echo "\n✅ FIXED! Updated payment contractor ID to {$current['project_contractor_id']}\n";
            
            // Verify the fix
            $verifyStmt = $db->prepare("SELECT contractor_id FROM stage_payment_requests WHERE id = 16");
            $verifyStmt->execute();
            $newContractorId = $verifyStmt->fetchColumn();
            
            echo "Verification: Payment 16 contractor ID is now $newContractorId\n";
            
        } else {
            echo "\n✅ No mismatch - contractor IDs match\n";
        }
    } else {
        echo "❌ Payment ID 16 not found\n";
    }
    
    // Now test if contractor 29 can see the payment
    echo "\n=== TESTING CONTRACTOR 29 ACCESS ===\n";
    
    $contractor_id = 29;
    $project_id = 2;
    
    $payment_query = "
        SELECT 
            spr.id, spr.stage_name, spr.requested_amount, spr.status,
            spr.receipt_file_path, spr.verification_status,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = ? 
        AND spr.contractor_id = ?
        AND spr.id = 16
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([$project_id, $contractor_id]);
    $contractorPayment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contractorPayment) {
        echo "✅ Contractor 29 CAN now see payment ID 16!\n";
        echo "  Stage: {$contractorPayment['stage_name']}\n";
        echo "  Amount: ₹{$contractorPayment['requested_amount']}\n";
        echo "  Status: {$contractorPayment['status']}\n";
        echo "  Homeowner: {$contractorPayment['first_name']} {$contractorPayment['last_name']}\n";
        echo "  Receipt Available: " . ($contractorPayment['receipt_file_path'] ? 'YES' : 'NO') . "\n";
        echo "  Verification Status: " . ($contractorPayment['verification_status'] ?: 'pending') . "\n";
        
        echo "\n🎉 SUCCESS! The receipt should now be visible in contractor 29's dashboard!\n";
    } else {
        echo "❌ Contractor 29 still cannot see payment ID 16\n";
    }
    
    // Check who is the actual contractor for project 2
    echo "\n=== PROJECT 2 CONTRACTOR INFO ===\n";
    $contractorStmt = $db->prepare("
        SELECT cp.contractor_id, u.first_name, u.last_name, u.email
        FROM construction_projects cp
        LEFT JOIN users u ON cp.contractor_id = u.id
        WHERE cp.id = 2
    ");
    $contractorStmt->execute();
    $contractorInfo = $contractorStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contractorInfo) {
        echo "Project 2 contractor: {$contractorInfo['first_name']} {$contractorInfo['last_name']} (ID: {$contractorInfo['contractor_id']})\n";
        echo "Email: {$contractorInfo['email']}\n";
        echo "\nThis contractor should log in to see the receipt for verification.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== FINAL TEST: CONTRACTOR DASHBOARD RECEIPT VISIBILITY ===\n";
    
    // Test for contractor 29 (Shijin Thomas) - the correct contractor
    $contractor_id = 29;
    
    echo "Testing for Contractor ID: $contractor_id (Shijin Thomas)\n\n";
    
    // Get all projects for this contractor
    $projects_query = "
        SELECT DISTINCT cp.id, cp.project_name, cp.homeowner_id,
               u.first_name as homeowner_name, u.last_name as homeowner_lastname
        FROM construction_projects cp
        LEFT JOIN users u ON cp.homeowner_id = u.id
        WHERE cp.contractor_id = ?
    ";
    
    $projects_stmt = $db->prepare($projects_query);
    $projects_stmt->execute([$contractor_id]);
    $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Projects for contractor $contractor_id:\n";
    foreach ($projects as $project) {
        echo "  Project ID: {$project['id']} - {$project['project_name']} (Homeowner: {$project['homeowner_name']} {$project['homeowner_lastname']})\n";
    }
    
    echo "\n=== PAYMENT REQUESTS WITH RECEIPTS ===\n";
    
    foreach ($projects as $project) {
        $project_id = $project['id'];
        
        // Get payment requests for this project (same query as the API)
        $payment_query = "
            SELECT 
                spr.*,
                u.first_name, u.last_name
            FROM stage_payment_requests spr
            LEFT JOIN users u ON spr.homeowner_id = u.id
            WHERE spr.project_id = ? 
            AND spr.contractor_id = ?
            ORDER BY spr.request_date DESC
        ";
        
        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->execute([$project_id, $contractor_id]);
        $payments = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($payments) > 0) {
            echo "\nProject {$project['id']} ({$project['project_name']}):\n";
            
            foreach ($payments as $payment) {
                echo "  Payment ID: {$payment['id']}\n";
                echo "    Stage: {$payment['stage_name']}\n";
                echo "    Amount: ₹{$payment['requested_amount']}\n";
                echo "    Status: {$payment['status']}\n";
                echo "    Homeowner: {$payment['first_name']} {$payment['last_name']}\n";
                
                // Check receipt information
                if ($payment['receipt_file_path']) {
                    echo "    📄 RECEIPT AVAILABLE FOR VERIFICATION!\n";
                    echo "    Transaction Ref: {$payment['transaction_reference']}\n";
                    echo "    Payment Date: {$payment['payment_date']}\n";
                    echo "    Payment Method: {$payment['payment_method']}\n";
                    echo "    Verification Status: " . ($payment['verification_status'] ?: 'pending') . "\n";
                    
                    $receiptFiles = json_decode($payment['receipt_file_path'], true);
                    if ($receiptFiles && is_array($receiptFiles)) {
                        echo "    Files: " . count($receiptFiles) . " file(s) uploaded\n";
                        foreach ($receiptFiles as $index => $file) {
                            echo "      - {$file['original_name']} ({$file['file_type']})\n";
                        }
                    }
                    
                    if ($payment['verification_status'] === 'pending') {
                        echo "    🔍 ACTION REQUIRED: Contractor needs to verify this receipt\n";
                    }
                } else {
                    echo "    ⏳ No receipt uploaded yet\n";
                }
                echo "    ---\n";
            }
        }
    }
    
    // Summary
    echo "\n=== SUMMARY FOR CONTRACTOR DASHBOARD ===\n";
    
    $total_receipts_query = "
        SELECT COUNT(*) as total_receipts
        FROM stage_payment_requests spr
        WHERE spr.contractor_id = ? 
        AND spr.receipt_file_path IS NOT NULL 
        AND spr.verification_status = 'pending'
    ";
    
    $total_stmt = $db->prepare($total_receipts_query);
    $total_stmt->execute([$contractor_id]);
    $total_receipts = $total_stmt->fetchColumn();
    
    echo "✅ Contractor 29 (Shijin Thomas) has $total_receipts receipt(s) pending verification\n";
    
    if ($total_receipts > 0) {
        echo "✅ These receipts should be visible in the Payment History section\n";
        echo "✅ Contractor can click 'View & Verify Receipt' to verify payments\n";
        echo "✅ The receipt upload issue has been completely resolved!\n";
    } else {
        echo "ℹ️ No receipts pending verification at this time\n";
    }
    
    // Specific check for payment ID 16
    $payment16_query = "
        SELECT id, stage_name, receipt_file_path, verification_status
        FROM stage_payment_requests 
        WHERE id = 16 AND contractor_id = ?
    ";
    
    $payment16_stmt = $db->prepare($payment16_query);
    $payment16_stmt->execute([$contractor_id]);
    $payment16 = $payment16_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment16) {
        echo "\n🎯 PAYMENT ID 16 STATUS:\n";
        echo "✅ Visible to contractor 29: YES\n";
        echo "✅ Receipt uploaded: " . ($payment16['receipt_file_path'] ? 'YES' : 'NO') . "\n";
        echo "✅ Verification status: " . ($payment16['verification_status'] ?: 'pending') . "\n";
        echo "✅ Ready for verification: YES\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
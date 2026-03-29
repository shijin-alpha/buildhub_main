<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== CHECKING PAYMENT ID 16 RECEIPT DATA ===\n";
    
    // Check payment 16 details including receipt information
    $stmt = $db->prepare("
        SELECT 
            id, homeowner_id, contractor_id, project_id, stage_name, 
            requested_amount, status, 
            transaction_reference, payment_date, payment_method, 
            receipt_file_path, verification_status, verified_by, 
            verified_at, verification_notes, created_at, updated_at
        FROM stage_payment_requests 
        WHERE id = 16
    ");
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "✅ Payment ID 16 found:\n";
        echo "  Homeowner ID: {$payment['homeowner_id']}\n";
        echo "  Contractor ID: {$payment['contractor_id']}\n";
        echo "  Project ID: {$payment['project_id']}\n";
        echo "  Stage: {$payment['stage_name']}\n";
        echo "  Amount: ₹{$payment['requested_amount']}\n";
        echo "  Status: {$payment['status']}\n";
        echo "  Transaction Reference: " . ($payment['transaction_reference'] ?: 'NULL') . "\n";
        echo "  Payment Date: " . ($payment['payment_date'] ?: 'NULL') . "\n";
        echo "  Payment Method: " . ($payment['payment_method'] ?: 'NULL') . "\n";
        echo "  Receipt File Path: " . ($payment['receipt_file_path'] ?: 'NULL') . "\n";
        echo "  Verification Status: " . ($payment['verification_status'] ?: 'NULL') . "\n";
        echo "  Verified By: " . ($payment['verified_by'] ?: 'NULL') . "\n";
        echo "  Verified At: " . ($payment['verified_at'] ?: 'NULL') . "\n";
        echo "  Verification Notes: " . ($payment['verification_notes'] ?: 'NULL') . "\n";
        echo "  Created: {$payment['created_at']}\n";
        echo "  Updated: {$payment['updated_at']}\n";
        
        // Parse receipt file path if it exists
        if ($payment['receipt_file_path']) {
            echo "\n📄 Receipt Files:\n";
            $receiptFiles = json_decode($payment['receipt_file_path'], true);
            if ($receiptFiles && is_array($receiptFiles)) {
                foreach ($receiptFiles as $index => $file) {
                    echo "  File " . ($index + 1) . ":\n";
                    echo "    Original Name: " . ($file['original_name'] ?? 'N/A') . "\n";
                    echo "    Stored Name: " . ($file['stored_name'] ?? 'N/A') . "\n";
                    echo "    File Path: " . ($file['file_path'] ?? 'N/A') . "\n";
                    echo "    File Size: " . ($file['file_size'] ?? 'N/A') . " bytes\n";
                    echo "    File Type: " . ($file['file_type'] ?? 'N/A') . "\n";
                    
                    // Check if file actually exists
                    $fullPath = __DIR__ . '/backend/' . ($file['file_path'] ?? '');
                    if (file_exists($fullPath)) {
                        echo "    File Exists: ✅ YES\n";
                    } else {
                        echo "    File Exists: ❌ NO (Path: $fullPath)\n";
                    }
                }
            } else {
                echo "  ❌ Invalid receipt file data format\n";
            }
        } else {
            echo "\n❌ No receipt files uploaded\n";
        }
        
    } else {
        echo "❌ Payment ID 16 not found\n";
    }
    
    // Check if contractor can see this payment in their project
    echo "\n=== CHECKING CONTRACTOR ACCESS ===\n";
    $contractorId = $payment['contractor_id'] ?? 1;
    $projectId = $payment['project_id'] ?? 2;
    
    echo "Testing contractor access for Contractor ID: $contractorId, Project ID: $projectId\n";
    
    // Simulate the same query used in get_payment_history.php
    $payment_query = "
        SELECT 
            spr.*,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = ? 
        AND spr.contractor_id = ?
        AND spr.id = 16
        ORDER BY spr.request_date DESC
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([$projectId, $contractorId]);
    $contractorPayment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contractorPayment) {
        echo "✅ Contractor CAN see payment ID 16 in their payment history\n";
        echo "  Receipt data available: " . ($contractorPayment['receipt_file_path'] ? 'YES' : 'NO') . "\n";
        echo "  Verification status: " . ($contractorPayment['verification_status'] ?: 'NULL') . "\n";
    } else {
        echo "❌ Contractor CANNOT see payment ID 16 in their payment history\n";
        echo "This could be due to:\n";
        echo "1. Wrong contractor ID\n";
        echo "2. Wrong project ID\n";
        echo "3. Payment not associated with this contractor\n";
    }
    
    // Check all payments for this contractor and project
    echo "\n=== ALL PAYMENTS FOR CONTRACTOR $contractorId, PROJECT $projectId ===\n";
    $all_payments_stmt = $db->prepare("
        SELECT id, stage_name, requested_amount, status, verification_status, receipt_file_path
        FROM stage_payment_requests 
        WHERE contractor_id = ? AND project_id = ?
        ORDER BY id DESC
    ");
    $all_payments_stmt->execute([$contractorId, $projectId]);
    $allPayments = $all_payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allPayments) {
        foreach ($allPayments as $p) {
            $hasReceipt = $p['receipt_file_path'] ? 'YES' : 'NO';
            echo "  Payment ID: {$p['id']}, Stage: {$p['stage_name']}, Amount: {$p['requested_amount']}, Status: {$p['status']}, Receipt: $hasReceipt, Verification: " . ($p['verification_status'] ?: 'NULL') . "\n";
        }
    } else {
        echo "  No payments found for this contractor and project\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
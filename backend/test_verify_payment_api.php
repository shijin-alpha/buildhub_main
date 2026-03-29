<?php
/**
 * Test: Verify Payment Receipt API
 * 
 * Tests the contractor payment verification endpoint
 */

require_once 'config/database.php';

echo "=== Testing Payment Verification API ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Check if stage_payment_requests table has the verification columns
    echo "1. Checking verification columns in stage_payment_requests:\n";
    $columnsStmt = $db->query("SHOW COLUMNS FROM stage_payment_requests LIKE 'verification%'");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($columns) > 0) {
        echo "   ✓ Found verification columns:\n";
        foreach ($columns as $col) {
            echo "     - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "   ✗ No verification columns found!\n";
        echo "   Run: backend/add_receipt_columns_to_stage_payments.php\n";
    }
    echo "\n";
    
    // 2. Find a payment with receipt uploaded
    echo "2. Finding payments with receipts:\n";
    $paymentsStmt = $db->query("
        SELECT 
            spr.id,
            spr.stage_name,
            spr.contractor_id,
            spr.homeowner_id,
            spr.status,
            spr.verification_status,
            spr.receipt_file_path,
            spr.project_id
        FROM stage_payment_requests spr
        WHERE spr.receipt_file_path IS NOT NULL
        AND spr.status IN ('approved', 'paid')
        ORDER BY spr.id DESC
        LIMIT 5
    ");
    
    $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($payments) > 0) {
        echo "   ✓ Found " . count($payments) . " payment(s) with receipts:\n";
        foreach ($payments as $payment) {
            $receiptCount = 0;
            if ($payment['receipt_file_path']) {
                $receipts = json_decode($payment['receipt_file_path'], true);
                $receiptCount = is_array($receipts) ? count($receipts) : 0;
            }
            
            echo "     - ID: {$payment['id']}\n";
            echo "       Stage: {$payment['stage_name']}\n";
            echo "       Project ID: {$payment['project_id']}\n";
            echo "       Status: {$payment['status']}\n";
            echo "       Verification: " . ($payment['verification_status'] ?: 'NULL') . "\n";
            echo "       Receipts: {$receiptCount} file(s)\n";
            echo "       Contractor ID: {$payment['contractor_id']}\n";
            echo "\n";
        }
    } else {
        echo "   ✗ No payments with receipts found\n";
        echo "   Upload a receipt first to test verification\n";
    }
    echo "\n";
    
    // 3. Check notification table
    echo "3. Checking notification tables:\n";
    $tables = ['alternative_payment_notifications', 'notifications', 'stage_payment_notifications'];
    
    foreach ($tables as $table) {
        $checkStmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($checkStmt->rowCount() > 0) {
            echo "   ✓ Table '{$table}' exists\n";
        } else {
            echo "   ✗ Table '{$table}' does not exist (optional)\n";
        }
    }
    echo "\n";
    
    // 4. Test verification update (dry run)
    if (count($payments) > 0) {
        $testPayment = $payments[0];
        echo "4. Testing verification update (dry run):\n";
        echo "   Payment ID: {$testPayment['id']}\n";
        echo "   Current status: {$testPayment['status']}\n";
        echo "   Current verification: " . ($testPayment['verification_status'] ?: 'NULL') . "\n";
        
        // Simulate the update
        $updateStmt = $db->prepare("
            SELECT 
                :payment_id as payment_id,
                :verification_status as new_verification_status,
                :contractor_id as verified_by,
                NOW() as verified_at,
                CASE 
                    WHEN :verification_status = 'verified' THEN 'paid'
                    ELSE :current_status
                END as new_status
        ");
        
        $updateStmt->execute([
            ':payment_id' => $testPayment['id'],
            ':verification_status' => 'verified',
            ':contractor_id' => $testPayment['contractor_id'],
            ':current_status' => $testPayment['status']
        ]);
        
        $result = $updateStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "   Would update to:\n";
        echo "     - Verification status: {$result['new_verification_status']}\n";
        echo "     - Payment status: {$result['new_status']}\n";
        echo "     - Verified by: {$result['verified_by']}\n";
        echo "     - Verified at: {$result['verified_at']}\n";
        echo "\n";
    }
    
    echo "=== Test Complete ===\n\n";
    
    echo "To test the API endpoint:\n";
    echo "1. Log in as contractor (ID: {$payments[0]['contractor_id']})\n";
    echo "2. Navigate to Payment History section\n";
    echo "3. Click 'Verify Payment' on payment ID: {$payments[0]['id']}\n";
    echo "4. Check browser console for any errors\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

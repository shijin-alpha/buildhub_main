<?php
/**
 * Show Receipt Storage Information
 * This script will show you exactly where receipts are saved and what data is stored
 */

require_once 'backend/config/database.php';

echo "🧾 RECEIPT STORAGE INFORMATION\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Show which tables store receipts
    echo "📋 TABLES THAT STORE RECEIPTS:\n";
    echo "-" . str_repeat("-", 30) . "\n";
    echo "1. stage_payment_requests - Main table for stage payments\n";
    echo "2. custom_payment_requests - Table for custom payment requests\n\n";
    
    // 2. Show the receipt-related columns
    echo "📊 RECEIPT-RELATED COLUMNS:\n";
    echo "-" . str_repeat("-", 30) . "\n";
    echo "• receipt_file_path - JSON array of uploaded files\n";
    echo "• transaction_reference - Payment reference number\n";
    echo "• payment_date - Date when payment was made\n";
    echo "• payment_method - Method used (bank_transfer, upi, etc.)\n";
    echo "• verification_status - pending/verified/rejected\n";
    echo "• verified_by - ID of who verified the receipt\n";
    echo "• verified_at - When it was verified\n";
    echo "• verification_notes - Notes from verifier\n\n";
    
    // 3. Show current receipts in the database
    echo "📁 CURRENT RECEIPTS IN DATABASE:\n";
    echo "-" . str_repeat("-", 40) . "\n";
    
    // Check stage_payment_requests
    $stageStmt = $db->query("
        SELECT 
            id, homeowner_id, contractor_id, stage_name, requested_amount,
            receipt_file_path, payment_method, verification_status, 
            transaction_reference, payment_date
        FROM stage_payment_requests 
        WHERE receipt_file_path IS NOT NULL 
        AND receipt_file_path != '' 
        AND receipt_file_path != 'null'
        ORDER BY id DESC
    ");
    $stageReceipts = $stageStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "STAGE PAYMENT RECEIPTS: " . count($stageReceipts) . " found\n";
    foreach ($stageReceipts as $receipt) {
        echo "\n  Payment ID: {$receipt['id']}\n";
        echo "  Stage: {$receipt['stage_name']}\n";
        echo "  Amount: ₹" . number_format($receipt['requested_amount'], 2) . "\n";
        echo "  Homeowner ID: {$receipt['homeowner_id']}\n";
        echo "  Contractor ID: {$receipt['contractor_id']}\n";
        echo "  Payment Method: {$receipt['payment_method']}\n";
        echo "  Verification Status: {$receipt['verification_status']}\n";
        echo "  Transaction Ref: {$receipt['transaction_reference']}\n";
        echo "  Payment Date: {$receipt['payment_date']}\n";
        
        // Parse and show file details
        $files = json_decode($receipt['receipt_file_path'], true);
        if ($files && is_array($files)) {
            echo "  Files Uploaded: " . count($files) . "\n";
            foreach ($files as $index => $file) {
                echo "    File " . ($index + 1) . ": {$file['original_name']}\n";
                echo "      Stored as: {$file['stored_name']}\n";
                echo "      Path: {$file['file_path']}\n";
                echo "      Size: " . round($file['file_size'] / 1024, 2) . " KB\n";
                echo "      Type: {$file['file_type']}\n";
            }
        }
        echo "  " . str_repeat("-", 50) . "\n";
    }
    
    // Check custom_payment_requests
    $customStmt = $db->query("
        SELECT 
            id, homeowner_id, contractor_id, request_title, requested_amount,
            receipt_file_path, payment_method, verification_status,
            transaction_reference, payment_date
        FROM custom_payment_requests 
        WHERE receipt_file_path IS NOT NULL 
        AND receipt_file_path != '' 
        AND receipt_file_path != 'null'
        ORDER BY id DESC
    ");
    $customReceipts = $customStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCUSTOM PAYMENT RECEIPTS: " . count($customReceipts) . " found\n";
    foreach ($customReceipts as $receipt) {
        echo "\n  Payment ID: {$receipt['id']}\n";
        echo "  Title: {$receipt['request_title']}\n";
        echo "  Amount: ₹" . number_format($receipt['requested_amount'], 2) . "\n";
        echo "  Homeowner ID: {$receipt['homeowner_id']}\n";
        echo "  Contractor ID: {$receipt['contractor_id']}\n";
        echo "  Payment Method: {$receipt['payment_method']}\n";
        echo "  Verification Status: {$receipt['verification_status']}\n";
        echo "  Transaction Ref: {$receipt['transaction_reference']}\n";
        echo "  Payment Date: {$receipt['payment_date']}\n";
        
        // Parse and show file details
        $files = json_decode($receipt['receipt_file_path'], true);
        if ($files && is_array($files)) {
            echo "  Files Uploaded: " . count($files) . "\n";
            foreach ($files as $index => $file) {
                echo "    File " . ($index + 1) . ": {$file['original_name']}\n";
                echo "      Stored as: {$file['stored_name']}\n";
                echo "      Path: {$file['file_path']}\n";
                echo "      Size: " . round($file['file_size'] / 1024, 2) . " KB\n";
                echo "      Type: {$file['file_type']}\n";
            }
        }
        echo "  " . str_repeat("-", 50) . "\n";
    }
    
    // 4. Show file system storage location
    echo "\n💾 FILE SYSTEM STORAGE:\n";
    echo "-" . str_repeat("-", 25) . "\n";
    echo "Base Directory: backend/uploads/payment_receipts/\n";
    echo "Structure: backend/uploads/payment_receipts/[PAYMENT_ID]/[FILENAME]\n";
    echo "Example: backend/uploads/payment_receipts/15/receipt_1768903670_0.jpg\n\n";
    
    // Check if upload directory exists
    $uploadBaseDir = 'backend/uploads/payment_receipts/';
    if (is_dir($uploadBaseDir)) {
        echo "✅ Upload directory exists: $uploadBaseDir\n";
        
        // List payment directories
        $paymentDirs = array_filter(scandir($uploadBaseDir), function($item) use ($uploadBaseDir) {
            return is_dir($uploadBaseDir . $item) && is_numeric($item);
        });
        
        if (!empty($paymentDirs)) {
            echo "📂 Payment directories found: " . implode(', ', $paymentDirs) . "\n";
            
            foreach ($paymentDirs as $paymentId) {
                $paymentDir = $uploadBaseDir . $paymentId . '/';
                $files = array_filter(scandir($paymentDir), function($item) {
                    return !in_array($item, ['.', '..']);
                });
                
                if (!empty($files)) {
                    echo "  Payment $paymentId: " . count($files) . " file(s) - " . implode(', ', $files) . "\n";
                }
            }
        } else {
            echo "📂 No payment directories found yet\n";
        }
    } else {
        echo "❌ Upload directory does not exist: $uploadBaseDir\n";
    }
    
    // 5. Summary
    echo "\n📋 SUMMARY:\n";
    echo "-" . str_repeat("-", 15) . "\n";
    echo "• Total Stage Payment Receipts: " . count($stageReceipts) . "\n";
    echo "• Total Custom Payment Receipts: " . count($customReceipts) . "\n";
    echo "• Total Receipts: " . (count($stageReceipts) + count($customReceipts)) . "\n";
    echo "\n✅ When you upload a receipt, it gets saved to:\n";
    echo "   1. Database: receipt_file_path column (JSON format)\n";
    echo "   2. File System: backend/uploads/payment_receipts/[PAYMENT_ID]/\n";
    echo "   3. Additional data: transaction_reference, payment_date, payment_method\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
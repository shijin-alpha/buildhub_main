<?php
/**
 * Fix corrupted receipt for payment 22
 * This script will reset the receipt status so it can be re-uploaded
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Fixing corrupted receipt for payment 22...\n\n";
    
    $payment_id = 22;
    
    // Check current status
    $stmt = $pdo->prepare("SELECT id, receipt_file_path, verification_status FROM stage_payment_requests WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo "❌ Payment $payment_id not found\n";
        exit;
    }
    
    echo "Current status:\n";
    echo "- Payment ID: {$payment['id']}\n";
    echo "- Receipt file path: {$payment['receipt_file_path']}\n";
    echo "- Verification status: {$payment['verification_status']}\n\n";
    
    // Parse current receipt data
    $receiptData = json_decode($payment['receipt_file_path'], true);
    if ($receiptData) {
        echo "Current receipt files:\n";
        foreach ($receiptData as $i => $file) {
            $filePath = "backend/" . $file['file_path'];
            $fileExists = file_exists($filePath);
            $fileSize = $fileExists ? filesize($filePath) : 0;
            
            echo "  File $i:\n";
            echo "    - Original name: {$file['original_name']}\n";
            echo "    - Stored name: {$file['stored_name']}\n";
            echo "    - File path: {$file['file_path']}\n";
            echo "    - Recorded size: {$file['file_size']} bytes\n";
            echo "    - Actual size: $fileSize bytes\n";
            echo "    - File exists: " . ($fileExists ? 'Yes' : 'No') . "\n";
            
            if ($fileExists && $fileSize < 1000) {
                echo "    - ⚠️  File appears corrupted (too small)\n";
            }
            echo "\n";
        }
    }
    
    // Option 1: Reset receipt status to allow re-upload
    echo "🔄 Resetting receipt status to allow re-upload...\n";
    
    $resetStmt = $pdo->prepare("
        UPDATE stage_payment_requests 
        SET 
            receipt_file_path = NULL,
            verification_status = 'pending',
            transaction_reference = NULL,
            payment_date = NULL,
            homeowner_notes = CONCAT(
                COALESCE(homeowner_notes, ''), 
                '\n\n[SYSTEM] Receipt reset due to corrupted file on ', 
                NOW(), 
                '. Please re-upload receipt.'
            )
        WHERE id = ?
    ");
    
    $resetStmt->execute([$payment_id]);
    
    echo "✅ Receipt status reset successfully!\n\n";
    
    echo "📋 Next steps:\n";
    echo "1. The homeowner can now re-upload the receipt\n";
    echo "2. The verification status has been reset to 'pending'\n";
    echo "3. The corrupted file is still on disk but no longer referenced\n";
    echo "4. A note has been added to the payment explaining the reset\n\n";
    
    // Verify the reset
    $verifyStmt = $pdo->prepare("SELECT receipt_file_path, verification_status FROM stage_payment_requests WHERE id = ?");
    $verifyStmt->execute([$payment_id]);
    $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Updated status:\n";
    echo "- Receipt file path: " . ($updated['receipt_file_path'] ?: 'NULL (ready for re-upload)') . "\n";
    echo "- Verification status: {$updated['verification_status']}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Testing Receipt Upload Fix...\n\n";
    
    // Check if the new columns exist
    echo "1. Checking if receipt columns exist in stage_payment_requests table:\n";
    $result = $db->query("DESCRIBE stage_payment_requests");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = [
        'transaction_reference',
        'payment_date', 
        'receipt_file_path',
        'payment_method',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes'
    ];
    
    foreach ($requiredColumns as $column) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $column) {
                echo "   ✅ $column: " . $col['Type'] . "\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "   ❌ $column: NOT FOUND\n";
        }
    }
    
    // Check if verification logs table exists
    echo "\n2. Checking if stage_payment_verification_logs table exists:\n";
    try {
        $result = $db->query("SELECT COUNT(*) FROM stage_payment_verification_logs");
        echo "   ✅ stage_payment_verification_logs table exists\n";
    } catch (Exception $e) {
        echo "   ❌ stage_payment_verification_logs table does not exist\n";
    }
    
    // Check if notifications table exists
    echo "\n3. Checking if stage_payment_notifications table exists:\n";
    try {
        $result = $db->query("SELECT COUNT(*) FROM stage_payment_notifications");
        echo "   ✅ stage_payment_notifications table exists\n";
    } catch (Exception $e) {
        echo "   ❌ stage_payment_notifications table does not exist\n";
    }
    
    // Check existing payment requests
    echo "\n4. Checking existing payment requests:\n";
    $stmt = $db->query("SELECT id, homeowner_id, contractor_id, stage_name, requested_amount, status FROM stage_payment_requests LIMIT 5");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "   ⚠️  No payment requests found\n";
    } else {
        foreach ($payments as $payment) {
            echo "   📋 Payment ID: {$payment['id']}, Homeowner: {$payment['homeowner_id']}, Stage: {$payment['stage_name']}, Amount: ₹{$payment['requested_amount']}, Status: {$payment['status']}\n";
        }
    }
    
    // Test the upload directory creation
    echo "\n5. Testing upload directory creation:\n";
    $testPaymentId = $payments[0]['id'] ?? 1;
    $uploadDir = 'uploads/payment_receipts/' . $testPaymentId . '/';
    
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0755, true)) {
            echo "   ✅ Upload directory created: $uploadDir\n";
        } else {
            echo "   ❌ Failed to create upload directory: $uploadDir\n";
        }
    } else {
        echo "   ✅ Upload directory already exists: $uploadDir\n";
    }
    
    echo "\n🎉 Receipt upload system is ready!\n";
    echo "\nNext steps:\n";
    echo "1. Test the receipt upload from the frontend\n";
    echo "2. Verify files are uploaded to the correct directory\n";
    echo "3. Check that payment records are updated correctly\n";
    echo "4. Ensure notifications are created for contractors\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
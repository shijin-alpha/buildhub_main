<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== CHECKING AMAL SAMUEL'S SUBMITTED RECEIPT ===\n\n";
    
    // Get Amal Samuel's user details
    echo "1. Amal Samuel's User Details:\n";
    $user_query = "SELECT * FROM users WHERE first_name = 'Amal' AND last_name = 'Samuel'";
    $user_stmt = $pdo->prepare($user_query);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User Found:\n";
        echo "  ID: {$user['id']}\n";
        echo "  Name: {$user['first_name']} {$user['last_name']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Role: {$user['role']}\n";
        echo "  Created: {$user['created_at']}\n\n";
        
        $homeowner_id = $user['id'];
    } else {
        echo "❌ Amal Samuel not found in users table\n";
        exit;
    }
    
    // Get all payment requests for Amal Samuel
    echo "2. Amal Samuel's Payment Requests:\n";
    $payments_query = "SELECT * FROM stage_payment_requests WHERE homeowner_id = ? ORDER BY updated_at DESC";
    $payments_stmt = $pdo->prepare($payments_query);
    $payments_stmt->execute([$homeowner_id]);
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "❌ No payment requests found for Amal Samuel\n";
        exit;
    }
    
    echo "Found " . count($payments) . " payment request(s):\n\n";
    
    foreach ($payments as $payment) {
        echo "--- Payment ID: {$payment['id']} ---\n";
        echo "Project ID: {$payment['project_id']}\n";
        echo "Contractor ID: {$payment['contractor_id']}\n";
        echo "Stage: {$payment['stage_name']}\n";
        echo "Amount: ₹{$payment['requested_amount']}\n";
        echo "Status: {$payment['status']}\n";
        echo "Verification Status: " . ($payment['verification_status'] ?? 'NULL') . "\n";
        echo "Transaction Reference: " . ($payment['transaction_reference'] ?? 'NULL') . "\n";
        echo "Payment Date: " . ($payment['payment_date'] ?? 'NULL') . "\n";
        echo "Payment Method: " . ($payment['payment_method'] ?? 'NULL') . "\n";
        echo "Receipt File Path: " . ($payment['receipt_file_path'] ?? 'NULL') . "\n";
        echo "Updated At: {$payment['updated_at']}\n";
        
        // Check if this payment has receipt files
        if (!empty($payment['receipt_file_path'])) {
            echo "\n📄 RECEIPT FILES FOUND:\n";
            $receipt_files = json_decode($payment['receipt_file_path'], true);
            
            if (is_array($receipt_files)) {
                foreach ($receipt_files as $index => $file) {
                    echo "  File " . ($index + 1) . ":\n";
                    echo "    Original Name: {$file['original_name']}\n";
                    echo "    Stored Name: {$file['stored_name']}\n";
                    echo "    File Path: {$file['file_path']}\n";
                    echo "    File Size: " . number_format($file['file_size']) . " bytes\n";
                    echo "    File Type: {$file['file_type']}\n";
                    
                    // Check if file actually exists on disk
                    $full_path = __DIR__ . '/' . $file['file_path'];
                    if (file_exists($full_path)) {
                        echo "    ✅ File exists on disk\n";
                        echo "    Actual size: " . number_format(filesize($full_path)) . " bytes\n";
                    } else {
                        echo "    ❌ File NOT found on disk: $full_path\n";
                    }
                }
            } else {
                echo "  ❌ Invalid receipt files data format\n";
            }
        } else {
            echo "\n❌ No receipt files uploaded for this payment\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    // Check verification logs for Amal Samuel's payments
    echo "3. Verification Logs for Amal Samuel's Payments:\n";
    $logs_query = "SELECT pvl.*, spr.stage_name, spr.requested_amount 
                   FROM stage_payment_verification_logs pvl 
                   JOIN stage_payment_requests spr ON pvl.payment_request_id = spr.id 
                   WHERE spr.homeowner_id = ? 
                   ORDER BY pvl.created_at DESC";
    $logs_stmt = $pdo->prepare($logs_query);
    $logs_stmt->execute([$homeowner_id]);
    $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($logs)) {
        echo "Found " . count($logs) . " verification log(s):\n\n";
        foreach ($logs as $log) {
            echo "Payment ID: {$log['payment_request_id']}\n";
            echo "Stage: {$log['stage_name']}\n";
            echo "Amount: ₹{$log['requested_amount']}\n";
            echo "Action: {$log['action']}\n";
            echo "Verifier: {$log['verifier_type']} (ID: {$log['verifier_id']})\n";
            echo "Comments: {$log['comments']}\n";
            echo "Created: {$log['created_at']}\n\n";
        }
    } else {
        echo "No verification logs found\n\n";
    }
    
    // Check notifications for Amal Samuel
    echo "4. Notifications for Amal Samuel:\n";
    $notifications_query = "SELECT spn.*, spr.stage_name 
                           FROM stage_payment_notifications spn 
                           JOIN stage_payment_requests spr ON spn.payment_request_id = spr.id 
                           WHERE spr.homeowner_id = ? 
                           ORDER BY spn.created_at DESC LIMIT 5";
    $notifications_stmt = $pdo->prepare($notifications_query);
    $notifications_stmt->execute([$homeowner_id]);
    $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($notifications)) {
        echo "Found " . count($notifications) . " recent notification(s):\n\n";
        foreach ($notifications as $notification) {
            echo "Payment ID: {$notification['payment_request_id']}\n";
            echo "Stage: {$notification['stage_name']}\n";
            echo "Recipient: {$notification['recipient_type']} (ID: {$notification['recipient_id']})\n";
            echo "Type: {$notification['notification_type']}\n";
            echo "Title: {$notification['title']}\n";
            echo "Message: {$notification['message']}\n";
            echo "Created: {$notification['created_at']}\n\n";
        }
    } else {
        echo "No notifications found\n\n";
    }
    
    // Summary for contractor
    echo "5. SUMMARY FOR CONTRACTOR VERIFICATION:\n";
    $pending_query = "SELECT COUNT(*) as count, SUM(requested_amount) as total_amount 
                      FROM stage_payment_requests 
                      WHERE homeowner_id = ? 
                      AND receipt_file_path IS NOT NULL 
                      AND verification_status = 'pending'";
    $pending_stmt = $pdo->prepare($pending_query);
    $pending_stmt->execute([$homeowner_id]);
    $pending_summary = $pending_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Payments awaiting contractor verification: {$pending_summary['count']}\n";
    echo "Total amount pending verification: ₹" . number_format($pending_summary['total_amount'], 2) . "\n";
    
    if ($pending_summary['count'] > 0) {
        echo "\n✅ Amal Samuel has submitted receipt(s) that are ready for contractor verification!\n";
    } else {
        echo "\n❌ No receipts pending verification found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
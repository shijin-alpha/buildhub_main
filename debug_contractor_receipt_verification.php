<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== CONTRACTOR RECEIPT VERIFICATION DEBUG ===\n\n";
    
    // Check payment ID 15 details after receipt upload
    echo "1. Checking Payment ID 15 details:\n";
    $payment_query = "SELECT * FROM stage_payment_requests WHERE id = 15";
    $payment_stmt = $pdo->prepare($payment_query);
    $payment_stmt->execute();
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "Payment ID: {$payment['id']}\n";
        echo "Homeowner ID: {$payment['homeowner_id']}\n";
        echo "Contractor ID: {$payment['contractor_id']}\n";
        echo "Status: {$payment['status']}\n";
        echo "Verification Status: " . ($payment['verification_status'] ?? 'NULL') . "\n";
        echo "Transaction Reference: " . ($payment['transaction_reference'] ?? 'NULL') . "\n";
        echo "Receipt File Path: " . ($payment['receipt_file_path'] ?? 'NULL') . "\n";
        echo "Payment Date: " . ($payment['payment_date'] ?? 'NULL') . "\n";
        echo "Updated At: {$payment['updated_at']}\n\n";
        
        // Check if receipt files exist
        if ($payment['receipt_file_path']) {
            $receipt_files = json_decode($payment['receipt_file_path'], true);
            echo "Receipt Files:\n";
            if (is_array($receipt_files)) {
                foreach ($receipt_files as $index => $file) {
                    echo "  File " . ($index + 1) . ": {$file['original_name']} ({$file['file_size']} bytes)\n";
                    echo "    Stored as: {$file['stored_name']}\n";
                    echo "    Path: {$file['file_path']}\n";
                }
            } else {
                echo "  Invalid receipt files data\n";
            }
        } else {
            echo "No receipt files found\n";
        }
        
        $contractor_id = $payment['contractor_id'];
        
    } else {
        echo "Payment ID 15 not found\n";
        exit;
    }
    
    echo "\n2. Checking contractor payment verification API:\n";
    
    // Test contractor payment verification API
    $contractor_api_url = "http://localhost/buildhub/backend/api/contractor/get_payment_verifications.php?contractor_id=" . $contractor_id;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
            ]
        ]
    ]);
    
    echo "API URL: $contractor_api_url\n";
    
    $response = file_get_contents($contractor_api_url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to get response from contractor API\n";
    } else {
        $data = json_decode($response, true);
        
        if ($data && $data['success']) {
            echo "✅ Contractor API response successful\n";
            
            if (isset($data['data']['pending_verifications'])) {
                $pending = $data['data']['pending_verifications'];
                echo "Pending verifications: " . count($pending) . "\n";
                
                foreach ($pending as $verification) {
                    if ($verification['id'] == 15) {
                        echo "✅ Payment ID 15 found in pending verifications!\n";
                        echo "  Status: {$verification['status']}\n";
                        echo "  Verification Status: " . ($verification['verification_status'] ?? 'NULL') . "\n";
                        echo "  Has Receipt Files: " . (isset($verification['receipt_files']) && !empty($verification['receipt_files']) ? 'YES' : 'NO') . "\n";
                        break;
                    }
                }
            } else {
                echo "No pending verifications found\n";
            }
        } else {
            echo "❌ Contractor API failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    }
    
    echo "\n3. Checking notification system:\n";
    
    // Check if notifications were created
    $notification_query = "SELECT * FROM stage_payment_notifications WHERE payment_request_id = 15 ORDER BY created_at DESC";
    $notification_stmt = $pdo->prepare($notification_query);
    $notification_stmt->execute();
    $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($notifications)) {
        echo "Found " . count($notifications) . " notifications for payment 15:\n";
        foreach ($notifications as $notification) {
            echo "  To: {$notification['recipient_type']} (ID: {$notification['recipient_id']})\n";
            echo "  Type: {$notification['notification_type']}\n";
            echo "  Title: {$notification['title']}\n";
            echo "  Created: {$notification['created_at']}\n\n";
        }
    } else {
        echo "No notifications found for payment 15\n";
    }
    
    echo "\n4. Checking verification logs:\n";
    
    // Check verification logs
    $log_query = "SELECT * FROM stage_payment_verification_logs WHERE payment_request_id = 15 ORDER BY created_at DESC";
    $log_stmt = $pdo->prepare($log_query);
    $log_stmt->execute();
    $logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($logs)) {
        echo "Found " . count($logs) . " verification logs for payment 15:\n";
        foreach ($logs as $log) {
            echo "  Action: {$log['action']}\n";
            echo "  By: {$log['verifier_type']} (ID: {$log['verifier_id']})\n";
            echo "  Comments: {$log['comments']}\n";
            echo "  Created: {$log['created_at']}\n\n";
        }
    } else {
        echo "No verification logs found for payment 15\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
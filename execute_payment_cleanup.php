<?php
/**
 * Execute Payment Cleanup - Direct Database Cleanup
 * Removes sample payment requests, keeping only the legitimate Foundation payment
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🧹 Starting Payment Request Cleanup...\n\n";
    
    // Show current state
    echo "📋 Current Payment Requests:\n";
    $stmt = $pdo->query("SELECT id, project_id, stage_name, status FROM stage_payment_requests ORDER BY id");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($requests as $request) {
        echo "- ID {$request['id']}: Project {$request['project_id']} - {$request['stage_name']} ({$request['status']})\n";
    }
    echo "\n";
    
    // Remove sample/test payment requests (keep only ID 15 - the legitimate Foundation payment)
    $removeIds = [1, 13, 14, 16, 17, 18, 19]; // All except 15
    
    if (!empty($removeIds)) {
        echo "🗑️ Removing sample payment requests (IDs: " . implode(', ', $removeIds) . ")...\n";
        
        // Remove related data first
        $placeholders = str_repeat('?,', count($removeIds) - 1) . '?';
        
        // Remove payment notifications
        $notificationQuery = "DELETE FROM stage_payment_notifications WHERE payment_request_id IN ($placeholders)";
        $notificationStmt = $pdo->prepare($notificationQuery);
        $notificationStmt->execute($removeIds);
        echo "✅ Removed payment notifications\n";
        
        // Remove verification logs
        $logQuery = "DELETE FROM stage_payment_verification_logs WHERE payment_request_id IN ($placeholders)";
        $logStmt = $pdo->prepare($logQuery);
        $logStmt->execute($removeIds);
        echo "✅ Removed verification logs\n";
        
        // Remove the payment requests
        $deleteQuery = "DELETE FROM stage_payment_requests WHERE id IN ($placeholders)";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $result = $deleteStmt->execute($removeIds);
        
        if ($result) {
            echo "✅ Successfully removed " . count($removeIds) . " sample payment requests\n\n";
        } else {
            echo "❌ Failed to remove sample payment requests\n\n";
        }
    }
    
    // Show final state
    echo "📊 Final Payment Requests:\n";
    $finalStmt = $pdo->query("SELECT id, project_id, stage_name, requested_amount, status FROM stage_payment_requests ORDER BY id");
    $finalRequests = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalRequests)) {
        echo "⚠️ No payment requests remaining\n";
    } else {
        foreach ($finalRequests as $request) {
            echo "- ID {$request['id']}: Project {$request['project_id']} - {$request['stage_name']} (₹" . number_format($request['requested_amount']) . " - {$request['status']})\n";
        }
    }
    
    echo "\n✅ Cleanup Complete!\n";
    echo "Now only the legitimate Foundation payment request remains.\n";
    echo "The payment request form will show all other stages as available for new requests.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
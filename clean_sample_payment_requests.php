<?php
/**
 * Clean Sample Payment Requests
 * Removes all sample/test payment requests, keeping only the legitimate Foundation payment
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>🧹 Cleaning Sample Payment Requests</h1>\n";
    
    // First, let's see what we have
    echo "<h2>Current Payment Requests:</h2>\n";
    $stmt = $db->query("SELECT id, project_id, stage_name, requested_amount, status, work_description FROM stage_payment_requests ORDER BY id");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>Project</th><th>Stage</th><th>Amount</th><th>Status</th><th>Description</th><th>Action</th></tr>\n";
    
    $keepIds = [];
    $removeIds = [];
    
    foreach ($requests as $request) {
        $isLegitimate = false;
        $reason = '';
        
        // Identify legitimate vs sample requests
        if ($request['id'] == 15 && $request['stage_name'] == 'Foundation' && $request['project_id'] == 37) {
            $isLegitimate = true;
            $reason = 'Legitimate Foundation payment';
            $keepIds[] = $request['id'];
        } else {
            // Check if it's a sample/test request
            if (strpos(strtolower($request['work_description']), 'sample') !== false ||
                strpos(strtolower($request['work_description']), 'test') !== false ||
                in_array($request['id'], [1, 13, 14, 16, 17, 18, 19])) {
                $reason = 'Sample/Test request - REMOVE';
                $removeIds[] = $request['id'];
            } else {
                $reason = 'Check manually';
            }
        }
        
        $rowColor = $isLegitimate ? '#d4edda' : '#f8d7da';
        echo "<tr style='background-color: $rowColor;'>\n";
        echo "<td>{$request['id']}</td>\n";
        echo "<td>{$request['project_id']}</td>\n";
        echo "<td>{$request['stage_name']}</td>\n";
        echo "<td>₹" . number_format($request['requested_amount']) . "</td>\n";
        echo "<td>{$request['status']}</td>\n";
        echo "<td>" . substr($request['work_description'], 0, 50) . "...</td>\n";
        echo "<td><strong>$reason</strong></td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>📋 Summary:</h2>\n";
    echo "<p><strong>Keep:</strong> " . count($keepIds) . " requests (IDs: " . implode(', ', $keepIds) . ")</p>\n";
    echo "<p><strong>Remove:</strong> " . count($removeIds) . " requests (IDs: " . implode(', ', $removeIds) . ")</p>\n";
    
    if (!empty($removeIds)) {
        echo "<h2>🗑️ Removing Sample Payment Requests:</h2>\n";
        
        // Remove the sample requests
        $placeholders = str_repeat('?,', count($removeIds) - 1) . '?';
        $deleteQuery = "DELETE FROM stage_payment_requests WHERE id IN ($placeholders)";
        
        $deleteStmt = $db->prepare($deleteQuery);
        $result = $deleteStmt->execute($removeIds);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Successfully removed " . count($removeIds) . " sample payment requests</p>\n";
            
            // Also clean up related data if any
            echo "<h3>🧹 Cleaning Related Data:</h3>\n";
            
            // Check for payment notifications
            $notificationQuery = "DELETE FROM stage_payment_notifications WHERE payment_request_id IN ($placeholders)";
            $notificationStmt = $db->prepare($notificationQuery);
            $notificationResult = $notificationStmt->execute($removeIds);
            echo "<p>Removed payment notifications: " . ($notificationResult ? "✅ Success" : "❌ Failed") . "</p>\n";
            
            // Check for verification logs
            $logQuery = "DELETE FROM stage_payment_verification_logs WHERE payment_request_id IN ($placeholders)";
            $logStmt = $db->prepare($logQuery);
            $logResult = $logStmt->execute($removeIds);
            echo "<p>Removed verification logs: " . ($logResult ? "✅ Success" : "❌ Failed") . "</p>\n";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to remove sample payment requests</p>\n";
        }
    }
    
    // Show final state
    echo "<h2>📊 Final Payment Requests:</h2>\n";
    $finalStmt = $db->query("SELECT id, project_id, stage_name, requested_amount, status, work_description FROM stage_payment_requests ORDER BY id");
    $finalRequests = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalRequests)) {
        echo "<p style='color: orange;'>⚠️ No payment requests remaining</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>ID</th><th>Project</th><th>Stage</th><th>Amount</th><th>Status</th><th>Description</th></tr>\n";
        
        foreach ($finalRequests as $request) {
            echo "<tr style='background-color: #d4edda;'>\n";
            echo "<td>{$request['id']}</td>\n";
            echo "<td>{$request['project_id']}</td>\n";
            echo "<td>{$request['stage_name']}</td>\n";
            echo "<td>₹" . number_format($request['requested_amount']) . "</td>\n";
            echo "<td>{$request['status']}</td>\n";
            echo "<td>" . substr($request['work_description'], 0, 50) . "...</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>✅ Cleanup Complete!</h2>\n";
    echo "<p>The payment request filtering system will now only show legitimate unpaid stages.</p>\n";
    echo "<p>Since only the Foundation stage has a legitimate payment request, other stages (Structure, Brickwork, Roofing, Electrical, Plumbing, Finishing) will be available for new payment requests.</p>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
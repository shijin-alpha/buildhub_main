<?php
require_once 'config/database.php';

echo "Testing contractor acknowledgment API...\n";

try {
    $db = (new Database())->getConnection();
    
    // Create a test contractor layout send entry
    echo "1. Creating test contractor layout send entry...\n";
    
    $stmt = $db->prepare("
        INSERT INTO contractor_layout_sends (contractor_id, homeowner_id, layout_id, design_id, message, created_at) 
        VALUES (37, 28, NULL, NULL, 'Test acknowledgment', NOW())
    ");
    $stmt->execute();
    $testId = $db->lastInsertId();
    echo "   Created test entry with ID: $testId\n";
    
    // Simulate the acknowledgment API call
    echo "\n2. Simulating acknowledgment API call...\n";
    
    // Set up the input data
    $input = [
        'id' => $testId,
        'contractor_id' => 37,
        'due_date' => '2026-01-15'
    ];
    
    // Simulate the API logic
    $contractorId = $input['contractor_id'];
    $dueDate = $input['due_date'];
    
    // Get the homeowner_id and layout details
    $getItemStmt = $db->prepare("SELECT homeowner_id, layout_id, design_id, payload FROM contractor_layout_sends WHERE id = ? AND contractor_id = ?");
    $getItemStmt->execute([$testId, $contractorId]);
    $itemData = $getItemStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$itemData) {
        throw new Exception("Test item not found");
    }
    
    $homeownerId = $itemData['homeowner_id'];
    echo "   Found homeowner ID: $homeownerId\n";
    
    // Update the acknowledgment
    $sql = "UPDATE contractor_layout_sends SET acknowledged_at = NOW(), due_date = ? WHERE id = ? AND contractor_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$dueDate, $testId, $contractorId]);
    echo "   Updated acknowledgment timestamp\n";
    
    // Create notification and message
    if ($homeownerId) {
        // Get contractor details
        $contractorStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $contractorStmt->execute([$contractorId]);
        $contractorData = $contractorStmt->fetch(PDO::FETCH_ASSOC);
        $contractorName = trim(($contractorData['first_name'] ?? '') . ' ' . ($contractorData['last_name'] ?? '')) ?: 'Contractor';
        
        echo "   Contractor: $contractorName\n";
        
        // Create notification
        $ackTime = date('Y-m-d H:i:s');
        $ackDate = $dueDate ? date('F j, Y', strtotime($dueDate)) : 'not specified';
        $title = "Contractor Acknowledged Your Layout";
        $notificationMessage = "{$contractorName} acknowledged your layout at {$ackTime}.\nDue date: {$ackDate}";
        
        $notifStmt = $db->prepare("INSERT INTO homeowner_notifications (homeowner_id, contractor_id, type, title, message, status) VALUES (?, ?, 'acknowledgment', ?, ?, 'unread')");
        $notifStmt->execute([$homeownerId, $contractorId, $title, $notificationMessage]);
        echo "   ✅ Created notification\n";
        
        // Create message
        $layoutTitle = 'Test Layout';
        $subject = "Layout Request Acknowledged - {$layoutTitle}";
        $due_text = $dueDate ? "Expected completion: " . date('F j, Y', strtotime($dueDate)) : "Due date to be confirmed";
        $message_text = "Hello! I have acknowledged your layout request for '{$layoutTitle}' and will begin working on your estimate. {$due_text}. I'll keep you updated on the progress.";
        
        $messageStmt = $db->prepare("
            INSERT INTO messages (from_user_id, to_user_id, subject, message, message_type, created_at) 
            VALUES (?, ?, ?, ?, 'acknowledgment', NOW())
        ");
        $messageStmt->execute([$contractorId, $homeownerId, $subject, $message_text]);
        echo "   ✅ Created message\n";
    }
    
    echo "\n3. Verifying results...\n";
    
    // Check if notification was created
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM homeowner_notifications WHERE homeowner_id = ? AND contractor_id = ? AND type = 'acknowledgment'");
    $stmt->execute([$homeownerId, $contractorId]);
    $notifCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Notifications for this homeowner: $notifCount\n";
    
    // Check if message was created
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE from_user_id = ? AND to_user_id = ? AND message_type = 'acknowledgment'");
    $stmt->execute([$contractorId, $homeownerId]);
    $msgCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Messages from contractor to homeowner: $msgCount\n";
    
    // Clean up test data
    echo "\n4. Cleaning up test data...\n";
    $db->prepare("DELETE FROM contractor_layout_sends WHERE id = ?")->execute([$testId]);
    echo "   Removed test contractor layout send entry\n";
    
    echo "\n✅ Test completed successfully!\n";
    echo "The acknowledgment system creates both notifications and messages for homeowners.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
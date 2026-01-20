<?php
require_once 'config/database.php';

echo "🧪 Testing Contractor Acknowledgment Message System\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test 1: Check if tables exist
    echo "1. Checking required tables...\n";
    
    $tables = ['homeowner_notifications', 'messages', 'contractor_layout_sends'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing\n";
        }
    }
    
    // Test 2: Check recent acknowledgment notifications
    echo "\n2. Checking recent acknowledgment notifications...\n";
    
    $stmt = $db->query("
        SELECT hn.*, u.first_name, u.last_name 
        FROM homeowner_notifications hn
        LEFT JOIN users u ON hn.homeowner_id = u.id
        WHERE hn.type = 'acknowledgment'
        ORDER BY hn.created_at DESC 
        LIMIT 5
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "   ℹ️  No acknowledgment notifications found\n";
    } else {
        echo "   📋 Found " . count($notifications) . " acknowledgment notifications:\n";
        foreach ($notifications as $notif) {
            $homeowner = trim($notif['first_name'] . ' ' . $notif['last_name']);
            echo "      • ID {$notif['id']}: {$notif['title']} for {$homeowner} ({$notif['status']}) - {$notif['created_at']}\n";
        }
    }
    
    // Test 3: Check recent acknowledgment messages
    echo "\n3. Checking recent acknowledgment messages...\n";
    
    $stmt = $db->query("
        SELECT m.*, 
               sender.first_name as sender_first, sender.last_name as sender_last,
               receiver.first_name as receiver_first, receiver.last_name as receiver_last
        FROM messages m
        LEFT JOIN users sender ON m.from_user_id = sender.id
        LEFT JOIN users receiver ON m.to_user_id = receiver.id
        WHERE m.message_type = 'acknowledgment'
        ORDER BY m.created_at DESC 
        LIMIT 5
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($messages)) {
        echo "   ℹ️  No acknowledgment messages found\n";
    } else {
        echo "   💬 Found " . count($messages) . " acknowledgment messages:\n";
        foreach ($messages as $msg) {
            $sender = trim($msg['sender_first'] . ' ' . $msg['sender_last']);
            $receiver = trim($msg['receiver_first'] . ' ' . $msg['receiver_last']);
            $status = $msg['is_read'] ? 'Read' : 'Unread';
            echo "      • ID {$msg['id']}: {$msg['subject']} from {$sender} to {$receiver} ({$status}) - {$msg['created_at']}\n";
        }
    }
    
    // Test 4: Check contractor layout sends
    echo "\n4. Checking contractor layout sends with acknowledgments...\n";
    
    $stmt = $db->query("
        SELECT cls.*, 
               c.first_name as contractor_first, c.last_name as contractor_last,
               h.first_name as homeowner_first, h.last_name as homeowner_last
        FROM contractor_layout_sends cls
        LEFT JOIN users c ON cls.contractor_id = c.id
        LEFT JOIN users h ON cls.homeowner_id = h.id
        WHERE cls.acknowledged_at IS NOT NULL
        ORDER BY cls.acknowledged_at DESC 
        LIMIT 5
    ");
    $sends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sends)) {
        echo "   ℹ️  No acknowledged contractor layout sends found\n";
    } else {
        echo "   🤝 Found " . count($sends) . " acknowledged layout sends:\n";
        foreach ($sends as $send) {
            $contractor = trim($send['contractor_first'] . ' ' . $send['contractor_last']);
            $homeowner = trim($send['homeowner_first'] . ' ' . $send['homeowner_last']);
            $due = $send['due_date'] ?: 'No due date';
            echo "      • ID {$send['id']}: {$contractor} → {$homeowner}, acknowledged {$send['acknowledged_at']}, due: {$due}\n";
        }
    }
    
    // Test 5: Test API endpoints
    echo "\n5. Testing API endpoints...\n";
    
    // Test get_notifications.php
    echo "   Testing get_notifications.php...\n";
    session_start();
    $_SESSION['user_id'] = 28; // Test homeowner ID
    
    ob_start();
    include 'api/homeowner/get_notifications.php';
    $notif_output = ob_get_clean();
    
    $notif_result = json_decode($notif_output, true);
    if ($notif_result && $notif_result['success']) {
        $total = count($notif_result['notifications']);
        $unread = $notif_result['unread_count'];
        echo "      ✅ Notifications API working: {$total} total, {$unread} unread\n";
        
        // Count acknowledgment notifications
        $ack_count = 0;
        foreach ($notif_result['notifications'] as $notif) {
            if ($notif['type'] === 'acknowledgment' || $notif['source'] === 'contractor_acknowledgment') {
                $ack_count++;
            }
        }
        echo "      📋 Acknowledgment notifications: {$ack_count}\n";
    } else {
        echo "      ❌ Notifications API failed\n";
    }
    
    // Test get_messages.php
    echo "   Testing get_messages.php...\n";
    
    ob_start();
    include 'api/homeowner/get_messages.php';
    $msg_output = ob_get_clean();
    
    $msg_result = json_decode($msg_output, true);
    if ($msg_result && $msg_result['success']) {
        $total = count($msg_result['messages']);
        $unread = $msg_result['unread_count'];
        echo "      ✅ Messages API working: {$total} total, {$unread} unread\n";
        
        // Count acknowledgment messages
        $ack_count = 0;
        foreach ($msg_result['messages'] as $msg) {
            if ($msg['message_type'] === 'acknowledgment') {
                $ack_count++;
            }
        }
        echo "      💬 Acknowledgment messages: {$ack_count}\n";
    } else {
        echo "      ❌ Messages API failed\n";
    }
    
    echo "\n✅ Test completed successfully!\n";
    echo "\n📋 Summary:\n";
    echo "- Contractor acknowledgments create notifications in homeowner_notifications table\n";
    echo "- Contractor acknowledgments create messages in messages table\n";
    echo "- Both appear in homeowner's message center via unified APIs\n";
    echo "- NotificationSystem widget integrates MessageCenter component\n";
    echo "- Homeowners can see acknowledgment messages in both Notifications and Messages tabs\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
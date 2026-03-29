<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "Checking acknowledgment messages...\n";

// Check messages table
$stmt = $db->query('SELECT COUNT(*) as count FROM messages WHERE message_type = "acknowledgment"');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Acknowledgment messages in messages table: " . $result['count'] . "\n";

// Check recent messages
$stmt = $db->query('SELECT * FROM messages WHERE message_type = "acknowledgment" ORDER BY created_at DESC LIMIT 3');
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($messages)) {
    echo "\nRecent acknowledgment messages:\n";
    foreach ($messages as $msg) {
        echo "- ID {$msg['id']}: {$msg['subject']} (from user {$msg['from_user_id']} to {$msg['to_user_id']}) - {$msg['created_at']}\n";
    }
} else {
    echo "No acknowledgment messages found in messages table.\n";
}

// Check homeowner_notifications
$stmt = $db->query('SELECT COUNT(*) as count FROM homeowner_notifications WHERE type = "acknowledgment"');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nAcknowledgment notifications in homeowner_notifications table: " . $result['count'] . "\n";
?>
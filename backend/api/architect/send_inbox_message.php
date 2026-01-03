<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields
    $required_fields = ['recipient_id', 'sender_id', 'message_type', 'title', 'message'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $recipient_id = $data['recipient_id'];
    $sender_id = $data['sender_id'];
    $message_type = $data['message_type']; // 'plan_saved', 'plan_updated', 'plan_submitted', etc.
    $title = $data['title'];
    $message = $data['message'];
    $metadata = isset($data['metadata']) ? json_encode($data['metadata']) : null;
    $priority = isset($data['priority']) ? $data['priority'] : 'normal'; // low, normal, high, urgent
    
    // Verify sender is an architect
    $sender_check = $db->prepare("SELECT role FROM users WHERE id = ? AND role = 'architect'");
    $sender_check->execute([$sender_id]);
    if (!$sender_check->fetch()) {
        throw new Exception('Unauthorized: Only architects can send plan messages');
    }
    
    // Verify recipient exists
    $recipient_check = $db->prepare("SELECT id, role FROM users WHERE id = ?");
    $recipient_check->execute([$recipient_id]);
    $recipient = $recipient_check->fetch(PDO::FETCH_ASSOC);
    if (!$recipient) {
        throw new Exception('Recipient not found');
    }
    
    // Insert message into inbox
    $query = "INSERT INTO inbox_messages (
        recipient_id, 
        sender_id, 
        message_type, 
        title, 
        message, 
        metadata, 
        priority, 
        is_read, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        $recipient_id,
        $sender_id,
        $message_type,
        $title,
        $message,
        $metadata,
        $priority
    ]);
    
    if (!$result) {
        throw new Exception('Failed to send message');
    }
    
    $message_id = $db->lastInsertId();
    
    // Also create a notification for real-time updates
    $notification_query = "INSERT INTO notifications (
        user_id, 
        type, 
        title, 
        message, 
        metadata, 
        is_read, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, 0, NOW())";
    
    $notification_stmt = $db->prepare($notification_query);
    $notification_stmt->execute([
        $recipient_id,
        $message_type,
        $title,
        $message,
        $metadata
    ]);
    
    // Get sender info for response
    $sender_info_query = "SELECT first_name, last_name, email FROM users WHERE id = ?";
    $sender_info_stmt = $db->prepare($sender_info_query);
    $sender_info_stmt->execute([$sender_id]);
    $sender_info = $sender_info_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Inbox message sent successfully',
        'data' => [
            'message_id' => $message_id,
            'recipient_id' => $recipient_id,
            'recipient_role' => $recipient['role'],
            'sender_name' => ($sender_info['first_name'] ?? '') . ' ' . ($sender_info['last_name'] ?? ''),
            'sender_email' => $sender_info['email'] ?? '',
            'message_type' => $message_type,
            'title' => $title,
            'priority' => $priority,
            'sent_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
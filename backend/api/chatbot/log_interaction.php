<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create chatbot_interactions table if it doesn't exist
    $createTable = "
        CREATE TABLE IF NOT EXISTS chatbot_interactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            session_id VARCHAR(255) NULL,
            user_question TEXT NOT NULL,
            bot_response TEXT NOT NULL,
            matched_intent VARCHAR(255) NULL,
            confidence_score DECIMAL(3,2) NULL,
            response_time_ms INT NULL,
            user_agent TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_created_at (created_at),
            INDEX idx_intent (matched_intent)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($createTable);

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    // Extract data
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = $input['conversation_id'] ?? session_id();
    $user_question = $input['question'] ?? $input['message'] ?? '';
    $bot_response = $input['response'] ?? '';
    $matched_intent = $input['intent'] ?? null;
    $confidence_score = isset($input['confidence']) ? floatval($input['confidence']) : null;
    $response_time_ms = isset($input['response_time']) ? intval($input['response_time']) : null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

    // Insert interaction log
    $stmt = $db->prepare("
        INSERT INTO chatbot_interactions (
            user_id, session_id, user_question, bot_response, matched_intent,
            confidence_score, response_time_ms, user_agent, ip_address
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $session_id,
        $user_question,
        $bot_response,
        $matched_intent,
        $confidence_score,
        $response_time_ms,
        $user_agent,
        $ip_address
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Interaction logged successfully',
        'interaction_id' => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log("Chatbot log interaction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
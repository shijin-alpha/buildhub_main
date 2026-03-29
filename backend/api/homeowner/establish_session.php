<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    
    // Get homeowner ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $homeowner_id = $input['homeowner_id'] ?? 32; // Default to test user
    
    // Get homeowner details from database
    $stmt = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ? AND role = 'homeowner'");
    $stmt->execute([$homeowner_id]);
    $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$homeowner) {
        echo json_encode([
            'success' => false,
            'message' => 'Homeowner not found'
        ]);
        exit;
    }
    
    // Establish session
    $_SESSION['user_id'] = $homeowner['id'];
    $_SESSION['user_role'] = 'homeowner';
    $_SESSION['user_name'] = $homeowner['first_name'] . ' ' . $homeowner['last_name'];
    $_SESSION['user_email'] = $homeowner['email'];
    $_SESSION['logged_in'] = true;
    
    error_log("Homeowner session established for user {$homeowner['id']} ({$homeowner['first_name']} {$homeowner['last_name']})");
    
    echo json_encode([
        'success' => true,
        'message' => 'Homeowner session established successfully',
        'user_id' => $homeowner['id'],
        'user_name' => $homeowner['first_name'] . ' ' . $homeowner['last_name'],
        'user_email' => $homeowner['email'],
        'session_id' => session_id()
    ]);
    
} catch (Exception $e) {
    error_log("Homeowner session establishment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to establish homeowner session: ' . $e->getMessage()
    ]);
}
?>
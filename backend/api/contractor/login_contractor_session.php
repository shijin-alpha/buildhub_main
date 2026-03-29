<?php
/**
 * Login Contractor Session API
 * Simple API to establish contractor session for testing purposes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['contractor_id'])) {
        echo json_encode(['success' => false, 'message' => 'Contractor ID is required']);
        exit;
    }
    
    $contractor_id = (int)$input['contractor_id'];
    
    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid contractor ID']);
        exit;
    }
    
    // Verify contractor exists and is active
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = ? AND role = 'contractor' AND status = 'approved'");
    $stmt->execute([$contractor_id]);
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contractor) {
        echo json_encode(['success' => false, 'message' => 'Contractor not found or inactive']);
        exit;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $contractor['id'];
    $_SESSION['role'] = $contractor['role'];
    $_SESSION['first_name'] = $contractor['first_name'];
    $_SESSION['last_name'] = $contractor['last_name'];
    $_SESSION['email'] = $contractor['email'];
    $_SESSION['login_time'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Contractor session established successfully',
        'data' => [
            'contractor_id' => $contractor['id'],
            'first_name' => $contractor['first_name'],
            'last_name' => $contractor['last_name'],
            'email' => $contractor['email'],
            'role' => $contractor['role'],
            'session_id' => session_id()
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Contractor login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
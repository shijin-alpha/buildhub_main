<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

// Set session variables for testing
$_SESSION['user_id'] = $user_id;
$_SESSION['user_role'] = 'homeowner';

// Get user details from database for better testing
try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT first_name, last_name, email FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Session established',
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => true, 
        'message' => 'Session established (without user details)',
        'error' => $e->getMessage()
    ]);
}
?>
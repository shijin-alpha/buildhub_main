<?php
/**
 * Check current session status and return user information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        require_once '../../config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Get user details from database
        $query = "SELECT id, first_name, last_name, email, role, status, is_verified, 
                         phone, city, state, company_name, created_at
                  FROM users 
                  WHERE id = ? AND status = 'approved' AND is_verified = 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'user' => $user,
                'session_id' => session_id()
            ]);
        } else {
            // User not found or not approved, destroy session
            session_destroy();
            echo json_encode([
                'success' => false,
                'authenticated' => false,
                'message' => 'User not found or not approved'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'message' => 'No active session'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'message' => 'Session check failed'
    ]);
}
?>
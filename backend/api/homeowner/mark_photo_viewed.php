<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is a homeowner
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $homeowner_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['photo_id'])) {
        echo json_encode(['success' => false, 'message' => 'Photo ID is required']);
        exit;
    }
    
    $photo_id = intval($input['photo_id']);
    
    // Verify photo belongs to homeowner
    $verifyStmt = $pdo->prepare("
        SELECT id FROM geo_photos 
        WHERE id = ? AND homeowner_id = ?
    ");
    $verifyStmt->execute([$photo_id, $homeowner_id]);
    
    if (!$verifyStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Photo not found or access denied']);
        exit;
    }
    
    // Mark photo as viewed
    $updateStmt = $pdo->prepare("
        UPDATE geo_photos 
        SET homeowner_viewed = TRUE, homeowner_viewed_at = CURRENT_TIMESTAMP
        WHERE id = ? AND homeowner_id = ?
    ");
    
    $updateStmt->execute([$photo_id, $homeowner_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo marked as viewed'
    ]);
    
} catch (Exception $e) {
    error_log("Mark photo viewed error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
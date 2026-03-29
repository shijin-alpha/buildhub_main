<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once '../../config/database.php';
    
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'architect') {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }
    
    $architect_id = $_SESSION['user_id'];
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['preview_id'])) {
        echo json_encode(['success' => false, 'message' => 'Preview ID is required']);
        exit;
    }
    
    $preview_id = intval($input['preview_id']);
    
    // Verify user is architect
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $architect_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $user['role'] !== 'architect') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get the concept preview to verify ownership and get file paths
    $previewStmt = $db->prepare("
        SELECT * FROM concept_previews 
        WHERE id = :id AND architect_id = :architect_id
    ");
    $previewStmt->execute([
        ':id' => $preview_id,
        ':architect_id' => $architect_id
    ]);
    $preview = $previewStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$preview) {
        echo json_encode(['success' => false, 'message' => 'Concept preview not found or access denied']);
        exit;
    }
    
    // Delete the image file if it exists
    $fileDeleted = false;
    if ($preview['image_path'] && file_exists($preview['image_path'])) {
        if (unlink($preview['image_path'])) {
            $fileDeleted = true;
        }
    }
    
    // Delete the database record
    $deleteStmt = $db->prepare("DELETE FROM concept_previews WHERE id = :id AND architect_id = :architect_id");
    $deleteResult = $deleteStmt->execute([
        ':id' => $preview_id,
        ':architect_id' => $architect_id
    ]);
    
    if ($deleteResult && $deleteStmt->rowCount() > 0) {
        $response = [
            'success' => true,
            'message' => 'Concept preview deleted successfully',
            'file_deleted' => $fileDeleted
        ];
        
        if (!$fileDeleted && $preview['image_path']) {
            $response['warning'] = 'Database record deleted but image file could not be removed';
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete concept preview']);
    }
    
} catch (Exception $e) {
    error_log("Delete concept preview error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ]);
}
?>
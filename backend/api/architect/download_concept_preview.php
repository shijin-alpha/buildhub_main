<?php
// Handle errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once '../../config/database.php';
    
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'architect') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }
    
    $architect_id = $_SESSION['user_id'];
    
    // Get preview ID from URL parameter
    $preview_id = isset($_GET['preview_id']) ? intval($_GET['preview_id']) : 0;
    
    if (!$preview_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Preview ID is required']);
        exit;
    }
    
    // Get the concept preview to verify ownership and get file path
    $previewStmt = $db->prepare("
        SELECT cp.*, lr.plot_size, lr.budget_range,
               CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users u ON lr.homeowner_id = u.id
        WHERE cp.id = :id AND cp.architect_id = :architect_id
    ");
    $previewStmt->execute([
        ':id' => $preview_id,
        ':architect_id' => $architect_id
    ]);
    $preview = $previewStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$preview) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Concept preview not found or access denied']);
        exit;
    }
    
    if (!$preview['image_path'] || !file_exists($preview['image_path'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Image file not found']);
        exit;
    }
    
    // Generate a meaningful filename
    $timestamp = date('Y-m-d', strtotime($preview['created_at']));
    $description = $preview['original_description'] ? 
        preg_replace('/[^a-zA-Z0-9]/', '_', substr($preview['original_description'], 0, 30)) : 
        'concept';
    $homeowner = preg_replace('/[^a-zA-Z0-9]/', '_', $preview['homeowner_name']);
    
    $filename = "concept_preview_{$preview['id']}_{$homeowner}_{$description}_{$timestamp}.png";
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($preview['image_path']));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output the file
    readfile($preview['image_path']);
    
} catch (Exception $e) {
    error_log("Download concept preview error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
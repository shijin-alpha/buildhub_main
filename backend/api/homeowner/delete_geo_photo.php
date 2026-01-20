<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['photo_id'])) {
        echo json_encode(['success' => false, 'message' => 'Photo ID is required']);
        exit;
    }

    $photo_id = (int)$input['photo_id'];

    if ($photo_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid photo ID']);
        exit;
    }

    // First, get the photo details to verify ownership and get file path
    $stmt = $db->prepare("
        SELECT 
            gp.*,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name
        FROM geo_photos gp
        LEFT JOIN users u ON gp.contractor_id = u.id
        WHERE gp.id = ?
    ");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$photo) {
        echo json_encode(['success' => false, 'message' => 'Photo not found']);
        exit;
    }

    // Verify that the homeowner has permission to delete this photo
    // For now, we'll allow homeowners to delete photos from their projects
    // In a more secure implementation, you might want to add additional checks
    
    // Start transaction
    $db->beginTransaction();

    try {
        // Delete the photo record from database
        $deleteStmt = $db->prepare("DELETE FROM geo_photos WHERE id = ?");
        $deleteStmt->execute([$photo_id]);

        if ($deleteStmt->rowCount() === 0) {
            throw new Exception('Failed to delete photo record');
        }

        // Try to delete the physical file
        $file_deleted = false;
        if ($photo['file_path'] && file_exists($photo['file_path'])) {
            if (unlink($photo['file_path'])) {
                $file_deleted = true;
            } else {
                // Log warning but don't fail the operation
                error_log("Warning: Could not delete physical file: " . $photo['file_path']);
            }
        }

        // Commit transaction
        $db->commit();

        // Log the deletion
        error_log("Photo deleted - ID: {$photo_id}, File: {$photo['file_path']}, Physical file deleted: " . ($file_deleted ? 'Yes' : 'No'));

        echo json_encode([
            'success' => true,
            'message' => 'Photo deleted successfully',
            'data' => [
                'photo_id' => $photo_id,
                'filename' => $photo['original_filename'],
                'file_deleted' => $file_deleted
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Delete geo photo error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get layout templates from library
    $query = "SELECT 
                id,
                name,
                description,
                style,
                rooms,
                preview_image,
                template_file,
                created_at
              FROM layout_templates
              WHERE status = 'active'
              ORDER BY name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'layouts' => $templates
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching layout library: ' . $e->getMessage()
    ]);
}
?>
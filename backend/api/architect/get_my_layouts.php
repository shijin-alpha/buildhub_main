<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;
    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $db->exec("CREATE TABLE IF NOT EXISTS layout_library (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        layout_type VARCHAR(100) NOT NULL,
        bedrooms INT NOT NULL,
        bathrooms INT NOT NULL,
        area INT NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        design_file_url VARCHAR(500),
        technical_details TEXT,
        price_range VARCHAR(100),
        view_price DECIMAL(10,2) DEFAULT 0,
        architect_id INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (architect_id) REFERENCES users(id)
    )");
    try { $db->exec("ALTER TABLE layout_library ADD COLUMN IF NOT EXISTS design_file_url VARCHAR(500) NULL AFTER image_url"); } catch (Exception $__) {}
    try { $db->exec("ALTER TABLE layout_library ADD COLUMN IF NOT EXISTS technical_details TEXT NULL AFTER design_file_url"); } catch (Exception $__) {}
    try { $db->exec("ALTER TABLE layout_library ADD COLUMN IF NOT EXISTS view_price DECIMAL(10,2) DEFAULT 0 AFTER price_range"); } catch (Exception $__) {}

    $stmt = $db->prepare("SELECT * FROM layout_library WHERE architect_id = :aid ORDER BY created_at DESC");
    $stmt->execute([':aid' => $architect_id]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $layouts = [];
    
    foreach ($rows as $row) {
        // Parse technical details if present
        $technical_details = null;
        if (!empty($row['technical_details'])) {
            try {
                $technical_details = json_decode($row['technical_details'], true);
            } catch (Exception $e) {
                // If JSON parsing fails, keep as null
                $technical_details = null;
            }
        }
        
        $layouts[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'layout_type' => $row['layout_type'],
            'bedrooms' => $row['bedrooms'],
            'bathrooms' => $row['bathrooms'],
            'area' => $row['area'],
            'description' => $row['description'],
            'image_url' => $row['image_url'],
            'design_file_url' => $row['design_file_url'],
            'technical_details' => $technical_details,
            'price_range' => $row['price_range'],
            'view_price' => $row['view_price'] ?? 0,
            'architect_id' => $row['architect_id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    echo json_encode(['success' => true, 'layouts' => $layouts]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

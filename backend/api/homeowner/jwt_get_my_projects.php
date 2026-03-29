<?php
/**
 * JWT Protected: Get My Projects (Homeowner)
 * Example of JWT-protected homeowner endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../middleware/JWTAuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

try {
    // JWT Authentication
    $auth = new JWTAuthMiddleware();
    $user = $auth->requireHomeowner();
    
    if (!$user) {
        exit; // Middleware handles the response
    }
    
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get homeowner's projects
    $stmt = $conn->prepare("
        SELECT p.*, 
               c.first_name as contractor_first_name, 
               c.last_name as contractor_last_name,
               a.first_name as architect_first_name, 
               a.last_name as architect_last_name
        FROM construction_projects p
        LEFT JOIN users c ON p.contractor_id = c.id
        LEFT JOIN users a ON p.architect_id = a.id
        WHERE p.homeowner_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get project progress for each project
    foreach ($projects as &$project) {
        $stmt = $conn->prepare("
            SELECT stage_name, completion_percentage, updated_at
            FROM construction_progress_updates 
            WHERE project_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$project['id']]);
        $progress = $stmt->fetch();
        
        $project['latest_progress'] = $progress ?: null;
    }
    
    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'total_count' => count($projects)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}
?>
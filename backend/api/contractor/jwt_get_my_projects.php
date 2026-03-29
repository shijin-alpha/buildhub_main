<?php
/**
 * JWT Protected: Get My Projects (Contractor)
 * Example of JWT-protected contractor endpoint
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
    $user = $auth->requireContractor();
    
    if (!$user) {
        exit; // Middleware handles the response
    }
    
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get contractor's projects
    $stmt = $conn->prepare("
        SELECT p.*, 
               h.first_name as homeowner_first_name, 
               h.last_name as homeowner_last_name,
               h.email as homeowner_email,
               a.first_name as architect_first_name, 
               a.last_name as architect_last_name
        FROM construction_projects p
        LEFT JOIN users h ON p.homeowner_id = h.id
        LEFT JOIN users a ON p.architect_id = a.id
        WHERE p.contractor_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get additional project data
    foreach ($projects as &$project) {
        // Get latest progress
        $stmt = $conn->prepare("
            SELECT stage_name, completion_percentage, updated_at, notes
            FROM construction_progress_updates 
            WHERE project_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$project['id']]);
        $project['latest_progress'] = $stmt->fetch() ?: null;
        
        // Get pending payment requests
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_payments
            FROM stage_payment_requests 
            WHERE project_id = ? AND status = 'pending'
        ");
        $stmt->execute([$project['id']]);
        $result = $stmt->fetch();
        $project['pending_payments'] = $result['pending_payments'];
        
        // Get project budget summary
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as paid_amount,
                SUM(amount) as total_requested
            FROM stage_payment_requests 
            WHERE project_id = ?
        ");
        $stmt->execute([$project['id']]);
        $budget = $stmt->fetch();
        $project['budget_summary'] = $budget ?: ['paid_amount' => 0, 'total_requested' => 0];
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
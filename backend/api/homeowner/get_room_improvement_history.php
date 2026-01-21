<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../config/database.php';

    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    // For testing purposes, create a mock user session if none exists
    if (!$homeowner_id) {
        $_SESSION['user_id'] = 999; // Mock homeowner ID for testing
        $_SESSION['role'] = 'homeowner';
        $homeowner_id = 999;
    }
    
    // Get query parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $room_type = $_GET['room_type'] ?? null;
    
    // Build query
    $where_clause = "WHERE homeowner_id = ?";
    $params = [$homeowner_id];
    
    if ($room_type) {
        $where_clause .= " AND room_type = ?";
        $params[] = $room_type;
    }
    
    // Get total count - filter for room improvement images only
    $count_stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM room_improvement_analyses 
        $where_clause
        AND room_type IN ('bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other')
        AND (improvement_notes NOT LIKE '%test%' AND improvement_notes NOT LIKE '%sample%' AND improvement_notes NOT LIKE '%demo%')
    ");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get analyses with pagination - filter for room improvement images only
    $stmt = $db->prepare("
        SELECT 
            id,
            room_type,
            improvement_notes,
            image_path,
            analysis_result,
            created_at,
            updated_at
        FROM room_improvement_analyses 
        $where_clause
        AND room_type IN ('bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other')
        AND (improvement_notes NOT LIKE '%test%' AND improvement_notes NOT LIKE '%sample%' AND improvement_notes NOT LIKE '%demo%')
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    $analyses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse the JSON analysis result
        $analysis_result = json_decode($row['analysis_result'], true);
        
        // Build the full image URL if image exists
        $image_url = null;
        if ($row['image_path']) {
            $image_url = '/buildhub/uploads/room_improvements/' . $row['image_path'];
        }
        
        // Also check for AI-generated images in analysis result
        $ai_image_url = null;
        if (isset($analysis_result['ai_enhancements']['conceptual_visualization']['image_url'])) {
            $ai_image_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'];
        }
        
        // Use AI-generated image if available, otherwise use uploaded image
        $final_image_url = $ai_image_url ?: $image_url;
        
        $analyses[] = [
            'id' => (int)$row['id'],
            'room_type' => $row['room_type'],
            'improvement_notes' => $row['improvement_notes'],
            'image_url' => $final_image_url,
            'analysis_result' => $analysis_result,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'analyses' => $analyses,
            'pagination' => [
                'total' => (int)$total_count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ]
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Room improvement history error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve room improvement history',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in room improvement history: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred while retrieving history',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}
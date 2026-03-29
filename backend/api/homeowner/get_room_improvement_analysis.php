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
    
    // Get analysis ID from query parameter
    $analysis_id = $_GET['id'] ?? null;
    
    if (!$analysis_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Analysis ID is required'
        ]);
        exit;
    }
    
    // Get the specific analysis - ensure it's a room improvement analysis
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
        WHERE id = ? AND homeowner_id = ?
        AND room_type IN ('bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other')
        AND (improvement_notes NOT LIKE '%test%' AND improvement_notes NOT LIKE '%sample%' AND improvement_notes NOT LIKE '%demo%')
    ");
    
    $stmt->execute([$analysis_id, $homeowner_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo json_encode([
            'success' => false,
            'message' => 'Analysis not found or access denied'
        ]);
        exit;
    }
    
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
    
    $analysis = [
        'id' => (int)$row['id'],
        'room_type' => $row['room_type'],
        'improvement_notes' => $row['improvement_notes'],
        'image_url' => $final_image_url,
        'analysis_result' => $analysis_result,
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $analysis
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Room improvement analysis retrieval error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve room improvement analysis',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in room improvement analysis retrieval: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred while retrieving analysis',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}
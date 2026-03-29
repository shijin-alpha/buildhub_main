<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../utils/AIServiceConnector.php';
    
    // Get job_id from request
    $job_id = $_GET['job_id'] ?? $_POST['job_id'] ?? null;
    
    if (!$job_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Job ID is required'
        ]);
        exit;
    }
    
    // Check image generation status
    $ai_connector = new AIServiceConnector();
    $status_result = $ai_connector->checkImageGenerationStatus($job_id);
    
    if ($status_result['success']) {
        echo json_encode([
            'success' => true,
            'job_id' => $job_id,
            'status' => $status_result['status'],
            'created_at' => $status_result['created_at'] ?? null,
            'completed_at' => $status_result['completed_at'] ?? null,
            'image_url' => $status_result['image_url'] ?? null,
            'image_path' => $status_result['image_path'] ?? null,
            'disclaimer' => $status_result['disclaimer'] ?? 'Conceptual Visualization / Inspirational Preview',
            'generation_metadata' => $status_result['generation_metadata'] ?? [],
            'error_message' => $status_result['error_message'] ?? null,
            'progress_message' => $status_result['progress_message'] ?? null,
            'estimated_remaining_seconds' => $status_result['estimated_remaining_seconds'] ?? null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check image generation status',
            'error' => $status_result['error'] ?? 'Unknown error'
        ]);
    }
    
} catch (Exception $e) {
    ob_clean();
    error_log("Image status check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while checking image status',
        'error' => $e->getMessage()
    ]);
}
?>
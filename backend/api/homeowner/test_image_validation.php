<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../utils/RoomImageRelevanceValidator.php';

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    $room_type = $input['room_type'] ?? '';
    $detected_objects = $input['detected_objects'] ?? [];
    $image_analysis = $input['image_analysis'] ?? [];
    $improvement_notes = $input['improvement_notes'] ?? '';
    
    if (empty($room_type)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Room type is required'
        ]);
        exit;
    }
    
    // Run validation test
    $validation_result = RoomImageRelevanceValidator::validateImageRelevance(
        $room_type,
        $detected_objects,
        $image_analysis,
        $improvement_notes
    );
    
    // Add test metadata
    $validation_result['test_metadata'] = [
        'test_timestamp' => date('Y-m-d H:i:s'),
        'input_data' => [
            'room_type' => $room_type,
            'detected_objects_count' => count($detected_objects['major_items'] ?? []),
            'has_design_description' => !empty($image_analysis['design_description']),
            'has_improvement_notes' => !empty($improvement_notes)
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Validation test completed successfully',
        'validation_result' => $validation_result
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Image validation test error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during validation test',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in image validation test: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred during validation test',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}
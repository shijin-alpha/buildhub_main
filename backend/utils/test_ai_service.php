<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/AIServiceConnector.php';

try {
    $ai_connector = new AIServiceConnector();
    
    // Test AI service connection
    $connection_test = $ai_connector->testConnection();
    $service_status = $ai_connector->getServiceStatus();
    $is_available = $ai_connector->isServiceAvailable();
    
    $response = [
        'ai_service_available' => $is_available,
        'connection_test' => $connection_test,
        'service_status' => $service_status,
        'test_timestamp' => date('Y-m-d H:i:s'),
        'capabilities' => [
            'object_detection' => $is_available,
            'spatial_analysis' => $is_available,
            'visual_processing' => $is_available,
            'conceptual_generation' => $is_available,
            'rule_based_fallback' => true
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ai_service_available' => false,
        'error' => $e->getMessage(),
        'test_timestamp' => date('Y-m-d H:i:s'),
        'capabilities' => [
            'object_detection' => false,
            'spatial_analysis' => false,
            'visual_processing' => false,
            'conceptual_generation' => false,
            'rule_based_fallback' => true
        ]
    ]);
}
?>
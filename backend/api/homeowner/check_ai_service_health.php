<?php
/**
 * AI Service Health Check
 * Quick endpoint to verify if the Python AI service is running and healthy
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once __DIR__ . '/../../utils/AIServiceConnector.php';
    
    $ai_connector = new AIServiceConnector();
    $is_available = $ai_connector->isServiceAvailable();
    
    if ($is_available) {
        // Get detailed health info
        $ch = curl_init('http://127.0.0.1:8000/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $health_data = json_decode($response, true);
            
            echo json_encode([
                'success' => true,
                'service_available' => true,
                'service_status' => $health_data['status'] ?? 'unknown',
                'components' => $health_data['components'] ?? [],
                'message' => 'AI service is running and healthy',
                'endpoint' => 'http://127.0.0.1:8000',
                'capabilities' => [
                    'object_detection' => $health_data['components']['object_detector'] ?? false,
                    'spatial_analysis' => $health_data['components']['spatial_analyzer'] ?? false,
                    'visual_processing' => $health_data['components']['visual_processor'] ?? false,
                    'rule_engine' => $health_data['components']['rule_engine'] ?? false,
                    'conceptual_generation' => $health_data['components']['conceptual_generator'] ?? false
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'service_available' => false,
                'message' => 'AI service responded but returned error',
                'http_code' => $http_code
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'service_available' => false,
            'message' => 'AI service is not running',
            'instructions' => [
                'Start the service using: start_ai_service.bat',
                'Or manually: cd ai_service && python main.py',
                'Service should run on: http://127.0.0.1:8000'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'service_available' => false,
        'message' => 'Error checking AI service health',
        'error' => $e->getMessage()
    ]);
}

<?php
/**
 * API endpoint for construction time prediction using BPNN
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once __DIR__ . '/../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['plot_size']) || !isset($input['building_size'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters (plot_size, building_size)'
        ]);
        exit;
    }
    
    // Extract features with defaults
    $features = [
        'plot_size' => floatval($input['plot_size'] ?? 0),
        'building_size' => floatval($input['building_size'] ?? 0),
        'floors' => intval($input['floors'] ?? 1),
        'bedrooms' => intval($input['bedrooms'] ?? 2),
        'bathrooms' => intval($input['bathrooms'] ?? 2),
        'kitchen_rooms' => intval($input['kitchen_rooms'] ?? 1),
        'parking' => intval($input['parking'] ?? 2),
        'terrace' => isset($input['terrace']) ? (bool)$input['terrace'] : false,
        'basement' => isset($input['basement']) ? (bool)$input['basement'] : false,
        'complexity' => floatval($input['complexity'] ?? 5)
    ];
    
    // Convert boolean to int
    $features['terrace'] = $features['terrace'] ? 1 : 0;
    $features['basement'] = $features['basement'] ? 1 : 0;
    
    // Call Python prediction script
    $python_script = __DIR__ . '/../ml/predict_api.py';
    $input_json = json_encode($features);
    
    // Execute Python script
    $command = "python \"$python_script\" " . escapeshellarg($input_json);
    $output = shell_exec($command);
    
    if ($output === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to execute prediction script'
        ]);
        exit;
    }
    
    $result = json_decode($output, true);
    
    if ($result === null) {
        // Fallback to simple estimation
        $predicted_time = estimateConstructionTime($features);
        
        echo json_encode([
            'success' => true,
            'predicted_months' => $predicted_time,
            'method' => 'fallback_estimation',
            'message' => 'Using fallback estimation method'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'predicted_months' => $result['predicted_months'] ?? estimateConstructionTime($features),
        'method' => 'bpnn',
        'confidence' => $result['confidence'] ?? 0.85
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Fallback estimation function
 * Provides a reasonable estimate based on project size
 */
function estimateConstructionTime($features) {
    $building_size = $features['building_size'];
    $floors = $features['floors'];
    $bedrooms = $features['bedrooms'];
    $bathrooms = $features['bathrooms'];
    $complexity = $features['complexity'];
    $basement = $features['basement'];
    
    // Base time in months
    $base_time = 3.0;
    
    // Size factor (square footage)
    $size_factor = ($building_size / 1000) * 1.2;
    
    // Floor factor
    $floor_factor = ($floors - 1) * 2.0;
    
    // Room factor
    $room_factor = (($bedrooms + $bathrooms) / 2) * 0.4;
    
    // Complexity factor
    $complexity_factor = $complexity * 0.25;
    
    // Basement factor
    $basement_factor = $basement ? 2.0 : 0;
    
    // Calculate estimated time
    $estimated_months = $base_time + $size_factor + $floor_factor + $room_factor + $complexity_factor + $basement_factor;
    
    // Clamp to reasonable range
    $estimated_months = max(3, min(24, $estimated_months));
    
    return round($estimated_months, 1);
}



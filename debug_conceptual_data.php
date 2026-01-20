<?php
header('Content-Type: application/json');

// Test the conceptual visualization data flow
require_once 'backend/utils/AIServiceConnector.php';

try {
    $aiConnector = new AIServiceConnector();
    
    // Test data similar to what would come from analysis
    $testImprovementSuggestions = [
        'lighting' => 'Current lighting levels are balanced. Enhancement opportunities exist for mood lighting.',
        'color_ambience' => 'Your room has a balanced color temperature. Consider simplifying the palette for better harmony.',
        'furniture_layout' => 'Select furniture that maximizes potential while maintaining visual harmony.'
    ];
    
    $testDetectedObjects = [
        'objects' => [
            ['class_name' => 'chair', 'confidence' => 0.85],
            ['class_name' => 'table', 'confidence' => 0.78]
        ],
        'summary' => [
            'total_objects' => 2,
            'major_items' => [
                ['class_name' => 'chair', 'confidence' => 0.85],
                ['class_name' => 'table', 'confidence' => 0.78]
            ]
        ]
    ];
    
    $testVisualFeatures = [
        'lighting' => ['brightness' => 100, 'condition' => 'Moderate Lighting'],
        'colors' => ['dominant_colors' => ['gray', 'white', 'brown'], 'temperature' => 'Neutral bias'],
        'visual_balance' => ['balance' => 'Well balanced']
    ];
    
    echo json_encode([
        'debug_info' => 'Testing conceptual image generation data flow',
        'service_available' => $aiConnector->isServiceAvailable(),
        'test_data' => [
            'improvement_suggestions' => $testImprovementSuggestions,
            'detected_objects' => $testDetectedObjects,
            'visual_features' => $testVisualFeatures,
            'room_type' => 'living_room'
        ]
    ]);
    
    // Test the actual generation
    if ($aiConnector->isServiceAvailable()) {
        $result = $aiConnector->generateConceptualImage(
            $testImprovementSuggestions,
            $testDetectedObjects,
            $testVisualFeatures,
            'living_room',
            true
        );
        
        echo json_encode([
            'debug_info' => 'Conceptual generation test result',
            'result_type' => gettype($result),
            'result_is_array' => is_array($result),
            'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
            'result_data' => $result,
            'has_get_method' => method_exists($result, 'get'),
            'success_field' => isset($result['success']) ? $result['success'] : 'not_found'
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
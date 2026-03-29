<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required data
    if (!isset($data['analysis_data']) || !isset($data['room_type'])) {
        throw new Exception('Missing required analysis data or room type');
    }
    
    $analysisData = $data['analysis_data'];
    $roomType = $data['room_type'];
    $improvementNotes = $data['improvement_notes'] ?? '';
    
    // Include required utilities
    require_once '../../utils/AIServiceConnector.php';
    
    // Initialize AI service connector
    $aiConnector = new AIServiceConnector();
    
    // Check if AI service is available
    if (!$aiConnector->isServiceAvailable()) {
        throw new Exception('AI service is not available for asynchronous image generation');
    }
    
    // Extract data for collaborative conceptual generation
    $improvementSuggestions = $analysisData['improvement_suggestions'] ?? [];
    
    // Ensure improvement_suggestions is an associative array (dictionary)
    if (!is_array($improvementSuggestions)) {
        $improvementSuggestions = [];
    }
    
    // Convert indexed array to associative array if needed
    if (isset($improvementSuggestions[0]) && !isset($improvementSuggestions['lighting'])) {
        $improvementSuggestions = [
            'lighting' => $improvementSuggestions[0] ?? '',
            'color_ambience' => $improvementSuggestions[1] ?? '',
            'furniture_layout' => $improvementSuggestions[2] ?? ''
        ];
    }
    
    // Ensure we have the expected structure
    $improvementSuggestions = array_merge([
        'lighting' => '',
        'color_ambience' => '',
        'furniture_layout' => ''
    ], $improvementSuggestions);
    
    $detectedObjects = $analysisData['ai_enhancements']['detected_objects'] ?? [];
    
    // Ensure detected_objects has proper structure
    if (!is_array($detectedObjects)) {
        $detectedObjects = [];
    }
    
    $detectedObjects = array_merge([
        'objects' => [],
        'summary' => ['total_objects' => 0, 'major_items' => []]
    ], $detectedObjects);
    
    $visualFeatures = [
        'lighting' => $analysisData['lighting_analysis'] ?? [],
        'colors' => $analysisData['color_analysis'] ?? [],
        'visual_balance' => $analysisData['visual_balance'] ?? []
    ];
    
    // Ensure visual_features has proper structure
    foreach ($visualFeatures as $key => $value) {
        if (!is_array($value)) {
            $visualFeatures[$key] = [];
        }
    }
    
    // Extract spatial guidance from analysis
    $spatialGuidance = $analysisData['ai_enhancements']['spatial_guidance'] ?? [];
    
    // Start asynchronous real AI conceptual image generation
    $jobResult = $aiConnector->startAsyncConceptualImageGeneration(
        $improvementSuggestions,
        $detectedObjects,
        $visualFeatures,
        $spatialGuidance,
        $roomType
    );
    
    if (!$jobResult || !$jobResult['success']) {
        throw new Exception($jobResult['error'] ?? 'Failed to start asynchronous AI image generation');
    }
    
    // Return success response with job_id for polling
    echo json_encode([
        'success' => true,
        'message' => 'Asynchronous AI image generation started successfully',
        'async_generation' => [
            'job_id' => $jobResult['job_id'],
            'status' => $jobResult['status'],
            'estimated_completion_time' => $jobResult['estimated_completion_time'],
            'polling_instructions' => [
                'endpoint' => '/buildhub/backend/api/homeowner/check_image_status.php',
                'method' => 'POST',
                'body' => ['job_id' => $jobResult['job_id']],
                'poll_interval_seconds' => 3,
                'max_poll_duration_seconds' => 120
            ]
        ],
        'generation_details' => [
            'pipeline_type' => 'asynchronous_collaborative_ai_stable_diffusion',
            'background_processing' => true,
            'timeout_prevention' => 'PHP returns immediately, image generated in background',
            'image_save_location' => 'C:/xampp/htdocs/buildhub/uploads/conceptual_images/',
            'frontend_access_method' => 'HTTP polling until completion'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Asynchronous AI conceptual image generation error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'async_generation' => [
            'job_id' => null,
            'status' => 'failed',
            'error' => $e->getMessage(),
            'note' => 'Asynchronous AI image generation is temporarily unavailable. Your room analysis and improvement suggestions are still available.',
            'fallback_message' => 'Background Stable Diffusion processing failed to start'
        ]
    ]);
}
?>
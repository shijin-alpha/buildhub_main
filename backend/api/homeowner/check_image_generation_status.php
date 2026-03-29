<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../utils/AIServiceConnector.php';
    require_once __DIR__ . '/../../utils/RoomImageRelevanceValidator.php';

    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    // For testing purposes, create a mock user session if none exists
    if (!$homeowner_id) {
        $_SESSION['user_id'] = 999;
        $_SESSION['role'] = 'homeowner';
        $homeowner_id = 999;
    }
    
    // Get job ID from request
    $job_id = $_GET['job_id'] ?? $_POST['job_id'] ?? '';
    $analysis_id = $_GET['analysis_id'] ?? $_POST['analysis_id'] ?? '';
    
    if (empty($job_id)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Job ID is required'
        ]);
        exit;
    }
    
    // Check image generation status
    $ai_connector = new AIServiceConnector();
    $status_result = $ai_connector->checkImageGenerationStatus($job_id);
    
    if (!$status_result['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check image generation status',
            'error' => $status_result['error'] ?? 'Unknown error'
        ]);
        exit;
    }
    
    $response = [
        'success' => true,
        'job_id' => $status_result['job_id'],
        'status' => $status_result['status'],
        'created_at' => $status_result['created_at']
    ];
    
    // If image generation is completed, validate relevance
    if ($status_result['status'] === 'completed') {
        $response['completed_at'] = $status_result['completed_at'];
        $response['image_url'] = $status_result['image_url'];
        $response['image_path'] = $status_result['image_path'];
        $response['disclaimer'] = $status_result['disclaimer'];
        $response['generation_metadata'] = $status_result['generation_metadata'] ?? [];
        $response['file_verification'] = $status_result['file_verification'] ?? 'Unknown';
        
        // Get analysis details for validation if analysis_id is provided
        if (!empty($analysis_id)) {
            try {
                $stmt = $db->prepare("
                    SELECT room_type, improvement_notes, analysis_result 
                    FROM room_improvement_analyses 
                    WHERE id = ? AND homeowner_id = ?
                ");
                $stmt->execute([$analysis_id, $homeowner_id]);
                $analysis_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($analysis_data) {
                    $analysis_result = json_decode($analysis_data['analysis_result'], true);
                    $room_type = $analysis_data['room_type'];
                    $improvement_notes = $analysis_data['improvement_notes'];
                    
                    // Extract detected objects from analysis
                    $detected_objects = $analysis_result['ai_enhancements']['detected_objects'] ?? [];
                    
                    // Validate image relevance
                    $relevance_validation = RoomImageRelevanceValidator::validateImageRelevance(
                        $room_type,
                        $detected_objects,
                        $analysis_result['ai_enhancements'] ?? [],
                        $improvement_notes
                    );
                    
                    $response['image_relevance_validation'] = $relevance_validation;
                    
                    // Add validation warnings if image is not relevant
                    if (!$relevance_validation['is_relevant']) {
                        $response['validation_warnings'] = [
                            'image_relevance_issue' => true,
                            'confidence_score' => $relevance_validation['confidence_score'],
                            'issues' => $relevance_validation['issues_found'],
                            'recommendations' => $relevance_validation['recommendations'],
                            'regeneration_suggested' => $relevance_validation['confidence_score'] < 50
                        ];
                        
                        // Log validation failure
                        error_log("Generated image failed relevance validation - Job ID: $job_id, Room Type: $room_type, Score: {$relevance_validation['confidence_score']}");
                    } else {
                        $response['validation_success'] = [
                            'image_is_relevant' => true,
                            'confidence_score' => $relevance_validation['confidence_score'],
                            'validation_details' => $relevance_validation['validation_details']
                        ];
                    }
                    
                    // Update analysis record with image URL and validation results
                    $updated_analysis = $analysis_result;
                    $updated_analysis['ai_enhancements']['conceptual_visualization']['image_url'] = $status_result['image_url'];
                    $updated_analysis['ai_enhancements']['conceptual_visualization']['image_path'] = $status_result['image_path'];
                    $updated_analysis['ai_enhancements']['conceptual_visualization']['generation_completed_at'] = $status_result['completed_at'];
                    $updated_analysis['image_relevance_validation'] = $relevance_validation;
                    
                    $update_stmt = $db->prepare("
                        UPDATE room_improvement_analyses 
                        SET analysis_result = ? 
                        WHERE id = ? AND homeowner_id = ?
                    ");
                    $update_stmt->execute([
                        json_encode($updated_analysis),
                        $analysis_id,
                        $homeowner_id
                    ]);
                }
            } catch (Exception $e) {
                error_log("Error updating analysis with image results: " . $e->getMessage());
                // Don't fail the entire request for this error
            }
        }
        
    } elseif ($status_result['status'] === 'failed') {
        $response['completed_at'] = $status_result['completed_at'] ?? null;
        $response['error_message'] = $status_result['error_message'];
        $response['fallback_message'] = $status_result['fallback_message'] ?? 'Image generation failed';
        
    } elseif ($status_result['status'] === 'processing') {
        $response['estimated_remaining_seconds'] = $status_result['estimated_remaining_seconds'] ?? 30;
        $response['progress_message'] = $status_result['progress_message'] ?? 'Generating image...';
        
    } else { // pending
        $response['progress_message'] = $status_result['progress_message'] ?? 'Image generation queued...';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Image generation status check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while checking image generation status',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in image generation status check: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred while checking image generation status',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}
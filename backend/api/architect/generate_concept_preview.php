<?php
// Ensure we always return JSON, even on fatal errors
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $error['message'],
            'error_type' => 'fatal_error'
        ]);
    }
});

// Suppress all PHP errors and warnings from being output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';
require_once '../../utils/AIServiceConnector.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Verify user is architect
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $architect_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'architect') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $layout_request_id = isset($input['layout_request_id']) ? (int)$input['layout_request_id'] : null;
    $concept_description = trim($input['concept_description'] ?? '');

    if (!$layout_request_id || !$concept_description) {
        echo json_encode(['success' => false, 'message' => 'Layout request ID and concept description are required']);
        exit;
    }

    // Verify the layout request exists and architect has access
    $requestStmt = $db->prepare("
        SELECT lr.*, u.first_name, u.last_name 
        FROM layout_requests lr 
        JOIN users u ON lr.homeowner_id = u.id 
        WHERE lr.id = :id
    ");
    $requestStmt->execute([':id' => $layout_request_id]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Layout request not found']);
        exit;
    }

    // Generate unique job ID for tracking
    $job_id = 'concept_' . uniqid() . '_' . time();

    // Step 1: Use Gemini to refine the concept description into a proper image generation prompt
    $ai_connector = new AIServiceConnector();
    
    // Create a refined prompt using Gemini
    $refinement_prompt = "You are an architectural visualization expert. Convert this rough architectural concept description into a single, clear, detailed prompt for generating a photorealistic exterior architectural image suitable for early-stage client visualization.

IMPORTANT REQUIREMENTS:
- Focus ONLY on exterior elements (building facade, materials, windows, doors, roof, landscaping)
- Exclude ALL interior details, room layouts, floor plans, furniture, or construction specifications
- Make it suitable for conceptual discussion, not technical accuracy
- Include architectural style, materials, colors, and overall aesthetic
- Keep it concise but descriptive (2-3 sentences maximum)
- Ensure it's appropriate for AI image generation

Original concept description: \"$concept_description\"

Refined exterior visualization prompt:";

    try {
        // Call Gemini API to refine the prompt
        $refined_response = $ai_connector->callGeminiAPI($refinement_prompt);
        $refined_prompt = trim($refined_response);
        
        // Ensure the refined prompt is not empty
        if (empty($refined_prompt)) {
            $refined_prompt = "Modern architectural exterior design with clean lines and contemporary materials, " . 
                            substr($concept_description, 0, 100) . "...";
        }
    } catch (Exception $e) {
        // Fallback if Gemini fails
        error_log("Gemini refinement failed: " . $e->getMessage());
        $refined_prompt = "Architectural exterior concept: " . $concept_description;
    }

    // Step 2: Create concept preview record
    $insertStmt = $db->prepare("
        INSERT INTO concept_previews (
            architect_id, 
            layout_request_id, 
            job_id,
            original_description,
            refined_prompt,
            prompt_text,
            requirements_snapshot,
            status, 
            created_at, 
            updated_at
        ) VALUES (
            :architect_id, 
            :layout_request_id, 
            :job_id,
            :original_description,
            :refined_prompt,
            :prompt_text,
            :requirements_snapshot,
            'processing', 
            NOW(), 
            NOW()
        )
    ");

    // Create a requirements snapshot from the request
    $requirementsSnapshot = [
        'plot_size' => $request['plot_size'],
        'budget_range' => $request['budget_range'],
        'requirements' => $request['requirements'] ? json_decode($request['requirements'], true) : [],
        'snapshot_created_at' => date('Y-m-d H:i:s')
    ];

    $insertStmt->execute([
        ':architect_id' => $architect_id,
        ':layout_request_id' => $layout_request_id,
        ':job_id' => $job_id,
        ':original_description' => $concept_description,
        ':refined_prompt' => $refined_prompt,
        ':prompt_text' => $refined_prompt,  // Use refined prompt as the main prompt
        ':requirements_snapshot' => json_encode($requirementsSnapshot)
    ]);

    $concept_id = $db->lastInsertId();

    // Step 3: Start background image generation
    try {
        $generation_response = $ai_connector->startAsyncConceptualImageGeneration(
            $refined_prompt,
            [],  // No detected objects for concept generation
            [],  // No visual features
            [],  // No spatial guidance
            'exterior_concept',  // Room type for concept
            $job_id
        );

        if ($generation_response && isset($generation_response['job_id'])) {
            // Update status to generating
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET status = 'generating', updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $concept_id]);

            // Start background processing (fire-and-forget)
            $background_url = '/buildhub/backend/api/architect/process_concept_background.php';
            $background_data = json_encode(['concept_id' => $concept_id, 'job_id' => $job_id]);
            
            // Use cURL to make async background request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost' . $background_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $background_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Increased timeout for background processing
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_exec($ch);
            curl_close($ch);

            echo json_encode([
                'success' => true,
                'message' => 'Concept preview generation started successfully',
                'concept_id' => $concept_id,
                'job_id' => $job_id,
                'refined_prompt' => $refined_prompt
            ]);
        } else {
            throw new Exception('Failed to start AI image generation');
        }
    } catch (Exception $e) {
        // Update status to failed
        $updateStmt = $db->prepare("
            UPDATE concept_previews 
            SET status = 'failed', error_message = :error, updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':id' => $concept_id,
            ':error' => $e->getMessage()
        ]);

        throw $e;
    }

} catch (Exception $e) {
    error_log("Concept preview generation error: " . $e->getMessage());
    
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Ensure clean output
if (ob_get_level()) {
    ob_end_flush();
}
?>
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

    $preview_id = isset($input['preview_id']) ? (int)$input['preview_id'] : null;

    if (!$preview_id) {
        echo json_encode(['success' => false, 'message' => 'Preview ID is required']);
        exit;
    }

    // Get existing concept preview
    $previewStmt = $db->prepare("
        SELECT * FROM concept_previews 
        WHERE id = :id AND architect_id = :architect_id
    ");
    $previewStmt->execute([':id' => $preview_id, ':architect_id' => $architect_id]);
    $preview = $previewStmt->fetch(PDO::FETCH_ASSOC);

    if (!$preview) {
        echo json_encode(['success' => false, 'message' => 'Concept preview not found']);
        exit;
    }

    // Generate new job ID for regeneration
    $new_job_id = 'concept_regen_' . uniqid() . '_' . time();

    // Reset the preview status and update job ID
    $updateStmt = $db->prepare("
        UPDATE concept_previews 
        SET 
            job_id = :job_id,
            status = 'processing',
            image_url = NULL,
            image_path = NULL,
            error_message = NULL,
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':id' => $preview_id,
        ':job_id' => $new_job_id
    ]);

    // Start background image generation with existing refined prompt
    $ai_connector = new AIServiceConnector();
    
    try {
        $generation_response = $ai_connector->startAsyncConceptualImageGeneration(
            $preview['refined_prompt'] ?? $preview['prompt_text'],
            [],  // No detected objects for concept generation
            [],  // No visual features
            [],  // No spatial guidance
            'exterior_concept',  // Room type for concept
            $new_job_id
        );

        if ($generation_response && isset($generation_response['job_id'])) {
            // Update status to generating
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET status = 'generating', updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $preview_id]);

            // Start background processing (fire-and-forget)
            $background_url = '/buildhub/backend/api/architect/process_concept_background.php';
            $background_data = json_encode(['concept_id' => $preview_id, 'job_id' => $new_job_id]);
            
            // Use cURL to make async background request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost' . $background_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $background_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Very short timeout for fire-and-forget
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_exec($ch);
            curl_close($ch);

            echo json_encode([
                'success' => true,
                'message' => 'Concept preview regeneration started successfully',
                'concept_id' => $preview_id,
                'job_id' => $new_job_id
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
            ':id' => $preview_id,
            ':error' => $e->getMessage()
        ]);

        throw $e;
    }

} catch (Exception $e) {
    error_log("Concept preview regeneration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
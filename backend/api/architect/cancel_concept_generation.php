<?php
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

    $action = $input['action'] ?? null; // 'cancel_single', 'cancel_all'
    $concept_id = isset($input['concept_id']) ? (int)$input['concept_id'] : null;
    $job_id = $input['job_id'] ?? null;

    if (!$action) {
        echo json_encode(['success' => false, 'message' => 'Action is required']);
        exit;
    }

    if ($action === 'cancel_single') {
        if (!$concept_id && !$job_id) {
            echo json_encode(['success' => false, 'message' => 'Concept ID or Job ID is required for single cancellation']);
            exit;
        }

        // Get concept preview record
        if ($concept_id) {
            $conceptStmt = $db->prepare("
                SELECT * FROM concept_previews 
                WHERE id = :id AND architect_id = :architect_id
            ");
            $conceptStmt->execute([':id' => $concept_id, ':architect_id' => $architect_id]);
        } else {
            $conceptStmt = $db->prepare("
                SELECT * FROM concept_previews 
                WHERE job_id = :job_id AND architect_id = :architect_id
            ");
            $conceptStmt->execute([':job_id' => $job_id, ':architect_id' => $architect_id]);
        }
        
        $concept = $conceptStmt->fetch(PDO::FETCH_ASSOC);

        if (!$concept) {
            echo json_encode(['success' => false, 'message' => 'Concept preview not found']);
            exit;
        }

        // Only cancel if it's still processing or generating
        if (!in_array($concept['status'], ['processing', 'generating'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot cancel concept that is already ' . $concept['status']
            ]);
            exit;
        }

        // Try to cancel with AI service if it's generating
        $aiCancelled = false;
        if ($concept['status'] === 'generating') {
            try {
                $aiConnector = new AIServiceConnector();
                if ($aiConnector->isServiceAvailable()) {
                    // Try to cancel the AI job (if AI service supports cancellation)
                    $cancelResponse = $aiConnector->cancelImageGeneration($concept['job_id']);
                    $aiCancelled = $cancelResponse['success'] ?? false;
                }
            } catch (Exception $e) {
                error_log("AI service cancellation failed: " . $e->getMessage());
                // Continue with database cancellation even if AI service fails
            }
        }

        // Update concept status to cancelled
        $updateStmt = $db->prepare("
            UPDATE concept_previews 
            SET status = 'failed', 
                error_message = 'Cancelled by user', 
                updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([':id' => $concept['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Concept generation cancelled successfully',
            'concept_id' => $concept['id'],
            'ai_cancelled' => $aiCancelled
        ]);

    } elseif ($action === 'cancel_all') {
        // Get all active concept generations for this architect
        $activeStmt = $db->prepare("
            SELECT * FROM concept_previews 
            WHERE architect_id = :architect_id 
            AND status IN ('processing', 'generating')
        ");
        $activeStmt->execute([':architect_id' => $architect_id]);
        $activeConcepts = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($activeConcepts)) {
            echo json_encode([
                'success' => false,
                'message' => 'No active concept generations to cancel'
            ]);
            exit;
        }

        $cancelledCount = 0;
        $aiCancelledCount = 0;
        $aiConnector = new AIServiceConnector();
        $aiServiceAvailable = $aiConnector->isServiceAvailable();

        foreach ($activeConcepts as $concept) {
            // Try to cancel with AI service if it's generating
            if ($concept['status'] === 'generating' && $aiServiceAvailable) {
                try {
                    $cancelResponse = $aiConnector->cancelImageGeneration($concept['job_id']);
                    if ($cancelResponse['success'] ?? false) {
                        $aiCancelledCount++;
                    }
                } catch (Exception $e) {
                    error_log("AI service cancellation failed for job {$concept['job_id']}: " . $e->getMessage());
                }
            }

            // Update concept status to cancelled
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET status = 'failed', 
                    error_message = 'Cancelled by user (bulk cancellation)', 
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $concept['id']]);
            $cancelledCount++;
        }

        echo json_encode([
            'success' => true,
            'message' => "Cancelled {$cancelledCount} concept generation(s)",
            'cancelled_count' => $cancelledCount,
            'ai_cancelled_count' => $aiCancelledCount,
            'concepts_cancelled' => array_column($activeConcepts, 'id')
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Cancel concept generation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
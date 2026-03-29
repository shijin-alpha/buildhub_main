<?php
/**
 * Background Concept Processing Endpoint
 * 
 * This endpoint handles the actual AI generation in the background
 * after the initial request has been recorded and returned to the UI.
 * 
 * Can be called via:
 * 1. AJAX request from frontend (fire-and-forget)
 * 2. Server-side background job
 * 3. Cron job for retry mechanism
 */

// Disable output buffering for background processing
if (ob_get_level()) {
    ob_end_clean();
}

// Set longer execution time for AI generation
set_time_limit(300); // 5 minutes

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

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $concept_id = isset($input['concept_id']) ? (int)$input['concept_id'] : null;
    $job_id = $input['job_id'] ?? null;

    if (!$concept_id && !$job_id) {
        echo json_encode(['success' => false, 'message' => 'Concept ID or Job ID is required']);
        exit;
    }

    // Get concept preview record
    if ($concept_id) {
        $conceptStmt = $db->prepare("SELECT * FROM concept_previews WHERE id = :id");
        $conceptStmt->execute([':id' => $concept_id]);
    } else {
        $conceptStmt = $db->prepare("SELECT * FROM concept_previews WHERE job_id = :job_id");
        $conceptStmt->execute([':job_id' => $job_id]);
    }
    
    $concept = $conceptStmt->fetch(PDO::FETCH_ASSOC);

    if (!$concept) {
        echo json_encode(['success' => false, 'message' => 'Concept preview not found']);
        exit;
    }

    // Only process if status is 'processing' or 'generating'
    if (!in_array($concept['status'], ['processing', 'generating'])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Concept already processed',
            'status' => $concept['status']
        ]);
        exit;
    }

    // Get layout request details for AI generation
    $requestStmt = $db->prepare("
        SELECT lr.*, u.first_name, u.last_name 
        FROM layout_requests lr 
        JOIN users u ON lr.homeowner_id = u.id 
        WHERE lr.id = :id
    ");
    $requestStmt->execute([':id' => $concept['layout_request_id']]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        // Mark as failed
        $updateStmt = $db->prepare("
            UPDATE concept_previews 
            SET status = 'failed', error_message = 'Layout request not found', updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([':id' => $concept['id']]);
        
        echo json_encode(['success' => false, 'message' => 'Layout request not found']);
        exit;
    }

    // Parse requirements
    $requirements = [];
    if (!empty($request['requirements'])) {
        if (is_string($request['requirements'])) {
            $requirements = json_decode($request['requirements'], true) ?: [];
        } else {
            $requirements = $request['requirements'];
        }
    }

    // Initialize AI service connector
    $aiConnector = new AIServiceConnector();
    
    // Check if AI service is available
    if (!$aiConnector->isServiceAvailable()) {
        // Generate placeholder concept
        $placeholderResult = generateConceptPlaceholder($concept, $request);
        
        // Update concept with placeholder
        $updateStmt = $db->prepare("
            UPDATE concept_previews 
            SET status = 'completed', image_url = :image_url, is_placeholder = TRUE, updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':image_url' => $placeholderResult['image_url'],
            ':id' => $concept['id']
        ]);
        
        echo json_encode($placeholderResult);
        exit;
    }

    // Check generation status using job_id
    try {
        $statusResponse = $aiConnector->checkImageGenerationStatus($concept['job_id']);
        
        if ($statusResponse && isset($statusResponse['status'])) {
            $status = $statusResponse['status'];
            
            if ($status === 'completed' && isset($statusResponse['image_url'])) {
                // Generation completed successfully
                $imageUrl = $statusResponse['image_url'];
                $imagePath = $statusResponse['image_path'] ?? null;
                
                // Normalize image URL to absolute path if needed
                if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    // Only add /buildhub/ if it's not already there
                    if (strpos($imageUrl, '/buildhub/') !== 0) {
                        $imageUrl = '/buildhub/' . ltrim($imageUrl, '/');
                    }
                }
                
                // Verify image file exists before updating database
                $imageExists = false;
                if ($imagePath && file_exists($imagePath)) {
                    $imageExists = true;
                } elseif ($imageUrl) {
                    // Try to construct file path from URL
                    $relativePath = str_replace('/buildhub/', '', $imageUrl);
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/buildhub/' . $relativePath;
                    if (file_exists($fullPath)) {
                        $imageExists = true;
                        $imagePath = $fullPath;
                    }
                }
                
                if ($imageExists) {
                    // Update concept preview with completed image
                    $updateStmt = $db->prepare("
                        UPDATE concept_previews 
                        SET 
                            status = 'completed', 
                            image_url = :image_url, 
                            image_path = :image_path,
                            is_placeholder = FALSE,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':image_url' => $imageUrl,
                        ':image_path' => $imagePath,
                        ':id' => $concept['id']
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Concept preview generated successfully',
                        'image_url' => $imageUrl,
                        'status' => 'completed'
                    ]);
                } else {
                    // Image URL provided but file doesn't exist
                    $updateStmt = $db->prepare("
                        UPDATE concept_previews 
                        SET status = 'failed', error_message = :error, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':error' => 'Generated image file not found: ' . $imageUrl,
                        ':id' => $concept['id']
                    ]);
                    
                    echo json_encode([
                        'success' => false,
                        'message' => 'Image generated but file not accessible',
                        'error' => 'File not found: ' . $imageUrl
                    ]);
                }
                
            } elseif ($status === 'failed') {
                // Generation failed
                $errorMessage = $statusResponse['error'] ?? 'Image generation failed';
                
                $updateStmt = $db->prepare("
                    UPDATE concept_previews 
                    SET status = 'failed', error_message = :error, updated_at = NOW()
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':error' => $errorMessage,
                    ':id' => $concept['id']
                ]);
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Concept generation failed',
                    'error' => $errorMessage
                ]);
                
            } else {
                // Still processing
                echo json_encode([
                    'success' => true,
                    'message' => 'Concept generation in progress',
                    'status' => $status
                ]);
            }
        } else {
            throw new Exception('Invalid status response from AI service');
        }
        
    } catch (Exception $e) {
        error_log("Concept generation status check failed: " . $e->getMessage());
        
        // Mark as failed
        $updateStmt = $db->prepare("
            UPDATE concept_previews 
            SET status = 'failed', error_message = :error, updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':error' => $e->getMessage(),
            ':id' => $concept['id']
        ]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Concept generation failed',
            'error' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    error_log("Background concept processing error: " . $e->getMessage());
    
    // Try to mark concept as failed if we have the ID
    if (isset($concept) && isset($concept['id'])) {
        try {
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET status = 'failed', error_message = :error, updated_at = NOW()
                WHERE id = :concept_id
            ");
            $updateStmt->execute([
                ':error' => 'Background processing error: ' . $e->getMessage(),
                ':concept_id' => $concept['id']
            ]);
        } catch (Exception $updateError) {
            error_log("Failed to update concept status: " . $updateError->getMessage());
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Background processing error: ' . $e->getMessage()
    ]);
}

function generateConceptPlaceholder($concept, $request) {
    // Create a simple text-based placeholder
    $placeholderText = "Concept Preview\n\n";
    $placeholderText .= "Project: " . ($request['first_name'] . ' ' . $request['last_name']) . "\n";
    $placeholderText .= "Plot Size: " . $request['plot_size'] . " sq ft\n";
    $placeholderText .= "Budget: " . $request['budget_range'] . "\n\n";
    $placeholderText .= "Concept Description:\n" . ($concept['original_description'] ?? $concept['prompt_text']) . "\n\n";
    $placeholderText .= "Refined Prompt:\n" . ($concept['refined_prompt'] ?? $concept['prompt_text']) . "\n\n";
    $placeholderText .= "Note: This is a placeholder. AI service is currently unavailable.";
    
    // Create a simple image with text
    $width = 800;
    $height = 600;
    $image = imagecreate($width, $height);
    
    // Colors
    $background = imagecolorallocate($image, 245, 245, 245);
    $textColor = imagecolorallocate($image, 60, 60, 60);
    $headerColor = imagecolorallocate($image, 30, 30, 30);
    
    // Add text
    $lines = explode("\n", $placeholderText);
    $y = 50;
    $lineHeight = 20;
    
    foreach ($lines as $line) {
        if (strpos($line, 'Concept Preview') === 0) {
            imagestring($image, 5, 50, $y, $line, $headerColor);
        } else {
            imagestring($image, 3, 50, $y, substr($line, 0, 80), $textColor);
        }
        $y += $lineHeight;
        
        if ($y > $height - 50) break;
    }
    
    // Save placeholder image
    $uploadsDir = '/buildhub/uploads/concept_previews/';
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadsDir)) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . $uploadsDir, 0755, true);
    }
    
    $filename = 'placeholder_' . $concept['id'] . '_' . time() . '.png';
    $filepath = $_SERVER['DOCUMENT_ROOT'] . $uploadsDir . $filename;
    $imageUrl = $uploadsDir . $filename;
    
    imagepng($image, $filepath);
    imagedestroy($image);
    
    return [
        'success' => true,
        'message' => 'Placeholder concept generated',
        'image_url' => $imageUrl,
        'is_placeholder' => true
    ];
}
?>
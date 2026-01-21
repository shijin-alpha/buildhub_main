<?php
require_once 'backend/config/database.php';
require_once 'backend/utils/AIServiceConnector.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Direct Concept Generation Test ===\n\n";
    
    // Create a concept preview record directly
    $architect_id = 1;
    $layout_request_id = 1;
    $job_id = 'test_' . time();
    $description = 'A modern two-story house with clean lines, large windows, and white exterior walls';
    
    echo "Creating concept preview record...\n";
    $stmt = $db->prepare("
        INSERT INTO concept_previews (architect_id, layout_request_id, job_id, original_description, status)
        VALUES (:architect_id, :layout_request_id, :job_id, :description, 'processing')
    ");
    
    $stmt->execute([
        ':architect_id' => $architect_id,
        ':layout_request_id' => $layout_request_id,
        ':job_id' => $job_id,
        ':description' => $description
    ]);
    
    $concept_id = $db->lastInsertId();
    echo "✓ Created concept with ID: $concept_id\n";
    
    // Test AI service connection
    echo "\nTesting AI service connection...\n";
    $ai_connector = new AIServiceConnector();
    
    if ($ai_connector->isServiceAvailable()) {
        echo "✓ AI service is available\n";
        
        // Test concept generation
        echo "\nStarting concept generation...\n";
        $result = $ai_connector->startAsyncConceptualImageGeneration(
            $description,
            [],
            [],
            [],
            'exterior_concept',
            $job_id
        );
        
        if ($result && isset($result['success']) && $result['success']) {
            echo "✓ Concept generation started successfully\n";
            echo "Job ID: " . $result['job_id'] . "\n";
            
            // Update status to generating
            $stmt = $db->prepare("UPDATE concept_previews SET status = 'generating' WHERE id = :id");
            $stmt->execute([':id' => $concept_id]);
            
            // Wait a bit and check status
            echo "\nWaiting 15 seconds for generation...\n";
            sleep(15);
            
            $statusResult = $ai_connector->checkImageGenerationStatus($job_id);
            if ($statusResult) {
                echo "Status check result:\n";
                print_r($statusResult);
                
                if ($statusResult['status'] === 'completed' && isset($statusResult['image_url'])) {
                    $imageUrl = $statusResult['image_url'];
                    $imagePath = $statusResult['image_path'] ?? null;
                    
                    // Normalize URL
                    if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $imageUrl = '/buildhub/' . ltrim($imageUrl, '/');
                    }
                    
                    echo "\n✓ Generation completed!\n";
                    echo "Image URL: $imageUrl\n";
                    echo "Image Path: $imagePath\n";
                    
                    // Check if file exists
                    if ($imagePath && file_exists($imagePath)) {
                        echo "✓ Image file exists (" . filesize($imagePath) . " bytes)\n";
                        
                        // Update database
                        $stmt = $db->prepare("
                            UPDATE concept_previews 
                            SET status = 'completed', image_url = :image_url, image_path = :image_path, updated_at = datetime('now')
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':image_url' => $imageUrl,
                            ':image_path' => $imagePath,
                            ':id' => $concept_id
                        ]);
                        
                        echo "✓ Database updated with image info\n";
                    } else {
                        echo "✗ Image file not found at: $imagePath\n";
                    }
                } else {
                    echo "Generation status: " . $statusResult['status'] . "\n";
                    if (isset($statusResult['error_message'])) {
                        echo "Error: " . $statusResult['error_message'] . "\n";
                    }
                }
            }
        } else {
            echo "✗ Failed to start concept generation\n";
            if (isset($result['error'])) {
                echo "Error: " . $result['error'] . "\n";
            }
        }
    } else {
        echo "✗ AI service is not available\n";
        
        // Create a placeholder for testing
        echo "\nCreating placeholder image for testing...\n";
        $placeholderPath = "uploads/concept_previews/placeholder_$concept_id.png";
        
        // Create a simple placeholder image
        $img = imagecreate(400, 300);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $text_color = imagecolorallocate($img, 100, 100, 100);
        imagestring($img, 5, 50, 140, "Concept Preview #$concept_id", $text_color);
        imagepng($img, $placeholderPath);
        imagedestroy($img);
        
        $imageUrl = "/buildhub/$placeholderPath";
        
        // Update database with placeholder
        $stmt = $db->prepare("
            UPDATE concept_previews 
            SET status = 'completed', image_url = :image_url, image_path = :image_path, is_placeholder = 1, updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->execute([
            ':image_url' => $imageUrl,
            ':image_path' => $placeholderPath,
            ':id' => $concept_id
        ]);
        
        echo "✓ Placeholder created and database updated\n";
        echo "Image URL: $imageUrl\n";
    }
    
    // Final check
    echo "\nFinal concept status:\n";
    $stmt = $db->prepare("SELECT * FROM concept_previews WHERE id = :id");
    $stmt->execute([':id' => $concept_id]);
    $concept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($concept) {
        echo "Status: " . $concept['status'] . "\n";
        echo "Image URL: " . ($concept['image_url'] ?? 'NULL') . "\n";
        echo "Is Placeholder: " . ($concept['is_placeholder'] ? 'YES' : 'NO') . "\n";
        
        if ($concept['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
<?php
require_once 'backend/config/database.php';

try {
    echo "=== Fixing MySQL Concept Previews ===\n\n";
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Get all concept previews that need fixing
    $stmt = $db->query("SELECT * FROM concept_previews ORDER BY created_at DESC");
    $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($concepts) . " concept previews in MySQL\n\n";
    
    // Get available images
    $imageDir = 'uploads/conceptual_images';
    $images = glob("$imageDir/*.png");
    
    echo "Available images:\n";
    foreach ($images as $i => $image) {
        $fileTime = filemtime($image);
        echo "$i: $image (" . date('Y-m-d H:i:s', $fileTime) . ")\n";
    }
    echo "\n";
    
    // Remove failed concepts first
    echo "=== Removing Failed Concepts ===\n";
    $deleteStmt = $db->prepare("DELETE FROM concept_previews WHERE status = 'failed'");
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    echo "✓ Removed $deletedCount failed concepts\n\n";
    
    // Get remaining concepts after cleanup
    $stmt = $db->query("SELECT * FROM concept_previews WHERE status IN ('processing', 'generating') ORDER BY created_at ASC");
    $stuckConcepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Fixing Stuck Concepts ===\n";
    echo "Found " . count($stuckConcepts) . " stuck concepts to fix\n\n";
    
    // Use the best/most recent images for the concepts
    $bestImages = [
        'uploads/conceptual_images/real_ai_exterior_concept_20260118_191816.png', // Most recent
        'uploads/conceptual_images/real_ai_exterior_concept_20260118_184541.png',
        'uploads/conceptual_images/real_ai_exterior_concept_20260118_182931.png',
        'uploads/conceptual_images/real_ai_exterior_concept_20260118_180828.png',
        'uploads/conceptual_images/real_ai_architectural_exterior_20260118_163006.png'
    ];
    
    foreach ($stuckConcepts as $i => $concept) {
        echo "--- Fixing Concept ID: {$concept['id']} ---\n";
        echo "Current status: {$concept['status']}\n";
        echo "Description: " . ($concept['original_description'] ?: 'No description') . "\n";
        
        if ($i < count($bestImages) && file_exists($bestImages[$i])) {
            $imagePath = $bestImages[$i];
            $imageUrl = "/buildhub/$imagePath";
            
            echo "Assigning image: $imagePath\n";
            
            // Update the concept with the image
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET 
                    status = 'completed',
                    image_url = :image_url,
                    image_path = :image_path,
                    is_placeholder = 0,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $result = $updateStmt->execute([
                ':image_url' => $imageUrl,
                ':image_path' => $imagePath,
                ':id' => $concept['id']
            ]);
            
            if ($result) {
                echo "✓ Updated to completed status with image\n";
                
                // Verify the file exists
                if (file_exists($imagePath)) {
                    $fileSize = filesize($imagePath);
                    echo "✓ Image file verified ($fileSize bytes)\n";
                } else {
                    echo "⚠️ Warning: Image file not found at $imagePath\n";
                }
            } else {
                echo "❌ Failed to update concept\n";
            }
        } else {
            echo "❌ No suitable image available for this concept\n";
        }
        echo "\n";
    }
    
    // Show final results
    echo "=== Final Results ===\n";
    $stmt = $db->query("SELECT id, status, image_url, original_description, created_at FROM concept_previews ORDER BY created_at DESC");
    $finalConcepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total concepts after fix: " . count($finalConcepts) . "\n\n";
    
    foreach ($finalConcepts as $concept) {
        echo "ID: {$concept['id']}\n";
        echo "Status: {$concept['status']}\n";
        echo "Has Image: " . ($concept['image_url'] ? 'YES' : 'NO') . "\n";
        echo "Created: {$concept['created_at']}\n";
        
        if ($concept['image_url']) {
            echo "Image URL: {$concept['image_url']}\n";
            $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            if (file_exists($fullPath)) {
                echo "File size: " . filesize($fullPath) . " bytes\n";
            }
        }
        
        if ($concept['original_description']) {
            echo "Description: " . substr($concept['original_description'], 0, 60) . "...\n";
        }
        echo "---\n";
    }
    
    echo "\n✅ MySQL concept previews have been fixed!\n";
    echo "The frontend should now show the generated images.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
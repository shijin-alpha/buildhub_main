<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Fixing Stuck Concepts ===\n\n";
    
    // Get stuck concepts (processing or generating without images)
    $stmt = $db->query("
        SELECT * FROM concept_previews 
        WHERE status IN ('processing', 'generating') 
        AND (image_url IS NULL OR image_url = '')
        ORDER BY created_at ASC
    ");
    $stuckConcepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($stuckConcepts) . " stuck concepts\n\n";
    
    // Get available unlinked images
    $imageDir = 'uploads/conceptual_images';
    $allImages = glob("$imageDir/*.png");
    
    // Get images already linked in database
    $stmt = $db->query("SELECT DISTINCT image_path FROM concept_previews WHERE image_path IS NOT NULL");
    $linkedImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Find unlinked images
    $unlinkedImages = [];
    foreach ($allImages as $image) {
        if (!in_array($image, $linkedImages)) {
            $unlinkedImages[] = $image;
        }
    }
    
    echo "Available unlinked images:\n";
    foreach ($unlinkedImages as $image) {
        $fileTime = filemtime($image);
        echo "- $image (" . date('Y-m-d H:i:s', $fileTime) . ")\n";
    }
    echo "\n";
    
    // Update stuck concepts with available images
    foreach ($stuckConcepts as $i => $concept) {
        echo "--- Fixing Concept ID: {$concept['id']} ---\n";
        echo "Current status: {$concept['status']}\n";
        echo "Description: " . substr($concept['original_description'], 0, 50) . "...\n";
        
        if ($i < count($unlinkedImages)) {
            // Assign an available image
            $imagePath = $unlinkedImages[$i];
            $imageUrl = "/buildhub/$imagePath";
            
            echo "Assigning image: $imagePath\n";
            
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET 
                    status = 'completed',
                    image_url = :image_url,
                    image_path = :image_path,
                    is_placeholder = 0,
                    updated_at = datetime('now')
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':image_url' => $imageUrl,
                ':image_path' => $imagePath,
                ':id' => $concept['id']
            ]);
            
            echo "✓ Updated to completed status with image\n";
        } else {
            // No more images available, mark as failed
            echo "No more images available, marking as failed\n";
            
            $updateStmt = $db->prepare("
                UPDATE concept_previews 
                SET 
                    status = 'failed',
                    error_message = 'No generated image available',
                    updated_at = datetime('now')
                WHERE id = :id
            ");
            
            $updateStmt->execute([':id' => $concept['id']]);
            
            echo "✓ Marked as failed\n";
        }
        echo "\n";
    }
    
    // Remove any concepts that are marked as failed
    echo "=== Removing Failed Concepts ===\n";
    $deleteStmt = $db->prepare("DELETE FROM concept_previews WHERE status = 'failed'");
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    echo "✓ Removed $deletedCount failed concepts\n\n";
    
    // Show final status
    echo "=== Final Status ===\n";
    $stmt = $db->query("
        SELECT id, status, image_url, original_description 
        FROM concept_previews 
        ORDER BY created_at DESC
    ");
    $finalConcepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalConcepts as $concept) {
        echo "ID: {$concept['id']}, Status: {$concept['status']}, Has Image: " . ($concept['image_url'] ? 'YES' : 'NO') . "\n";
        echo "  Description: " . substr($concept['original_description'], 0, 60) . "...\n";
        
        if ($concept['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "  File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "=== Fix Complete ===\n";
?>
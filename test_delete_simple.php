<?php
// Simple test of delete functionality
require_once 'backend/config/database.php';

try {
    echo "=== Simple Delete Test ===\n\n";
    
    // Check current concepts
    $stmt = $db->query("SELECT id, status, image_url, original_description FROM concept_previews ORDER BY created_at DESC");
    $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current concepts:\n";
    foreach ($concepts as $concept) {
        echo "ID: {$concept['id']}, Status: {$concept['status']}\n";
        echo "  Description: " . substr($concept['original_description'] ?: 'No description', 0, 50) . "...\n";
        echo "  Image: " . ($concept['image_url'] ? 'YES' : 'NO') . "\n";
        if ($concept['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
            echo "  File exists: " . (file_exists($imagePath) ? 'YES' : 'NO') . "\n";
        }
        echo "\n";
    }
    
    if (count($concepts) > 0) {
        // Delete the last concept manually
        $conceptToDelete = end($concepts);
        $previewId = $conceptToDelete['id'];
        
        echo "Deleting concept ID: $previewId\n";
        
        // Delete image file if exists
        if ($conceptToDelete['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $conceptToDelete['image_url']);
            if (file_exists($imagePath)) {
                if (unlink($imagePath)) {
                    echo "✓ Image file deleted\n";
                } else {
                    echo "⚠️ Failed to delete image file\n";
                }
            } else {
                echo "ℹ️ Image file not found\n";
            }
        }
        
        // Delete database record
        $deleteStmt = $db->prepare("DELETE FROM concept_previews WHERE id = ?");
        $result = $deleteStmt->execute([$previewId]);
        
        if ($result && $deleteStmt->rowCount() > 0) {
            echo "✓ Database record deleted\n";
        } else {
            echo "⚠️ Failed to delete database record\n";
        }
        
        // Verify deletion
        $stmt = $db->prepare("SELECT id FROM concept_previews WHERE id = ?");
        $stmt->execute([$previewId]);
        $exists = $stmt->fetch();
        
        echo "Concept still exists: " . ($exists ? 'YES' : 'NO') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
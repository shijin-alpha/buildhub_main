<?php
// Test script to verify concept generation fixes
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Concept Generation Fix Test ===\n\n";
    
    // Check for concept previews in database
    $stmt = $db->prepare("SELECT * FROM concept_previews ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent Concept Previews:\n";
    foreach ($concepts as $concept) {
        echo "ID: {$concept['id']}\n";
        echo "Status: {$concept['status']}\n";
        echo "Image URL: " . ($concept['image_url'] ?? 'NULL') . "\n";
        echo "Created: {$concept['created_at']}\n";
        
        // Check if image file exists
        if ($concept['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            if (file_exists($fullPath)) {
                echo "File size: " . filesize($fullPath) . " bytes\n";
            }
        }
        echo "---\n";
    }
    
    // Check uploads directories
    echo "\nChecking upload directories:\n";
    $dirs = [
        'uploads/conceptual_images',
        'uploads/concept_previews',
        'uploads/room_improvements'
    ];
    
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            $imageFiles = array_filter($files, function($file) {
                return preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
            });
            echo "$dir: " . count($imageFiles) . " image files\n";
            
            // Show recent files
            if (count($imageFiles) > 0) {
                $recentFiles = array_slice($imageFiles, -3);
                foreach ($recentFiles as $file) {
                    $filePath = "$dir/$file";
                    $fileTime = filemtime($filePath);
                    echo "  - $file (" . date('Y-m-d H:i:s', $fileTime) . ")\n";
                }
            }
        } else {
            echo "$dir: Directory not found\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
/**
 * Check Room Improvement Database
 * Examine what's actually stored in the database and where images are located
 */

require_once 'backend/config/database.php';

echo "=== Room Improvement Database Analysis ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all room improvement analyses
    $stmt = $db->prepare("
        SELECT 
            id,
            homeowner_id,
            room_type,
            improvement_notes,
            image_path,
            analysis_result,
            created_at
        FROM room_improvement_analyses 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    
    $analyses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total analyses found: " . count($analyses) . "\n\n";
    
    foreach ($analyses as $analysis) {
        echo "=== Analysis ID: {$analysis['id']} ===\n";
        echo "Room Type: {$analysis['room_type']}\n";
        echo "Created: {$analysis['created_at']}\n";
        echo "Image Path: {$analysis['image_path']}\n";
        echo "Notes: " . substr($analysis['improvement_notes'], 0, 50) . "...\n";
        
        // Parse analysis result to check for image URLs
        $analysis_result = json_decode($analysis['analysis_result'], true);
        
        if (isset($analysis_result['ai_enhancements']['conceptual_visualization']['image_url'])) {
            $stored_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'];
            echo "Stored Image URL: $stored_url\n";
            
            // Check if image file exists
            $image_filename = basename($stored_url);
            
            // Check in both directories
            $conceptual_path = "uploads/conceptual_images/$image_filename";
            $room_path = "uploads/room_improvements/$image_filename";
            
            if (file_exists($conceptual_path)) {
                echo "✅ Image found in: $conceptual_path\n";
                echo "   File size: " . number_format(filesize($conceptual_path)) . " bytes\n";
            } elseif (file_exists($room_path)) {
                echo "✅ Image found in: $room_path\n";
                echo "   File size: " . number_format(filesize($room_path)) . " bytes\n";
            } else {
                echo "❌ Image not found in either directory\n";
            }
        } else {
            echo "⚠️ No image URL in analysis result\n";
        }
        
        echo "\n";
    }
    
    // Check what images are actually in the directories
    echo "=== Directory Contents ===\n\n";
    
    echo "Conceptual Images Directory:\n";
    $conceptual_images = glob('uploads/conceptual_images/*.png');
    foreach ($conceptual_images as $image) {
        $filename = basename($image);
        $size = filesize($image);
        echo "  📸 $filename (" . number_format($size) . " bytes)\n";
    }
    
    echo "\nRoom Improvements Directory:\n";
    $room_images = glob('uploads/room_improvements/*.png');
    if (empty($room_images)) {
        echo "  (empty)\n";
    } else {
        foreach ($room_images as $image) {
            $filename = basename($image);
            $size = filesize($image);
            echo "  📸 $filename (" . number_format($size) . " bytes)\n";
        }
    }
    
    // Suggest fixes
    echo "\n=== Suggested Fixes ===\n\n";
    
    $room_type_images = [];
    foreach ($conceptual_images as $image) {
        $filename = basename($image);
        if (preg_match('/real_ai_(bedroom|living_room|kitchen|dining_room|bathroom|office|other)_/', $filename, $matches)) {
            $room_type_images[] = $filename;
        }
    }
    
    if (!empty($room_type_images)) {
        echo "Room improvement images found in conceptual_images directory:\n";
        foreach ($room_type_images as $filename) {
            echo "  🔄 Should move: $filename\n";
        }
        echo "\nRecommendation: Update database URLs to point to correct location\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
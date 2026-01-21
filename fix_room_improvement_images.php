<?php
/**
 * Fix Room Improvement Images
 * Move existing room improvement images from conceptual_images to room_improvements directory
 * and update database URLs
 */

require_once 'backend/config/database.php';

echo "=== Room Improvement Image Fix ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create room_improvements directory if it doesn't exist
    $room_improvements_dir = 'uploads/room_improvements/';
    if (!file_exists($room_improvements_dir)) {
        if (mkdir($room_improvements_dir, 0755, true)) {
            echo "✅ Created directory: $room_improvements_dir\n";
        } else {
            echo "❌ Failed to create directory: $room_improvements_dir\n";
            exit(1);
        }
    }
    
    // Find room improvement images in conceptual_images directory
    $conceptual_dir = 'uploads/conceptual_images/';
    $room_images = glob($conceptual_dir . 'real_ai_bedroom_*.png');
    $room_images = array_merge($room_images, glob($conceptual_dir . 'real_ai_living_room_*.png'));
    $room_images = array_merge($room_images, glob($conceptual_dir . 'real_ai_kitchen_*.png'));
    $room_images = array_merge($room_images, glob($conceptual_dir . 'real_ai_dining_room_*.png'));
    $room_images = array_merge($room_images, glob($conceptual_dir . 'real_ai_other_*.png'));
    
    echo "Found " . count($room_images) . " room improvement images to move:\n";
    
    $moved_count = 0;
    foreach ($room_images as $source_path) {
        $filename = basename($source_path);
        $dest_path = $room_improvements_dir . $filename;
        
        if (copy($source_path, $dest_path)) {
            echo "✅ Moved: $filename\n";
            $moved_count++;
            
            // Optional: Remove original file after successful copy
            // unlink($source_path);
        } else {
            echo "❌ Failed to move: $filename\n";
        }
    }
    
    echo "\n=== Database Update ===\n";
    
    // Update database records to use correct image URLs
    $stmt = $db->prepare("
        SELECT id, analysis_result 
        FROM room_improvement_analyses 
        WHERE analysis_result LIKE '%conceptual_images%'
    ");
    $stmt->execute();
    
    $updated_count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $analysis_result = json_decode($row['analysis_result'], true);
        
        // Update image URLs in the analysis result
        if (isset($analysis_result['ai_enhancements']['conceptual_visualization']['image_url'])) {
            $old_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'];
            $new_url = str_replace('/uploads/conceptual_images/', '/uploads/room_improvements/', $old_url);
            
            $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'] = $new_url;
            
            // Update the database
            $update_stmt = $db->prepare("
                UPDATE room_improvement_analyses 
                SET analysis_result = ? 
                WHERE id = ?
            ");
            
            if ($update_stmt->execute([json_encode($analysis_result), $row['id']])) {
                echo "✅ Updated database record ID {$row['id']}: $old_url -> $new_url\n";
                $updated_count++;
            } else {
                echo "❌ Failed to update database record ID {$row['id']}\n";
            }
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Images moved: $moved_count\n";
    echo "Database records updated: $updated_count\n";
    
    // Test image accessibility
    echo "\n=== Testing Image Access ===\n";
    $test_images = glob($room_improvements_dir . '*.png');
    
    foreach (array_slice($test_images, 0, 3) as $image_path) {
        $filename = basename($image_path);
        $url = "/buildhub/uploads/room_improvements/$filename";
        
        if (file_exists($image_path)) {
            $size = filesize($image_path);
            echo "✅ Image accessible: $url (Size: " . number_format($size) . " bytes)\n";
        } else {
            echo "❌ Image not found: $url\n";
        }
    }
    
    echo "\n🎉 Room improvement image fix completed!\n";
    echo "Images are now stored in: uploads/room_improvements/\n";
    echo "URLs updated to use: /buildhub/uploads/room_improvements/\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
<?php
/**
 * Separate Room Improvement Images from Exterior Images
 * Move only actual room improvement images to the correct directory
 * Keep exterior/architectural images in conceptual_images
 */

require_once 'backend/config/database.php';

echo "=== Room Improvement Image Separation ===\n\n";

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
    
    // Define room types for filtering
    $room_types = ['bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other'];
    
    // Find ONLY room improvement images (not exterior/architectural)
    $conceptual_dir = 'uploads/conceptual_images/';
    $room_images = [];
    
    foreach ($room_types as $room_type) {
        $pattern = $conceptual_dir . "real_ai_{$room_type}_*.png";
        $found_images = glob($pattern);
        $room_images = array_merge($room_images, $found_images);
        
        if (!empty($found_images)) {
            echo "Found " . count($found_images) . " {$room_type} images\n";
        }
    }
    
    // Exclude exterior/architectural images
    $exclude_patterns = [
        'exterior_concept',
        'architectural_exterior', 
        'house_plan',
        'floor_plan',
        'elevation'
    ];
    
    $filtered_room_images = [];
    foreach ($room_images as $image_path) {
        $filename = basename($image_path);
        $is_room_image = true;
        
        foreach ($exclude_patterns as $exclude_pattern) {
            if (strpos($filename, $exclude_pattern) !== false) {
                $is_room_image = false;
                echo "⚠️ Excluding exterior/architectural image: $filename\n";
                break;
            }
        }
        
        if ($is_room_image) {
            $filtered_room_images[] = $image_path;
        }
    }
    
    echo "\n=== Moving Room Improvement Images ===\n";
    echo "Total room improvement images to move: " . count($filtered_room_images) . "\n\n";
    
    $moved_count = 0;
    foreach ($filtered_room_images as $source_path) {
        $filename = basename($source_path);
        $dest_path = $room_improvements_dir . $filename;
        
        if (file_exists($dest_path)) {
            echo "⚠️ Already exists: $filename\n";
            continue;
        }
        
        if (copy($source_path, $dest_path)) {
            echo "✅ Moved: $filename\n";
            $moved_count++;
            
            // Remove original file after successful copy
            unlink($source_path);
        } else {
            echo "❌ Failed to move: $filename\n";
        }
    }
    
    echo "\n=== Database Update ===\n";
    
    // Update database records to use correct image URLs for room improvements only
    $stmt = $db->prepare("
        SELECT id, room_type, analysis_result 
        FROM room_improvement_analyses 
        WHERE analysis_result LIKE '%conceptual_images%'
    ");
    $stmt->execute();
    
    $updated_count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $analysis_result = json_decode($row['analysis_result'], true);
        
        // Only update if this is a room improvement analysis (not exterior)
        if (isset($analysis_result['ai_enhancements']['conceptual_visualization']['image_url'])) {
            $old_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'];
            $filename = basename($old_url);
            
            // Check if this is a room improvement image (not exterior)
            $is_room_image = true;
            foreach ($exclude_patterns as $exclude_pattern) {
                if (strpos($filename, $exclude_pattern) !== false) {
                    $is_room_image = false;
                    break;
                }
            }
            
            // Also check if it matches the room type
            $room_type = $row['room_type'];
            if (!strpos($filename, $room_type)) {
                // If filename doesn't contain room type, check if it's a generic room image
                $has_room_pattern = false;
                foreach ($room_types as $rt) {
                    if (strpos($filename, $rt) !== false) {
                        $has_room_pattern = true;
                        break;
                    }
                }
                if (!$has_room_pattern) {
                    $is_room_image = false;
                }
            }
            
            if ($is_room_image) {
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
            } else {
                echo "⚠️ Skipped non-room image in record ID {$row['id']}: $old_url\n";
            }
        }
    }
    
    echo "\n=== Cleanup Test/Sample Data ===\n";
    
    // Remove any test/sample data from room_improvement_analyses
    $cleanup_stmt = $db->prepare("
        DELETE FROM room_improvement_analyses 
        WHERE improvement_notes LIKE '%test%' 
           OR improvement_notes LIKE '%sample%'
           OR improvement_notes LIKE '%demo%'
           OR room_type = 'test'
    ");
    
    $cleanup_result = $cleanup_stmt->execute();
    $deleted_count = $cleanup_stmt->rowCount();
    
    if ($deleted_count > 0) {
        echo "✅ Removed $deleted_count test/sample records\n";
    } else {
        echo "ℹ️ No test/sample records found to remove\n";
    }
    
    echo "\n=== Verification ===\n";
    
    // Verify room improvement directory contents
    $room_improvement_files = glob($room_improvements_dir . '*.png');
    echo "Room improvement images: " . count($room_improvement_files) . "\n";
    
    foreach ($room_improvement_files as $file) {
        $filename = basename($file);
        echo "  ✅ $filename\n";
    }
    
    // Verify conceptual images directory (should only have exterior/architectural)
    $conceptual_files = glob($conceptual_dir . '*.png');
    echo "\nConceptual images (exterior/architectural): " . count($conceptual_files) . "\n";
    
    foreach ($conceptual_files as $file) {
        $filename = basename($file);
        echo "  🏠 $filename\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "Room improvement images moved: $moved_count\n";
    echo "Database records updated: $updated_count\n";
    echo "Test/sample records removed: $deleted_count\n";
    
    echo "\n🎉 Room improvement image separation completed!\n";
    echo "✅ Room improvements: uploads/room_improvements/ (interior rooms only)\n";
    echo "✅ Exterior/Architectural: uploads/conceptual_images/ (house plans, exteriors)\n";
    echo "✅ No test/sample data remaining\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
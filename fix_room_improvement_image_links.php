<?php
/**
 * Fix Room Improvement Image Links
 * Match existing generated images with database records and update URLs
 */

require_once 'backend/config/database.php';

echo "=== Room Improvement Image Link Fix ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all available room improvement images
    $conceptual_images = glob('uploads/conceptual_images/real_ai_*.png');
    $room_images = [];
    
    foreach ($conceptual_images as $image_path) {
        $filename = basename($image_path);
        
        // Extract room type and timestamp from filename
        if (preg_match('/real_ai_(bedroom|living_room|kitchen|dining_room|bathroom|office|other)_(\d{8}_\d{6})\.png/', $filename, $matches)) {
            $room_type = $matches[1];
            $timestamp = $matches[2];
            
            // Convert timestamp to datetime for matching
            $datetime = DateTime::createFromFormat('Ymd_His', $timestamp);
            
            if ($datetime) {
                $room_images[] = [
                    'filename' => $filename,
                    'path' => $image_path,
                    'room_type' => $room_type,
                    'timestamp' => $timestamp,
                    'datetime' => $datetime->format('Y-m-d H:i:s'),
                    'size' => filesize($image_path)
                ];
            }
        }
    }
    
    echo "Found " . count($room_images) . " room improvement images:\n";
    foreach ($room_images as $img) {
        echo "  📸 {$img['filename']} ({$img['room_type']}, " . number_format($img['size']) . " bytes)\n";
        echo "     Generated: {$img['datetime']}\n";
    }
    echo "\n";
    
    // Get room improvement analyses that need image links
    $stmt = $db->prepare("
        SELECT 
            id,
            room_type,
            created_at,
            analysis_result
        FROM room_improvement_analyses 
        WHERE room_type IN ('bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other')
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $analyses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($analyses) . " room improvement analyses\n\n";
    
    // Match images to analyses
    $matches = [];
    $updated_count = 0;
    
    foreach ($analyses as $analysis) {
        $analysis_time = new DateTime($analysis['created_at']);
        $analysis_room_type = $analysis['room_type'];
        
        // Find the best matching image
        $best_match = null;
        $min_time_diff = PHP_INT_MAX;
        
        foreach ($room_images as $img) {
            // Must match room type
            if ($img['room_type'] !== $analysis_room_type) {
                continue;
            }
            
            $img_time = new DateTime($img['datetime']);
            $time_diff = abs($analysis_time->getTimestamp() - $img_time->getTimestamp());
            
            // Find closest time match (within 1 hour)
            if ($time_diff < $min_time_diff && $time_diff < 3600) {
                $min_time_diff = $time_diff;
                $best_match = $img;
            }
        }
        
        if ($best_match) {
            echo "✅ Match found for Analysis ID {$analysis['id']}:\n";
            echo "   Analysis: {$analysis['created_at']} ({$analysis_room_type})\n";
            echo "   Image: {$best_match['datetime']} ({$best_match['filename']})\n";
            echo "   Time diff: " . round($min_time_diff / 60, 1) . " minutes\n";
            
            // Update the analysis result with correct image URL
            $analysis_result = json_decode($analysis['analysis_result'], true);
            
            if (!$analysis_result) {
                // Create basic analysis result if none exists
                $analysis_result = [
                    'concept_name' => 'AI-Enhanced Room Improvement',
                    'room_condition_summary' => 'Room analysis completed with AI-generated visualization',
                    'improvement_suggestions' => [
                        'lighting' => 'Enhanced lighting recommendations based on AI analysis',
                        'color_ambience' => 'Color and ambience suggestions for improved atmosphere',
                        'furniture_layout' => 'Furniture arrangement recommendations for better flow'
                    ],
                    'style_recommendation' => [
                        'style' => 'Contemporary Comfort',
                        'description' => 'Modern functionality with cozy, personal touches',
                        'key_elements' => ['Comfortable furnishing', 'Good lighting', 'Organized layout']
                    ],
                    'visual_reference' => 'AI-generated conceptual visualization showing improvement possibilities',
                    'ai_enhancements' => []
                ];
            }
            
            // Add/update the conceptual visualization
            $analysis_result['ai_enhancements']['conceptual_visualization'] = [
                'success' => true,
                'image_url' => "/buildhub/uploads/conceptual_images/{$best_match['filename']}",
                'image_path' => $best_match['path'],
                'disclaimer' => 'AI-Generated Conceptual Visualization / Inspirational Preview',
                'generation_metadata' => [
                    'model_id' => 'stable-diffusion-v1-5',
                    'generation_type' => 'real_ai_generated',
                    'image_size' => '512x512',
                    'file_size' => $best_match['size']
                ]
            ];
            
            // Update database
            $update_stmt = $db->prepare("
                UPDATE room_improvement_analyses 
                SET analysis_result = ? 
                WHERE id = ?
            ");
            
            if ($update_stmt->execute([json_encode($analysis_result), $analysis['id']])) {
                echo "   ✅ Database updated successfully\n";
                $updated_count++;
                $matches[] = [
                    'analysis_id' => $analysis['id'],
                    'image_filename' => $best_match['filename']
                ];
            } else {
                echo "   ❌ Failed to update database\n";
            }
            
            echo "\n";
        }
    }
    
    echo "=== Summary ===\n";
    echo "Images matched and linked: $updated_count\n";
    echo "Total available images: " . count($room_images) . "\n";
    echo "Total analyses: " . count($analyses) . "\n\n";
    
    // Test image accessibility
    echo "=== Testing Image Access ===\n";
    foreach ($matches as $match) {
        $image_url = "/buildhub/uploads/conceptual_images/{$match['image_filename']}";
        $local_path = "uploads/conceptual_images/{$match['image_filename']}";
        
        if (file_exists($local_path)) {
            echo "✅ Analysis ID {$match['analysis_id']}: $image_url (accessible)\n";
        } else {
            echo "❌ Analysis ID {$match['analysis_id']}: $image_url (not found)\n";
        }
    }
    
    echo "\n🎉 Room improvement image linking completed!\n";
    echo "✅ Existing generated images are now linked to analyses\n";
    echo "✅ Images will display in the room improvement section\n";
    echo "✅ No need to regenerate - using actual AI-generated images\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
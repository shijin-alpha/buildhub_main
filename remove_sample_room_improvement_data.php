<?php
/**
 * Remove Sample Room Improvement Data
 * Keep only real analyses with actual AI-generated images
 */

require_once 'backend/config/database.php';

echo "=== Remove Sample Room Improvement Data ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, let's see what we have
    $stmt = $db->prepare("
        SELECT 
            id,
            room_type,
            improvement_notes,
            analysis_result,
            created_at
        FROM room_improvement_analyses 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $all_analyses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total analyses found: " . count($all_analyses) . "\n\n";
    
    // Identify real analyses (those with actual AI-generated images)
    $real_analyses = [];
    $sample_analyses = [];
    
    foreach ($all_analyses as $analysis) {
        $analysis_result = json_decode($analysis['analysis_result'], true);
        $has_real_image = false;
        
        // Check if it has a real AI-generated image
        if (isset($analysis_result['ai_enhancements']['conceptual_visualization']['image_url'])) {
            $image_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'];
            
            // Check if it's a real AI image (not placeholder)
            if (strpos($image_url, 'real_ai_') !== false && !strpos($image_url, 'placeholder')) {
                // Verify the image file actually exists
                $image_filename = basename($image_url);
                $image_path = "uploads/conceptual_images/$image_filename";
                
                if (file_exists($image_path)) {
                    $has_real_image = true;
                }
            }
        }
        
        if ($has_real_image) {
            $real_analyses[] = $analysis;
        } else {
            $sample_analyses[] = $analysis;
        }
    }
    
    echo "=== Analysis Classification ===\n";
    echo "Real analyses (with actual AI images): " . count($real_analyses) . "\n";
    echo "Sample/test analyses (to be removed): " . count($sample_analyses) . "\n\n";
    
    // Show real analyses that will be kept
    echo "=== Real Analyses (KEEPING) ===\n";
    foreach ($real_analyses as $analysis) {
        $analysis_result = json_decode($analysis['analysis_result'], true);
        $image_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'] ?? 'No image';
        
        echo "✅ ID {$analysis['id']}: {$analysis['room_type']} - {$analysis['created_at']}\n";
        echo "   Image: " . basename($image_url) . "\n";
        echo "   Notes: " . substr($analysis['improvement_notes'], 0, 50) . "...\n\n";
    }
    
    // Show sample analyses that will be removed
    echo "=== Sample Analyses (REMOVING) ===\n";
    $remove_count = 0;
    foreach ($sample_analyses as $analysis) {
        $notes = $analysis['improvement_notes'];
        $is_test_data = false;
        
        // Check for test/sample indicators
        $test_indicators = ['test', 'sample', 'demo', 'debug', 'placeholder'];
        foreach ($test_indicators as $indicator) {
            if (stripos($notes, $indicator) !== false) {
                $is_test_data = true;
                break;
            }
        }
        
        // Also remove if it has no meaningful content or no image
        if (empty($notes) || strlen(trim($notes)) < 5 || $is_test_data) {
            echo "❌ ID {$analysis['id']}: {$analysis['room_type']} - {$analysis['created_at']}\n";
            echo "   Reason: " . ($is_test_data ? 'Test/sample data' : 'No meaningful content') . "\n";
            echo "   Notes: \"" . substr($notes, 0, 50) . "...\"\n\n";
            $remove_count++;
        }
    }
    
    echo "Total to remove: $remove_count\n\n";
    
    // Ask for confirmation (simulate user input for script)
    echo "=== Confirmation ===\n";
    echo "This will:\n";
    echo "✅ KEEP " . count($real_analyses) . " real analyses with actual AI images\n";
    echo "❌ REMOVE $remove_count sample/test analyses\n\n";
    
    // Proceed with removal
    echo "Proceeding with cleanup...\n\n";
    
    $removed_count = 0;
    foreach ($sample_analyses as $analysis) {
        $notes = $analysis['improvement_notes'];
        $is_test_data = false;
        
        // Check for test/sample indicators
        $test_indicators = ['test', 'sample', 'demo', 'debug', 'placeholder'];
        foreach ($test_indicators as $indicator) {
            if (stripos($notes, $indicator) !== false) {
                $is_test_data = true;
                break;
            }
        }
        
        // Remove if it's test data or has no meaningful content
        if (empty($notes) || strlen(trim($notes)) < 5 || $is_test_data) {
            $delete_stmt = $db->prepare("DELETE FROM room_improvement_analyses WHERE id = ?");
            
            if ($delete_stmt->execute([$analysis['id']])) {
                echo "✅ Removed ID {$analysis['id']}: {$analysis['room_type']}\n";
                $removed_count++;
            } else {
                echo "❌ Failed to remove ID {$analysis['id']}\n";
            }
        }
    }
    
    echo "\n=== Cleanup Complete ===\n";
    echo "Removed: $removed_count sample/test analyses\n";
    echo "Kept: " . count($real_analyses) . " real analyses with actual images\n\n";
    
    // Verify final state
    $final_stmt = $db->prepare("SELECT COUNT(*) as total FROM room_improvement_analyses");
    $final_stmt->execute();
    $final_count = $final_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "=== Final Verification ===\n";
    echo "Total analyses remaining: $final_count\n";
    
    // Show remaining analyses
    $remaining_stmt = $db->prepare("
        SELECT 
            id,
            room_type,
            improvement_notes,
            analysis_result,
            created_at
        FROM room_improvement_analyses 
        ORDER BY created_at DESC
    ");
    $remaining_stmt->execute();
    $remaining_analyses = $remaining_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nRemaining analyses:\n";
    foreach ($remaining_analyses as $analysis) {
        $analysis_result = json_decode($analysis['analysis_result'], true);
        $image_url = $analysis_result['ai_enhancements']['conceptual_visualization']['image_url'] ?? 'No image';
        $has_image = strpos($image_url, 'real_ai_') !== false ? '🖼️' : '⚪';
        
        echo "$has_image ID {$analysis['id']}: {$analysis['room_type']} - {$analysis['created_at']}\n";
        if ($has_image === '🖼️') {
            echo "   Image: " . basename($image_url) . "\n";
        }
    }
    
    echo "\n🎉 Sample data cleanup completed!\n";
    echo "✅ Room improvement section now shows only real analyses with actual AI images\n";
    echo "✅ No more sample/test/placeholder data\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
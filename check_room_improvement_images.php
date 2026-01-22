<?php
/**
 * Check room improvement images in database and file system
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 Room Improvement Images Analysis\n";
    echo "==================================\n\n";
    
    // Check database records
    $stmt = $db->prepare("
        SELECT id, homeowner_id, room_type, image_path, created_at,
               JSON_EXTRACT(analysis_result, '$.ai_enhancements.conceptual_visualization.image_url') as conceptual_image_url
        FROM room_improvement_analyses 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Database Records (Last 10):\n";
    echo str_repeat("-", 50) . "\n";
    
    if (empty($records)) {
        echo "No room improvement analyses found in database.\n\n";
    } else {
        foreach ($records as $record) {
            echo "ID: {$record['id']}\n";
            echo "Homeowner: {$record['homeowner_id']}\n";
            echo "Room Type: {$record['room_type']}\n";
            echo "Image Path: {$record['image_path']}\n";
            echo "Conceptual Image: " . ($record['conceptual_image_url'] ?: 'None') . "\n";
            echo "Created: {$record['created_at']}\n";
            
            // Check if uploaded image file exists
            $uploaded_image_path = __DIR__ . '/uploads/room_improvements/' . $record['image_path'];
            $uploaded_exists = file_exists($uploaded_image_path);
            echo "Uploaded Image Exists: " . ($uploaded_exists ? '✅ YES' : '❌ NO') . "\n";
            
            if ($uploaded_exists) {
                $file_size = filesize($uploaded_image_path);
                echo "File Size: " . number_format($file_size / 1024, 2) . " KB\n";
            }
            
            // Check if conceptual image exists
            if ($record['conceptual_image_url']) {
                $conceptual_path = __DIR__ . $record['conceptual_image_url'];
                $conceptual_exists = file_exists($conceptual_path);
                echo "Conceptual Image Exists: " . ($conceptual_exists ? '✅ YES' : '❌ NO') . "\n";
                
                if ($conceptual_exists) {
                    $file_size = filesize($conceptual_path);
                    echo "Conceptual File Size: " . number_format($file_size / 1024, 2) . " KB\n";
                }
            }
            
            echo str_repeat("-", 30) . "\n";
        }
    }
    
    // Check physical files in uploads directory
    echo "\n📁 Physical Files in uploads/room_improvements/:\n";
    echo str_repeat("-", 50) . "\n";
    
    $upload_dir = __DIR__ . '/uploads/room_improvements/';
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        $image_files = array_filter($files, function($file) {
            return preg_match('/\.(jpg|jpeg|png)$/i', $file);
        });
        
        if (empty($image_files)) {
            echo "No image files found in uploads/room_improvements/\n";
        } else {
            foreach ($image_files as $file) {
                $file_path = $upload_dir . $file;
                $file_size = filesize($file_path);
                $file_time = date('Y-m-d H:i:s', filemtime($file_path));
                
                echo "File: $file\n";
                echo "Size: " . number_format($file_size / 1024, 2) . " KB\n";
                echo "Modified: $file_time\n";
                
                // Check if this file is referenced in database
                $referenced = false;
                foreach ($records as $record) {
                    if ($record['image_path'] === $file) {
                        $referenced = true;
                        break;
                    }
                }
                echo "Referenced in DB: " . ($referenced ? '✅ YES' : '❌ NO (orphaned)') . "\n";
                echo str_repeat("-", 20) . "\n";
            }
        }
    } else {
        echo "❌ uploads/room_improvements/ directory does not exist\n";
    }
    
    // Check conceptual images directory
    echo "\n🎨 Physical Files in uploads/conceptual_images/:\n";
    echo str_repeat("-", 50) . "\n";
    
    $conceptual_dir = __DIR__ . '/uploads/conceptual_images/';
    if (is_dir($conceptual_dir)) {
        $files = scandir($conceptual_dir);
        $image_files = array_filter($files, function($file) {
            return preg_match('/\.(jpg|jpeg|png)$/i', $file);
        });
        
        if (empty($image_files)) {
            echo "No conceptual image files found\n";
        } else {
            foreach ($image_files as $file) {
                $file_path = $conceptual_dir . $file;
                $file_size = filesize($file_path);
                $file_time = date('Y-m-d H:i:s', filemtime($file_path));
                
                echo "File: $file\n";
                echo "Size: " . number_format($file_size / 1024, 2) . " KB\n";
                echo "Modified: $file_time\n";
                echo str_repeat("-", 20) . "\n";
            }
        }
    } else {
        echo "❌ uploads/conceptual_images/ directory does not exist\n";
    }
    
    // Provide recommendations
    echo "\n💡 Recommendations:\n";
    echo str_repeat("-", 50) . "\n";
    
    if (empty($records)) {
        echo "1. No room improvement analyses found - system may not be used yet\n";
    } else {
        $missing_files = 0;
        foreach ($records as $record) {
            $uploaded_image_path = __DIR__ . '/uploads/room_improvements/' . $record['image_path'];
            if (!file_exists($uploaded_image_path)) {
                $missing_files++;
            }
        }
        
        if ($missing_files > 0) {
            echo "1. ⚠️ $missing_files uploaded images are missing from file system\n";
            echo "   - Images may have been deleted during validation failures\n";
            echo "   - Check image validation logic in analyze_room_improvement.php\n";
        } else {
            echo "1. ✅ All uploaded images exist in file system\n";
        }
    }
    
    echo "2. 🔧 Frontend URL Construction:\n";
    echo "   - React dev server runs on port 3000\n";
    echo "   - Apache serves files on port 80\n";
    echo "   - Proxy configuration should handle image requests\n";
    echo "   - Check if images are accessible via: http://localhost/buildhub/uploads/room_improvements/\n";
    
    echo "\n3. 🧪 Test Image Access:\n";
    if (!empty($image_files)) {
        $test_file = array_values($image_files)[0];
        echo "   - Test URL: http://localhost/buildhub/uploads/room_improvements/$test_file\n";
        echo "   - Via React proxy: http://localhost:3000/buildhub/uploads/room_improvements/$test_file\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
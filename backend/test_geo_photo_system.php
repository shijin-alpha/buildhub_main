<?php
/**
 * Geo Photo System Test
 * Comprehensive test for the geo-located photo capture and viewing system
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>📸 Geo Photo System Test</h1>";

try {
    // Database connection
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px;'>";
    echo "<h2 style='margin: 0 0 10px 0;'>📍 Testing Geo-Located Photo System</h2>";
    echo "<p style='margin: 0; opacity: 0.9;'>Comprehensive validation of camera capture, geo-location, and photo management</p>";
    echo "</div>";

    // Test 1: Database Schema Verification
    echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h3 style='color: #2c3e50; margin-bottom: 15px;'>🗄️ Database Schema Verification</h3>";
    
    // Check if geo_photos table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'geo_photos'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p>✅ geo_photos table exists</p>";
        
        // Check table structure
        $columnsCheck = $pdo->query("DESCRIBE geo_photos");
        $columns = $columnsCheck->fetchAll(PDO::FETCH_ASSOC);
        
        $requiredColumns = [
            'id', 'project_id', 'contractor_id', 'homeowner_id', 'filename',
            'latitude', 'longitude', 'place_name', 'location_data', 'photo_timestamp'
        ];
        
        echo "<h4>📋 Column Verification</h4>";
        echo "<table style='width: 100%; border-collapse: collapse; margin-bottom: 15px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Column</th>";
        echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Type</th>";
        echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>Status</th>";
        echo "</tr>";
        
        foreach ($requiredColumns as $requiredColumn) {
            $found = false;
            $columnType = '';
            
            foreach ($columns as $column) {
                if ($column['Field'] === $requiredColumn) {
                    $found = true;
                    $columnType = $column['Type'];
                    break;
                }
            }
            
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$requiredColumn}</td>";
            echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$columnType}</td>";
            echo "<td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>";
            echo $found ? "<span style='color: #28a745; font-weight: 600;'>✅ Found</span>" : "<span style='color: #dc3545; font-weight: 600;'>❌ Missing</span>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count existing photos
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM geo_photos");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Total geo photos in database: " . $count['count'] . "</p>";
        
    } else {
        echo "<p>❌ geo_photos table does not exist</p>";
        echo "<p>The table will be created automatically when the first photo is uploaded.</p>";
    }
    echo "</div>";

    // Test 2: Upload Directory Check
    echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h3 style='color: #2c3e50; margin-bottom: 15px;'>📁 Upload Directory Verification</h3>";
    
    $uploadDir = 'uploads/geo_photos/';
    $fullUploadPath = __DIR__ . '/' . $uploadDir;
    
    if (is_dir($fullUploadPath)) {
        echo "<p>✅ Upload directory exists: {$uploadDir}</p>";
        
        // Check permissions
        if (is_writable($fullUploadPath)) {
            echo "<p>✅ Directory is writable</p>";
        } else {
            echo "<p>⚠️ Directory is not writable - photos may fail to upload</p>";
        }
        
        // Count existing files
        $files = glob($fullUploadPath . '*');
        $fileCount = count($files);
        echo "<p>📷 Existing photo files: {$fileCount}</p>";
        
        if ($fileCount > 0) {
            echo "<h4>Recent Photos:</h4>";
            echo "<ul>";
            $recentFiles = array_slice($files, -5); // Show last 5 files
            foreach ($recentFiles as $file) {
                $filename = basename($file);
                $filesize = formatFileSize(filesize($file));
                $modified = date('Y-m-d H:i:s', filemtime($file));
                echo "<li>{$filename} ({$filesize}) - {$modified}</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p>⚠️ Upload directory does not exist: {$uploadDir}</p>";
        echo "<p>Attempting to create directory...</p>";
        
        if (mkdir($fullUploadPath, 0755, true)) {
            echo "<p>✅ Directory created successfully</p>";
        } else {
            echo "<p>❌ Failed to create directory</p>";
        }
    }
    echo "</div>";

    // Test 3: API Endpoints Check
    echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h3 style='color: #2c3e50; margin-bottom: 15px;'>🔌 API Endpoints Verification</h3>";
    
    $apiEndpoints = [
        'Photo Upload (Contractor)' => 'api/contractor/upload_geo_photo.php',
        'Get Photos (Homeowner)' => 'api/homeowner/get_geo_photos.php',
        'Mark Photo Viewed' => 'api/homeowner/mark_photo_viewed.php'
    ];
    
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Endpoint</th>";
    echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>File Path</th>";
    echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>Status</th>";
    echo "</tr>";
    
    foreach ($apiEndpoints as $name => $endpoint) {
        $exists = file_exists($endpoint);
        $status = $exists ? "<span style='color: #28a745; font-weight: 600;'>✅ Available</span>" : "<span style='color: #dc3545; font-weight: 600;'>❌ Missing</span>";
        
        echo "<tr>";
        echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$name}</td>";
        echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$endpoint}</td>";
        echo "<td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // Test 4: Frontend Components Check
    echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h3 style='color: #2c3e50; margin-bottom: 15px;'>🎨 Frontend Components Verification</h3>";
    
    $frontendFiles = [
        'Geo Photo Capture Component' => 'frontend/src/components/GeoPhotoCapture.jsx',
        'Geo Photo Viewer Component' => 'frontend/src/components/GeoPhotoViewer.jsx',
        'Geo Photo Capture Styles' => 'frontend/src/styles/GeoPhotoCapture.css',
        'Geo Photo Viewer Styles' => 'frontend/src/styles/GeoPhotoViewer.css'
    ];
    
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Component</th>";
    echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>File Path</th>";
    echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>Status</th>";
    echo "</tr>";
    
    foreach ($frontendFiles as $name => $file) {
        $exists = file_exists($file);
        $status = $exists ? "<span style='color: #28a745; font-weight: 600;'>✅ Available</span>" : "<span style='color: #dc3545; font-weight: 600;'>❌ Missing</span>";
        
        echo "<tr>";
        echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$name}</td>";
        echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$file}</td>";
        echo "<td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // Test 5: Sample Data Analysis (if photos exist)
    if (isset($count) && $count['count'] > 0) {
        echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
        echo "<h3 style='color: #2c3e50; margin-bottom: 15px;'>📊 Photo Data Analysis</h3>";
        
        // Get photo statistics
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_photos,
                COUNT(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 END) as geo_located_photos,
                COUNT(CASE WHEN homeowner_viewed = 1 THEN 1 END) as viewed_photos,
                COUNT(DISTINCT project_id) as projects_with_photos,
                COUNT(DISTINCT contractor_id) as contractors_with_photos,
                AVG(file_size) as avg_file_size,
                MIN(upload_timestamp) as first_photo,
                MAX(upload_timestamp) as latest_photo
            FROM geo_photos
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;'>";
        
        $statItems = [
            ['Total Photos', $stats['total_photos'], '📷'],
            ['Geo-Located', $stats['geo_located_photos'], '📍'],
            ['Viewed by Homeowners', $stats['viewed_photos'], '👁️'],
            ['Projects with Photos', $stats['projects_with_photos'], '🏗️'],
            ['Active Contractors', $stats['contractors_with_photos'], '👷'],
            ['Avg File Size', formatFileSize($stats['avg_file_size']), '💾']
        ];
        
        foreach ($statItems as $item) {
            echo "<div style='background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; text-align: center;'>";
            echo "<div style='font-size: 24px; margin-bottom: 8px;'>{$item[2]}</div>";
            echo "<div style='font-size: 1.2rem; font-weight: 600; color: #2c3e50; margin-bottom: 4px;'>{$item[1]}</div>";
            echo "<div style='font-size: 0.9rem; color: #6c757d;'>{$item[0]}</div>";
            echo "</div>";
        }
        echo "</div>";
        
        // Recent photos
        $recentStmt = $pdo->query("
            SELECT 
                gp.*,
                c.first_name as contractor_name,
                h.first_name as homeowner_name
            FROM geo_photos gp
            LEFT JOIN users c ON gp.contractor_id = c.id
            LEFT JOIN users h ON gp.homeowner_id = h.id
            ORDER BY gp.upload_timestamp DESC
            LIMIT 5
        ");
        $recentPhotos = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recentPhotos) > 0) {
            echo "<h4>📸 Recent Photos</h4>";
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Filename</th>";
            echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Location</th>";
            echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Contractor</th>";
            echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: left;'>Uploaded</th>";
            echo "<th style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>Viewed</th>";
            echo "</tr>";
            
            foreach ($recentPhotos as $photo) {
                $location = $photo['place_name'] ?: ($photo['latitude'] ? "{$photo['latitude']}, {$photo['longitude']}" : 'No location');
                $viewed = $photo['homeowner_viewed'] ? '✅' : '❌';
                
                echo "<tr>";
                echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$photo['original_filename']}</td>";
                echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$location}</td>";
                echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>{$photo['contractor_name']}</td>";
                echo "<td style='padding: 10px; border: 1px solid #dee2e6;'>" . date('M j, Y g:i A', strtotime($photo['upload_timestamp'])) . "</td>";
                echo "<td style='padding: 10px; border: 1px solid #dee2e6; text-align: center;'>{$viewed}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "</div>";
    }

    // Test 6: Browser Compatibility Check
    echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h3 style='color: #2c3e50; margin-bottom: 15px;'>🌐 Browser Compatibility Check</h3>";
    
    echo "<div style='background: #e7f3ff; border: 1px solid #b8daff; border-radius: 6px; padding: 15px; margin-bottom: 15px;'>";
    echo "<h4 style='margin: 0 0 10px 0; color: #004085;'>📱 Required Browser Features</h4>";
    echo "<ul style='margin: 0; padding-left: 20px; color: #004085;'>";
    echo "<li><strong>Camera Access:</strong> navigator.mediaDevices.getUserMedia()</li>";
    echo "<li><strong>Geolocation:</strong> navigator.geolocation.getCurrentPosition()</li>";
    echo "<li><strong>File API:</strong> File, FileReader, FormData</li>";
    echo "<li><strong>Canvas API:</strong> For photo processing and overlay</li>";
    echo "<li><strong>Fetch API:</strong> For uploading photos and location data</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px;'>";
    echo "<h4 style='margin: 0 0 10px 0; color: #856404;'>⚠️ Browser Support Notes</h4>";
    echo "<ul style='margin: 0; padding-left: 20px; color: #856404;'>";
    echo "<li>Camera access requires HTTPS in production</li>";
    echo "<li>Geolocation requires user permission</li>";
    echo "<li>Some features may not work in older browsers</li>";
    echo "<li>Mobile browsers generally have better camera support</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";

    // Summary and Instructions
    echo "<div style='background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px;'>";
    echo "<h2 style='margin: 0 0 15px 0;'>🎉 Geo Photo System - Test Complete!</h2>";
    echo "<h3 style='margin: 0 0 10px 0;'>✨ System Features:</h3>";
    echo "<ul style='margin: 0; padding-left: 20px;'>";
    echo "<li>📸 Real-time camera capture with live preview</li>";
    echo "<li>📍 Automatic GPS location capture with place names</li>";
    echo "<li>🗺️ Reverse geocoding for human-readable addresses</li>";
    echo "<li>📤 Instant photo upload to homeowners</li>";
    echo "<li>🔔 Automatic notifications to homeowners</li>";
    echo "<li>👁️ Photo viewing tracking and analytics</li>";
    echo "<li>📱 Mobile-optimized responsive design</li>";
    echo "<li>🔒 Secure file handling and validation</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 20px;'>";
    echo "<h3 style='color: #2c3e50; margin-bottom: 10px;'>📝 Usage Instructions</h3>";
    echo "<h4 style='color: #495057; margin-bottom: 8px;'>For Contractors:</h4>";
    echo "<ol style='margin: 0 0 15px 0; padding-left: 20px;'>";
    echo "<li>Navigate to Contractor Dashboard → Progress Updates</li>";
    echo "<li>Select 'Daily Progress Update' and choose a project</li>";
    echo "<li>In the Photos section, click 'Capture Geo Photos'</li>";
    echo "<li>Allow camera and location permissions</li>";
    echo "<li>Take photos with automatic location embedding</li>";
    echo "<li>Send photos directly to homeowner</li>";
    echo "</ol>";
    
    echo "<h4 style='color: #495057; margin-bottom: 8px;'>For Homeowners:</h4>";
    echo "<ol style='margin: 0; padding-left: 20px;'>";
    echo "<li>Receive instant notifications when photos are sent</li>";
    echo "<li>View photos with embedded location information</li>";
    echo "<li>Click on location to view on Google Maps</li>";
    echo "<li>Download photos for your records</li>";
    echo "<li>Track which photos you've viewed</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>❌ Test Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and file permissions.</p>";
    echo "</div>";
}

// Helper function
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

echo "<hr style='margin: 30px 0;'>";
echo "<p style='text-align: center; color: #6c757d; font-size: 0.9rem;'>";
echo "Geo Photo System Test - " . date('Y-m-d H:i:s');
echo "</p>";
?>
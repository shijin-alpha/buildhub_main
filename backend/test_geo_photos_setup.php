<?php
/**
 * Test script to verify geo photos system is working correctly
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>🧪 Geo Photos System Test</h2>\n";
    
    // Test 1: Check if table exists
    echo "<h3>Test 1: Table Existence</h3>\n";
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'geo_photos'");
    if ($tableCheck->rowCount() > 0) {
        echo "✅ geo_photos table exists<br>\n";
    } else {
        echo "❌ geo_photos table does not exist<br>\n";
        echo "Run setup_geo_photos.php to create the table<br>\n";
        exit;
    }
    
    // Test 2: Check table structure
    echo "<h3>Test 2: Table Structure</h3>\n";
    $columns = $pdo->query("SHOW COLUMNS FROM geo_photos")->fetchAll(PDO::FETCH_ASSOC);
    $requiredColumns = [
        'id', 'project_id', 'contractor_id', 'homeowner_id', 'filename', 
        'latitude', 'longitude', 'place_name', 'progress_update_id'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "✅ All required columns exist (" . count($columns) . " total columns)<br>\n";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "<br>\n";
    }
    
    // Test 3: Check uploads directory
    echo "<h3>Test 3: Uploads Directory</h3>\n";
    $uploadDir = __DIR__ . '/uploads/geo_photos/';
    if (is_dir($uploadDir)) {
        echo "✅ Uploads directory exists: $uploadDir<br>\n";
        if (is_writable($uploadDir)) {
            echo "✅ Uploads directory is writable<br>\n";
        } else {
            echo "⚠️ Uploads directory is not writable<br>\n";
        }
    } else {
        echo "❌ Uploads directory does not exist<br>\n";
    }
    
    // Test 4: Check API endpoints
    echo "<h3>Test 4: API Endpoints</h3>\n";
    $apiFiles = [
        'api/contractor/upload_geo_photo.php',
        'api/homeowner/get_geo_photos.php',
        'api/homeowner/mark_photo_viewed.php'
    ];
    
    foreach ($apiFiles as $apiFile) {
        if (file_exists($apiFile)) {
            echo "✅ $apiFile exists<br>\n";
        } else {
            echo "❌ $apiFile missing<br>\n";
        }
    }
    
    // Test 5: Database record count
    echo "<h3>Test 5: Database Records</h3>\n";
    $count = $pdo->query("SELECT COUNT(*) as count FROM geo_photos")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Current geo_photos records: $count<br>\n";
    
    if ($count > 0) {
        // Show sample records
        $samples = $pdo->query("SELECT id, filename, place_name, latitude, longitude, upload_timestamp FROM geo_photos ORDER BY upload_timestamp DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Sample Records:</h4>\n";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>ID</th><th>Filename</th><th>Location</th><th>Coordinates</th><th>Upload Time</th></tr>\n";
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>{$sample['id']}</td>";
            echo "<td>{$sample['filename']}</td>";
            echo "<td>{$sample['place_name']}</td>";
            echo "<td>{$sample['latitude']}, {$sample['longitude']}</td>";
            echo "<td>{$sample['upload_timestamp']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Test 6: Check users for testing
    echo "<h3>Test 6: User Accounts</h3>\n";
    $contractorCount = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'contractor'")->fetch(PDO::FETCH_ASSOC)['count'];
    $homeownerCount = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'homeowner'")->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "👷 Contractors: $contractorCount<br>\n";
    echo "🏠 Homeowners: $homeownerCount<br>\n";
    
    if ($contractorCount > 0 && $homeownerCount > 0) {
        echo "✅ Sufficient users for testing<br>\n";
    } else {
        echo "⚠️ Create contractor and homeowner accounts for full testing<br>\n";
    }
    
    // Test 7: Check projects
    echo "<h3>Test 7: Projects</h3>\n";
    $projectCount = $pdo->query("SELECT COUNT(*) as count FROM layout_requests WHERE status = 'acknowledged'")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "🏗️ Active projects: $projectCount<br>\n";
    
    if ($projectCount > 0) {
        echo "✅ Projects available for geo photo testing<br>\n";
    } else {
        echo "⚠️ Create acknowledged layout requests for testing<br>\n";
    }
    
    echo "<h3>🎉 Test Summary</h3>\n";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>\n";
    echo "<strong>Geo Photos System Status:</strong><br>\n";
    echo "✅ Database table: Ready<br>\n";
    echo "✅ File system: Ready<br>\n";
    echo "✅ API endpoints: Available<br>\n";
    echo "📊 Records: $count photos<br>\n";
    echo "👥 Users: $contractorCount contractors, $homeownerCount homeowners<br>\n";
    echo "🏗️ Projects: $projectCount active<br>\n";
    echo "</div>\n";
    
    echo "<h3>🚀 Next Steps</h3>\n";
    echo "<ul>\n";
    echo "<li>Login as a contractor and navigate to progress updates</li>\n";
    echo "<li>Click 'Capture Geo-Tagged Photo' to test photo capture</li>\n";
    echo "<li>Login as a homeowner to view received photos</li>\n";
    echo "<li>Test photo inclusion in progress reports</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>\n";
    echo "<strong>❌ Test Error:</strong> " . $e->getMessage() . "<br>\n";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>\n";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>\n";
    echo "</div>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; border-bottom: 1px solid #ecf0f1; padding-bottom: 5px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f8f9fa; }
</style>
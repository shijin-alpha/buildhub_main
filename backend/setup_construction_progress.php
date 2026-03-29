<?php
/**
 * Setup script for Construction Progress Update functionality
 * This script creates the necessary database tables and sample data
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Setting up Construction Progress Update System</h2>\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/database/create_construction_progress_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "<h3>Creating Database Tables...</h3>\n";
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr(trim($statement), 0, 50) . "...\n<br>";
            } catch (PDOException $e) {
                // Some statements might fail if tables already exist, that's okay
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠ Warning: " . $e->getMessage() . "\n<br>";
                }
            }
        }
    }
    
    echo "<h3>Creating Upload Directories...</h3>\n";
    
    // Create upload directories
    $uploadDirs = [
        __DIR__ . '/uploads/progress_photos',
        __DIR__ . '/uploads/progress_photos/temp'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0777, true)) {
                echo "✓ Created directory: $dir\n<br>";
            } else {
                echo "✗ Failed to create directory: $dir\n<br>";
            }
        } else {
            echo "✓ Directory already exists: $dir\n<br>";
        }
    }
    
    echo "<h3>Adding Sample Project Locations...</h3>\n";
    
    // Add sample project locations for testing
    $sampleLocations = [
        [1, 12.9716, 77.5946, 'Bangalore, Karnataka', 100],
        [2, 19.0760, 72.8777, 'Mumbai, Maharashtra', 150],
        [3, 28.7041, 77.1025, 'New Delhi, Delhi', 200]
    ];
    
    $locationStmt = $db->prepare("
        INSERT IGNORE INTO project_locations (project_id, latitude, longitude, address, radius_meters) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleLocations as $location) {
        try {
            $locationStmt->execute($location);
            echo "✓ Added sample location for project {$location[0]}: {$location[3]}\n<br>";
        } catch (PDOException $e) {
            echo "⚠ Location already exists for project {$location[0]}\n<br>";
        }
    }
    
    echo "<h3>Verifying Table Structure...</h3>\n";
    
    // Verify tables were created
    $tables = [
        'construction_progress_updates',
        'project_locations', 
        'progress_notifications'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n<br>";
            
            // Show column count
            $colStmt = $db->prepare("SHOW COLUMNS FROM $table");
            $colStmt->execute();
            $columnCount = $colStmt->rowCount();
            echo "&nbsp;&nbsp;→ $columnCount columns\n<br>";
        } else {
            echo "✗ Table '$table' not found\n<br>";
        }
    }
    
    echo "<h3>Testing API Endpoints...</h3>\n";
    
    // Test if API files exist
    $apiFiles = [
        'api/contractor/submit_progress_update.php',
        'api/contractor/get_progress_updates.php',
        'api/contractor/get_assigned_projects.php',
        'api/homeowner/get_progress_updates.php',
        'api/homeowner/mark_notifications_read.php'
    ];
    
    foreach ($apiFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            echo "✓ API endpoint exists: $file\n<br>";
        } else {
            echo "✗ API endpoint missing: $file\n<br>";
        }
    }
    
    echo "<h3>Setup Complete!</h3>\n";
    echo "<p><strong>Construction Progress Update System is ready to use.</strong></p>\n";
    echo "<p>Features available:</p>\n";
    echo "<ul>\n";
    echo "<li>✓ Contractors can submit progress updates with photos</li>\n";
    echo "<li>✓ Automatic geo-location verification</li>\n";
    echo "<li>✓ Homeowner notifications for new updates</li>\n";
    echo "<li>✓ Progress timeline with photo gallery</li>\n";
    echo "<li>✓ Stage completion tracking</li>\n";
    echo "<li>✓ Delay reporting system</li>\n";
    echo "</ul>\n";
    
    echo "<h4>Next Steps:</h4>\n";
    echo "<ol>\n";
    echo "<li>Add the ConstructionProgressUpdate component to your Contractor Dashboard</li>\n";
    echo "<li>Add the HomeownerProgressView component to your Homeowner Dashboard</li>\n";
    echo "<li>Test the functionality with sample data</li>\n";
    echo "<li>Configure project locations for geo-verification</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<h3>Setup Error</h3>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}
?>
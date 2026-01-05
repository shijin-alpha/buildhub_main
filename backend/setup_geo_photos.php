<?php
/**
 * Setup script for geo_photos table and related functionality
 * Run this script to create the geo_photos table and ensure proper database structure
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Setting up geo_photos system...\n";
    
    // Create geo_photos table
    $createGeoPhotosTable = "
        CREATE TABLE IF NOT EXISTS geo_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            place_name TEXT NULL,
            location_accuracy DECIMAL(8, 2) NULL,
            location_data JSON NULL,
            photo_timestamp TIMESTAMP NULL,
            upload_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_sent_to_homeowner BOOLEAN DEFAULT TRUE,
            homeowner_viewed BOOLEAN DEFAULT FALSE,
            homeowner_viewed_at TIMESTAMP NULL,
            progress_update_id INT NULL,
            is_included_in_progress BOOLEAN DEFAULT FALSE,
            progress_association_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_project_contractor (project_id, contractor_id),
            INDEX idx_homeowner (homeowner_id),
            INDEX idx_upload_date (upload_timestamp),
            INDEX idx_location (latitude, longitude),
            INDEX idx_progress_update (progress_update_id),
            INDEX idx_viewed (homeowner_viewed),
            
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createGeoPhotosTable);
    echo "✅ geo_photos table created successfully\n";
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/geo_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✅ Created uploads/geo_photos directory\n";
    } else {
        echo "✅ uploads/geo_photos directory already exists\n";
    }
    
    // Check if we need to add progress update columns to existing table
    $checkColumns = $pdo->query("SHOW COLUMNS FROM geo_photos LIKE 'progress_update_id'");
    if ($checkColumns->rowCount() == 0) {
        echo "Adding progress update columns to existing geo_photos table...\n";
        
        $alterTable = "
            ALTER TABLE geo_photos 
            ADD COLUMN progress_update_id INT NULL AFTER homeowner_viewed_at,
            ADD COLUMN is_included_in_progress BOOLEAN DEFAULT FALSE AFTER progress_update_id,
            ADD COLUMN progress_association_date TIMESTAMP NULL AFTER is_included_in_progress,
            ADD INDEX idx_progress_update (progress_update_id)
        ";
        
        try {
            $pdo->exec($alterTable);
            echo "✅ Progress update columns added successfully\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "✅ Progress update columns already exist\n";
            } else {
                throw $e;
            }
        }
    } else {
        echo "✅ Progress update columns already exist\n";
    }
    
    // Insert sample data for testing (optional)
    if (isset($_GET['sample_data']) && $_GET['sample_data'] === 'true') {
        echo "Creating sample geo photo data...\n";
        
        // Check if we have users to work with
        $userCheck = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role IN ('contractor', 'homeowner')");
        $userCount = $userCheck->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($userCount >= 2) {
            // Get sample contractor and homeowner
            $contractorStmt = $pdo->query("SELECT id FROM users WHERE role = 'contractor' LIMIT 1");
            $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
            
            $homeownerStmt = $pdo->query("SELECT id FROM users WHERE role = 'homeowner' LIMIT 1");
            $homeowner = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contractor && $homeowner) {
                // Create sample project if needed
                $projectCheck = $pdo->prepare("SELECT id FROM layout_requests WHERE contractor_id = ? AND homeowner_id = ? LIMIT 1");
                $projectCheck->execute([$contractor['id'], $homeowner['id']]);
                $project = $projectCheck->fetch(PDO::FETCH_ASSOC);
                
                if (!$project) {
                    $createProject = $pdo->prepare("
                        INSERT INTO layout_requests (
                            homeowner_id, contractor_id, requirements, budget_range, 
                            plot_size, building_size, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, 'acknowledged', NOW())
                    ");
                    $createProject->execute([
                        $homeowner['id'],
                        $contractor['id'],
                        'Sample project for geo photo testing',
                        '10-15 Lakhs',
                        '30x40 feet',
                        '1200 sq ft'
                    ]);
                    $projectId = $pdo->lastInsertId();
                    echo "✅ Created sample project (ID: $projectId)\n";
                } else {
                    $projectId = $project['id'];
                    echo "✅ Using existing project (ID: $projectId)\n";
                }
                
                // Create sample geo photo record (without actual file)
                $sampleLocation = json_encode([
                    'latitude' => 9.591565,
                    'longitude' => 76.522034,
                    'placeName' => 'Kottayam, Kerala, India',
                    'accuracy' => 15.5,
                    'timestamp' => date('c')
                ]);
                
                $insertSample = $pdo->prepare("
                    INSERT INTO geo_photos (
                        project_id, contractor_id, homeowner_id, filename, original_filename,
                        file_path, file_size, mime_type, latitude, longitude, place_name,
                        location_accuracy, location_data, photo_timestamp, upload_timestamp
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $insertSample->execute([
                    $projectId,
                    $contractor['id'],
                    $homeowner['id'],
                    'sample_geo_photo_' . time() . '.jpg',
                    'sample_construction_photo.jpg',
                    '/uploads/geo_photos/sample_geo_photo_' . time() . '.jpg',
                    1024000, // 1MB
                    'image/jpeg',
                    9.591565,
                    76.522034,
                    'Kottayam, Kerala, India',
                    15.5,
                    $sampleLocation
                ]);
                
                echo "✅ Created sample geo photo record\n";
            } else {
                echo "⚠️ No contractor or homeowner found for sample data\n";
            }
        } else {
            echo "⚠️ Not enough users found for sample data creation\n";
        }
    }
    
    // Verify table structure
    echo "\nVerifying table structure...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM geo_photos")->fetchAll(PDO::FETCH_ASSOC);
    echo "Table has " . count($columns) . " columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check record count
    $count = $pdo->query("SELECT COUNT(*) as count FROM geo_photos")->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nCurrent geo_photos records: $count\n";
    
    echo "\n🎉 Geo photos system setup completed successfully!\n";
    echo "\nYou can now:\n";
    echo "- Capture geo-tagged photos from contractor dashboard\n";
    echo "- View photos from homeowner dashboard\n";
    echo "- Include geo photos in progress updates\n";
    echo "\nTo create sample data, run: setup_geo_photos.php?sample_data=true\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up geo photos system: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
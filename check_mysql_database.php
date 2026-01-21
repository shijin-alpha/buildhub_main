<?php
// Check the actual MySQL database that the frontend uses
require_once 'backend/config/database.php';

try {
    echo "=== Checking MySQL Database ===\n\n";
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "✓ Connected to MySQL database\n\n";
    
    // Check if concept_previews table exists
    $stmt = $db->query("SHOW TABLES LIKE 'concept_previews'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "❌ concept_previews table does not exist in MySQL\n";
        echo "Creating the table...\n";
        
        // Create the table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS concept_previews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            architect_id INT NOT NULL,
            layout_request_id INT NOT NULL,
            job_id VARCHAR(255) UNIQUE,
            original_description TEXT NOT NULL,
            refined_prompt TEXT,
            status ENUM('processing', 'generating', 'completed', 'failed') DEFAULT 'processing',
            image_url VARCHAR(500),
            image_path VARCHAR(500),
            is_placeholder BOOLEAN DEFAULT FALSE,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_architect_id (architect_id),
            INDEX idx_layout_request_id (layout_request_id),
            INDEX idx_status (status),
            INDEX idx_job_id (job_id),
            INDEX idx_created_at (created_at)
        )";
        
        $db->exec($createTableSQL);
        echo "✓ concept_previews table created\n\n";
    } else {
        echo "✓ concept_previews table exists\n\n";
    }
    
    // Check existing concept previews
    $stmt = $db->query("SELECT * FROM concept_previews ORDER BY created_at DESC");
    $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total concept previews in MySQL: " . count($concepts) . "\n\n";
    
    if (count($concepts) > 0) {
        foreach ($concepts as $concept) {
            echo "--- Concept ID: {$concept['id']} ---\n";
            echo "Status: {$concept['status']}\n";
            echo "Image URL: " . ($concept['image_url'] ?? 'NULL') . "\n";
            echo "Description: " . substr($concept['original_description'], 0, 50) . "...\n";
            echo "Created: {$concept['created_at']}\n";
            
            if ($concept['image_url']) {
                $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
                $fullPath = __DIR__ . '/' . $imagePath;
                echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No concept previews found in MySQL database\n";
        echo "The frontend is showing old data or cached data\n\n";
        
        // Let's create some test data with real images
        echo "Creating test concept previews with real images...\n";
        
        // Check available images
        $imageDir = 'uploads/conceptual_images';
        $images = glob("$imageDir/*.png");
        
        if (count($images) > 0) {
            // Get or create test architect and layout request
            $stmt = $db->query("SELECT id FROM users WHERE role = 'architect' LIMIT 1");
            $architect = $stmt->fetch();
            
            if (!$architect) {
                // Create test architect
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Test', 'Architect', 'architect@test.com', 'architect', 'approved']);
                $architect_id = $db->lastInsertId();
            } else {
                $architect_id = $architect['id'];
            }
            
            $stmt = $db->query("SELECT id FROM layout_requests LIMIT 1");
            $layout_request = $stmt->fetch();
            
            if (!$layout_request) {
                // Create test layout request
                $stmt = $db->prepare("INSERT INTO layout_requests (user_id, homeowner_id, plot_size, budget_range, location, requirements, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$architect_id, $architect_id, '2000 sq ft', '10-15 lakhs', 'Test Location', 'Modern house concept', 'approved']);
                $layout_request_id = $db->lastInsertId();
            } else {
                $layout_request_id = $layout_request['id'];
            }
            
            // Create concept previews with real images
            $concepts = [
                'A modern two-story house with clean lines, large windows, and white exterior walls',
                'A traditional Kerala-style house with sloped roof, wooden elements, and natural materials',
                'A contemporary villa with glass facades, minimalist design, and open spaces'
            ];
            
            foreach ($concepts as $i => $description) {
                if ($i < count($images)) {
                    $imagePath = $images[$i];
                    $imageUrl = "/buildhub/$imagePath";
                    
                    $stmt = $db->prepare("
                        INSERT INTO concept_previews (
                            architect_id, layout_request_id, job_id, original_description, 
                            status, image_url, image_path, is_placeholder
                        ) VALUES (?, ?, ?, ?, 'completed', ?, ?, 0)
                    ");
                    
                    $stmt->execute([
                        $architect_id,
                        $layout_request_id,
                        'mysql_test_' . ($i + 1),
                        $description,
                        $imageUrl,
                        $imagePath
                    ]);
                    
                    echo "✓ Created concept: " . substr($description, 0, 40) . "...\n";
                }
            }
            
            echo "\n✓ Test concept previews created with real images\n";
        } else {
            echo "No images found in $imageDir\n";
        }
    }
    
    // Check users table
    echo "\n=== Users in MySQL ===\n";
    $stmt = $db->query("SELECT id, first_name, last_name, email, role FROM users WHERE role IN ('architect', 'homeowner') LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, Role: {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nThis might mean:\n";
    echo "1. MySQL is not running\n";
    echo "2. Database 'buildhub' doesn't exist\n";
    echo "3. Connection credentials are wrong\n";
    echo "4. The system is actually using a different database\n";
}
?>
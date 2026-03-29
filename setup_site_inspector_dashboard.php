<?php
/**
 * Site Inspector Dashboard Setup Script
 * 
 * This script sets up the database schema and creates sample data
 * for the Site Inspector Dashboard functionality.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Site Inspector Dashboard Setup</h1>\n";

try {
    // Include database configuration
    require_once 'backend/config/db.php';
    
    echo "<h2>Step 1: Database Connection</h2>\n";
    echo "✓ Connected to database successfully<br>\n";
    
    // Step 2: Update users table to support site_inspector role
    echo "<h2>Step 2: Update Users Table</h2>\n";
    
    try {
        $alterQuery = "ALTER TABLE `users` MODIFY COLUMN `role` ENUM('homeowner','contractor','architect','site_inspector') DEFAULT NULL";
        $pdo->exec($alterQuery);
        echo "✓ Updated users table to support site_inspector role<br>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'site_inspector') !== false) {
            echo "✓ Users table already supports site_inspector role<br>\n";
        } else {
            throw $e;
        }
    }
    
    // Step 3: Create site inspector specific tables
    echo "<h2>Step 3: Create Site Inspector Tables</h2>\n";
    
    // Site inspector assignments table
    $createAssignmentsTable = "
    CREATE TABLE IF NOT EXISTS `site_inspector_assignments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `inspector_id` int(11) NOT NULL,
      `project_id` int(11) NOT NULL,
      `assigned_by` int(11) NOT NULL,
      `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
      `status` enum('active','inactive','completed') DEFAULT 'active',
      `notes` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `inspector_id` (`inspector_id`),
      KEY `project_id` (`project_id`),
      KEY `assigned_by` (`assigned_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createAssignmentsTable);
    echo "✓ Created site_inspector_assignments table<br>\n";
    
    // Inspection reports table
    $createReportsTable = "
    CREATE TABLE IF NOT EXISTS `inspection_reports` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` int(11) NOT NULL,
      `inspector_id` int(11) NOT NULL,
      `inspection_date` date NOT NULL,
      `inspection_stage` varchar(100) NOT NULL,
      `inspection_type` enum('routine','milestone','quality','safety','final') DEFAULT 'routine',
      `overall_status` enum('approved','rejected','needs_attention','pending') DEFAULT 'pending',
      `quality_score` decimal(3,1) DEFAULT NULL COMMENT 'Score out of 10',
      `safety_compliance` enum('compliant','non_compliant','partial') DEFAULT 'compliant',
      `notes` text DEFAULT NULL,
      `recommendations` text DEFAULT NULL,
      `next_inspection_date` date DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `project_id` (`project_id`),
      KEY `inspector_id` (`inspector_id`),
      KEY `inspection_date` (`inspection_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createReportsTable);
    echo "✓ Created inspection_reports table<br>\n";
    
    // Inspection photos table
    $createPhotosTable = "
    CREATE TABLE IF NOT EXISTS `inspection_photos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `inspection_report_id` int(11) NOT NULL,
      `file_path` varchar(500) NOT NULL,
      `file_name` varchar(255) NOT NULL,
      `file_size` int(11) DEFAULT NULL,
      `mime_type` varchar(100) DEFAULT NULL,
      `caption` varchar(500) DEFAULT NULL,
      `photo_type` enum('progress','issue','quality','safety','completion') DEFAULT 'progress',
      `latitude` decimal(10,8) DEFAULT NULL,
      `longitude` decimal(11,8) DEFAULT NULL,
      `location_accuracy` decimal(8,2) DEFAULT NULL,
      `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `inspection_report_id` (`inspection_report_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createPhotosTable);
    echo "✓ Created inspection_photos table<br>\n";
    
    // Inspection checklist items table
    $createChecklistTable = "
    CREATE TABLE IF NOT EXISTS `inspection_checklist_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `inspection_report_id` int(11) NOT NULL,
      `category` varchar(100) NOT NULL,
      `item_description` varchar(500) NOT NULL,
      `status` enum('pass','fail','na','pending') DEFAULT 'pending',
      `notes` text DEFAULT NULL,
      `priority` enum('low','medium','high','critical') DEFAULT 'medium',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `inspection_report_id` (`inspection_report_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createChecklistTable);
    echo "✓ Created inspection_checklist_items table<br>\n";
    
    // Inspection notifications table
    $createNotificationsTable = "
    CREATE TABLE IF NOT EXISTS `inspection_notifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `inspection_report_id` int(11) NOT NULL,
      `recipient_id` int(11) NOT NULL,
      `recipient_type` enum('homeowner','contractor','admin') NOT NULL,
      `notification_type` enum('inspection_scheduled','inspection_completed','issue_found','approval_required') NOT NULL,
      `title` varchar(255) NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `inspection_report_id` (`inspection_report_id`),
      KEY `recipient_id` (`recipient_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createNotificationsTable);
    echo "✓ Created inspection_notifications table<br>\n";
    
    // Step 4: Create sample site inspector user
    echo "<h2>Step 4: Create Sample Site Inspector</h2>\n";
    
    $checkInspector = "SELECT id FROM users WHERE email = 'inspector@buildhub.com'";
    $stmt = $pdo->prepare($checkInspector);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $insertInspector = "INSERT INTO `users` 
                          (`first_name`, `last_name`, `email`, `password`, `role`, `status`, `is_verified`, `phone`, `city`, `state`) 
                          VALUES 
                          ('Site', 'Inspector', 'inspector@buildhub.com', ?, 'site_inspector', 'approved', 1, '9876543210', 'Mumbai', 'Maharashtra')";
        
        $hashedPassword = password_hash('inspector123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare($insertInspector);
        $stmt->execute([$hashedPassword]);
        
        $inspectorId = $pdo->lastInsertId();
        echo "✓ Created sample site inspector user (ID: $inspectorId)<br>\n";
        echo "&nbsp;&nbsp;Email: inspector@buildhub.com<br>\n";
        echo "&nbsp;&nbsp;Password: inspector123<br>\n";
    } else {
        echo "✓ Site inspector user already exists<br>\n";
    }
    
    // Step 5: Create sample project assignments
    echo "<h2>Step 5: Create Sample Project Assignments</h2>\n";
    
    // Get inspector ID
    $getInspectorId = "SELECT id FROM users WHERE email = 'inspector@buildhub.com'";
    $stmt = $pdo->prepare($getInspectorId);
    $stmt->execute();
    $inspector = $stmt->fetch();
    
    if ($inspector) {
        $inspectorId = $inspector['id'];
        
        // Get some existing projects
        $getProjects = "SELECT id FROM construction_projects LIMIT 3";
        $stmt = $pdo->prepare($getProjects);
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
        if (count($projects) > 0) {
            // Get an admin user to assign projects
            $getAdmin = "SELECT id FROM users WHERE role IN ('admin', 'contractor') LIMIT 1";
            $stmt = $pdo->prepare($getAdmin);
            $stmt->execute();
            $admin = $stmt->fetch();
            
            $assignedBy = $admin ? $admin['id'] : 1; // Fallback to ID 1
            
            foreach ($projects as $project) {
                // Check if assignment already exists
                $checkAssignment = "SELECT id FROM site_inspector_assignments 
                                   WHERE inspector_id = ? AND project_id = ?";
                $stmt = $pdo->prepare($checkAssignment);
                $stmt->execute([$inspectorId, $project['id']]);
                
                if ($stmt->rowCount() == 0) {
                    $insertAssignment = "INSERT INTO site_inspector_assignments 
                                        (inspector_id, project_id, assigned_by, notes) 
                                        VALUES (?, ?, ?, 'Sample assignment for testing')";
                    $stmt = $pdo->prepare($insertAssignment);
                    $stmt->execute([$inspectorId, $project['id'], $assignedBy]);
                    
                    echo "✓ Assigned project {$project['id']} to site inspector<br>\n";
                }
            }
        } else {
            echo "⚠ No existing projects found to assign<br>\n";
        }
    }
    
    // Step 6: Create upload directories
    echo "<h2>Step 6: Create Upload Directories</h2>\n";
    
    $uploadDirs = [
        'backend/uploads/inspection_photos'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            echo "✓ Created directory: $dir<br>\n";
        } else {
            echo "✓ Directory already exists: $dir<br>\n";
        }
    }
    
    echo "<h2>Setup Complete!</h2>\n";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<strong>Site Inspector Dashboard is now ready!</strong><br><br>\n";
    echo "<strong>Login Credentials:</strong><br>\n";
    echo "Email: inspector@buildhub.com<br>\n";
    echo "Password: inspector123<br><br>\n";
    echo "<strong>Features Available:</strong><br>\n";
    echo "• View assigned construction projects<br>\n";
    echo "• Create detailed inspection reports<br>\n";
    echo "• Upload geo-tagged photos and documents<br>\n";
    echo "• Track inspection history<br>\n";
    echo "• Structured checklist system<br>\n";
    echo "• Automatic notifications to stakeholders<br>\n";
    echo "</div>\n";
    
    echo "<h3>Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Access the Site Inspector Dashboard at: <strong>/site-inspector-dashboard</strong></li>\n";
    echo "<li>Login with the credentials above</li>\n";
    echo "<li>Assign more projects to inspectors via the admin dashboard</li>\n";
    echo "<li>Create inspection reports and upload photos</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<strong>Setup Error:</strong><br>\n";
    echo $e->getMessage() . "<br>\n";
    echo "</div>\n";
    
    error_log("Site Inspector Setup Error: " . $e->getMessage());
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
h2 {
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}
</style>
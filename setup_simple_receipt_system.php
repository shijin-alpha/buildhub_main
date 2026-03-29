<?php
/**
 * Setup Simple Receipt System
 * Creates the database table and upload directories
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Setting up Simple Receipt System...</h2>\n";
    
    // Create the simple_receipts table
    $sql = "
    CREATE TABLE IF NOT EXISTS `simple_receipts` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` int(11) NOT NULL,
      `contractor_id` int(11) NOT NULL,
      `homeowner_id` int(11) NOT NULL,
      `receipt_title` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `file_path` varchar(500) NOT NULL,
      `original_filename` varchar(255) NOT NULL,
      `file_size` int(11) NOT NULL,
      `mime_type` varchar(100) NOT NULL,
      `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_project` (`project_id`),
      KEY `idx_contractor` (`contractor_id`),
      KEY `idx_homeowner` (`homeowner_id`),
      KEY `idx_upload_date` (`upload_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $db->exec($sql);
    echo "✅ Database table 'simple_receipts' created successfully<br>\n";
    
    // Create upload directories
    $upload_base = 'backend/uploads/simple_receipts';
    if (!file_exists($upload_base)) {
        mkdir($upload_base, 0755, true);
        echo "✅ Upload directory created: {$upload_base}<br>\n";
    } else {
        echo "✅ Upload directory already exists: {$upload_base}<br>\n";
    }
    
    // Test database connection
    $test_query = $db->query("SELECT COUNT(*) as count FROM simple_receipts");
    $result = $test_query->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database connection test passed - Current receipts: {$result['count']}<br>\n";
    
    echo "<br><h3>🎉 Simple Receipt System Setup Complete!</h3>\n";
    echo "<p><strong>What was created:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Database table: <code>simple_receipts</code></li>\n";
    echo "<li>Upload directory: <code>backend/uploads/simple_receipts/</code></li>\n";
    echo "<li>API endpoints: Upload and viewing APIs</li>\n";
    echo "<li>React components: Upload modal, viewer, and manager</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>How to use:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Import SimpleReceiptManager component</li>\n";
    echo "<li>Pass projectId and userRole props</li>\n";
    echo "<li>Contractors can upload receipts</li>\n";
    echo "<li>Both roles can view all receipts</li>\n";
    echo "</ol>\n";
    
    echo "<p><a href='test_simple_receipt_system.html'>📋 View System Documentation</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up system: " . $e->getMessage() . "<br>\n";
    echo "Please check your database connection and try again.<br>\n";
}
?>
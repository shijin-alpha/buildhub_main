<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up Stage Payment Management System...\n";
    
    // Read and execute the SQL file
    $sql_content = file_get_contents(__DIR__ . '/database/create_stage_payment_tables.sql');
    
    // Split SQL statements by semicolon and execute each one
    $statements = explode(';', $sql_content);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // Skip errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "Stage Payment Management System setup completed successfully!\n";
    
    // Check if tables were created
    $tables = ['construction_stage_payments', 'project_stage_payment_requests', 'project_payment_schedule', 'payment_notifications'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' created successfully\n";
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
    
    // Check data insertion
    $stmt = $db->query("SELECT COUNT(*) FROM construction_stage_payments");
    $stages_count = $stmt->fetchColumn();
    echo "✓ Inserted $stages_count construction stage payment structures\n";
    
} catch (Exception $e) {
    echo "Error setting up Stage Payment Management System: " . $e->getMessage() . "\n";
}
?>
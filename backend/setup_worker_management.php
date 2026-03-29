<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up Worker Management System...\n";
    
    // Read and execute the SQL file
    $sql_content = file_get_contents(__DIR__ . '/database/create_worker_management_tables.sql');
    
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
    
    echo "Worker Management System setup completed successfully!\n";
    
    // Check if tables were created
    $tables = ['worker_types', 'construction_phases', 'phase_worker_requirements', 'contractor_workers', 'progress_worker_assignments'];
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' created successfully\n";
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
    
    // Check data insertion
    $stmt = $db->prepare("SELECT COUNT(*) FROM worker_types");
    $stmt->execute();
    $worker_types_count = $stmt->fetchColumn();
    echo "✓ Inserted $worker_types_count worker types\n";
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM construction_phases");
    $stmt->execute();
    $phases_count = $stmt->fetchColumn();
    echo "✓ Inserted $phases_count construction phases\n";
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM phase_worker_requirements");
    $stmt->execute();
    $requirements_count = $stmt->fetchColumn();
    echo "✓ Inserted $requirements_count phase worker requirements\n";
    
} catch (Exception $e) {
    echo "Error setting up Worker Management System: " . $e->getMessage() . "\n";
}
?>
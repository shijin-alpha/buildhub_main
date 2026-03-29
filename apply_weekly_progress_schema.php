<?php
/**
 * Apply Weekly Progress Summary Schema
 * Creates the weekly_progress_summary table and related tables
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting schema migration for weekly progress summary...\n\n";
    
    // Create weekly_progress_summary table
    echo "Creating weekly_progress_summary table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS weekly_progress_summary (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            week_start_date DATE NOT NULL,
            week_end_date DATE NOT NULL,
            stages_worked JSON NOT NULL,
            start_progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            end_progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            total_labour_used JSON NOT NULL,
            delays_and_reasons TEXT NULL,
            weekly_remarks TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_project_week (project_id, week_start_date),
            INDEX idx_contractor_week (contractor_id, week_start_date),
            INDEX idx_week_range (week_start_date, week_end_date),
            
            UNIQUE KEY unique_weekly_update (project_id, contractor_id, week_start_date),
            
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✓ weekly_progress_summary table created successfully\n\n";
    
    // Verify table creation
    $check = $db->query("SHOW TABLES LIKE 'weekly_progress_summary'");
    if ($check->rowCount() > 0) {
        echo "✓ Table verification successful\n";
        
        // Show table structure
        $structure = $db->query("DESCRIBE weekly_progress_summary");
        echo "\nTable structure:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-30s %-20s %-10s\n", "Field", "Type", "Null");
        echo str_repeat("-", 80) . "\n";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            printf("%-30s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
        }
        echo str_repeat("-", 80) . "\n";
    } else {
        echo "✗ Table verification failed\n";
    }
    
    echo "\n✓ Schema migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

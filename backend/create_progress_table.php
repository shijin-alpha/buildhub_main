<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating construction_progress_updates table...\n";
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS construction_progress_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            stage_name VARCHAR(100) NOT NULL,
            stage_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL,
            completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            remarks TEXT NULL,
            delay_reason VARCHAR(100) NULL,
            delay_description TEXT NULL,
            photo_paths JSON NULL,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            location_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_project_contractor (project_id, contractor_id),
            INDEX idx_homeowner (homeowner_id),
            INDEX idx_stage (stage_name, stage_status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ Created construction_progress_updates table\n";
    
    // Now create the worker assignments table
    $db->exec("
        CREATE TABLE IF NOT EXISTS progress_worker_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            progress_update_id INT NOT NULL,
            worker_id INT NOT NULL,
            work_date DATE NOT NULL,
            hours_worked DECIMAL(4,2) DEFAULT 8.00,
            overtime_hours DECIMAL(4,2) DEFAULT 0.00,
            daily_wage DECIMAL(8,2) NOT NULL,
            overtime_rate DECIMAL(8,2) DEFAULT 0.00,
            total_payment DECIMAL(10,2) GENERATED ALWAYS AS (
                (hours_worked * daily_wage / 8) + (overtime_hours * overtime_rate)
            ) STORED,
            work_description TEXT NULL,
            performance_rating ENUM('excellent', 'good', 'average', 'poor') NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (progress_update_id) REFERENCES construction_progress_updates(id) ON DELETE CASCADE,
            FOREIGN KEY (worker_id) REFERENCES contractor_workers(id) ON DELETE CASCADE,
            INDEX idx_progress_worker (progress_update_id, worker_id),
            INDEX idx_work_date (work_date),
            INDEX idx_worker_date (worker_id, work_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ Created progress_worker_assignments table\n";
    
    echo "All tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
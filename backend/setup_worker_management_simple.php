<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up Worker Management System...\n";
    
    // 1. Create worker_types table
    $db->exec("
        CREATE TABLE IF NOT EXISTS worker_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_name VARCHAR(100) NOT NULL,
            category ENUM('skilled', 'semi_skilled', 'unskilled') NOT NULL,
            description TEXT NULL,
            base_wage_per_day DECIMAL(8,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_worker_type (type_name),
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created worker_types table\n";
    
    // 2. Create construction_phases table
    $db->exec("
        CREATE TABLE IF NOT EXISTS construction_phases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phase_name VARCHAR(100) NOT NULL,
            phase_order INT NOT NULL,
            description TEXT NULL,
            typical_duration_days INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_phase_name (phase_name),
            INDEX idx_phase_order (phase_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created construction_phases table\n";
    
    // 3. Create phase_worker_requirements table
    $db->exec("
        CREATE TABLE IF NOT EXISTS phase_worker_requirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phase_id INT NOT NULL,
            worker_type_id INT NOT NULL,
            is_required BOOLEAN DEFAULT TRUE,
            min_workers INT DEFAULT 1,
            max_workers INT DEFAULT 10,
            priority_level ENUM('essential', 'important', 'optional') DEFAULT 'important',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (phase_id) REFERENCES construction_phases(id) ON DELETE CASCADE,
            FOREIGN KEY (worker_type_id) REFERENCES worker_types(id) ON DELETE CASCADE,
            UNIQUE KEY unique_phase_worker (phase_id, worker_type_id),
            INDEX idx_phase_priority (phase_id, priority_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created phase_worker_requirements table\n";
    
    // 4. Create contractor_workers table
    $db->exec("
        CREATE TABLE IF NOT EXISTS contractor_workers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            worker_name VARCHAR(255) NOT NULL,
            worker_type_id INT NOT NULL,
            experience_years INT DEFAULT 0,
            skill_level ENUM('apprentice', 'junior', 'senior', 'master') DEFAULT 'junior',
            daily_wage DECIMAL(8,2) NOT NULL,
            phone_number VARCHAR(20) NULL,
            is_available BOOLEAN DEFAULT TRUE,
            is_main_worker BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (worker_type_id) REFERENCES worker_types(id) ON DELETE CASCADE,
            INDEX idx_contractor_available (contractor_id, is_available),
            INDEX idx_worker_type_skill (worker_type_id, skill_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created contractor_workers table\n";
    
    // 5. Create progress_worker_assignments table
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
    
    // Insert default worker types
    $worker_types = [
        ['Mason', 'skilled', 'Skilled in brickwork, plastering, and masonry', 800.00],
        ['Carpenter', 'skilled', 'Skilled in woodwork, formwork, and finishing', 750.00],
        ['Electrician', 'skilled', 'Electrical installations and wiring', 900.00],
        ['Plumber', 'skilled', 'Plumbing installations and pipe work', 850.00],
        ['Welder', 'skilled', 'Metal welding and fabrication', 800.00],
        ['Painter', 'skilled', 'Wall painting and finishing work', 600.00],
        ['Tiler', 'skilled', 'Floor and wall tile installation', 700.00],
        ['Steel Fixer', 'skilled', 'Reinforcement steel work', 750.00],
        ['Assistant Mason', 'semi_skilled', 'Assists masons with mixing and preparation', 500.00],
        ['Assistant Carpenter', 'semi_skilled', 'Assists carpenters with cutting and preparation', 450.00],
        ['Assistant Electrician', 'semi_skilled', 'Assists with electrical installations', 550.00],
        ['Assistant Plumber', 'semi_skilled', 'Assists with plumbing work', 500.00],
        ['Machine Operator', 'semi_skilled', 'Operates construction machinery', 600.00],
        ['Helper', 'unskilled', 'General construction helper', 350.00],
        ['Laborer', 'unskilled', 'Manual labor and material handling', 300.00],
        ['Cleaner', 'unskilled', 'Site cleaning and maintenance', 250.00],
        ['Watchman', 'unskilled', 'Site security and monitoring', 300.00],
        ['Material Handler', 'unskilled', 'Loading and unloading materials', 350.00]
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO worker_types (type_name, category, description, base_wage_per_day) VALUES (?, ?, ?, ?)");
    foreach ($worker_types as $type) {
        $stmt->execute($type);
    }
    echo "✓ Inserted worker types\n";
    
    // Insert construction phases
    $phases = [
        ['Site Preparation', 1, 'Land clearing, excavation, and site setup', 7],
        ['Foundation', 2, 'Foundation excavation, concrete work, and curing', 14],
        ['Structure', 3, 'Column, beam, and slab construction', 21],
        ['Brickwork', 4, 'Wall construction and masonry work', 18],
        ['Roofing', 5, 'Roof structure and covering installation', 10],
        ['Electrical', 6, 'Electrical wiring and installations', 12],
        ['Plumbing', 7, 'Plumbing installations and pipe work', 10],
        ['Finishing', 8, 'Plastering, painting, and final touches', 15],
        ['Flooring', 9, 'Floor installation and finishing', 8],
        ['Final Inspection', 10, 'Quality checks and handover preparation', 3]
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO construction_phases (phase_name, phase_order, description, typical_duration_days) VALUES (?, ?, ?, ?)");
    foreach ($phases as $phase) {
        $stmt->execute($phase);
    }
    echo "✓ Inserted construction phases\n";
    
    echo "Worker Management System setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error setting up Worker Management System: " . $e->getMessage() . "\n";
}
?>
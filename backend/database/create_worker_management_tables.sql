-- Worker Management System for Construction Progress
-- This creates tables for managing workers based on construction phases

-- 1. Worker types and specializations
CREATE TABLE IF NOT EXISTS worker_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    category ENUM('skilled', 'semi_skilled', 'unskilled') NOT NULL,
    description TEXT NULL,
    base_wage_per_day DECIMAL(8,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_worker_type (type_name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Construction phases and required worker types
CREATE TABLE IF NOT EXISTS construction_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phase_name VARCHAR(100) NOT NULL,
    phase_order INT NOT NULL,
    description TEXT NULL,
    typical_duration_days INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_phase_name (phase_name),
    INDEX idx_phase_order (phase_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Phase worker requirements (which workers needed for each phase)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Available workers for contractors
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Worker assignments to progress updates
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Insert default worker types
INSERT IGNORE INTO worker_types (type_name, category, description, base_wage_per_day) VALUES
-- Skilled Workers
('Mason', 'skilled', 'Skilled in brickwork, plastering, and masonry', 800.00),
('Carpenter', 'skilled', 'Skilled in woodwork, formwork, and finishing', 750.00),
('Electrician', 'skilled', 'Electrical installations and wiring', 900.00),
('Plumber', 'skilled', 'Plumbing installations and pipe work', 850.00),
('Welder', 'skilled', 'Metal welding and fabrication', 800.00),
('Painter', 'skilled', 'Wall painting and finishing work', 600.00),
('Tiler', 'skilled', 'Floor and wall tile installation', 700.00),
('Steel Fixer', 'skilled', 'Reinforcement steel work', 750.00),

-- Semi-Skilled Workers  
('Assistant Mason', 'semi_skilled', 'Assists masons with mixing and preparation', 500.00),
('Assistant Carpenter', 'semi_skilled', 'Assists carpenters with cutting and preparation', 450.00),
('Assistant Electrician', 'semi_skilled', 'Assists with electrical installations', 550.00),
('Assistant Plumber', 'semi_skilled', 'Assists with plumbing work', 500.00),
('Machine Operator', 'semi_skilled', 'Operates construction machinery', 600.00),

-- Unskilled Workers
('Helper', 'unskilled', 'General construction helper', 350.00),
('Laborer', 'unskilled', 'Manual labor and material handling', 300.00),
('Cleaner', 'unskilled', 'Site cleaning and maintenance', 250.00),
('Watchman', 'unskilled', 'Site security and monitoring', 300.00),
('Material Handler', 'unskilled', 'Loading and unloading materials', 350.00);

-- 7. Insert construction phases
INSERT IGNORE INTO construction_phases (phase_name, phase_order, description, typical_duration_days) VALUES
('Site Preparation', 1, 'Land clearing, excavation, and site setup', 7),
('Foundation', 2, 'Foundation excavation, concrete work, and curing', 14),
('Structure', 3, 'Column, beam, and slab construction', 21),
('Brickwork', 4, 'Wall construction and masonry work', 18),
('Roofing', 5, 'Roof structure and covering installation', 10),
('Electrical', 6, 'Electrical wiring and installations', 12),
('Plumbing', 7, 'Plumbing installations and pipe work', 10),
('Finishing', 8, 'Plastering, painting, and final touches', 15),
('Flooring', 9, 'Floor installation and finishing', 8),
('Final Inspection', 10, 'Quality checks and handover preparation', 3);

-- 8. Insert phase worker requirements
-- Site Preparation
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Site Preparation'),
    wt.id,
    CASE wt.type_name
        WHEN 'Machine Operator' THEN TRUE
        WHEN 'Laborer' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Machine Operator' THEN 1
        WHEN 'Laborer' THEN 4
        WHEN 'Helper' THEN 2
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Machine Operator' THEN 2
        WHEN 'Laborer' THEN 8
        WHEN 'Helper' THEN 4
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Machine Operator' THEN 'essential'
        WHEN 'Laborer' THEN 'essential'
        WHEN 'Helper' THEN 'important'
        ELSE 'optional'
    END
FROM worker_types wt
WHERE wt.type_name IN ('Machine Operator', 'Laborer', 'Helper', 'Watchman');

-- Foundation
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Foundation'),
    wt.id,
    CASE wt.type_name
        WHEN 'Mason' THEN TRUE
        WHEN 'Assistant Mason' THEN TRUE
        WHEN 'Steel Fixer' THEN TRUE
        WHEN 'Laborer' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 2
        WHEN 'Assistant Mason' THEN 2
        WHEN 'Steel Fixer' THEN 1
        WHEN 'Laborer' THEN 4
        WHEN 'Helper' THEN 2
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 4
        WHEN 'Assistant Mason' THEN 4
        WHEN 'Steel Fixer' THEN 3
        WHEN 'Laborer' THEN 8
        WHEN 'Helper' THEN 4
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 'essential'
        WHEN 'Steel Fixer' THEN 'essential'
        WHEN 'Assistant Mason' THEN 'important'
        WHEN 'Laborer' THEN 'important'
        WHEN 'Helper' THEN 'important'
        ELSE 'optional'
    END
FROM worker_types wt
WHERE wt.type_name IN ('Mason', 'Assistant Mason', 'Steel Fixer', 'Laborer', 'Helper', 'Machine Operator');

-- Structure
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Structure'),
    wt.id,
    CASE wt.type_name
        WHEN 'Mason' THEN TRUE
        WHEN 'Assistant Mason' THEN TRUE
        WHEN 'Steel Fixer' THEN TRUE
        WHEN 'Carpenter' THEN TRUE
        WHEN 'Assistant Carpenter' THEN TRUE
        WHEN 'Welder' THEN TRUE
        WHEN 'Laborer' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 3
        WHEN 'Assistant Mason' THEN 3
        WHEN 'Steel Fixer' THEN 2
        WHEN 'Carpenter' THEN 2
        WHEN 'Assistant Carpenter' THEN 2
        WHEN 'Welder' THEN 1
        WHEN 'Laborer' THEN 6
        WHEN 'Helper' THEN 4
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 6
        WHEN 'Assistant Mason' THEN 6
        WHEN 'Steel Fixer' THEN 4
        WHEN 'Carpenter' THEN 4
        WHEN 'Assistant Carpenter' THEN 4
        WHEN 'Welder' THEN 2
        WHEN 'Laborer' THEN 10
        WHEN 'Helper' THEN 6
        ELSE 0
    END,
    'essential'
FROM worker_types wt
WHERE wt.type_name IN ('Mason', 'Assistant Mason', 'Steel Fixer', 'Carpenter', 'Assistant Carpenter', 'Welder', 'Laborer', 'Helper');

-- Brickwork
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Brickwork'),
    wt.id,
    CASE wt.type_name
        WHEN 'Mason' THEN TRUE
        WHEN 'Assistant Mason' THEN TRUE
        WHEN 'Laborer' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 4
        WHEN 'Assistant Mason' THEN 4
        WHEN 'Laborer' THEN 4
        WHEN 'Helper' THEN 2
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 8
        WHEN 'Assistant Mason' THEN 8
        WHEN 'Laborer' THEN 6
        WHEN 'Helper' THEN 4
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 'essential'
        WHEN 'Assistant Mason' THEN 'essential'
        WHEN 'Laborer' THEN 'important'
        WHEN 'Helper' THEN 'important'
        ELSE 'optional'
    END
FROM worker_types wt
WHERE wt.type_name IN ('Mason', 'Assistant Mason', 'Laborer', 'Helper');

-- Electrical
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Electrical'),
    wt.id,
    CASE wt.type_name
        WHEN 'Electrician' THEN TRUE
        WHEN 'Assistant Electrician' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Electrician' THEN 2
        WHEN 'Assistant Electrician' THEN 2
        WHEN 'Helper' THEN 1
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Electrician' THEN 4
        WHEN 'Assistant Electrician' THEN 4
        WHEN 'Helper' THEN 2
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Electrician' THEN 'essential'
        WHEN 'Assistant Electrician' THEN 'important'
        WHEN 'Helper' THEN 'important'
        ELSE 'optional'
    END
FROM worker_types wt
WHERE wt.type_name IN ('Electrician', 'Assistant Electrician', 'Helper');

-- Plumbing
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Plumbing'),
    wt.id,
    CASE wt.type_name
        WHEN 'Plumber' THEN TRUE
        WHEN 'Assistant Plumber' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Plumber' THEN 2
        WHEN 'Assistant Plumber' THEN 2
        WHEN 'Helper' THEN 1
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Plumber' THEN 4
        WHEN 'Assistant Plumber' THEN 4
        WHEN 'Helper' THEN 2
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Plumber' THEN 'essential'
        WHEN 'Assistant Plumber' THEN 'important'
        WHEN 'Helper' THEN 'important'
        ELSE 'optional'
    END
FROM worker_types wt
WHERE wt.type_name IN ('Plumber', 'Assistant Plumber', 'Helper');

-- Finishing
INSERT IGNORE INTO phase_worker_requirements (phase_id, worker_type_id, is_required, min_workers, max_workers, priority_level)
SELECT 
    (SELECT id FROM construction_phases WHERE phase_name = 'Finishing'),
    wt.id,
    CASE wt.type_name
        WHEN 'Mason' THEN TRUE
        WHEN 'Painter' THEN TRUE
        WHEN 'Tiler' THEN TRUE
        WHEN 'Carpenter' THEN TRUE
        WHEN 'Helper' THEN TRUE
        ELSE FALSE
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 2
        WHEN 'Painter' THEN 2
        WHEN 'Tiler' THEN 1
        WHEN 'Carpenter' THEN 1
        WHEN 'Helper' THEN 2
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 4
        WHEN 'Painter' THEN 4
        WHEN 'Tiler' THEN 3
        WHEN 'Carpenter' THEN 3
        WHEN 'Helper' THEN 4
        ELSE 0
    END,
    CASE wt.type_name
        WHEN 'Mason' THEN 'essential'
        WHEN 'Painter' THEN 'essential'
        WHEN 'Tiler' THEN 'important'
        WHEN 'Carpenter' THEN 'important'
        WHEN 'Helper' THEN 'important'
        ELSE 'optional'
    END
FROM worker_types wt
WHERE wt.type_name IN ('Mason', 'Painter', 'Tiler', 'Carpenter', 'Helper');

-- 9. Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_worker_assignments_date ON progress_worker_assignments(work_date, worker_id);
CREATE INDEX IF NOT EXISTS idx_contractor_workers_type ON contractor_workers(contractor_id, worker_type_id, is_available);
CREATE INDEX IF NOT EXISTS idx_phase_requirements_priority ON phase_worker_requirements(phase_id, priority_level, is_required);

-- 10. Create view for easy worker selection
CREATE OR REPLACE VIEW contractor_worker_summary AS
SELECT 
    cw.id as worker_id,
    cw.contractor_id,
    cw.worker_name,
    cw.skill_level,
    cw.daily_wage,
    cw.is_main_worker,
    cw.is_available,
    cw.experience_years,
    wt.type_name as worker_type,
    wt.category as worker_category,
    wt.base_wage_per_day as base_wage,
    CASE 
        WHEN cw.is_main_worker THEN 'Main Worker'
        WHEN cw.skill_level = 'master' THEN 'Master'
        WHEN cw.skill_level = 'senior' THEN 'Senior'
        WHEN cw.skill_level = 'junior' THEN 'Junior'
        ELSE 'Apprentice'
    END as worker_role,
    CASE 
        WHEN cw.daily_wage > wt.base_wage_per_day * 1.5 THEN 'Premium'
        WHEN cw.daily_wage > wt.base_wage_per_day * 1.2 THEN 'Above Average'
        WHEN cw.daily_wage >= wt.base_wage_per_day * 0.8 THEN 'Standard'
        ELSE 'Below Average'
    END as wage_category
FROM contractor_workers cw
JOIN worker_types wt ON cw.worker_type_id = wt.id
WHERE cw.is_available = 1;

-- Success message
SELECT 'Worker Management System created successfully!' as message;
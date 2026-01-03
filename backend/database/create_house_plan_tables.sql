-- Custom House Plan Drawing Feature Tables

-- House plans created by architects
CREATE TABLE IF NOT EXISTS house_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    architect_id INT NOT NULL,
    layout_request_id INT NULL, -- Links to specific customer request
    plan_name VARCHAR(255) NOT NULL,
    plot_width DECIMAL(8,2) NOT NULL, -- in feet
    plot_height DECIMAL(8,2) NOT NULL, -- in feet
    plan_data JSON NOT NULL, -- Stores room positions, sizes, and properties
    total_area DECIMAL(10,2) NOT NULL, -- calculated total area in sqft
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    version INT DEFAULT 1,
    parent_plan_id INT NULL, -- For versioning/revisions
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_plan_id) REFERENCES house_plans(id) ON DELETE SET NULL,
    INDEX idx_architect_request (architect_id, layout_request_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room templates for quick placement
CREATE TABLE IF NOT EXISTS room_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('bedroom', 'bathroom', 'kitchen', 'living', 'dining', 'utility', 'outdoor', 'other') NOT NULL,
    default_width DECIMAL(6,2) NOT NULL, -- in feet
    default_height DECIMAL(6,2) NOT NULL, -- in feet
    min_width DECIMAL(6,2) NOT NULL,
    min_height DECIMAL(6,2) NOT NULL,
    max_width DECIMAL(6,2) NOT NULL,
    max_height DECIMAL(6,2) NOT NULL,
    color VARCHAR(7) DEFAULT '#e3f2fd', -- hex color for UI
    icon VARCHAR(50), -- icon name for UI
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Plan sharing and approval workflow
CREATE TABLE IF NOT EXISTS house_plan_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_plan_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'revision_requested') NOT NULL,
    feedback TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_plan_homeowner (house_plan_id, homeowner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default room templates with enhanced properties
INSERT INTO room_templates (name, category, default_width, default_height, min_width, min_height, max_width, max_height, color, icon) VALUES
('Master Bedroom', 'bedroom', 14, 12, 10, 10, 20, 16, '#e8f5e8', '🛏️'),
('Bedroom', 'bedroom', 12, 10, 8, 8, 16, 14, '#e8f5e8', '🛏️'),
('Living Room', 'living', 16, 14, 12, 10, 24, 20, '#fff3e0', '🛋️'),
('Kitchen', 'kitchen', 12, 8, 8, 6, 16, 12, '#fce4ec', '🍳'),
('Dining Room', 'dining', 12, 10, 8, 8, 16, 14, '#f3e5f5', '🍽️'),
('Bathroom', 'bathroom', 8, 6, 5, 4, 12, 10, '#e1f5fe', '🚿'),
('Master Bathroom', 'bathroom', 10, 8, 6, 5, 14, 12, '#e1f5fe', '🛁'),
('Utility Room', 'utility', 8, 6, 4, 4, 12, 10, '#f1f8e9', '🧹'),
('Balcony', 'outdoor', 8, 4, 4, 3, 16, 8, '#e8f5e8', '🌿'),
('Terrace', 'outdoor', 12, 8, 6, 4, 20, 16, '#e8f5e8', '🏡'),
('Study Room', 'other', 10, 8, 6, 6, 14, 12, '#fff8e1', '📚'),
('Store Room', 'utility', 6, 6, 4, 4, 10, 10, '#f5f5f5', '📦'),
('Pooja Room', 'other', 6, 6, 4, 4, 8, 8, '#fff3e0', '🕉️'),
('Entrance Hall', 'living', 8, 6, 4, 4, 12, 10, '#fff3e0', '🚪');
-- Create tables for contractor and architect dashboards

-- Layout requests from homeowners
CREATE TABLE IF NOT EXISTS layout_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    homeowner_id INT NOT NULL,
    plot_size VARCHAR(100) NOT NULL,
    budget_range VARCHAR(100) NOT NULL,
    requirements TEXT NOT NULL,
    preferred_style VARCHAR(100),
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Architect layouts/designs
CREATE TABLE IF NOT EXISTS architect_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    architect_id INT NOT NULL,
    layout_request_id INT NOT NULL,
    design_type ENUM('custom', 'template') NOT NULL,
    description TEXT NOT NULL,
    layout_file VARCHAR(255),
    template_id INT,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE
);

-- Contractor proposals
CREATE TABLE IF NOT EXISTS contractor_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    layout_request_id INT NOT NULL,
    materials TEXT NOT NULL,
    cost_breakdown TEXT NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    timeline VARCHAR(100) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE
);

-- Layout templates library
CREATE TABLE IF NOT EXISTS layout_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    style VARCHAR(100),
    rooms INT,
    preview_image VARCHAR(255),
    template_file VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample layout templates
INSERT INTO layout_templates (name, description, style, rooms) VALUES
('Modern Villa Template', 'Contemporary villa design with open spaces and large windows', 'Modern', 4),
('Traditional House Template', 'Classic house design with traditional architectural elements', 'Traditional', 3),
('Compact Home Template', 'Space-efficient design perfect for small plots', 'Compact', 2),
('Luxury Mansion Template', 'Grand mansion design with premium features', 'Luxury', 6),
('Eco-Friendly Home Template', 'Sustainable design with green building features', 'Eco-Friendly', 3);

-- Insert some sample layout requests for testing
INSERT INTO layout_requests (homeowner_id, plot_size, budget_range, requirements, preferred_style) VALUES
(1, '30x40 feet', '₹15-20 lakhs', 'Need a 3BHK house with modern kitchen and spacious living room', 'Modern'),
(1, '40x60 feet', '₹25-30 lakhs', 'Want a traditional style house with 4 bedrooms and garden space', 'Traditional'),
(1, '25x30 feet', '₹10-15 lakhs', 'Compact 2BHK house with efficient space utilization', 'Compact');

-- Create indexes for better performance
CREATE INDEX idx_layout_requests_homeowner ON layout_requests(homeowner_id);
CREATE INDEX idx_layout_requests_status ON layout_requests(status);
CREATE INDEX idx_architect_layouts_architect ON architect_layouts(architect_id);
CREATE INDEX idx_architect_layouts_request ON architect_layouts(layout_request_id);
CREATE INDEX idx_architect_layouts_status ON architect_layouts(status);
CREATE INDEX idx_contractor_proposals_contractor ON contractor_proposals(contractor_id);
CREATE INDEX idx_contractor_proposals_request ON contractor_proposals(layout_request_id);
CREATE INDEX idx_contractor_proposals_status ON contractor_proposals(status);
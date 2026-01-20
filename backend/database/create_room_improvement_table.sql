-- Create table for room improvement analyses
CREATE TABLE IF NOT EXISTS room_improvement_analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    homeowner_id INT NOT NULL,
    room_type ENUM('bedroom', 'living_room', 'kitchen', 'dining_room', 'other') NOT NULL,
    improvement_notes TEXT,
    image_path VARCHAR(255) NOT NULL,
    analysis_result JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_homeowner_id (homeowner_id),
    INDEX idx_room_type (room_type),
    INDEX idx_created_at (created_at)
);

-- Add comment to table
ALTER TABLE room_improvement_analyses 
COMMENT = 'Stores room improvement analysis results with AI-generated suggestions';
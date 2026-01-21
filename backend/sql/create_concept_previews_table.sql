-- Create concept_previews table for storing architectural concept preview generations
CREATE TABLE IF NOT EXISTS concept_previews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    architect_id INT NOT NULL,
    layout_request_id INT NOT NULL,
    job_id VARCHAR(255) UNIQUE,
    original_description TEXT NOT NULL,
    refined_prompt TEXT,
    status ENUM('processing', 'generating', 'completed', 'failed') DEFAULT 'processing',
    image_url VARCHAR(500),
    image_path VARCHAR(500),
    is_placeholder BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE,
    
    INDEX idx_architect_id (architect_id),
    INDEX idx_layout_request_id (layout_request_id),
    INDEX idx_status (status),
    INDEX idx_job_id (job_id),
    INDEX idx_created_at (created_at)
);
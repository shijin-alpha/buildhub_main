-- Create inbox_messages table for internal messaging system
CREATE TABLE IF NOT EXISTS inbox_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_type VARCHAR(50) NOT NULL DEFAULT 'general',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    metadata JSON NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_message_type (message_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_priority (priority)
);

-- Add some sample message types for reference
INSERT IGNORE INTO inbox_messages (recipient_id, sender_id, message_type, title, message, priority) VALUES
(1, 2, 'plan_saved', 'House Plan Saved', 'Your house plan has been saved successfully.', 'normal'),
(1, 2, 'plan_submitted', 'House Plan Submitted for Review', 'Your house plan has been submitted and is awaiting your review.', 'high'),
(1, 2, 'plan_updated', 'House Plan Updated', 'Your house plan has been updated with new changes.', 'normal')
ON DUPLICATE KEY UPDATE id=id;
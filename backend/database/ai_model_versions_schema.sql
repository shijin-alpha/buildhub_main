-- AI Model Versions Table
-- Tracks all trained model versions with performance metrics

CREATE TABLE IF NOT EXISTS ai_model_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_type VARCHAR(50) NOT NULL COMMENT 'cost_overrun or time_delay',
    model_version VARCHAR(20) NOT NULL COMMENT 'e.g., v1, v2, v3',
    accuracy DECIMAL(5,4) NOT NULL COMMENT 'Model accuracy score',
    precision_score DECIMAL(5,4) DEFAULT NULL COMMENT 'Precision metric',
    recall_score DECIMAL(5,4) DEFAULT NULL COMMENT 'Recall metric',
    f1_score DECIMAL(5,4) DEFAULT NULL COMMENT 'F1 score metric',
    trained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Training completion time',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this version is currently in use',
    notes TEXT DEFAULT NULL COMMENT 'Optional notes about this version',
    
    INDEX idx_model_type (model_type),
    INDEX idx_trained_at (trained_at),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_model_version (model_type, model_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ML model version tracking and metrics';

-- Insert initial versions for existing models
INSERT INTO ai_model_versions (model_type, model_version, accuracy, notes) 
VALUES 
    ('cost_overrun', 'v1', 0.8500, 'Initial baseline model'),
    ('time_delay', 'v1', 0.8200, 'Initial baseline model')
ON DUPLICATE KEY UPDATE accuracy = VALUES(accuracy);

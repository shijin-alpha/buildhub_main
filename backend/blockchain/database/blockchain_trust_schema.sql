-- Blockchain Trust Layer Database Schema
-- Contract Address: 0xf8e81D47203A594245E36C48e151709F0C19fBe8
-- 
-- This schema adds blockchain integration tables without modifying existing payment tables
-- All tables are prefixed with 'blockchain_' to avoid conflicts

-- Table: blockchain_trust_records
-- Stores local copies of blockchain proof data for quick access
CREATE TABLE IF NOT EXISTS blockchain_trust_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    operation_type ENUM('initiation', 'completion', 'verification') NOT NULL,
    proof_hash VARCHAR(66) NOT NULL, -- 0x + 64 hex chars
    metadata_hash VARCHAR(66) NOT NULL,
    verifier_type TINYINT NULL, -- 1=contractor, 2=admin (only for verification operations)
    blockchain_tx_hash VARCHAR(66) NULL, -- Transaction hash when recorded on blockchain
    blockchain_recorded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_proof_hash (proof_hash),
    INDEX idx_blockchain_tx_hash (blockchain_tx_hash),
    INDEX idx_created_at (created_at),
    
    UNIQUE KEY unique_payment_operation (payment_id, operation_type, verifier_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blockchain_integration_status
-- Tracks integration status and events for each payment
CREATE TABLE IF NOT EXISTS blockchain_integration_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blockchain_operation_queue
-- Queue for asynchronous blockchain operations
CREATE TABLE IF NOT EXISTS blockchain_operation_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL,
    parameters JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_method_name (method_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blockchain_operation_logs
-- Comprehensive logging of all blockchain operations
CREATE TABLE IF NOT EXISTS blockchain_operation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NULL,
    operation_type VARCHAR(50) NOT NULL,
    status ENUM('success', 'error', 'warning', 'info') NOT NULL,
    message TEXT NULL,
    error_message TEXT NULL,
    blockchain_tx_hash VARCHAR(66) NULL,
    gas_used INT NULL,
    gas_price BIGINT NULL,
    execution_time_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_blockchain_tx_hash (blockchain_tx_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blockchain_network_status
-- Tracks blockchain network health and connectivity
CREATE TABLE IF NOT EXISTS blockchain_network_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    network_name VARCHAR(20) NOT NULL DEFAULT 'sepolia',
    rpc_url VARCHAR(255) NOT NULL,
    contract_address VARCHAR(42) NOT NULL DEFAULT '0xf8e81D47203A594245E36C48e151709F0C19fBe8',
    is_accessible BOOLEAN DEFAULT FALSE,
    last_block_number BIGINT NULL,
    avg_gas_price BIGINT NULL,
    response_time_ms INT NULL,
    error_message TEXT NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_network_name (network_name),
    INDEX idx_contract_address (contract_address),
    INDEX idx_checked_at (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: payment_proof_data
-- Stores detailed proof data for audit purposes
CREATE TABLE IF NOT EXISTS payment_proof_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    proof_hash VARCHAR(66) NOT NULL,
    proof_type ENUM('initiation', 'completion', 'verification') NOT NULL,
    proof_data JSON NOT NULL, -- Contains the actual data used to generate the proof
    metadata JSON NULL, -- Additional metadata for the proof
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_proof_hash (proof_hash),
    INDEX idx_proof_type (proof_type),
    
    UNIQUE KEY unique_payment_proof (payment_id, proof_type, proof_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Add blockchain reference columns to existing payment_requests table
-- These columns are optional and won't break existing functionality
ALTER TABLE payment_requests 
ADD COLUMN blockchain_proof_hash VARCHAR(66) NULL AFTER amount,
ADD COLUMN blockchain_updated_at TIMESTAMP NULL AFTER blockchain_proof_hash,
ADD INDEX idx_blockchain_proof_hash (blockchain_proof_hash);

-- Optional: Add blockchain reference columns to existing stage_payments table
ALTER TABLE stage_payments 
ADD COLUMN blockchain_proof_hash VARCHAR(66) NULL AFTER amount,
ADD COLUMN blockchain_updated_at TIMESTAMP NULL AFTER blockchain_proof_hash,
ADD INDEX idx_blockchain_proof_hash (blockchain_proof_hash);

-- Optional: Add blockchain reference columns to existing custom_payment_requests table
ALTER TABLE custom_payment_requests 
ADD COLUMN blockchain_proof_hash VARCHAR(66) NULL AFTER amount,
ADD COLUMN blockchain_updated_at TIMESTAMP NULL AFTER blockchain_proof_hash,
ADD INDEX idx_blockchain_proof_hash (blockchain_proof_hash);

-- Create views for easy access to blockchain integration data

-- View: payment_blockchain_summary
-- Provides a summary of blockchain integration status for each payment
CREATE OR REPLACE VIEW payment_blockchain_summary AS
SELECT 
    p.id as payment_id,
    p.project_id,
    p.amount,
    p.stage,
    p.status as payment_status,
    p.created_at as payment_created_at,
    p.blockchain_proof_hash,
    p.blockchain_updated_at,
    COUNT(btr.id) as blockchain_operations,
    SUM(CASE WHEN btr.operation_type = 'initiation' THEN 1 ELSE 0 END) as initiation_recorded,
    SUM(CASE WHEN btr.operation_type = 'completion' THEN 1 ELSE 0 END) as completion_recorded,
    SUM(CASE WHEN btr.operation_type = 'verification' THEN 1 ELSE 0 END) as verifications_recorded,
    SUM(CASE WHEN btr.blockchain_tx_hash IS NOT NULL THEN 1 ELSE 0 END) as blockchain_confirmed,
    MAX(btr.blockchain_recorded_at) as last_blockchain_update,
    CASE 
        WHEN COUNT(btr.id) = 0 THEN 'not_integrated'
        WHEN SUM(CASE WHEN btr.blockchain_tx_hash IS NOT NULL THEN 1 ELSE 0 END) = 0 THEN 'pending'
        WHEN SUM(CASE WHEN btr.blockchain_tx_hash IS NOT NULL THEN 1 ELSE 0 END) < COUNT(btr.id) THEN 'partial'
        ELSE 'complete'
    END as blockchain_status
FROM payment_requests p
LEFT JOIN blockchain_trust_records btr ON p.id = btr.payment_id
GROUP BY p.id, p.project_id, p.amount, p.stage, p.status, p.created_at, p.blockchain_proof_hash, p.blockchain_updated_at;

-- View: blockchain_audit_trail
-- Provides complete audit trail for blockchain operations
CREATE OR REPLACE VIEW blockchain_audit_trail AS
SELECT 
    btr.payment_id,
    btr.operation_type,
    btr.proof_hash,
    btr.metadata_hash,
    btr.verifier_type,
    CASE 
        WHEN btr.verifier_type = 1 THEN 'contractor'
        WHEN btr.verifier_type = 2 THEN 'admin'
        ELSE NULL
    END as verifier_role,
    btr.blockchain_tx_hash,
    btr.blockchain_recorded_at,
    btr.created_at as local_recorded_at,
    CASE 
        WHEN btr.blockchain_tx_hash IS NOT NULL THEN 'confirmed'
        ELSE 'pending'
    END as blockchain_status,
    CONCAT('https://sepolia.etherscan.io/tx/', btr.blockchain_tx_hash) as explorer_url,
    ppd.proof_data,
    ppd.metadata as proof_metadata
FROM blockchain_trust_records btr
LEFT JOIN payment_proof_data ppd ON btr.payment_id = ppd.payment_id AND btr.proof_hash = ppd.proof_hash
ORDER BY btr.payment_id, btr.created_at;

-- View: blockchain_health_dashboard
-- Provides health metrics for blockchain integration
CREATE OR REPLACE VIEW blockchain_health_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM blockchain_trust_records) as total_records,
    (SELECT COUNT(*) FROM blockchain_trust_records WHERE blockchain_tx_hash IS NOT NULL) as confirmed_records,
    (SELECT COUNT(*) FROM blockchain_trust_records WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOURS)) as records_last_24h,
    (SELECT COUNT(*) FROM blockchain_operation_queue WHERE status = 'pending') as pending_operations,
    (SELECT COUNT(*) FROM blockchain_operation_queue WHERE status = 'failed') as failed_operations,
    (SELECT COUNT(DISTINCT payment_id) FROM blockchain_trust_records) as integrated_payments,
    (SELECT AVG(execution_time_ms) FROM blockchain_operation_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as avg_execution_time_ms,
    (SELECT COUNT(*) FROM blockchain_operation_logs WHERE status = 'error' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as errors_last_hour,
    '0xf8e81D47203A594245E36C48e151709F0C19fBe8' as contract_address,
    'sepolia' as network_name;

-- Insert initial network status record
INSERT INTO blockchain_network_status (
    network_name, 
    rpc_url, 
    contract_address, 
    is_accessible, 
    checked_at
) VALUES (
    'sepolia',
    'https://sepolia.infura.io/v3/YOUR_PROJECT_ID',
    '0xf8e81D47203A594245E36C48e151709F0C19fBe8',
    FALSE,
    NOW()
) ON DUPLICATE KEY UPDATE checked_at = NOW();

-- Create stored procedures for common operations

DELIMITER //

-- Procedure: get_payment_blockchain_status
-- Returns comprehensive blockchain status for a payment
CREATE PROCEDURE get_payment_blockchain_status(IN payment_id_param INT)
BEGIN
    SELECT 
        pbs.*,
        '0xf8e81D47203A594245E36C48e151709F0C19fBe8' as contract_address,
        'sepolia' as network,
        CONCAT('https://sepolia.etherscan.io/address/0xf8e81D47203A594245E36C48e151709F0C19fBe8') as contract_explorer_url
    FROM payment_blockchain_summary pbs
    WHERE pbs.payment_id = payment_id_param;
END //

-- Procedure: get_blockchain_health_metrics
-- Returns current health metrics for blockchain integration
CREATE PROCEDURE get_blockchain_health_metrics()
BEGIN
    SELECT 
        bhd.*,
        ROUND((confirmed_records / NULLIF(total_records, 0)) * 100, 2) as confirmation_rate_percent,
        CASE 
            WHEN errors_last_hour = 0 AND pending_operations < 10 THEN 'healthy'
            WHEN errors_last_hour < 5 AND pending_operations < 50 THEN 'warning'
            ELSE 'critical'
        END as health_status
    FROM blockchain_health_dashboard bhd;
END //

-- Procedure: cleanup_old_blockchain_logs
-- Cleans up old blockchain operation logs (run periodically)
CREATE PROCEDURE cleanup_old_blockchain_logs(IN retention_days INT DEFAULT 30)
BEGIN
    DELETE FROM blockchain_operation_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    DELETE FROM blockchain_integration_status 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    DELETE FROM blockchain_operation_queue 
    WHERE status IN ('completed', 'failed') 
    AND processed_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
END //

DELIMITER ;

-- Create triggers for automatic logging

DELIMITER //

-- Trigger: log_blockchain_record_changes
-- Automatically logs changes to blockchain trust records
CREATE TRIGGER log_blockchain_record_changes
AFTER UPDATE ON blockchain_trust_records
FOR EACH ROW
BEGIN
    IF NEW.blockchain_tx_hash IS NOT NULL AND OLD.blockchain_tx_hash IS NULL THEN
        INSERT INTO blockchain_operation_logs (
            payment_id, 
            operation_type, 
            status, 
            message, 
            blockchain_tx_hash
        ) VALUES (
            NEW.payment_id,
            CONCAT(NEW.operation_type, '_confirmed'),
            'success',
            'Blockchain transaction confirmed',
            NEW.blockchain_tx_hash
        );
    END IF;
END //

DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blockchain_trust_records TO 'buildhub_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blockchain_integration_status TO 'buildhub_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blockchain_operation_queue TO 'buildhub_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blockchain_operation_logs TO 'buildhub_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blockchain_network_status TO 'buildhub_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON payment_proof_data TO 'buildhub_user'@'localhost';

-- Create indexes for performance optimization
CREATE INDEX idx_blockchain_trust_records_composite ON blockchain_trust_records (payment_id, operation_type, blockchain_tx_hash);
CREATE INDEX idx_blockchain_operation_logs_composite ON blockchain_operation_logs (payment_id, status, created_at);
CREATE INDEX idx_blockchain_integration_status_composite ON blockchain_integration_status (payment_id, event_type, created_at);

-- Insert sample configuration data
INSERT INTO blockchain_network_status (
    network_name,
    rpc_url,
    contract_address,
    is_accessible,
    checked_at
) VALUES (
    'sepolia',
    'https://sepolia.infura.io/v3/YOUR_PROJECT_ID',
    '0xf8e81D47203A594245E36C48e151709F0C19fBe8',
    FALSE,
    NOW()
) ON DUPLICATE KEY UPDATE 
    rpc_url = VALUES(rpc_url),
    contract_address = VALUES(contract_address),
    checked_at = VALUES(checked_at);

-- Schema validation queries
-- Run these to verify the schema was created correctly

-- Check if all tables exist
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'blockchain_%'
ORDER BY TABLE_NAME;

-- Check if views exist
SELECT 
    TABLE_NAME as VIEW_NAME,
    VIEW_DEFINITION
FROM information_schema.VIEWS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE '%blockchain%'
ORDER BY TABLE_NAME;

-- Check if stored procedures exist
SELECT 
    ROUTINE_NAME,
    ROUTINE_TYPE,
    CREATED
FROM information_schema.ROUTINES 
WHERE ROUTINE_SCHEMA = DATABASE() 
AND ROUTINE_NAME LIKE '%blockchain%'
ORDER BY ROUTINE_NAME;
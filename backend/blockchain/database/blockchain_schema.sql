-- Blockchain Integration Database Schema
-- This schema supports the TrustLayer smart contract integration

-- Table to store blockchain payment records locally
CREATE TABLE IF NOT EXISTS blockchain_payment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proof_hash VARCHAR(66) UNIQUE NOT NULL,
    project_id INT,
    stage VARCHAR(50),
    amount DECIMAL(15,2),
    status ENUM('initiated', 'blockchain_pending', 'blockchain_confirmed', 'completed', 'failed') DEFAULT 'initiated',
    tx_hash VARCHAR(66),
    block_number BIGINT,
    confirmations INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    payment_data JSON,
    INDEX idx_proof_hash (proof_hash),
    INDEX idx_project_id (project_id),
    INDEX idx_status (status),
    INDEX idx_tx_hash (tx_hash)
);

-- Table to store blockchain verification records locally
CREATE TABLE IF NOT EXISTS blockchain_verification_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proof_hash VARCHAR(66) NOT NULL,
    verification_hash VARCHAR(66) NOT NULL,
    verifier_type TINYINT NOT NULL COMMENT '1=contractor, 2=admin',
    tx_hash VARCHAR(66),
    block_number BIGINT,
    confirmations INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    verification_data JSON,
    INDEX idx_proof_hash (proof_hash),
    INDEX idx_verification_hash (verification_hash),
    INDEX idx_verifier_type (verifier_type),
    INDEX idx_tx_hash (tx_hash),
    FOREIGN KEY (proof_hash) REFERENCES blockchain_payment_records(proof_hash) ON DELETE CASCADE
);

-- Table to store blockchain transaction logs
CREATE TABLE IF NOT EXISTS blockchain_transaction_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_hash VARCHAR(66) NOT NULL,
    contract_address VARCHAR(42) NOT NULL,
    method_name VARCHAR(100) NOT NULL,
    parameters JSON,
    gas_used BIGINT,
    gas_price BIGINT,
    block_number BIGINT,
    block_hash VARCHAR(66),
    status ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tx_hash (tx_hash),
    INDEX idx_contract_address (contract_address),
    INDEX idx_method_name (method_name),
    INDEX idx_status (status),
    INDEX idx_block_number (block_number)
);

-- Table to store authorized blockchain addresses
CREATE TABLE IF NOT EXISTS blockchain_authorized_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(42) UNIQUE NOT NULL,
    address_type ENUM('owner', 'recorder', 'admin') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    added_by VARCHAR(42),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_address (address),
    INDEX idx_address_type (address_type),
    INDEX idx_is_active (is_active)
);

-- Table to store blockchain network configuration
CREATE TABLE IF NOT EXISTS blockchain_network_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    network_name VARCHAR(50) NOT NULL,
    network_id INT NOT NULL,
    rpc_url VARCHAR(255) NOT NULL,
    explorer_url VARCHAR(255),
    contract_address VARCHAR(42) NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_network_name (network_name),
    INDEX idx_network_id (network_id),
    INDEX idx_is_active (is_active)
);

-- Table to store blockchain event logs
CREATE TABLE IF NOT EXISTS blockchain_event_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_hash VARCHAR(66) NOT NULL,
    contract_address VARCHAR(42) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    event_data JSON,
    block_number BIGINT,
    block_hash VARCHAR(66),
    log_index INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tx_hash (tx_hash),
    INDEX idx_contract_address (contract_address),
    INDEX idx_event_name (event_name),
    INDEX idx_block_number (block_number)
);

-- Insert default authorized address
INSERT INTO blockchain_authorized_addresses (address, address_type, is_active) 
VALUES ('0xf8e81D47203A594245E36C48e151709F0C19fBe8', 'owner', TRUE)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default network configuration
INSERT INTO blockchain_network_config (network_name, network_id, rpc_url, explorer_url, contract_address, is_active)
VALUES ('ethereum', 1, 'https://mainnet.infura.io/v3/YOUR_INFURA_PROJECT_ID', 'https://etherscan.io', '0xf8e81D47203A594245E36C48e151709F0C19fBe8', TRUE)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Create views for easier data access
CREATE OR REPLACE VIEW blockchain_payment_summary AS
SELECT 
    bpr.proof_hash,
    bpr.project_id,
    bpr.stage,
    bpr.amount,
    bpr.status,
    bpr.tx_hash,
    bpr.created_at,
    bpr.updated_at,
    COUNT(bvr.id) as verification_count,
    GROUP_CONCAT(bvr.verifier_type) as verifier_types
FROM blockchain_payment_records bpr
LEFT JOIN blockchain_verification_records bvr ON bpr.proof_hash = bvr.proof_hash
GROUP BY bpr.proof_hash;

CREATE OR REPLACE VIEW blockchain_transaction_summary AS
SELECT 
    btl.tx_hash,
    btl.contract_address,
    btl.method_name,
    btl.status,
    btl.block_number,
    btl.gas_used,
    btl.created_at,
    COUNT(bel.id) as event_count
FROM blockchain_transaction_logs btl
LEFT JOIN blockchain_event_logs bel ON btl.tx_hash = bel.tx_hash
GROUP BY btl.tx_hash;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetPaymentBlockchainStatus(IN p_proof_hash VARCHAR(66))
BEGIN
    SELECT 
        bpr.*,
        COUNT(bvr.id) as verification_count,
        btl.status as transaction_status,
        btl.block_number,
        btl.confirmations
    FROM blockchain_payment_records bpr
    LEFT JOIN blockchain_verification_records bvr ON bpr.proof_hash = bvr.proof_hash
    LEFT JOIN blockchain_transaction_logs btl ON bpr.tx_hash = btl.tx_hash
    WHERE bpr.proof_hash = p_proof_hash
    GROUP BY bpr.proof_hash;
END //

CREATE PROCEDURE GetProjectBlockchainActivity(IN p_project_id INT)
BEGIN
    SELECT 
        bpr.proof_hash,
        bpr.stage,
        bpr.amount,
        bpr.status,
        bpr.created_at,
        COUNT(bvr.id) as verification_count
    FROM blockchain_payment_records bpr
    LEFT JOIN blockchain_verification_records bvr ON bpr.proof_hash = bvr.proof_hash
    WHERE bpr.project_id = p_project_id
    GROUP BY bpr.proof_hash
    ORDER BY bpr.created_at DESC;
END //

DELIMITER ;
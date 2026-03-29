<?php

/**
 * Create Stored Procedures for Blockchain Integration
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating stored procedures...\n";
    
    // Drop existing procedures
    $db->exec("DROP PROCEDURE IF EXISTS GetPaymentBlockchainAudit");
    $db->exec("DROP PROCEDURE IF EXISTS UpdateBlockchainIntegrationStatus");
    
    // Create GetPaymentBlockchainAudit procedure
    $procedure1 = "
    CREATE PROCEDURE GetPaymentBlockchainAudit(IN payment_request_id INT)
    BEGIN
        SELECT 
            pba.*,
            btr.transaction_type,
            btr.blockchain_tx_hash,
            btr.status as blockchain_status,
            btr.block_number,
            btr.created_at as blockchain_record_created,
            btr.confirmed_at as blockchain_confirmed
        FROM payment_blockchain_audit pba
        LEFT JOIN blockchain_trust_records btr ON pba.proof_hash = btr.proof_hash
        WHERE pba.payment_request_id = payment_request_id
        ORDER BY btr.created_at ASC;
    END
    ";
    
    $db->exec($procedure1);
    echo "✅ Created GetPaymentBlockchainAudit procedure\n";
    
    // Create UpdateBlockchainIntegrationStatus procedure
    $procedure2 = "
    CREATE PROCEDURE UpdateBlockchainIntegrationStatus(
        IN p_payment_request_id INT,
        IN p_proof_hash VARCHAR(64),
        IN p_transaction_type VARCHAR(50),
        IN p_success BOOLEAN
    )
    BEGIN
        INSERT INTO blockchain_integration_status 
        (payment_request_id, proof_hash, last_blockchain_sync, sync_attempts)
        VALUES (p_payment_request_id, p_proof_hash, NOW(), 1)
        ON DUPLICATE KEY UPDATE
            last_blockchain_sync = NOW(),
            sync_attempts = sync_attempts + 1,
            initiation_recorded = CASE WHEN p_transaction_type = 'payment_initiation' AND p_success THEN TRUE ELSE initiation_recorded END,
            completion_recorded = CASE WHEN p_transaction_type = 'payment_completion' AND p_success THEN TRUE ELSE completion_recorded END,
            contractor_verification_recorded = CASE WHEN p_transaction_type = 'contractor_verification' AND p_success THEN TRUE ELSE contractor_verification_recorded END,
            admin_verification_recorded = CASE WHEN p_transaction_type = 'admin_verification' AND p_success THEN TRUE ELSE admin_verification_recorded END,
            last_error = CASE WHEN p_success THEN NULL ELSE last_error END;
    END
    ";
    
    $db->exec($procedure2);
    echo "✅ Created UpdateBlockchainIntegrationStatus procedure\n";
    
    echo "\n✅ All stored procedures created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating stored procedures: " . $e->getMessage() . "\n";
    exit(1);
}
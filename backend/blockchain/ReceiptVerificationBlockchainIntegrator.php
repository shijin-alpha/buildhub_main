<?php
/**
 * Receipt Verification Blockchain Integrator
 * 
 * Simple integration layer that adds blockchain hashing functionality
 * when payment receipts are verified by contractors or admins.
 * 
 * This integrator focuses specifically on receipt verification events
 * and generates cryptographic hashes for immutable audit trails.
 */

require_once __DIR__ . '/BlockchainTrustLayer.php';

class ReceiptVerificationBlockchainIntegrator {
    private $blockchainTrustLayer;
    private $db;
    private $enabled;
    
    public function __construct($database) {
        $this->db = $database;
        $this->enabled = true;
        
        try {
            $this->blockchainTrustLayer = new BlockchainTrustLayer($database);
        } catch (Exception $e) {
            error_log("Failed to initialize blockchain trust layer: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Record receipt verification on blockchain when contractor verifies receipt
     * 
     * @param array $paymentData Payment information
     * @param array $verificationData Verification details
     * @return array|null Blockchain record result
     */
    public function recordContractorReceiptVerification($paymentData, $verificationData) {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            // Prepare payment data for blockchain recording
            $blockchainPaymentData = [
                'payment_id' => $paymentData['payment_id'] ?? $paymentData['id'],
                'project_id' => $paymentData['project_id'],
                'amount' => $paymentData['requested_amount'] ?? $paymentData['amount'],
                'stage' => $paymentData['stage_name'] ?? $paymentData['stage'],
                'payment_type' => 'receipt_verification',
                'created_at' => time()
            ];
            
            // Prepare verification data for blockchain
            $blockchainVerificationData = [
                'type' => 'contractor_receipt_verification',
                'method' => 'manual_verification',
                'verification_status' => $verificationData['verification_status'],
                'verification_notes' => $verificationData['verification_notes'] ?? '',
                'contractor_id' => $verificationData['contractor_id'] ?? null,
                'verified_at' => time()
            ];
            
            // Record verification on blockchain using existing trust layer
            $result = $this->blockchainTrustLayer->recordVerification(
                $blockchainPaymentData, 
                $blockchainVerificationData, 
                1 // Contractor verifier type
            );
            
            if ($result) {
                // Store local record of blockchain integration
                $this->storeReceiptVerificationRecord(
                    $paymentData['payment_id'] ?? $paymentData['id'],
                    'contractor',
                    $result,
                    $verificationData['verification_status']
                );
                
                error_log("Receipt verification recorded on blockchain: Payment ID {$blockchainPaymentData['payment_id']}, Hash: {$result}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to record contractor receipt verification on blockchain: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Record receipt verification on blockchain when admin verifies receipt
     * 
     * @param array $paymentData Payment information
     * @param array $verificationData Verification details
     * @return array|null Blockchain record result
     */
    public function recordAdminReceiptVerification($paymentData, $verificationData) {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            // Prepare payment data for blockchain recording
            $blockchainPaymentData = [
                'payment_id' => $paymentData['payment_id'] ?? $paymentData['id'],
                'project_id' => $paymentData['project_id'],
                'amount' => $paymentData['requested_amount'] ?? $paymentData['amount'],
                'stage' => $paymentData['stage_name'] ?? $paymentData['stage'],
                'payment_type' => 'receipt_verification',
                'created_at' => time()
            ];
            
            // Prepare verification data for blockchain
            $blockchainVerificationData = [
                'type' => 'admin_receipt_verification',
                'method' => 'admin_approval',
                'verification_action' => $verificationData['verification_action'] ?? 'admin_approved',
                'admin_notes' => $verificationData['admin_notes'] ?? '',
                'admin_username' => $verificationData['admin_username'] ?? 'admin',
                'auto_progress_update' => $verificationData['auto_progress_update'] ?? false,
                'verified_at' => time()
            ];
            
            // Record verification on blockchain using existing trust layer
            $result = $this->blockchainTrustLayer->recordVerification(
                $blockchainPaymentData, 
                $blockchainVerificationData, 
                2 // Admin verifier type
            );
            
            if ($result) {
                // Store local record of blockchain integration
                $this->storeReceiptVerificationRecord(
                    $paymentData['payment_id'] ?? $paymentData['id'],
                    'admin',
                    $result,
                    $verificationData['verification_action'] ?? 'admin_approved'
                );
                
                error_log("Admin receipt verification recorded on blockchain: Payment ID {$blockchainPaymentData['payment_id']}, Hash: {$result}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to record admin receipt verification on blockchain: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate receipt verification hash for local storage
     * 
     * @param array $paymentData Payment information
     * @param array $verificationData Verification details
     * @param string $verifierType Type of verifier (contractor/admin)
     * @return string Generated hash
     */
    public function generateReceiptVerificationHash($paymentData, $verificationData, $verifierType) {
        $hashData = [
            'payment_id' => $paymentData['payment_id'] ?? $paymentData['id'],
            'project_id' => $paymentData['project_id'],
            'verifier_type' => $verifierType,
            'verification_timestamp' => time(),
            'verification_status' => $verificationData['verification_status'] ?? $verificationData['verification_action'],
            'has_receipt' => !empty($paymentData['receipt_file_path']),
            'stage' => $paymentData['stage_name'] ?? $paymentData['stage']
        ];
        
        // Sort the array keys manually for consistent hashing
        ksort($hashData);
        return hash('sha256', json_encode($hashData));
    }
    
    /**
     * Get receipt verification blockchain records for a payment
     * 
     * @param int $paymentId Payment ID
     * @return array|null Verification records
     */
    public function getReceiptVerificationRecords($paymentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    verifier_type,
                    blockchain_hash,
                    verification_status,
                    created_at
                FROM receipt_verification_blockchain_records 
                WHERE payment_id = ? 
                ORDER BY created_at ASC
            ");
            
            $stmt->execute([$paymentId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($records)) {
                return null;
            }
            
            return [
                'payment_id' => $paymentId,
                'total_verifications' => count($records),
                'verifications' => $records
            ];
            
        } catch (Exception $e) {
            error_log("Failed to get receipt verification records: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Store local record of receipt verification blockchain integration
     * 
     * @param int $paymentId Payment ID
     * @param string $verifierType Verifier type (contractor/admin)
     * @param string $blockchainHash Blockchain hash
     * @param string $verificationStatus Verification status
     */
    private function storeReceiptVerificationRecord($paymentId, $verifierType, $blockchainHash, $verificationStatus) {
        try {
            // Create table if it doesn't exist
            $this->createReceiptVerificationTable();
            
            $stmt = $this->db->prepare("
                INSERT INTO receipt_verification_blockchain_records (
                    payment_id,
                    verifier_type,
                    blockchain_hash,
                    verification_status,
                    created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $paymentId,
                $verifierType,
                $blockchainHash,
                $verificationStatus
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to store receipt verification record: " . $e->getMessage());
        }
    }
    
    /**
     * Create receipt verification blockchain records table
     */
    private function createReceiptVerificationTable() {
        try {
            $createTable = "
                CREATE TABLE IF NOT EXISTS receipt_verification_blockchain_records (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    payment_id INT NOT NULL,
                    verifier_type ENUM('contractor', 'admin') NOT NULL,
                    blockchain_hash VARCHAR(66) NOT NULL COMMENT 'Blockchain verification hash',
                    verification_status VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_payment_id (payment_id),
                    INDEX idx_verifier_type (verifier_type),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Blockchain records for receipt verification events'
            ";
            
            $this->db->exec($createTable);
            
        } catch (Exception $e) {
            error_log("Failed to create receipt verification table: " . $e->getMessage());
        }
    }
    
    /**
     * Check if blockchain integration is enabled
     * 
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Get blockchain health status
     * 
     * @return array Health status
     */
    public function getHealthStatus() {
        if (!$this->enabled) {
            return [
                'enabled' => false,
                'status' => 'disabled',
                'message' => 'Blockchain integration disabled'
            ];
        }
        
        try {
            $healthCheck = $this->blockchainTrustLayer->healthCheck();
            
            return [
                'enabled' => true,
                'status' => $healthCheck['contract_accessible'] ? 'healthy' : 'degraded',
                'blockchain_health' => $healthCheck,
                'message' => $healthCheck['contract_accessible'] ? 
                           'Blockchain integration operational' : 
                           'Blockchain integration degraded - local recording only'
            ];
            
        } catch (Exception $e) {
            return [
                'enabled' => true,
                'status' => 'error',
                'message' => 'Health check failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Static factory method for easy integration
     * 
     * @param object $database Database connection
     * @return ReceiptVerificationBlockchainIntegrator
     */
    public static function create($database) {
        return new self($database);
    }
}
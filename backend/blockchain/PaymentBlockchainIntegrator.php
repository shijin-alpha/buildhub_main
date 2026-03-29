<?php
/**
 * Payment Blockchain Integrator
 * 
 * Integration hooks for existing payment APIs to connect with blockchain trust layer
 * Contract Address: 0xf8e81D47203A594245E36C48e151709F0C19fBe8
 */

require_once __DIR__ . '/BlockchainTrustLayer.php';
require_once __DIR__ . '/config/blockchain_config.php';

class PaymentBlockchainIntegrator {
    private $trustLayer;
    private $db;
    private $config;
    
    public function __construct($database) {
        $this->db = $database;
        $this->config = getBlockchainConfig();
        
        if ($this->config['enabled']) {
            $this->trustLayer = new BlockchainTrustLayer($database);
        }
    }
    
    /**
     * Create integration hooks for existing payment system
     */
    public static function createIntegrationHooks($database) {
        return new self($database);
    }
    
    /**
     * Hook: Payment initiated
     * Call this after successful payment creation in existing APIs
     */
    public function onPaymentInitiated($paymentData) {
        if (!$this->isEnabled()) {
            return null;
        }
        
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Record on blockchain
            $proofHash = $this->trustLayer->recordPaymentInitiation($paymentData);
            
            // Update payment record with blockchain reference
            if ($proofHash) {
                $this->updatePaymentWithBlockchainRef($paymentData['payment_id'], 'initiation', $proofHash);
            }
            
            // Log integration event
            $this->logIntegrationEvent('payment_initiated', $paymentData['payment_id'], [
                'proof_hash' => $proofHash,
                'contract_address' => TRUST_CONTRACT_ADDRESS
            ]);
            
            return $proofHash;
            
        } catch (Exception $e) {
            $this->logIntegrationError('payment_initiation_failed', $paymentData['payment_id'], $e);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Hook: Payment completed
     * Call this after successful payment verification in existing APIs
     */
    public function onPaymentCompleted($paymentData, $completionData = []) {
        if (!$this->isEnabled()) {
            return null;
        }
        
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Prepare completion data
            $completionData = array_merge([
                'type' => 'standard',
                'method' => 'manual',
                'completed_at' => date('Y-m-d H:i:s')
            ], $completionData);
            
            // Record on blockchain
            $completionHash = $this->trustLayer->recordPaymentCompletion($paymentData, $completionData);
            
            // Update payment record
            if ($completionHash) {
                $this->updatePaymentWithBlockchainRef($paymentData['payment_id'], 'completion', $completionHash);
            }
            
            // Log integration event
            $this->logIntegrationEvent('payment_completed', $paymentData['payment_id'], [
                'completion_hash' => $completionHash,
                'completion_type' => $completionData['type']
            ]);
            
            return $completionHash;
            
        } catch (Exception $e) {
            $this->logIntegrationError('payment_completion_failed', $paymentData['payment_id'], $e);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Hook: Contractor verification
     * Call this after contractor confirms payment receipt
     */
    public function onContractorVerification($paymentData, $verificationData = []) {
        if (!$this->isEnabled() || !ENABLE_CONTRACTOR_VERIFICATION_RECORDING) {
            return null;
        }
        
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Prepare verification data
            $verificationData = array_merge([
                'type' => 'contractor_confirmation',
                'role' => 'contractor',
                'verified_at' => date('Y-m-d H:i:s')
            ], $verificationData);
            
            // Record on blockchain (verifier type 1 = contractor)
            $verificationHash = $this->trustLayer->recordVerification($paymentData, $verificationData, 1);
            
            // Update payment record
            if ($verificationHash) {
                $this->updatePaymentWithBlockchainRef($paymentData['payment_id'], 'contractor_verification', $verificationHash);
            }
            
            // Log integration event
            $this->logIntegrationEvent('contractor_verified', $paymentData['payment_id'], [
                'verification_hash' => $verificationHash,
                'verification_type' => $verificationData['type']
            ]);
            
            return $verificationHash;
            
        } catch (Exception $e) {
            $this->logIntegrationError('contractor_verification_failed', $paymentData['payment_id'], $e);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Hook: Admin verification
     * Call this after admin approves payment
     */
    public function onAdminVerification($paymentData, $verificationData = []) {
        if (!$this->isEnabled() || !ENABLE_ADMIN_VERIFICATION_RECORDING) {
            return null;
        }
        
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Prepare verification data
            $verificationData = array_merge([
                'type' => 'admin_approval',
                'role' => 'admin',
                'verified_at' => date('Y-m-d H:i:s')
            ], $verificationData);
            
            // Record on blockchain (verifier type 2 = admin)
            $verificationHash = $this->trustLayer->recordVerification($paymentData, $verificationData, 2);
            
            // Update payment record
            if ($verificationHash) {
                $this->updatePaymentWithBlockchainRef($paymentData['payment_id'], 'admin_verification', $verificationHash);
            }
            
            // Log integration event
            $this->logIntegrationEvent('admin_verified', $paymentData['payment_id'], [
                'verification_hash' => $verificationHash,
                'verification_type' => $verificationData['type']
            ]);
            
            return $verificationHash;
            
        } catch (Exception $e) {
            $this->logIntegrationError('admin_verification_failed', $paymentData['payment_id'], $e);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Get payment audit trail
     */
    public function getPaymentAuditTrail($paymentId) {
        if (!$this->isEnabled() || !ENABLE_AUDIT_TRAIL_API) {
            return null;
        }
        
        try {
            return $this->trustLayer->getPaymentAuditTrail($paymentId);
        } catch (Exception $e) {
            $this->logIntegrationError('audit_trail_retrieval_failed', $paymentId, $e);
            return null;
        }
    }
    
    /**
     * Get blockchain integration status for payment
     */
    public function getPaymentBlockchainStatus($paymentId) {
        try {
            $sql = "SELECT 
                        payment_id,
                        COUNT(*) as total_operations,
                        SUM(CASE WHEN operation_type = 'initiation' THEN 1 ELSE 0 END) as initiation_recorded,
                        SUM(CASE WHEN operation_type = 'completion' THEN 1 ELSE 0 END) as completion_recorded,
                        SUM(CASE WHEN operation_type = 'verification' THEN 1 ELSE 0 END) as verifications_recorded,
                        SUM(CASE WHEN blockchain_tx_hash IS NOT NULL THEN 1 ELSE 0 END) as blockchain_confirmed,
                        MAX(blockchain_recorded_at) as last_blockchain_update
                    FROM blockchain_trust_records 
                    WHERE payment_id = ?
                    GROUP BY payment_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $paymentId);
            $stmt->execute();
            
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result) {
                return [
                    'payment_id' => $paymentId,
                    'blockchain_integrated' => false,
                    'total_operations' => 0,
                    'contract_address' => TRUST_CONTRACT_ADDRESS
                ];
            }
            
            return array_merge($result, [
                'blockchain_integrated' => true,
                'integration_complete' => $result['blockchain_confirmed'] > 0,
                'contract_address' => TRUST_CONTRACT_ADDRESS,
                'explorer_url' => getAddressExplorerUrl(TRUST_CONTRACT_ADDRESS)
            ]);
            
        } catch (Exception $e) {
            $this->logIntegrationError('status_retrieval_failed', $paymentId, $e);
            return null;
        }
    }
    
    /**
     * Get contract statistics
     */
    public function getContractStatistics() {
        if (!$this->isEnabled()) {
            return null;
        }
        
        try {
            $blockchainStats = $this->trustLayer->getContractStats();
            $localStats = $this->getLocalIntegrationStats();
            
            return [
                'blockchain_stats' => $blockchainStats,
                'local_stats' => $localStats,
                'contract_address' => TRUST_CONTRACT_ADDRESS,
                'network' => $this->config['network'],
                'explorer_url' => getAddressExplorerUrl(TRUST_CONTRACT_ADDRESS)
            ];
            
        } catch (Exception $e) {
            $this->logIntegrationError('stats_retrieval_failed', 0, $e);
            return null;
        }
    }
    
    /**
     * Perform health check
     */
    public function healthCheck() {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'status' => 'disabled',
                'contract_address' => TRUST_CONTRACT_ADDRESS
            ];
        }
        
        try {
            $blockchainHealth = $this->trustLayer->healthCheck();
            $integrationHealth = $this->checkIntegrationHealth();
            
            return [
                'enabled' => true,
                'blockchain_health' => $blockchainHealth,
                'integration_health' => $integrationHealth,
                'contract_address' => TRUST_CONTRACT_ADDRESS,
                'overall_status' => $blockchainHealth['contract_accessible'] && $integrationHealth['database_accessible'] ? 'healthy' : 'degraded'
            ];
            
        } catch (Exception $e) {
            return [
                'enabled' => true,
                'status' => 'error',
                'error' => $e->getMessage(),
                'contract_address' => TRUST_CONTRACT_ADDRESS
            ];
        }
    }
    
    /**
     * Validate payment data
     */
    private function validatePaymentData($paymentData) {
        $required = ['payment_id', 'project_id'];
        
        foreach ($required as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        if (!is_numeric($paymentData['payment_id']) || $paymentData['payment_id'] <= 0) {
            throw new InvalidArgumentException("Invalid payment_id: {$paymentData['payment_id']}");
        }
        
        if (!is_numeric($paymentData['project_id']) || $paymentData['project_id'] <= 0) {
            throw new InvalidArgumentException("Invalid project_id: {$paymentData['project_id']}");
        }
    }
    
    /**
     * Update payment record with blockchain reference
     */
    private function updatePaymentWithBlockchainRef($paymentId, $operationType, $hash) {
        try {
            // Check if payment_requests table has blockchain columns
            $sql = "SHOW COLUMNS FROM payment_requests LIKE 'blockchain_proof_hash'";
            $result = $this->db->query($sql);
            
            if ($result->num_rows > 0) {
                $sql = "UPDATE payment_requests 
                        SET blockchain_proof_hash = ?, blockchain_updated_at = NOW() 
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('si', $hash, $paymentId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Silently fail if blockchain columns don't exist
            error_log("Failed to update payment with blockchain reference: " . $e->getMessage());
        }
    }
    
    /**
     * Log integration event
     */
    private function logIntegrationEvent($eventType, $paymentId, $data = []) {
        try {
            $sql = "INSERT INTO blockchain_integration_status 
                    (payment_id, event_type, event_data, created_at) 
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $eventData = json_encode($data);
            $stmt->bind_param('iss', $paymentId, $eventType, $eventData);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log integration event: " . $e->getMessage());
        }
    }
    
    /**
     * Log integration error
     */
    private function logIntegrationError($errorType, $paymentId, $exception) {
        try {
            $sql = "INSERT INTO blockchain_operation_logs 
                    (payment_id, operation_type, status, error_message, created_at) 
                    VALUES (?, ?, 'error', ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $errorMessage = $exception->getMessage();
            $stmt->bind_param('iss', $paymentId, $errorType, $errorMessage);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log integration error: " . $e->getMessage());
        }
        
        // Send alert if configured
        if (BLOCKCHAIN_ALERT_ON_FAILURE && BLOCKCHAIN_ALERT_EMAIL) {
            $this->sendErrorAlert($errorType, $paymentId, $exception);
        }
    }
    
    /**
     * Get local integration statistics
     */
    private function getLocalIntegrationStats() {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT payment_id) as total_payments_integrated,
                        COUNT(*) as total_operations,
                        SUM(CASE WHEN blockchain_tx_hash IS NOT NULL THEN 1 ELSE 0 END) as blockchain_confirmed_operations,
                        COUNT(DISTINCT CASE WHEN operation_type = 'initiation' THEN payment_id END) as payments_initiated,
                        COUNT(DISTINCT CASE WHEN operation_type = 'completion' THEN payment_id END) as payments_completed,
                        COUNT(CASE WHEN operation_type = 'verification' THEN 1 END) as total_verifications
                    FROM blockchain_trust_records";
            
            $result = $this->db->query($sql);
            return $result->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check integration health
     */
    private function checkIntegrationHealth() {
        try {
            // Test database connectivity
            $sql = "SELECT COUNT(*) as count FROM blockchain_trust_records LIMIT 1";
            $result = $this->db->query($sql);
            $databaseAccessible = $result !== false;
            
            // Check recent operations
            $sql = "SELECT COUNT(*) as recent_operations 
                    FROM blockchain_trust_records 
                    WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $result = $this->db->query($sql);
            $recentOperations = $result ? $result->fetch_assoc()['recent_operations'] : 0;
            
            return [
                'database_accessible' => $databaseAccessible,
                'recent_operations' => $recentOperations,
                'integration_active' => $recentOperations > 0
            ];
        } catch (Exception $e) {
            return [
                'database_accessible' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send error alert
     */
    private function sendErrorAlert($errorType, $paymentId, $exception) {
        try {
            $subject = "BuildHub Blockchain Integration Error - {$errorType}";
            $message = "Error in blockchain integration:\n\n";
            $message .= "Error Type: {$errorType}\n";
            $message .= "Payment ID: {$paymentId}\n";
            $message .= "Contract Address: " . TRUST_CONTRACT_ADDRESS . "\n";
            $message .= "Error Message: " . $exception->getMessage() . "\n";
            $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
            
            mail(BLOCKCHAIN_ALERT_EMAIL, $subject, $message);
        } catch (Exception $e) {
            error_log("Failed to send blockchain error alert: " . $e->getMessage());
        }
    }
    
    /**
     * Check if blockchain integration is enabled
     */
    private function isEnabled() {
        return $this->config['enabled'] && $this->trustLayer !== null;
    }
}
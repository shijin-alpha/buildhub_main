<?php
require_once __DIR__ . '/../config/blockchain_config.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * TrustLayer Service
 * 
 * Handles all interactions with the TrustLayer smart contract
 * including payment recording, verification, and status checking.
 */
class TrustLayerService {
    
    private $contractAddress;
    private $contractABI;
    private $db;
    private $logger;
    
    public function __construct() {
        $this->contractAddress = BlockchainConfig::getContractAddress();
        $this->contractABI = BlockchainConfig::getContractABI();
        $this->db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeLogger();
    }
    
    /**
     * Initialize logging system
     */
    private function initializeLogger() {
        // Simple file-based logging for blockchain operations
        $this->logger = function($message, $level = 'INFO') {
            if (BlockchainConfig::ENABLE_BLOCKCHAIN_LOGGING) {
                $timestamp = date('Y-m-d H:i:s');
                $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
                file_put_contents(__DIR__ . '/../../logs/blockchain.log', $logEntry, FILE_APPEND | LOCK_EX);
            }
        };
    }
    
    /**
     * Generate proof hash for payment
     */
    public function generateProofHash(array $paymentData): string {
        // Create a hash that doesn't include sensitive data
        $proofData = [
            'project_id' => $paymentData['project_id'] ?? '',
            'stage' => $paymentData['stage'] ?? '',
            'timestamp' => $paymentData['timestamp'] ?? time(),
            'type' => $paymentData['type'] ?? 'payment'
        ];
        
        return hash('sha256', json_encode($proofData));
    }
    
    /**
     * Generate metadata hash for payment
     */
    public function generateMetadataHash(array $paymentData): string {
        // Create metadata hash with non-sensitive categorization data
        $metadataData = [
            'amount_range' => $this->getAmountRange($paymentData['amount'] ?? 0),
            'stage_category' => $this->getStageCategory($paymentData['stage'] ?? ''),
            'payment_type' => $paymentData['type'] ?? 'standard',
            'verification_required' => $paymentData['verification_required'] ?? false
        ];
        
        return hash('sha256', json_encode($metadataData));
    }
    
    /**
     * Get amount range category (for privacy)
     */
    private function getAmountRange(float $amount): string {
        if ($amount < 1000) return 'small';
        if ($amount < 10000) return 'medium';
        if ($amount < 50000) return 'large';
        return 'xlarge';
    }
    
    /**
     * Get stage category
     */
    private function getStageCategory(string $stage): string {
        $stageMap = [
            'foundation' => 'structural',
            'framing' => 'structural',
            'roofing' => 'structural',
            'electrical' => 'systems',
            'plumbing' => 'systems',
            'finishing' => 'completion',
            'final' => 'completion'
        ];
        
        return $stageMap[strtolower($stage)] ?? 'general';
    }
    
    /**
     * Record payment initiation on blockchain
     */
    public function recordPaymentInitiation(array $paymentData): array {
        try {
            $proofHash = $this->generateProofHash($paymentData);
            $metadataHash = $this->generateMetadataHash($paymentData);
            $timestamp = time();
            
            // Store in local database first
            $this->storeLocalPaymentRecord($proofHash, $paymentData, 'initiated');
            
            // Log the blockchain operation
            ($this->logger)("Recording payment initiation - ProofHash: {$proofHash}", 'INFO');
            
            // In a real implementation, this would interact with the blockchain
            // For now, we'll simulate the blockchain interaction
            $txHash = $this->simulateBlockchainTransaction('recordPaymentInitiation', [
                'proofHash' => $proofHash,
                'metadataHash' => $metadataHash,
                'timestamp' => $timestamp
            ]);
            
            // Update local record with transaction hash
            $this->updateLocalPaymentRecord($proofHash, ['tx_hash' => $txHash, 'status' => 'blockchain_pending']);
            
            return [
                'success' => true,
                'proof_hash' => $proofHash,
                'metadata_hash' => $metadataHash,
                'tx_hash' => $txHash,
                'explorer_url' => BlockchainConfig::getTransactionUrl($txHash)
            ];
            
        } catch (Exception $e) {
            ($this->logger)("Error recording payment initiation: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Record payment completion on blockchain
     */
    public function recordPaymentCompletion(string $proofHash, array $completionData): array {
        try {
            $completionHash = hash('sha256', json_encode([
                'completed_at' => $completionData['completed_at'] ?? time(),
                'completion_type' => $completionData['type'] ?? 'standard',
                'verified_by' => $completionData['verified_by'] ?? 'system'
            ]));
            
            $timestamp = time();
            
            // Update local database
            $this->updateLocalPaymentRecord($proofHash, ['status' => 'completed', 'completed_at' => $timestamp]);
            
            // Log the blockchain operation
            ($this->logger)("Recording payment completion - ProofHash: {$proofHash}", 'INFO');
            
            // Simulate blockchain interaction
            $txHash = $this->simulateBlockchainTransaction('recordPaymentCompletion', [
                'proofHash' => $proofHash,
                'completionHash' => $completionHash,
                'timestamp' => $timestamp
            ]);
            
            return [
                'success' => true,
                'proof_hash' => $proofHash,
                'completion_hash' => $completionHash,
                'tx_hash' => $txHash,
                'explorer_url' => BlockchainConfig::getTransactionUrl($txHash)
            ];
            
        } catch (Exception $e) {
            ($this->logger)("Error recording payment completion: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Record verification on blockchain
     */
    public function recordVerification(string $proofHash, array $verificationData): array {
        try {
            $verificationHash = hash('sha256', json_encode([
                'verifier_type' => $verificationData['verifier_type'] ?? 1,
                'verification_data' => $verificationData['verification_data'] ?? '',
                'timestamp' => time()
            ]));
            
            $timestamp = time();
            $verifierType = $verificationData['verifier_type'] ?? 1; // 1=contractor, 2=admin
            
            // Store verification in local database
            $this->storeLocalVerificationRecord($proofHash, $verificationHash, $verifierType);
            
            // Log the blockchain operation
            ($this->logger)("Recording verification - ProofHash: {$proofHash}, Type: {$verifierType}", 'INFO');
            
            // Simulate blockchain interaction
            $txHash = $this->simulateBlockchainTransaction('recordVerification', [
                'proofHash' => $proofHash,
                'verificationHash' => $verificationHash,
                'timestamp' => $timestamp,
                'verifierType' => $verifierType
            ]);
            
            return [
                'success' => true,
                'proof_hash' => $proofHash,
                'verification_hash' => $verificationHash,
                'tx_hash' => $txHash,
                'explorer_url' => BlockchainConfig::getTransactionUrl($txHash)
            ];
            
        } catch (Exception $e) {
            ($this->logger)("Error recording verification: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payment status from blockchain
     */
    public function getPaymentStatus(string $proofHash): array {
        try {
            // Get from local database first
            $localRecord = $this->getLocalPaymentRecord($proofHash);
            
            if (!$localRecord) {
                return [
                    'exists' => false,
                    'completed' => false,
                    'verification_count' => 0
                ];
            }
            
            // In a real implementation, this would query the blockchain
            // For now, we'll use local data
            return [
                'exists' => true,
                'completed' => $localRecord['status'] === 'completed',
                'verification_count' => $this->getVerificationCount($proofHash),
                'local_record' => $localRecord
            ];
            
        } catch (Exception $e) {
            ($this->logger)("Error getting payment status: " . $e->getMessage(), 'ERROR');
            return [
                'exists' => false,
                'completed' => false,
                'verification_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Store payment record in local database
     */
    private function storeLocalPaymentRecord(string $proofHash, array $paymentData, string $status): void {
        $stmt = $this->db->prepare("
            INSERT INTO blockchain_payment_records 
            (proof_hash, project_id, stage, amount, status, created_at, payment_data) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE 
            status = VALUES(status), updated_at = NOW()
        ");
        
        $stmt->execute([
            $proofHash,
            $paymentData['project_id'] ?? null,
            $paymentData['stage'] ?? null,
            $paymentData['amount'] ?? null,
            $status,
            json_encode($paymentData)
        ]);
    }
    
    /**
     * Update payment record in local database
     */
    private function updateLocalPaymentRecord(string $proofHash, array $updateData): void {
        $setParts = [];
        $values = [];
        
        foreach ($updateData as $key => $value) {
            $setParts[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $setParts[] = "updated_at = NOW()";
        $values[] = $proofHash;
        
        $sql = "UPDATE blockchain_payment_records SET " . implode(', ', $setParts) . " WHERE proof_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
    
    /**
     * Get payment record from local database
     */
    private function getLocalPaymentRecord(string $proofHash): ?array {
        $stmt = $this->db->prepare("SELECT * FROM blockchain_payment_records WHERE proof_hash = ?");
        $stmt->execute([$proofHash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Store verification record in local database
     */
    private function storeLocalVerificationRecord(string $proofHash, string $verificationHash, int $verifierType): void {
        $stmt = $this->db->prepare("
            INSERT INTO blockchain_verification_records 
            (proof_hash, verification_hash, verifier_type, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$proofHash, $verificationHash, $verifierType]);
    }
    
    /**
     * Get verification count for a payment
     */
    private function getVerificationCount(string $proofHash): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM blockchain_verification_records WHERE proof_hash = ?");
        $stmt->execute([$proofHash]);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Simulate blockchain transaction (replace with real Web3 integration)
     */
    private function simulateBlockchainTransaction(string $method, array $params): string {
        // Generate a realistic-looking transaction hash
        $txData = $method . json_encode($params) . time() . rand(1000, 9999);
        return '0x' . hash('sha256', $txData);
    }
    
    /**
     * Get contract statistics
     */
    public function getContractStats(): array {
        try {
            // Get local statistics
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payments,
                    (SELECT COUNT(*) FROM blockchain_verification_records) as total_verifications
                FROM blockchain_payment_records
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'contract_address' => $this->contractAddress,
                'total_payments' => (int) $stats['total_payments'],
                'completed_payments' => (int) $stats['completed_payments'],
                'total_verifications' => (int) $stats['total_verifications'],
                'contract_active' => true // In real implementation, query from blockchain
            ];
            
        } catch (Exception $e) {
            ($this->logger)("Error getting contract stats: " . $e->getMessage(), 'ERROR');
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
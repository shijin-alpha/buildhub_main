<?php
/**
 * Blockchain Trust Layer Integration
 * 
 * Core class for integrating BuildHub payment system with Ethereum blockchain
 * Contract Address: 0xf8e81D47203A594245E36C48e151709F0C19fBe8
 */

require_once __DIR__ . '/config/blockchain_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use Web3\Providers\HttpProvider as Web3HttpProvider;
use Web3\Providers\HttpProvider;

class BlockchainTrustLayer {
    private $web3;
    private $contract;
    private $db;
    private $config;
    private $logger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->config = getBlockchainConfig();
        $this->initializeLogger(); // Initialize logger first
        
        try {
            // Only initialize Web3 if we have proper configuration
            if ($this->config['enabled'] && 
                $this->config['contract_address'] !== '0x0000000000000000000000000000000000000000') {
                $this->initializeWeb3();
                $this->initializeContract();
            }
        } catch (Exception $e) {
            // Don't fail completely, just log the error and continue with local database functionality
            error_log("Blockchain initialization failed: " . $e->getMessage());
            $this->config['enabled'] = false;
        }
    }
    
    /**
     * Initialize Web3 connection
     */
    private function initializeWeb3() {
        try {
            // Check if Web3 classes are available
            if (!class_exists('Web3\Providers\HttpProvider')) {
                throw new Exception("Web3 HttpProvider class not found");
            }
            
            $provider = new Web3HttpProvider($this->config['rpc_url']);
            $this->web3 = new Web3($provider);
            
            $this->log('INFO', 'Web3 connection initialized', [
                'network' => $this->config['network'],
                'rpc_url' => $this->config['rpc_url']
            ]);
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to initialize Web3', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Initialize smart contract
     */
    private function initializeContract() {
        try {
            $abi = getTrustContractABI();
            $this->contract = new Contract($this->web3->provider, $abi);
            $this->contract->at($this->config['contract_address']);
            
            $this->log('INFO', 'Smart contract initialized', [
                'address' => $this->config['contract_address'],
                'abi_functions' => count($abi)
            ]);
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to initialize contract', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Initialize logger
     */
    private function initializeLogger() {
        $this->logger = new class($this->config['log_file'], $this->config['log_level']) {
            private $logFile;
            private $logLevel;
            private $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARN' => 2, 'ERROR' => 3];
            
            public function __construct($logFile, $logLevel) {
                $this->logFile = $logFile;
                $this->logLevel = $logLevel;
            }
            
            public function log($level, $message, $context = []) {
                if ($this->levels[$level] >= $this->levels[$this->logLevel]) {
                    $timestamp = date('Y-m-d H:i:s');
                    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
                    $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
                    file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
                }
            }
        };
    }
    
    /**
     * Record payment initiation on blockchain
     */
    public function recordPaymentInitiation($paymentData) {
        if (!$this->config['enabled'] || !ENABLE_PAYMENT_INITIATION_RECORDING) {
            return null;
        }
        
        try {
            $proofHash = $this->generatePaymentProofHash($paymentData);
            $metadataHash = $this->generateMetadataHash($paymentData);
            $timestamp = time();
            
            // Store locally first
            $this->storeLocalProof($paymentData['payment_id'], 'initiation', $proofHash, $metadataHash);
            
            // Record on blockchain
            if ($this->config['async_mode']) {
                $this->queueBlockchainOperation('recordPaymentInitiation', [
                    $proofHash,
                    $metadataHash,
                    $timestamp
                ]);
            } else {
                $txHash = $this->executeContractMethod('recordPaymentInitiation', [
                    $proofHash,
                    $metadataHash,
                    $timestamp
                ]);
                
                $this->updateLocalProofWithTx($paymentData['payment_id'], 'initiation', $txHash);
            }
            
            $this->log('INFO', 'Payment initiation recorded', [
                'payment_id' => $paymentData['payment_id'],
                'proof_hash' => $proofHash,
                'contract_address' => $this->config['contract_address']
            ]);
            
            return $proofHash;
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to record payment initiation', [
                'payment_id' => $paymentData['payment_id'],
                'error' => $e->getMessage()
            ]);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Record payment completion on blockchain
     */
    public function recordPaymentCompletion($paymentData, $completionData) {
        if (!$this->config['enabled'] || !ENABLE_PAYMENT_COMPLETION_RECORDING) {
            return null;
        }
        
        try {
            $proofHash = $this->generatePaymentProofHash($paymentData);
            $completionHash = $this->generateCompletionHash($completionData);
            $timestamp = time();
            
            // Store locally first
            $this->storeLocalProof($paymentData['payment_id'], 'completion', $proofHash, $completionHash);
            
            // Record on blockchain
            if ($this->config['async_mode']) {
                $this->queueBlockchainOperation('recordPaymentCompletion', [
                    $proofHash,
                    $completionHash,
                    $timestamp
                ]);
            } else {
                $txHash = $this->executeContractMethod('recordPaymentCompletion', [
                    $proofHash,
                    $completionHash,
                    $timestamp
                ]);
                
                $this->updateLocalProofWithTx($paymentData['payment_id'], 'completion', $txHash);
            }
            
            $this->log('INFO', 'Payment completion recorded', [
                'payment_id' => $paymentData['payment_id'],
                'proof_hash' => $proofHash,
                'completion_hash' => $completionHash
            ]);
            
            return $completionHash;
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to record payment completion', [
                'payment_id' => $paymentData['payment_id'],
                'error' => $e->getMessage()
            ]);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Record verification on blockchain
     */
    public function recordVerification($paymentData, $verificationData, $verifierType) {
        if (!$this->config['enabled']) {
            return null;
        }
        
        $enabledFlag = $verifierType === 1 ? ENABLE_CONTRACTOR_VERIFICATION_RECORDING : ENABLE_ADMIN_VERIFICATION_RECORDING;
        if (!$enabledFlag) {
            return null;
        }
        
        try {
            $proofHash = $this->generatePaymentProofHash($paymentData);
            $verificationHash = $this->generateVerificationHash($verificationData);
            $timestamp = time();
            
            // Store locally first
            $this->storeLocalProof($paymentData['payment_id'], 'verification', $proofHash, $verificationHash, $verifierType);
            
            // Record on blockchain
            if ($this->config['async_mode']) {
                $this->queueBlockchainOperation('recordVerification', [
                    $proofHash,
                    $verificationHash,
                    $timestamp,
                    $verifierType
                ]);
            } else {
                $txHash = $this->executeContractMethod('recordVerification', [
                    $proofHash,
                    $verificationHash,
                    $timestamp,
                    $verifierType
                ]);
                
                $this->updateLocalProofWithTx($paymentData['payment_id'], 'verification', $txHash);
            }
            
            $this->log('INFO', 'Verification recorded', [
                'payment_id' => $paymentData['payment_id'],
                'proof_hash' => $proofHash,
                'verifier_type' => $verifierType === 1 ? 'contractor' : 'admin'
            ]);
            
            return $verificationHash;
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to record verification', [
                'payment_id' => $paymentData['payment_id'],
                'verifier_type' => $verifierType,
                'error' => $e->getMessage()
            ]);
            
            if (!$this->config['fail_silently']) {
                throw $e;
            }
            return null;
        }
    }
    
    /**
     * Get payment audit trail from blockchain
     */
    public function getPaymentAuditTrail($paymentId) {
        try {
            // Get local proof data
            $localProofs = $this->getLocalProofs($paymentId);
            if (empty($localProofs)) {
                return null;
            }
            
            $auditTrail = [];
            
            foreach ($localProofs as $proof) {
                $blockchainData = null;
                
                if ($proof['blockchain_tx_hash']) {
                    $blockchainData = $this->getBlockchainRecord($proof['proof_hash']);
                }
                
                $auditTrail[] = [
                    'type' => $proof['operation_type'],
                    'proof_hash' => $proof['proof_hash'],
                    'metadata_hash' => $proof['metadata_hash'],
                    'local_timestamp' => $proof['created_at'],
                    'blockchain_tx_hash' => $proof['blockchain_tx_hash'],
                    'blockchain_data' => $blockchainData,
                    'explorer_url' => $proof['blockchain_tx_hash'] ? getTransactionExplorerUrl($proof['blockchain_tx_hash']) : null
                ];
            }
            
            return $auditTrail;
            
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to get audit trail', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Execute contract method
     */
    private function executeContractMethod($method, $params) {
        $gasPrice = $this->estimateGasPrice();
        $gasLimit = $this->config['gas_limit'];
        
        $transaction = [
            'from' => $this->config['public_address'],
            'gas' => '0x' . dechex($gasLimit),
            'gasPrice' => '0x' . dechex($gasPrice)
        ];
        
        $result = null;
        
        // Combine parameters with transaction data
        $allParams = array_merge($params, [$transaction, function ($err, $txHash) use (&$result) {
            if ($err) {
                throw new Exception('Contract method execution failed: ' . $err->getMessage());
            }
            $result = $txHash;
        }]);
        
        // Call contract method with all parameters
        call_user_func_array([$this->contract, 'send'], array_merge([$method], $allParams));
        
        return $result;
    }
    
    /**
     * Generate payment proof hash
     */
    private function generatePaymentProofHash($paymentData) {
        $proofData = [
            'payment_id' => $paymentData['payment_id'],
            'project_id' => $paymentData['project_id'],
            'stage' => $paymentData['stage'] ?? '',
            'timestamp' => $paymentData['created_at'] ?? time(),
            'contract_address' => $this->config['contract_address']
        ];
        
        return '0x' . hash('sha256', json_encode($proofData));
    }
    
    /**
     * Generate metadata hash
     */
    private function generateMetadataHash($paymentData) {
        $metadata = [
            'amount_range' => $this->getAmountRange($paymentData['amount'] ?? 0),
            'stage_category' => $this->getStageCategory($paymentData['stage'] ?? ''),
            'payment_type' => $paymentData['payment_type'] ?? 'standard',
            'timestamp' => time()
        ];
        
        return '0x' . hash('sha256', json_encode($metadata));
    }
    
    /**
     * Generate completion hash
     */
    private function generateCompletionHash($completionData) {
        $completion = [
            'completion_type' => $completionData['type'] ?? 'standard',
            'verification_method' => $completionData['method'] ?? 'manual',
            'timestamp' => time()
        ];
        
        return '0x' . hash('sha256', json_encode($completion));
    }
    
    /**
     * Generate verification hash
     */
    private function generateVerificationHash($verificationData) {
        $verification = [
            'verification_type' => $verificationData['type'] ?? 'standard',
            'verifier_role' => $verificationData['role'] ?? 'unknown',
            'timestamp' => time()
        ];
        
        return '0x' . hash('sha256', json_encode($verification));
    }
    
    /**
     * Store local proof data
     */
    private function storeLocalProof($paymentId, $operationType, $proofHash, $metadataHash, $verifierType = null) {
        $sql = "INSERT INTO blockchain_trust_records 
                (payment_id, operation_type, proof_hash, metadata_hash, verifier_type, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$paymentId, $operationType, $proofHash, $metadataHash, $verifierType]);
    }
    
    /**
     * Update local proof with blockchain transaction hash
     */
    private function updateLocalProofWithTx($paymentId, $operationType, $txHash) {
        $sql = "UPDATE blockchain_trust_records 
                SET blockchain_tx_hash = ?, blockchain_recorded_at = NOW() 
                WHERE payment_id = ? AND operation_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$txHash, $paymentId, $operationType]);
    }
    
    /**
     * Get local proofs for payment
     */
    private function getLocalProofs($paymentId) {
        $sql = "SELECT * FROM blockchain_trust_records WHERE payment_id = ? ORDER BY created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$paymentId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Queue blockchain operation for async processing
     */
    private function queueBlockchainOperation($method, $params) {
        $sql = "INSERT INTO blockchain_operation_queue 
                (method_name, parameters, status, created_at) 
                VALUES (?, ?, 'pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        $paramsJson = json_encode($params);
        $stmt->execute([$method, $paramsJson]);
    }
    
    /**
     * Estimate gas price
     */
    private function estimateGasPrice() {
        try {
            $gasPrice = null;
            $this->web3->eth->gasPrice(function ($err, $price) use (&$gasPrice) {
                if (!$err) {
                    $gasPrice = $price->toHex();
                }
            });
            
            if ($gasPrice) {
                $gasPriceInt = hexdec($gasPrice);
                return min($gasPriceInt, $this->config['max_gas_price']);
            }
        } catch (Exception $e) {
            $this->log('WARN', 'Failed to estimate gas price, using default', ['error' => $e->getMessage()]);
        }
        
        return $this->config['gas_price'];
    }
    
    /**
     * Get amount range for privacy
     */
    private function getAmountRange($amount) {
        if ($amount < 1000) return 'small';
        if ($amount < 10000) return 'medium';
        if ($amount < 50000) return 'large';
        return 'xlarge';
    }
    
    /**
     * Get stage category for privacy
     */
    private function getStageCategory($stage) {
        $categories = [
            'foundation' => 'structural',
            'framing' => 'structural',
            'roofing' => 'exterior',
            'siding' => 'exterior',
            'electrical' => 'systems',
            'plumbing' => 'systems',
            'flooring' => 'interior',
            'painting' => 'interior'
        ];
        
        return $categories[strtolower($stage)] ?? 'general';
    }
    
    /**
     * Get blockchain record
     */
    private function getBlockchainRecord($proofHash) {
        try {
            $record = null;
            $this->contract->call('getPaymentRecord', $proofHash, function ($err, $result) use (&$record) {
                if (!$err && $result) {
                    $record = $result;
                }
            });
            
            return $record;
        } catch (Exception $e) {
            $this->log('WARN', 'Failed to get blockchain record', [
                'proof_hash' => $proofHash,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Log message
     */
    private function log($level, $message, $context = []) {
        $this->logger->log($level, $message, $context);
    }
    
    /**
     * Get contract statistics
     */
    public function getContractStats() {
        try {
            $stats = null;
            $this->contract->call('getContractStats', function ($err, $result) use (&$stats) {
                if (!$err && $result) {
                    $stats = [
                        'total_payments' => $result[0]->toString(),
                        'total_verifications' => $result[1]->toString(),
                        'is_active' => $result[2]
                    ];
                }
            });
            
            return $stats;
        } catch (Exception $e) {
            $this->log('ERROR', 'Failed to get contract stats', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Check if contract is accessible
     */
    public function healthCheck() {
        try {
            // If contract is not initialized, return basic status
            if (!$this->contract) {
                return [
                    'web3_connected' => $this->web3 !== null,
                    'contract_accessible' => false,
                    'contract_active' => false,
                    'contract_address' => $this->config['contract_address'],
                    'network' => $this->config['network'],
                    'error' => 'Contract not initialized - Web3 dependencies missing'
                ];
            }
            
            $isActive = null;
            $this->contract->call('contractActive', function ($err, $result) use (&$isActive) {
                if (!$err) {
                    $isActive = $result;
                }
            });
            
            return [
                'web3_connected' => $this->web3 !== null,
                'contract_accessible' => $isActive !== null,
                'contract_active' => $isActive,
                'contract_address' => $this->config['contract_address'],
                'network' => $this->config['network']
            ];
        } catch (Exception $e) {
            return [
                'web3_connected' => false,
                'contract_accessible' => false,
                'contract_active' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
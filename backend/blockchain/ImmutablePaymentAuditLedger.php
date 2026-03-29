<?php
/**
 * Immutable Payment Audit Ledger
 * 
 * Blockchain-inspired immutable audit and verification mechanism for payment verification.
 * Implements core blockchain principles: hashing, block chaining, immutability, and timestamped verification.
 * 
 * This system operates as a tamper-evident audit layer without modifying existing payment workflows.
 * It generates cryptographic hashes from payment context and maintains an append-only ledger.
 * 
 * Key Features:
 * - Cryptographic hash generation from payment context
 * - Append-only audit ledger with immutability guarantees
 * - Block chaining with previous hash references
 * - Timestamped verification entries
 * - Tamper detection through hash verification
 * - Dispute prevention through immutable audit trail
 */

class ImmutablePaymentAuditLedger {
    private $db;
    private $config;
    
    // Audit entry types
    const ENTRY_TYPE_PAYMENT_COMPLETION = 'payment_completion';
    const ENTRY_TYPE_CONTRACTOR_VERIFICATION = 'contractor_verification';
    const ENTRY_TYPE_ADMIN_VERIFICATION = 'admin_verification';
    
    // Hash algorithms
    const HASH_ALGORITHM = 'sha256';
    const BLOCK_HASH_ALGORITHM = 'sha256';
    
    public function __construct($database) {
        $this->db = $database;
        $this->config = [
            'enabled' => true,
            'hash_algorithm' => self::HASH_ALGORITHM,
            'block_hash_algorithm' => self::BLOCK_HASH_ALGORITHM,
            'include_sensitive_data' => false, // Privacy protection
            'max_context_size' => 4096, // Limit context size for performance
            'verification_window_hours' => 72, // Time window for verification
        ];
        
        $this->initializeAuditTables();
    }
    
    /**
     * Initialize audit ledger tables if they don't exist
     */
    private function initializeAuditTables() {
        try {
            // Main audit ledger table - append-only immutable records
            $createLedgerTable = "
                CREATE TABLE IF NOT EXISTS immutable_payment_audit_ledger (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    block_number BIGINT NOT NULL,
                    entry_type ENUM('payment_completion', 'contractor_verification', 'admin_verification') NOT NULL,
                    payment_id INT NOT NULL,
                    project_id INT NOT NULL,
                    
                    -- Cryptographic hashes
                    content_hash VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of payment context',
                    previous_hash VARCHAR(64) NOT NULL COMMENT 'Hash of previous block for chaining',
                    block_hash VARCHAR(64) NOT NULL COMMENT 'Hash of this entire block',
                    
                    -- Immutable context data (privacy-protected)
                    payment_context_hash VARCHAR(64) NOT NULL COMMENT 'Hash of payment details',
                    verification_context_hash VARCHAR(64) NULL COMMENT 'Hash of verification details',
                    
                    -- Metadata (non-sensitive)
                    amount_range ENUM('small', 'medium', 'large', 'xlarge') NOT NULL,
                    stage_category VARCHAR(50) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    
                    -- Verification details
                    verifier_type ENUM('contractor', 'admin') NULL,
                    verification_action VARCHAR(50) NULL,
                    
                    -- Immutable timestamps
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    block_timestamp BIGINT NOT NULL COMMENT 'Unix timestamp for block creation',
                    
                    -- Integrity constraints
                    UNIQUE KEY unique_block_number (block_number),
                    INDEX idx_payment_id (payment_id),
                    INDEX idx_project_id (project_id),
                    INDEX idx_entry_type (entry_type),
                    INDEX idx_content_hash (content_hash),
                    INDEX idx_block_hash (block_hash),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
                COMMENT='Immutable audit ledger for payment verification - append-only';
            ";
            
            $this->db->exec($createLedgerTable);
            
            // Audit verification log - tracks verification attempts and results
            $createVerificationLog = "
                CREATE TABLE IF NOT EXISTS audit_verification_log (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    ledger_entry_id BIGINT NOT NULL,
                    verification_type ENUM('hash_verification', 'chain_verification', 'tamper_detection') NOT NULL,
                    verification_result ENUM('valid', 'invalid', 'suspicious') NOT NULL,
                    verification_details JSON NULL,
                    verified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_ledger_entry_id (ledger_entry_id),
                    INDEX idx_verification_type (verification_type),
                    INDEX idx_verification_result (verification_result),
                    INDEX idx_verified_at (verified_at),
                    
                    FOREIGN KEY (ledger_entry_id) REFERENCES immutable_payment_audit_ledger(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Verification log for audit ledger integrity checks';
            ";
            
            $this->db->exec($createVerificationLog);
            
            // Audit statistics table - for performance and monitoring
            $createStatsTable = "
                CREATE TABLE IF NOT EXISTS audit_ledger_statistics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    total_entries BIGINT NOT NULL DEFAULT 0,
                    total_payment_completions BIGINT NOT NULL DEFAULT 0,
                    total_contractor_verifications BIGINT NOT NULL DEFAULT 0,
                    total_admin_verifications BIGINT NOT NULL DEFAULT 0,
                    last_block_number BIGINT NOT NULL DEFAULT 0,
                    last_block_hash VARCHAR(64) NOT NULL DEFAULT '',
                    integrity_check_count BIGINT NOT NULL DEFAULT 0,
                    last_integrity_check TIMESTAMP NULL,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY single_row (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Statistics and metadata for audit ledger';
            ";
            
            $this->db->exec($createStatsTable);
            
            // Initialize statistics if empty
            $this->db->exec("
                INSERT IGNORE INTO audit_ledger_statistics (id, total_entries, last_block_number) 
                VALUES (1, 0, 0)
            ");
            
        } catch (Exception $e) {
            error_log("Failed to initialize audit ledger tables: " . $e->getMessage());
            throw new Exception("Audit ledger initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Record payment completion in the immutable audit ledger
     * Called after successful payment completion (Razorpay or bank transfer)
     */
    public function recordPaymentCompletion($paymentData) {
        if (!$this->config['enabled']) {
            return null;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Generate payment context hash
            $paymentContext = $this->buildPaymentContext($paymentData);
            $contentHash = $this->generateContentHash($paymentContext);
            
            // Get previous block hash for chaining
            $previousHash = $this->getLastBlockHash();
            
            // Get next block number
            $blockNumber = $this->getNextBlockNumber();
            
            // Create audit entry
            $auditEntry = [
                'block_number' => $blockNumber,
                'entry_type' => self::ENTRY_TYPE_PAYMENT_COMPLETION,
                'payment_id' => $paymentData['payment_id'],
                'project_id' => $paymentData['project_id'],
                'content_hash' => $contentHash,
                'previous_hash' => $previousHash,
                'payment_context_hash' => $this->generatePaymentContextHash($paymentData),
                'amount_range' => $this->getAmountRange($paymentData['amount'] ?? 0),
                'stage_category' => $this->getStageCategory($paymentData['stage'] ?? ''),
                'payment_method' => $paymentData['payment_method'] ?? 'unknown',
                'block_timestamp' => time()
            ];
            
            // Generate block hash
            $blockHash = $this->generateBlockHash($auditEntry);
            $auditEntry['block_hash'] = $blockHash;
            
            // Insert into ledger
            $ledgerEntryId = $this->insertAuditEntry($auditEntry);
            
            // Update statistics
            $this->updateStatistics('payment_completion', $blockNumber, $blockHash);
            
            $this->db->commit();
            
            // Log the audit entry creation
            error_log("Payment completion recorded in audit ledger: Payment ID {$paymentData['payment_id']}, Block #{$blockNumber}, Hash: {$blockHash}");
            
            return [
                'ledger_entry_id' => $ledgerEntryId,
                'block_number' => $blockNumber,
                'content_hash' => $contentHash,
                'block_hash' => $blockHash,
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Failed to record payment completion in audit ledger: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Record contractor verification in the immutable audit ledger
     * Called after contractor confirms payment receipt
     */
    public function recordContractorVerification($paymentData, $verificationData) {
        if (!$this->config['enabled']) {
            return null;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Find the original payment completion entry
            $originalEntry = $this->findPaymentCompletionEntry($paymentData['payment_id']);
            if (!$originalEntry) {
                throw new Exception("Original payment completion entry not found for payment ID: {$paymentData['payment_id']}");
            }
            
            // Generate verification context hash
            $verificationContext = $this->buildVerificationContext($verificationData, 'contractor');
            $contentHash = $this->generateContentHash($verificationContext);
            
            // Get previous block hash for chaining
            $previousHash = $this->getLastBlockHash();
            
            // Get next block number
            $blockNumber = $this->getNextBlockNumber();
            
            // Create verification audit entry
            $auditEntry = [
                'block_number' => $blockNumber,
                'entry_type' => self::ENTRY_TYPE_CONTRACTOR_VERIFICATION,
                'payment_id' => $paymentData['payment_id'],
                'project_id' => $paymentData['project_id'],
                'content_hash' => $contentHash,
                'previous_hash' => $previousHash,
                'payment_context_hash' => $originalEntry['payment_context_hash'], // Link to original
                'verification_context_hash' => $this->generateVerificationContextHash($verificationData),
                'amount_range' => $originalEntry['amount_range'],
                'stage_category' => $originalEntry['stage_category'],
                'payment_method' => $originalEntry['payment_method'],
                'verifier_type' => 'contractor',
                'verification_action' => $verificationData['verification_status'] ?? 'verified',
                'block_timestamp' => time()
            ];
            
            // Generate block hash
            $blockHash = $this->generateBlockHash($auditEntry);
            $auditEntry['block_hash'] = $blockHash;
            
            // Insert into ledger
            $ledgerEntryId = $this->insertAuditEntry($auditEntry);
            
            // Update statistics
            $this->updateStatistics('contractor_verification', $blockNumber, $blockHash);
            
            $this->db->commit();
            
            // Log the verification entry creation
            error_log("Contractor verification recorded in audit ledger: Payment ID {$paymentData['payment_id']}, Block #{$blockNumber}, Hash: {$blockHash}");
            
            return [
                'ledger_entry_id' => $ledgerEntryId,
                'block_number' => $blockNumber,
                'content_hash' => $contentHash,
                'block_hash' => $blockHash,
                'original_payment_hash' => $originalEntry['content_hash'],
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Failed to record contractor verification in audit ledger: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Record admin verification in the immutable audit ledger
     * Called after admin approves/rejects payment verification
     */
    public function recordAdminVerification($paymentData, $verificationData) {
        if (!$this->config['enabled']) {
            return null;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Find the original payment completion entry
            $originalEntry = $this->findPaymentCompletionEntry($paymentData['payment_id']);
            if (!$originalEntry) {
                throw new Exception("Original payment completion entry not found for payment ID: {$paymentData['payment_id']}");
            }
            
            // Generate verification context hash
            $verificationContext = $this->buildVerificationContext($verificationData, 'admin');
            $contentHash = $this->generateContentHash($verificationContext);
            
            // Get previous block hash for chaining
            $previousHash = $this->getLastBlockHash();
            
            // Get next block number
            $blockNumber = $this->getNextBlockNumber();
            
            // Create admin verification audit entry
            $auditEntry = [
                'block_number' => $blockNumber,
                'entry_type' => self::ENTRY_TYPE_ADMIN_VERIFICATION,
                'payment_id' => $paymentData['payment_id'],
                'project_id' => $paymentData['project_id'],
                'content_hash' => $contentHash,
                'previous_hash' => $previousHash,
                'payment_context_hash' => $originalEntry['payment_context_hash'], // Link to original
                'verification_context_hash' => $this->generateVerificationContextHash($verificationData),
                'amount_range' => $originalEntry['amount_range'],
                'stage_category' => $originalEntry['stage_category'],
                'payment_method' => $originalEntry['payment_method'],
                'verifier_type' => 'admin',
                'verification_action' => $verificationData['verification_action'] ?? 'admin_approved',
                'block_timestamp' => time()
            ];
            
            // Generate block hash
            $blockHash = $this->generateBlockHash($auditEntry);
            $auditEntry['block_hash'] = $blockHash;
            
            // Insert into ledger
            $ledgerEntryId = $this->insertAuditEntry($auditEntry);
            
            // Update statistics
            $this->updateStatistics('admin_verification', $blockNumber, $blockHash);
            
            $this->db->commit();
            
            // Log the verification entry creation
            error_log("Admin verification recorded in audit ledger: Payment ID {$paymentData['payment_id']}, Block #{$blockNumber}, Hash: {$blockHash}");
            
            return [
                'ledger_entry_id' => $ledgerEntryId,
                'block_number' => $blockNumber,
                'content_hash' => $contentHash,
                'block_hash' => $blockHash,
                'original_payment_hash' => $originalEntry['content_hash'],
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Failed to record admin verification in audit ledger: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get complete audit trail for a payment
     */
    public function getPaymentAuditTrail($paymentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    block_number,
                    entry_type,
                    content_hash,
                    previous_hash,
                    block_hash,
                    payment_context_hash,
                    verification_context_hash,
                    amount_range,
                    stage_category,
                    payment_method,
                    verifier_type,
                    verification_action,
                    created_at,
                    block_timestamp
                FROM immutable_payment_audit_ledger 
                WHERE payment_id = ? 
                ORDER BY block_number ASC
            ");
            
            $stmt->execute([$paymentId]);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($entries)) {
                return null;
            }
            
            // Verify chain integrity for this payment
            $chainIntegrity = $this->verifyPaymentChainIntegrity($entries);
            
            return [
                'payment_id' => $paymentId,
                'total_entries' => count($entries),
                'chain_integrity' => $chainIntegrity,
                'entries' => array_map(function($entry) {
                    return [
                        'ledger_entry_id' => $entry['id'],
                        'block_number' => $entry['block_number'],
                        'entry_type' => $entry['entry_type'],
                        'content_hash' => $entry['content_hash'],
                        'block_hash' => $entry['block_hash'],
                        'verifier_type' => $entry['verifier_type'],
                        'verification_action' => $entry['verification_action'],
                        'timestamp' => $entry['created_at'],
                        'block_timestamp' => $entry['block_timestamp'],
                        'metadata' => [
                            'amount_range' => $entry['amount_range'],
                            'stage_category' => $entry['stage_category'],
                            'payment_method' => $entry['payment_method']
                        ]
                    ];
                }, $entries)
            ];
            
        } catch (Exception $e) {
            error_log("Failed to get payment audit trail: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify the integrity of the entire audit ledger
     */
    public function verifyLedgerIntegrity($startBlock = null, $endBlock = null) {
        try {
            $whereClause = "";
            $params = [];
            
            if ($startBlock !== null && $endBlock !== null) {
                $whereClause = "WHERE block_number BETWEEN ? AND ?";
                $params = [$startBlock, $endBlock];
            } elseif ($startBlock !== null) {
                $whereClause = "WHERE block_number >= ?";
                $params = [$startBlock];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    block_number,
                    entry_type,
                    payment_id,
                    project_id,
                    content_hash,
                    previous_hash,
                    block_hash,
                    payment_context_hash,
                    verification_context_hash,
                    amount_range,
                    stage_category,
                    payment_method,
                    verifier_type,
                    verification_action,
                    block_timestamp
                FROM immutable_payment_audit_ledger 
                {$whereClause}
                ORDER BY block_number ASC
            ");
            
            $stmt->execute($params);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($entries)) {
                return [
                    'valid' => true,
                    'message' => 'No entries to verify',
                    'total_entries' => 0,
                    'verified_entries' => 0,
                    'invalid_entries' => []
                ];
            }
            
            $invalidEntries = [];
            $verifiedCount = 0;
            $previousHash = '';
            
            foreach ($entries as $entry) {
                $isValid = true;
                $errors = [];
                
                // Verify block hash
                $entryForHash = [
                    'block_number' => $entry['block_number'],
                    'entry_type' => $entry['entry_type'],
                    'payment_id' => $entry['payment_id'],
                    'project_id' => $entry['project_id'],
                    'content_hash' => $entry['content_hash'],
                    'previous_hash' => $entry['previous_hash'],
                    'payment_context_hash' => $entry['payment_context_hash'],
                    'verification_context_hash' => $entry['verification_context_hash'],
                    'amount_range' => $entry['amount_range'],
                    'stage_category' => $entry['stage_category'],
                    'payment_method' => $entry['payment_method'],
                    'verifier_type' => $entry['verifier_type'],
                    'verification_action' => $entry['verification_action'],
                    'block_timestamp' => $entry['block_timestamp']
                ];
                
                $expectedBlockHash = $this->generateBlockHash($entryForHash);
                if ($entry['block_hash'] !== $expectedBlockHash) {
                    $isValid = false;
                    $errors[] = "Block hash mismatch";
                }
                
                // Verify chain linkage (except for first block)
                if ($entry['block_number'] > 1 && $entry['previous_hash'] !== $previousHash) {
                    $isValid = false;
                    $errors[] = "Chain linkage broken";
                }
                
                if (!$isValid) {
                    $invalidEntries[] = [
                        'block_number' => $entry['block_number'],
                        'payment_id' => $entry['payment_id'],
                        'entry_type' => $entry['entry_type'],
                        'errors' => $errors
                    ];
                } else {
                    $verifiedCount++;
                }
                
                $previousHash = $entry['block_hash'];
            }
            
            // Log integrity check
            $this->logIntegrityCheck(count($entries), $verifiedCount, count($invalidEntries));
            
            return [
                'valid' => empty($invalidEntries),
                'message' => empty($invalidEntries) ? 'Ledger integrity verified' : 'Integrity violations detected',
                'total_entries' => count($entries),
                'verified_entries' => $verifiedCount,
                'invalid_entries' => $invalidEntries,
                'integrity_percentage' => count($entries) > 0 ? round(($verifiedCount / count($entries)) * 100, 2) : 100
            ];
            
        } catch (Exception $e) {
            error_log("Failed to verify ledger integrity: " . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Integrity verification failed: ' . $e->getMessage(),
                'total_entries' => 0,
                'verified_entries' => 0,
                'invalid_entries' => []
            ];
        }
    }
    
    /**
     * Get audit ledger statistics
     */
    public function getAuditStatistics() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    total_entries,
                    total_payment_completions,
                    total_contractor_verifications,
                    total_admin_verifications,
                    last_block_number,
                    last_block_hash,
                    integrity_check_count,
                    last_integrity_check,
                    updated_at
                FROM audit_ledger_statistics 
                WHERE id = 1
            ");
            
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stats) {
                return [
                    'total_entries' => 0,
                    'total_payment_completions' => 0,
                    'total_contractor_verifications' => 0,
                    'total_admin_verifications' => 0,
                    'last_block_number' => 0,
                    'last_block_hash' => '',
                    'integrity_check_count' => 0,
                    'last_integrity_check' => null
                ];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Failed to get audit statistics: " . $e->getMessage());
            return null;
        }
    }
    
    // Private helper methods
    
    private function buildPaymentContext($paymentData) {
        return [
            'payment_id' => $paymentData['payment_id'],
            'project_id' => $paymentData['project_id'],
            'amount_range' => $this->getAmountRange($paymentData['amount'] ?? 0),
            'stage_category' => $this->getStageCategory($paymentData['stage'] ?? ''),
            'payment_method' => $paymentData['payment_method'] ?? 'unknown',
            'timestamp' => time(),
            'context_version' => '1.0'
        ];
    }
    
    private function buildVerificationContext($verificationData, $verifierType) {
        return [
            'verifier_type' => $verifierType,
            'verification_action' => $verificationData['verification_action'] ?? $verificationData['verification_status'] ?? 'verified',
            'verification_timestamp' => time(),
            'has_notes' => !empty($verificationData['verification_notes'] ?? $verificationData['admin_notes']),
            'context_version' => '1.0'
        ];
    }
    
    private function generateContentHash($context) {
        // Sort the array keys manually for consistent hashing
        ksort($context);
        $contextJson = json_encode($context);
        return hash($this->config['hash_algorithm'], $contextJson);
    }
    
    private function generatePaymentContextHash($paymentData) {
        $sensitiveContext = [
            'payment_id' => $paymentData['payment_id'],
            'project_id' => $paymentData['project_id'],
            'timestamp' => time()
        ];
        
        // Sort the array keys manually for consistent hashing
        ksort($sensitiveContext);
        return hash($this->config['hash_algorithm'], json_encode($sensitiveContext));
    }
    
    private function generateVerificationContextHash($verificationData) {
        $sensitiveContext = [
            'verification_timestamp' => time(),
            'has_verification_data' => !empty($verificationData)
        ];
        
        // Sort the array keys manually for consistent hashing
        ksort($sensitiveContext);
        return hash($this->config['hash_algorithm'], json_encode($sensitiveContext));
    }
    
    private function generateBlockHash($auditEntry) {
        // Create a copy without the block_hash field
        $blockData = $auditEntry;
        unset($blockData['block_hash']);
        
        // Remove null values to ensure consistent hashing
        $blockData = array_filter($blockData, function($value) {
            return $value !== null;
        });
        
        // Sort keys for consistent hashing
        ksort($blockData);
        
        $blockJson = json_encode($blockData);
        return hash($this->config['block_hash_algorithm'], $blockJson);
    }
    
    private function getLastBlockHash() {
        $stmt = $this->db->prepare("
            SELECT block_hash 
            FROM immutable_payment_audit_ledger 
            ORDER BY block_number DESC 
            LIMIT 1
        ");
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['block_hash'] : hash($this->config['hash_algorithm'], 'genesis_block');
    }
    
    private function getNextBlockNumber() {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(block_number), 0) + 1 as next_block 
            FROM immutable_payment_audit_ledger
        ");
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['next_block'];
    }
    
    private function insertAuditEntry($auditEntry) {
        $stmt = $this->db->prepare("
            INSERT INTO immutable_payment_audit_ledger (
                block_number, entry_type, payment_id, project_id,
                content_hash, previous_hash, block_hash,
                payment_context_hash, verification_context_hash,
                amount_range, stage_category, payment_method,
                verifier_type, verification_action, block_timestamp
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $auditEntry['block_number'],
            $auditEntry['entry_type'],
            $auditEntry['payment_id'],
            $auditEntry['project_id'],
            $auditEntry['content_hash'],
            $auditEntry['previous_hash'],
            $auditEntry['block_hash'],
            $auditEntry['payment_context_hash'],
            $auditEntry['verification_context_hash'] ?? null,
            $auditEntry['amount_range'],
            $auditEntry['stage_category'],
            $auditEntry['payment_method'],
            $auditEntry['verifier_type'] ?? null,
            $auditEntry['verification_action'] ?? null,
            $auditEntry['block_timestamp']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function updateStatistics($operationType, $blockNumber, $blockHash) {
        $field = '';
        switch ($operationType) {
            case 'payment_completion':
                $field = 'total_payment_completions';
                break;
            case 'contractor_verification':
                $field = 'total_contractor_verifications';
                break;
            case 'admin_verification':
                $field = 'total_admin_verifications';
                break;
        }
        
        $stmt = $this->db->prepare("
            UPDATE audit_ledger_statistics 
            SET 
                total_entries = total_entries + 1,
                {$field} = {$field} + 1,
                last_block_number = :block_number,
                last_block_hash = :block_hash,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        
        $stmt->execute([
            ':block_number' => $blockNumber,
            ':block_hash' => $blockHash
        ]);
    }
    
    private function findPaymentCompletionEntry($paymentId) {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM immutable_payment_audit_ledger 
            WHERE payment_id = ? AND entry_type = ? 
            ORDER BY block_number ASC 
            LIMIT 1
        ");
        
        $stmt->execute([$paymentId, self::ENTRY_TYPE_PAYMENT_COMPLETION]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function verifyPaymentChainIntegrity($entries) {
        if (empty($entries)) {
            return ['valid' => true, 'message' => 'No entries to verify'];
        }
        
        $previousHash = '';
        foreach ($entries as $index => $entry) {
            // Verify block hash
            $expectedBlockHash = $this->generateBlockHash($entry);
            if ($entry['block_hash'] !== $expectedBlockHash) {
                return [
                    'valid' => false,
                    'message' => "Block hash mismatch at entry {$index}",
                    'entry_id' => $entry['id']
                ];
            }
            
            // Verify chain linkage (except for first entry)
            if ($index > 0 && $entry['previous_hash'] !== $previousHash) {
                return [
                    'valid' => false,
                    'message' => "Chain linkage broken at entry {$index}",
                    'entry_id' => $entry['id']
                ];
            }
            
            $previousHash = $entry['block_hash'];
        }
        
        return ['valid' => true, 'message' => 'Chain integrity verified'];
    }
    
    private function logIntegrityCheck($totalEntries, $verifiedEntries, $invalidCount) {
        $stmt = $this->db->prepare("
            UPDATE audit_ledger_statistics 
            SET 
                integrity_check_count = integrity_check_count + 1,
                last_integrity_check = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        
        $stmt->execute();
        
        // Log to verification log
        if ($invalidCount > 0) {
            error_log("Audit ledger integrity check: {$invalidCount} invalid entries found out of {$totalEntries} total entries");
        }
    }
    
    private function getAmountRange($amount) {
        if ($amount < 1000) return 'small';
        if ($amount < 10000) return 'medium';
        if ($amount < 50000) return 'large';
        return 'xlarge';
    }
    
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
}
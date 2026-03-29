<?php
/**
 * Payment Audit Integrator
 * 
 * Non-intrusive integration layer that hooks into existing payment workflows
 * to automatically record entries in the immutable audit ledger.
 * 
 * This class provides static methods that can be called from existing APIs
 * without modifying their core logic or error handling.
 */

require_once __DIR__ . '/ImmutablePaymentAuditLedger.php';

class PaymentAuditIntegrator {
    private static $instance = null;
    private $auditLedger;
    private $db;
    private $enabled;
    
    private function __construct($database) {
        $this->db = $database;
        $this->enabled = true; // Can be controlled via config
        
        try {
            $this->auditLedger = new ImmutablePaymentAuditLedger($database);
        } catch (Exception $e) {
            error_log("Failed to initialize audit ledger: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($database) {
        if (self::$instance === null) {
            self::$instance = new self($database);
        }
        return self::$instance;
    }
    
    /**
     * Hook for payment completion - call this after successful payment processing
     * 
     * Integration points:
     * - After Razorpay payment verification
     * - After bank transfer payment confirmation
     * - After alternative payment method completion
     */
    public static function onPaymentCompleted($database, $paymentData) {
        try {
            $integrator = self::getInstance($database);
            
            if (!$integrator->enabled) {
                return null;
            }
            
            // Normalize payment data
            $normalizedData = $integrator->normalizePaymentData($paymentData);
            
            // Record in audit ledger
            $result = $integrator->auditLedger->recordPaymentCompletion($normalizedData);
            
            if ($result) {
                error_log("Payment audit recorded: Payment ID {$normalizedData['payment_id']}, Block #{$result['block_number']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Never fail the main payment flow due to audit issues
            error_log("Payment audit integration failed (non-critical): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Hook for contractor verification - call this after contractor verifies payment
     * 
     * Integration points:
     * - backend/api/contractor/verify_payment_receipt.php
     * - Any contractor payment confirmation endpoint
     */
    public static function onContractorVerification($database, $paymentData, $verificationData) {
        try {
            $integrator = self::getInstance($database);
            
            if (!$integrator->enabled) {
                return null;
            }
            
            // Normalize data
            $normalizedPaymentData = $integrator->normalizePaymentData($paymentData);
            $normalizedVerificationData = $integrator->normalizeVerificationData($verificationData, 'contractor');
            
            // Record in audit ledger
            $result = $integrator->auditLedger->recordContractorVerification(
                $normalizedPaymentData, 
                $normalizedVerificationData
            );
            
            if ($result) {
                error_log("Contractor verification audit recorded: Payment ID {$normalizedPaymentData['payment_id']}, Block #{$result['block_number']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Never fail the main verification flow due to audit issues
            error_log("Contractor verification audit integration failed (non-critical): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Hook for admin verification - call this after admin approves/rejects payment
     * 
     * Integration points:
     * - backend/api/admin/verify_payment_receipt.php
     * - Any admin payment verification endpoint
     */
    public static function onAdminVerification($database, $paymentData, $verificationData) {
        try {
            $integrator = self::getInstance($database);
            
            if (!$integrator->enabled) {
                return null;
            }
            
            // Normalize data
            $normalizedPaymentData = $integrator->normalizePaymentData($paymentData);
            $normalizedVerificationData = $integrator->normalizeVerificationData($verificationData, 'admin');
            
            // Record in audit ledger
            $result = $integrator->auditLedger->recordAdminVerification(
                $normalizedPaymentData, 
                $normalizedVerificationData
            );
            
            if ($result) {
                error_log("Admin verification audit recorded: Payment ID {$normalizedPaymentData['payment_id']}, Block #{$result['block_number']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Never fail the main verification flow due to audit issues
            error_log("Admin verification audit integration failed (non-critical): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get audit trail for a payment - for dispute resolution and transparency
     */
    public static function getPaymentAuditTrail($database, $paymentId) {
        try {
            $integrator = self::getInstance($database);
            
            if (!$integrator->enabled) {
                return null;
            }
            
            return $integrator->auditLedger->getPaymentAuditTrail($paymentId);
            
        } catch (Exception $e) {
            error_log("Failed to get payment audit trail: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify ledger integrity - for system health checks
     */
    public static function verifyLedgerIntegrity($database, $startBlock = null, $endBlock = null) {
        try {
            $integrator = self::getInstance($database);
            
            if (!$integrator->enabled) {
                return ['valid' => true, 'message' => 'Audit ledger disabled'];
            }
            
            return $integrator->auditLedger->verifyLedgerIntegrity($startBlock, $endBlock);
            
        } catch (Exception $e) {
            error_log("Failed to verify ledger integrity: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Integrity check failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get audit statistics - for monitoring and reporting
     */
    public static function getAuditStatistics($database) {
        try {
            $integrator = self::getInstance($database);
            
            if (!$integrator->enabled) {
                return null;
            }
            
            return $integrator->auditLedger->getAuditStatistics();
            
        } catch (Exception $e) {
            error_log("Failed to get audit statistics: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Normalize payment data from various sources
     */
    private function normalizePaymentData($paymentData) {
        // Handle different payment data structures from various APIs
        $normalized = [
            'payment_id' => null,
            'project_id' => null,
            'amount' => 0,
            'stage' => '',
            'payment_method' => 'unknown'
        ];
        
        // Extract payment_id
        if (isset($paymentData['payment_id'])) {
            $normalized['payment_id'] = $paymentData['payment_id'];
        } elseif (isset($paymentData['id'])) {
            $normalized['payment_id'] = $paymentData['id'];
        } elseif (isset($paymentData['payment_request_id'])) {
            $normalized['payment_id'] = $paymentData['payment_request_id'];
        }
        
        // Extract project_id
        if (isset($paymentData['project_id'])) {
            $normalized['project_id'] = $paymentData['project_id'];
        }
        
        // Extract amount
        if (isset($paymentData['amount'])) {
            $normalized['amount'] = $paymentData['amount'];
        } elseif (isset($paymentData['requested_amount'])) {
            $normalized['amount'] = $paymentData['requested_amount'];
        } elseif (isset($paymentData['approved_amount'])) {
            $normalized['amount'] = $paymentData['approved_amount'];
        }
        
        // Extract stage
        if (isset($paymentData['stage'])) {
            $normalized['stage'] = $paymentData['stage'];
        } elseif (isset($paymentData['stage_name'])) {
            $normalized['stage'] = $paymentData['stage_name'];
        }
        
        // Extract payment method
        if (isset($paymentData['payment_method'])) {
            $normalized['payment_method'] = $paymentData['payment_method'];
        } elseif (isset($paymentData['payment_type'])) {
            $normalized['payment_method'] = $paymentData['payment_type'];
        }
        
        // If we still don't have payment_id, try to fetch from database
        if (!$normalized['payment_id'] && isset($paymentData['razorpay_payment_id'])) {
            $normalized['payment_id'] = $this->findPaymentIdByRazorpayId($paymentData['razorpay_payment_id']);
        }
        
        return $normalized;
    }
    
    /**
     * Normalize verification data from various sources
     */
    private function normalizeVerificationData($verificationData, $verifierType) {
        $normalized = [
            'verifier_type' => $verifierType,
            'verification_action' => 'verified',
            'verification_status' => 'verified',
            'verification_notes' => '',
            'verification_timestamp' => time()
        ];
        
        // Extract verification action/status
        if (isset($verificationData['verification_action'])) {
            $normalized['verification_action'] = $verificationData['verification_action'];
        } elseif (isset($verificationData['verification_status'])) {
            $normalized['verification_action'] = $verificationData['verification_status'];
        } elseif (isset($verificationData['action'])) {
            $normalized['verification_action'] = $verificationData['action'];
        }
        
        // Extract verification notes
        if (isset($verificationData['verification_notes'])) {
            $normalized['verification_notes'] = $verificationData['verification_notes'];
        } elseif (isset($verificationData['admin_notes'])) {
            $normalized['verification_notes'] = $verificationData['admin_notes'];
        } elseif (isset($verificationData['notes'])) {
            $normalized['verification_notes'] = $verificationData['notes'];
        }
        
        // Extract additional context based on verifier type
        if ($verifierType === 'admin') {
            if (isset($verificationData['admin_username'])) {
                $normalized['admin_username'] = $verificationData['admin_username'];
            }
            if (isset($verificationData['auto_progress_update'])) {
                $normalized['auto_progress_update'] = $verificationData['auto_progress_update'];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Find payment ID by Razorpay payment ID
     */
    private function findPaymentIdByRazorpayId($razorpayPaymentId) {
        try {
            // Check stage payment transactions
            $stmt = $this->db->prepare("
                SELECT payment_request_id 
                FROM stage_payment_transactions 
                WHERE razorpay_payment_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$razorpayPaymentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['payment_request_id'];
            }
            
            // Check alternative payments
            $stmt = $this->db->prepare("
                SELECT id 
                FROM alternative_payments 
                WHERE razorpay_payment_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$razorpayPaymentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['id'];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Failed to find payment ID by Razorpay ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Enable or disable audit integration
     */
    public static function setEnabled($enabled) {
        if (self::$instance) {
            self::$instance->enabled = $enabled;
        }
    }
    
    /**
     * Check if audit integration is enabled
     */
    public static function isEnabled() {
        return self::$instance ? self::$instance->enabled : false;
    }
}
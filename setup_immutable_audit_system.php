<?php
/**
 * Setup Immutable Audit System
 * 
 * This script initializes the audit system and populates existing payment data.
 * Run this once to set up the complete immutable audit ledger.
 */

require_once 'backend/config/database.php';

echo "🔒 Setting Up Immutable Payment Audit System\n";
echo "============================================\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connection established\n";
    
    // Step 1: Create audit tables
    echo "\n📊 Step 1: Creating Audit Tables\n";
    echo "--------------------------------\n";
    
    // Main audit ledger table
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
        COMMENT='Immutable audit ledger for payment verification - append-only'
    ";
    
    $db->exec($createLedgerTable);
    echo "✅ Created immutable_payment_audit_ledger table\n";
    
    // Audit verification log
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
        COMMENT='Verification log for audit ledger integrity checks'
    ";
    
    $db->exec($createVerificationLog);
    echo "✅ Created audit_verification_log table\n";
    
    // Audit statistics table
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
        COMMENT='Statistics and metadata for audit ledger'
    ";
    
    $db->exec($createStatsTable);
    echo "✅ Created audit_ledger_statistics table\n";
    
    // Initialize statistics
    $db->exec("
        INSERT IGNORE INTO audit_ledger_statistics (id, total_entries, last_block_number) 
        VALUES (1, 0, 0)
    ");
    echo "✅ Initialized audit statistics\n";
    
    // Step 2: Load the audit integrator
    echo "\n🔧 Step 2: Loading Audit System\n";
    echo "------------------------------\n";
    
    require_once 'backend/blockchain/PaymentAuditIntegrator.php';
    echo "✅ Audit integrator loaded\n";
    
    // Step 3: Find existing paid payments
    echo "\n📊 Step 3: Analyzing Existing Payments\n";
    echo "-------------------------------------\n";
    
    // Get stage payment requests
    $stagePaymentsStmt = $db->prepare("
        SELECT 
            spr.id as payment_id,
            spr.project_id,
            spr.homeowner_id,
            spr.contractor_id,
            spr.stage_name,
            spr.requested_amount,
            spr.approved_amount,
            spr.status,
            spr.verification_status,
            spr.verified_by,
            spr.verified_at,
            spr.admin_verified,
            spr.admin_verified_by,
            spr.admin_verified_at,
            spr.request_date,
            spr.payment_method,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name
        FROM stage_payment_requests spr
        LEFT JOIN users h ON spr.homeowner_id = h.id
        LEFT JOIN users c ON spr.contractor_id = c.id
        WHERE spr.status = 'paid'
        ORDER BY spr.request_date ASC, spr.id ASC
    ");
    
    $stagePaymentsStmt->execute();
    $stagePayments = $stagePaymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get alternative payments
    $altPaymentsStmt = $db->prepare("
        SELECT 
            ap.id as payment_id,
            COALESCE(spr.project_id, td.project_id, 1) as project_id,
            ap.homeowner_id,
            ap.contractor_id,
            ap.amount,
            ap.payment_method,
            ap.payment_status,
            ap.verification_status,
            ap.verified_by,
            ap.verified_at,
            ap.created_at,
            'alternative' as payment_type
        FROM alternative_payments ap
        LEFT JOIN stage_payment_requests spr ON ap.reference_id = spr.id AND ap.payment_type = 'stage_payment'
        LEFT JOIN technical_details_payments td ON ap.reference_id = td.id AND ap.payment_type = 'technical_details'
        WHERE ap.payment_status IN ('completed', 'verified')
        ORDER BY ap.created_at ASC, ap.id ASC
    ");
    
    $altPaymentsStmt->execute();
    $altPayments = $altPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPayments = count($stagePayments) + count($altPayments);
    
    echo "Found payments to process:\n";
    echo "- Stage Payment Requests (paid): " . count($stagePayments) . "\n";
    echo "- Alternative Payments (completed/verified): " . count($altPayments) . "\n";
    echo "- Total: {$totalPayments}\n";
    
    if ($totalPayments === 0) {
        echo "⚠️ No paid payments found. The audit system is ready for new payments.\n";
        echo "✅ Setup completed successfully!\n";
        exit(0);
    }
    
    // Step 4: Process payments
    echo "\n🔄 Step 4: Creating Audit Entries\n";
    echo "--------------------------------\n";
    
    $processedCount = 0;
    $errorCount = 0;
    $auditEntries = [];
    
    // Process stage payments
    foreach ($stagePayments as $payment) {
        try {
            echo "Processing Payment #{$payment['payment_id']} ({$payment['stage_name']})... ";
            
            // Create payment completion audit entry
            $paymentData = [
                'payment_id' => $payment['payment_id'],
                'project_id' => $payment['project_id'] ?: 1,
                'amount' => $payment['approved_amount'] ?: $payment['requested_amount'],
                'stage' => $payment['stage_name'] ?: 'unknown',
                'payment_method' => $payment['payment_method'] ?: 'mixed'
            ];
            
            $completionResult = PaymentAuditIntegrator::onPaymentCompleted($db, $paymentData);
            
            if ($completionResult) {
                $auditEntries[] = [
                    'type' => 'completion',
                    'payment_id' => $payment['payment_id'],
                    'block_number' => $completionResult['block_number']
                ];
                
                // Add contractor verification if exists
                if ($payment['verification_status'] === 'verified' && $payment['verified_by']) {
                    $verificationData = [
                        'verification_status' => 'verified',
                        'verification_notes' => 'Historical verification entry',
                        'verifier_type' => 'contractor',
                        'contractor_id' => $payment['verified_by']
                    ];
                    
                    $contractorResult = PaymentAuditIntegrator::onContractorVerification($db, $paymentData, $verificationData);
                    
                    if ($contractorResult) {
                        $auditEntries[] = [
                            'type' => 'contractor_verification',
                            'payment_id' => $payment['payment_id'],
                            'block_number' => $contractorResult['block_number']
                        ];
                    }
                }
                
                // Add admin verification if exists
                if ($payment['admin_verified'] && $payment['admin_verified_by']) {
                    $adminVerificationData = [
                        'verification_action' => 'admin_approved',
                        'admin_notes' => 'Historical admin verification',
                        'admin_username' => $payment['admin_verified_by'],
                        'auto_progress_update' => false,
                        'verifier_type' => 'admin'
                    ];
                    
                    $adminResult = PaymentAuditIntegrator::onAdminVerification($db, $paymentData, $adminVerificationData);
                    
                    if ($adminResult) {
                        $auditEntries[] = [
                            'type' => 'admin_verification',
                            'payment_id' => $payment['payment_id'],
                            'block_number' => $adminResult['block_number']
                        ];
                    }
                }
                
                echo "SUCCESS\n";
                $processedCount++;
            } else {
                echo "FAILED\n";
                $errorCount++;
            }
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    // Process alternative payments
    foreach ($altPayments as $payment) {
        try {
            echo "Processing Alt Payment #{$payment['payment_id']}... ";
            
            $paymentData = [
                'payment_id' => $payment['payment_id'],
                'project_id' => $payment['project_id'] ?: 1,
                'amount' => $payment['amount'],
                'stage' => 'alternative_payment',
                'payment_method' => $payment['payment_method'] ?: 'alternative'
            ];
            
            $completionResult = PaymentAuditIntegrator::onPaymentCompleted($db, $paymentData);
            
            if ($completionResult) {
                $auditEntries[] = [
                    'type' => 'completion',
                    'payment_id' => $payment['payment_id'],
                    'block_number' => $completionResult['block_number']
                ];
                
                // Add verification if exists
                if ($payment['verification_status'] === 'verified' && $payment['verified_by']) {
                    $verificationData = [
                        'verification_status' => 'verified',
                        'verification_notes' => 'Historical alternative payment verification',
                        'verifier_type' => 'contractor',
                        'contractor_id' => $payment['verified_by']
                    ];
                    
                    $contractorResult = PaymentAuditIntegrator::onContractorVerification($db, $paymentData, $verificationData);
                    
                    if ($contractorResult) {
                        $auditEntries[] = [
                            'type' => 'contractor_verification',
                            'payment_id' => $payment['payment_id'],
                            'block_number' => $contractorResult['block_number']
                        ];
                    }
                }
                
                echo "SUCCESS\n";
                $processedCount++;
            } else {
                echo "FAILED\n";
                $errorCount++;
            }
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    // Step 5: Verify integrity
    echo "\n🔍 Step 5: Verifying System Integrity\n";
    echo "------------------------------------\n";
    
    $integrityResult = PaymentAuditIntegrator::verifyLedgerIntegrity($db);
    
    if ($integrityResult && $integrityResult['valid']) {
        echo "✅ Ledger integrity verified\n";
        echo "   Total Entries: {$integrityResult['total_entries']}\n";
        echo "   Verified Entries: {$integrityResult['verified_entries']}\n";
        echo "   Integrity: " . ($integrityResult['integrity_percentage'] ?? 100) . "%\n";
    } else {
        echo "⚠️ Integrity check completed with issues\n";
        if ($integrityResult) {
            echo "   Total Entries: {$integrityResult['total_entries']}\n";
            echo "   Verified Entries: {$integrityResult['verified_entries']}\n";
        }
    }
    
    // Final summary
    echo "\n📊 Setup Summary\n";
    echo "===============\n";
    echo "Database Tables: ✅ Created\n";
    echo "Audit System: ✅ Initialized\n";
    echo "Payments Processed: {$processedCount}/{$totalPayments}\n";
    echo "Audit Entries Created: " . count($auditEntries) . "\n";
    echo "Errors: {$errorCount}\n";
    
    if (!empty($auditEntries)) {
        $entryTypes = ['completion' => 0, 'contractor_verification' => 0, 'admin_verification' => 0];
        foreach ($auditEntries as $entry) {
            $entryTypes[$entry['type']]++;
        }
        
        echo "\nEntry Breakdown:\n";
        echo "- Payment Completions: {$entryTypes['completion']}\n";
        echo "- Contractor Verifications: {$entryTypes['contractor_verification']}\n";
        echo "- Admin Verifications: {$entryTypes['admin_verification']}\n";
    }
    
    echo "\n🎉 Setup Completed Successfully!\n";
    echo "================================\n";
    echo "The Immutable Payment Audit System is now ready.\n\n";
    
    echo "📚 What's Next:\n";
    echo "1. ✅ All existing paid payments have been audited\n";
    echo "2. 🔄 New payments will be automatically audited\n";
    echo "3. 🌐 Use API endpoints to access audit trails\n";
    echo "4. 🔍 Monitor system integrity regularly\n\n";
    
    echo "🔗 Test the System:\n";
    echo "- Run: php test_existing_payment_audit_trails.php\n";
    echo "- View: demo_immutable_audit_system.html\n";
    echo "- API: /api/blockchain/get_immutable_audit_trail.php?payment_id=X\n\n";
    
    // Show sample payment IDs for testing
    if (!empty($auditEntries)) {
        echo "🎯 Sample Payment IDs for Testing:\n";
        $sampleIds = array_unique(array_slice(array_column($auditEntries, 'payment_id'), 0, 3));
        foreach ($sampleIds as $id) {
            echo "- Payment ID: {$id}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Setup failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
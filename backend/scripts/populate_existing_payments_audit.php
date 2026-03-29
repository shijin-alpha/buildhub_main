<?php
/**
 * Populate Existing Payments Audit Script
 * 
 * This script creates audit entries for all existing paid payments in the system.
 * It retroactively populates the immutable audit ledger with historical payment data.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../blockchain/PaymentAuditIntegrator.php';

echo "🔒 Populating Immutable Audit Ledger with Existing Paid Payments\n";
echo "================================================================\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connection established\n";
    
    // Get all existing paid payments from stage_payment_requests
    echo "\n📊 Analyzing existing paid payments...\n";
    echo "------------------------------------\n";
    
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
            spr.request_date,
            spr.response_date,
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
    
    // Get all existing paid alternative payments
    $altPaymentsStmt = $db->prepare("
        SELECT 
            ap.id as payment_id,
            COALESCE(spr.project_id, 0) as project_id,
            ap.homeowner_id,
            ap.contractor_id,
            ap.amount,
            ap.payment_method,
            ap.payment_status,
            ap.verification_status,
            ap.verified_by,
            ap.verified_at,
            ap.created_at,
            ap.updated_at,
            'alternative' as payment_type,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name
        FROM alternative_payments ap
        LEFT JOIN stage_payment_requests spr ON ap.reference_id = spr.id AND ap.payment_type = 'stage_payment'
        LEFT JOIN users h ON ap.homeowner_id = h.id
        LEFT JOIN users c ON ap.contractor_id = c.id
        WHERE ap.payment_status IN ('completed', 'verified')
        ORDER BY ap.created_at ASC, ap.id ASC
    ");
    
    $altPaymentsStmt->execute();
    $altPayments = $altPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPayments = count($stagePayments) + count($altPayments);
    
    echo "Found payments to process:\n";
    echo "- Stage Payment Requests (paid): " . count($stagePayments) . "\n";
    echo "- Alternative Payments (completed/verified): " . count($altPayments) . "\n";
    echo "- Total: {$totalPayments}\n\n";
    
    if ($totalPayments === 0) {
        echo "⚠️ No paid payments found to process.\n";
        exit(0);
    }
    
    // Check if audit entries already exist
    $existingStmt = $db->prepare("SELECT COUNT(*) as count FROM immutable_payment_audit_ledger");
    $existingStmt->execute();
    $existingCount = $existingStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($existingCount > 0) {
        echo "⚠️ Warning: {$existingCount} audit entries already exist.\n";
        echo "This script will add new entries for existing payments.\n";
        echo "Continue? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'y' && trim($line) !== 'Y') {
            echo "Operation cancelled.\n";
            exit(0);
        }
        fclose($handle);
        echo "\n";
    }
    
    $processedCount = 0;
    $errorCount = 0;
    $auditEntries = [];
    
    // Process stage payment requests
    echo "🔄 Processing stage payment requests...\n";
    echo "-------------------------------------\n";
    
    foreach ($stagePayments as $payment) {
        try {
            echo "Processing Payment ID: {$payment['payment_id']} (Project: {$payment['project_id']})... ";
            
            // Check if audit entry already exists
            $checkStmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM immutable_payment_audit_ledger 
                WHERE payment_id = ? AND entry_type = 'payment_completion'
            ");
            $checkStmt->execute([$payment['payment_id']]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($exists) {
                echo "SKIPPED (already exists)\n";
                continue;
            }
            
            // Create payment completion audit entry
            $paymentData = [
                'payment_id' => $payment['payment_id'],
                'project_id' => $payment['project_id'],
                'amount' => $payment['approved_amount'] ?: $payment['requested_amount'],
                'stage' => $payment['stage_name'],
                'payment_method' => $payment['payment_method'] ?: 'mixed'
            ];
            
            $completionResult = PaymentAuditIntegrator::onPaymentCompleted($db, $paymentData);
            
            if ($completionResult) {
                $auditEntries[] = [
                    'type' => 'completion',
                    'payment_id' => $payment['payment_id'],
                    'block_number' => $completionResult['block_number'],
                    'timestamp' => $payment['request_date']
                ];
                
                // Create contractor verification entry if verified
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
                            'block_number' => $contractorResult['block_number'],
                            'timestamp' => $payment['verified_at']
                        ];
                    }
                }
                
                // Create admin verification entry if verification status indicates admin approval
                if ($payment['verification_status'] === 'admin_approved' || 
                    ($payment['verification_status'] === 'verified' && $payment['status'] === 'paid')) {
                    $adminVerificationData = [
                        'verification_action' => 'admin_approved',
                        'admin_notes' => 'Historical admin verification entry',
                        'admin_username' => 'system_admin',
                        'auto_progress_update' => false,
                        'verifier_type' => 'admin'
                    ];
                    
                    $adminResult = PaymentAuditIntegrator::onAdminVerification($db, $paymentData, $adminVerificationData);
                    
                    if ($adminResult) {
                        $auditEntries[] = [
                            'type' => 'admin_verification',
                            'payment_id' => $payment['payment_id'],
                            'block_number' => $adminResult['block_number'],
                            'timestamp' => $payment['response_date'] ?: $payment['verified_at']
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
    echo "\n🔄 Processing alternative payments...\n";
    echo "-----------------------------------\n";
    
    foreach ($altPayments as $payment) {
        try {
            echo "Processing Alt Payment ID: {$payment['payment_id']} (Project: {$payment['project_id']})... ";
            
            // Check if audit entry already exists
            $checkStmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM immutable_payment_audit_ledger 
                WHERE payment_id = ? AND entry_type = 'payment_completion'
            ");
            $checkStmt->execute([$payment['payment_id']]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($exists) {
                echo "SKIPPED (already exists)\n";
                continue;
            }
            
            // Create payment completion audit entry
            $paymentData = [
                'payment_id' => $payment['payment_id'],
                'project_id' => $payment['project_id'],
                'amount' => $payment['amount'],
                'stage' => 'alternative_payment',
                'payment_method' => $payment['payment_method']
            ];
            
            $completionResult = PaymentAuditIntegrator::onPaymentCompleted($db, $paymentData);
            
            if ($completionResult) {
                $auditEntries[] = [
                    'type' => 'completion',
                    'payment_id' => $payment['payment_id'],
                    'block_number' => $completionResult['block_number'],
                    'timestamp' => $payment['created_at']
                ];
                
                // Create verification entry if verified
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
                            'block_number' => $contractorResult['block_number'],
                            'timestamp' => $payment['verified_at']
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
    
    // Summary
    echo "\n📊 Processing Summary\n";
    echo "===================\n";
    echo "Total Payments Found: {$totalPayments}\n";
    echo "Successfully Processed: {$processedCount}\n";
    echo "Errors: {$errorCount}\n";
    echo "Total Audit Entries Created: " . count($auditEntries) . "\n\n";
    
    // Show audit entries created
    if (!empty($auditEntries)) {
        echo "📝 Audit Entries Created:\n";
        echo "------------------------\n";
        
        $entryTypes = [
            'completion' => 0,
            'contractor_verification' => 0,
            'admin_verification' => 0
        ];
        
        foreach ($auditEntries as $entry) {
            $entryTypes[$entry['type']]++;
        }
        
        echo "- Payment Completions: {$entryTypes['completion']}\n";
        echo "- Contractor Verifications: {$entryTypes['contractor_verification']}\n";
        echo "- Admin Verifications: {$entryTypes['admin_verification']}\n\n";
        
        // Show sample entries
        echo "Sample Audit Entries:\n";
        $sampleCount = min(5, count($auditEntries));
        for ($i = 0; $i < $sampleCount; $i++) {
            $entry = $auditEntries[$i];
            echo "- Block #{$entry['block_number']}: {$entry['type']} for Payment {$entry['payment_id']}\n";
        }
        
        if (count($auditEntries) > 5) {
            echo "... and " . (count($auditEntries) - 5) . " more entries\n";
        }
    }
    
    // Verify integrity
    echo "\n🔍 Verifying Ledger Integrity...\n";
    echo "-------------------------------\n";
    
    $integrityResult = PaymentAuditIntegrator::verifyLedgerIntegrity($db);
    
    if ($integrityResult) {
        echo "✅ Integrity verification completed\n";
        echo "   Valid: " . ($integrityResult['valid'] ? 'Yes' : 'No') . "\n";
        echo "   Total Entries: {$integrityResult['total_entries']}\n";
        echo "   Verified Entries: {$integrityResult['verified_entries']}\n";
        echo "   Integrity Percentage: " . ($integrityResult['integrity_percentage'] ?? 0) . "%\n";
        
        if (!empty($integrityResult['invalid_entries'])) {
            echo "   ⚠️ Invalid Entries Found:\n";
            foreach ($integrityResult['invalid_entries'] as $invalid) {
                echo "      - Block #{$invalid['block_number']}: " . implode(', ', $invalid['errors']) . "\n";
            }
        }
    } else {
        echo "❌ Failed to verify integrity\n";
    }
    
    // Get final statistics
    echo "\n📈 Final Audit Statistics\n";
    echo "------------------------\n";
    
    $stats = PaymentAuditIntegrator::getAuditStatistics($db);
    
    if ($stats) {
        echo "Total Entries: {$stats['total_entries']}\n";
        echo "Payment Completions: {$stats['total_payment_completions']}\n";
        echo "Contractor Verifications: {$stats['total_contractor_verifications']}\n";
        echo "Admin Verifications: {$stats['total_admin_verifications']}\n";
        echo "Last Block Number: {$stats['last_block_number']}\n";
    }
    
    echo "\n🎉 Population completed successfully!\n";
    echo "===================================\n";
    echo "The immutable audit ledger has been populated with existing payment data.\n";
    echo "All future payments will be automatically audited through the integrated hooks.\n\n";
    
    echo "📚 Next Steps:\n";
    echo "1. Review the audit entries in the database\n";
    echo "2. Test the audit trail API with existing payment IDs\n";
    echo "3. Monitor the system for new payments being automatically audited\n";
    echo "4. Set up regular integrity verification checks\n\n";
    
    // Show some example API calls
    if (!empty($auditEntries)) {
        $samplePaymentId = $auditEntries[0]['payment_id'];
        echo "🔗 Example API Calls:\n";
        echo "- Get audit trail: GET /api/blockchain/get_immutable_audit_trail.php?payment_id={$samplePaymentId}\n";
        echo "- Verify integrity: GET /api/blockchain/verify_audit_ledger_integrity.php\n";
        echo "- View demo: open demo_immutable_audit_system.html\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Population failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
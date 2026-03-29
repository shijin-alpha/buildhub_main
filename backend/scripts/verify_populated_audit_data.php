<?php
/**
 * Verify Populated Audit Data Script
 * 
 * This script verifies the integrity and completeness of the populated audit data
 * for existing paid payments in the system.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../blockchain/PaymentAuditIntegrator.php';

echo "🔍 Verifying Populated Audit Data\n";
echo "=================================\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connection established\n";
    
    // Get audit statistics
    echo "\n📊 Current Audit Statistics\n";
    echo "---------------------------\n";
    
    $stats = PaymentAuditIntegrator::getAuditStatistics($db);
    
    if ($stats) {
        echo "Total Entries: {$stats['total_entries']}\n";
        echo "Payment Completions: {$stats['total_payment_completions']}\n";
        echo "Contractor Verifications: {$stats['total_contractor_verifications']}\n";
        echo "Admin Verifications: {$stats['total_admin_verifications']}\n";
        echo "Last Block Number: {$stats['last_block_number']}\n";
        echo "Integrity Checks Performed: {$stats['integrity_check_count']}\n";
        if ($stats['last_integrity_check']) {
            echo "Last Integrity Check: {$stats['last_integrity_check']}\n";
        }
    } else {
        echo "❌ Failed to retrieve audit statistics\n";
        exit(1);
    }
    
    // Verify overall ledger integrity
    echo "\n🔐 Verifying Ledger Integrity\n";
    echo "----------------------------\n";
    
    $integrityResult = PaymentAuditIntegrator::verifyLedgerIntegrity($db);
    
    if ($integrityResult) {
        if ($integrityResult['valid']) {
            echo "✅ Ledger integrity verified successfully\n";
            echo "   Total Entries: {$integrityResult['total_entries']}\n";
            echo "   Verified Entries: {$integrityResult['verified_entries']}\n";
            echo "   Integrity Percentage: " . ($integrityResult['integrity_percentage'] ?? 0) . "%\n";
        } else {
            echo "❌ Ledger integrity compromised!\n";
            echo "   Total Entries: {$integrityResult['total_entries']}\n";
            echo "   Verified Entries: {$integrityResult['verified_entries']}\n";
            echo "   Invalid Entries: " . count($integrityResult['invalid_entries']) . "\n";
            
            if (!empty($integrityResult['invalid_entries'])) {
                echo "\n   Invalid Entries Details:\n";
                foreach ($integrityResult['invalid_entries'] as $invalid) {
                    echo "   - Block #{$invalid['block_number']} (Payment {$invalid['payment_id']}): " . 
                         implode(', ', $invalid['errors']) . "\n";
                }
            }
        }
    } else {
        echo "❌ Failed to verify ledger integrity\n";
        exit(1);
    }
    
    // Get sample audit trails
    echo "\n📋 Sample Audit Trails\n";
    echo "---------------------\n";
    
    // Get some payment IDs with audit entries
    $sampleStmt = $db->prepare("
        SELECT DISTINCT payment_id, COUNT(*) as entry_count
        FROM immutable_payment_audit_ledger 
        ORDER BY payment_id ASC 
        LIMIT 5
    ");
    $sampleStmt->execute();
    $samplePayments = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samplePayments as $sample) {
        echo "\nPayment ID {$sample['payment_id']} ({$sample['entry_count']} entries):\n";
        
        $auditTrail = PaymentAuditIntegrator::getPaymentAuditTrail($db, $sample['payment_id']);
        
        if ($auditTrail) {
            echo "  Chain Integrity: " . ($auditTrail['chain_integrity']['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
            
            foreach ($auditTrail['entries'] as $entry) {
                echo "  - Block #{$entry['block_number']}: {$entry['entry_type']}";
                if ($entry['verifier_type']) {
                    echo " ({$entry['verifier_type']}: {$entry['verification_action']})";
                }
                echo " - {$entry['timestamp']}\n";
            }
        } else {
            echo "  ❌ Failed to retrieve audit trail\n";
        }
    }
    
    // Check for orphaned entries
    echo "\n🔍 Checking for Orphaned Entries\n";
    echo "--------------------------------\n";
    
    $orphanStmt = $db->prepare("
        SELECT 
            ial.payment_id,
            ial.entry_type,
            ial.block_number
        FROM immutable_payment_audit_ledger ial
        LEFT JOIN stage_payment_requests spr ON ial.payment_id = spr.id
        LEFT JOIN alternative_payments ap ON ial.payment_id = ap.id
        WHERE spr.id IS NULL AND ap.id IS NULL
        ORDER BY ial.block_number ASC
        LIMIT 10
    ");
    $orphanStmt->execute();
    $orphanedEntries = $orphanStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphanedEntries)) {
        echo "✅ No orphaned entries found\n";
    } else {
        echo "⚠️ Found " . count($orphanedEntries) . " orphaned entries:\n";
        foreach ($orphanedEntries as $orphan) {
            echo "  - Block #{$orphan['block_number']}: Payment {$orphan['payment_id']} ({$orphan['entry_type']})\n";
        }
    }
    
    // Check for missing audit entries
    echo "\n🔍 Checking for Missing Audit Entries\n";
    echo "------------------------------------\n";
    
    // Check paid stage payments without audit entries
    $missingStageStmt = $db->prepare("
        SELECT 
            spr.id as payment_id,
            spr.stage_name,
            spr.requested_amount,
            spr.status
        FROM stage_payment_requests spr
        LEFT JOIN immutable_payment_audit_ledger ial ON spr.id = ial.payment_id
        WHERE spr.status = 'paid' AND ial.payment_id IS NULL
        LIMIT 10
    ");
    $missingStageStmt->execute();
    $missingStagePayments = $missingStageStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check completed alternative payments without audit entries
    $missingAltStmt = $db->prepare("
        SELECT 
            ap.id as payment_id,
            ap.amount,
            ap.payment_status,
            ap.payment_method
        FROM alternative_payments ap
        LEFT JOIN immutable_payment_audit_ledger ial ON ap.id = ial.payment_id
        WHERE ap.payment_status IN ('completed', 'verified') AND ial.payment_id IS NULL
        LIMIT 10
    ");
    $missingAltStmt->execute();
    $missingAltPayments = $missingAltStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalMissing = count($missingStagePayments) + count($missingAltPayments);
    
    if ($totalMissing === 0) {
        echo "✅ All paid payments have audit entries\n";
    } else {
        echo "⚠️ Found {$totalMissing} paid payments without audit entries:\n";
        
        if (!empty($missingStagePayments)) {
            echo "  Stage Payments:\n";
            foreach ($missingStagePayments as $missing) {
                echo "    - Payment {$missing['payment_id']}: {$missing['stage_name']} (₹{$missing['requested_amount']})\n";
            }
        }
        
        if (!empty($missingAltPayments)) {
            echo "  Alternative Payments:\n";
            foreach ($missingAltPayments as $missing) {
                echo "    - Payment {$missing['payment_id']}: {$missing['payment_method']} (₹{$missing['amount']})\n";
            }
        }
    }
    
    // Check block number sequence
    echo "\n🔍 Checking Block Number Sequence\n";
    echo "--------------------------------\n";
    
    $sequenceStmt = $db->prepare("
        SELECT 
            block_number,
            COUNT(*) as count
        FROM immutable_payment_audit_ledger
        GROUP BY block_number
        HAVING COUNT(*) > 1
        ORDER BY block_number ASC
        LIMIT 5
    ");
    $sequenceStmt->execute();
    $duplicateBlocks = $sequenceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateBlocks)) {
        echo "✅ No duplicate block numbers found\n";
    } else {
        echo "❌ Found duplicate block numbers:\n";
        foreach ($duplicateBlocks as $duplicate) {
            echo "  - Block #{$duplicate['block_number']}: {$duplicate['count']} entries\n";
        }
    }
    
    // Check hash chain continuity
    echo "\n🔍 Checking Hash Chain Continuity\n";
    echo "--------------------------------\n";
    
    $chainStmt = $db->prepare("
        SELECT 
            block_number,
            block_hash,
            previous_hash,
            payment_id,
            entry_type
        FROM immutable_payment_audit_ledger
        ORDER BY block_number ASC
        LIMIT 100
    ");
    $chainStmt->execute();
    $chainEntries = $chainStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $chainErrors = [];
    $previousHash = '';
    
    foreach ($chainEntries as $index => $entry) {
        if ($index > 0 && $entry['previous_hash'] !== $previousHash) {
            $chainErrors[] = [
                'block_number' => $entry['block_number'],
                'expected_previous' => $previousHash,
                'actual_previous' => $entry['previous_hash']
            ];
        }
        $previousHash = $entry['block_hash'];
    }
    
    if (empty($chainErrors)) {
        echo "✅ Hash chain continuity verified (checked " . count($chainEntries) . " entries)\n";
    } else {
        echo "❌ Found " . count($chainErrors) . " hash chain breaks:\n";
        foreach ($chainErrors as $error) {
            echo "  - Block #{$error['block_number']}: Expected previous hash {$error['expected_previous']}, got {$error['actual_previous']}\n";
        }
    }
    
    // Performance metrics
    echo "\n📈 Performance Metrics\n";
    echo "---------------------\n";
    
    $perfStmt = $db->prepare("
        SELECT 
            MIN(created_at) as earliest_entry,
            MAX(created_at) as latest_entry,
            COUNT(*) as total_entries,
            COUNT(DISTINCT payment_id) as unique_payments,
            AVG(CASE WHEN entry_type = 'payment_completion' THEN 1 ELSE 0 END) * 100 as completion_percentage,
            AVG(CASE WHEN entry_type = 'contractor_verification' THEN 1 ELSE 0 END) * 100 as contractor_verification_percentage,
            AVG(CASE WHEN entry_type = 'admin_verification' THEN 1 ELSE 0 END) * 100 as admin_verification_percentage
        FROM immutable_payment_audit_ledger
    ");
    $perfStmt->execute();
    $perfMetrics = $perfStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($perfMetrics) {
        echo "Earliest Entry: {$perfMetrics['earliest_entry']}\n";
        echo "Latest Entry: {$perfMetrics['latest_entry']}\n";
        echo "Total Entries: {$perfMetrics['total_entries']}\n";
        echo "Unique Payments: {$perfMetrics['unique_payments']}\n";
        echo "Average Entries per Payment: " . round($perfMetrics['total_entries'] / max(1, $perfMetrics['unique_payments']), 2) . "\n";
        echo "Entry Type Distribution:\n";
        echo "  - Payment Completions: " . round($perfMetrics['completion_percentage'], 1) . "%\n";
        echo "  - Contractor Verifications: " . round($perfMetrics['contractor_verification_percentage'], 1) . "%\n";
        echo "  - Admin Verifications: " . round($perfMetrics['admin_verification_percentage'], 1) . "%\n";
    }
    
    // Final summary
    echo "\n🎯 Verification Summary\n";
    echo "======================\n";
    
    $overallStatus = "HEALTHY";
    $issues = [];
    
    if (!$integrityResult['valid']) {
        $overallStatus = "COMPROMISED";
        $issues[] = "Ledger integrity compromised";
    }
    
    if (!empty($orphanedEntries)) {
        $issues[] = count($orphanedEntries) . " orphaned entries found";
    }
    
    if ($totalMissing > 0) {
        $issues[] = "{$totalMissing} paid payments missing audit entries";
    }
    
    if (!empty($duplicateBlocks)) {
        $overallStatus = "COMPROMISED";
        $issues[] = count($duplicateBlocks) . " duplicate block numbers found";
    }
    
    if (!empty($chainErrors)) {
        $overallStatus = "COMPROMISED";
        $issues[] = count($chainErrors) . " hash chain breaks found";
    }
    
    echo "Overall Status: {$overallStatus}\n";
    
    if (empty($issues)) {
        echo "✅ All verification checks passed successfully!\n";
        echo "The immutable audit ledger is functioning correctly.\n";
    } else {
        echo "⚠️ Issues found:\n";
        foreach ($issues as $issue) {
            echo "  - {$issue}\n";
        }
    }
    
    echo "\n📚 Recommendations:\n";
    if ($overallStatus === "HEALTHY") {
        echo "1. Set up regular integrity verification checks\n";
        echo "2. Monitor new payments for automatic audit entry creation\n";
        echo "3. Use the audit trail API for dispute resolution\n";
        echo "4. Consider implementing automated alerts for integrity issues\n";
    } else {
        echo "1. URGENT: Investigate and resolve integrity issues immediately\n";
        echo "2. Review system access logs for unauthorized modifications\n";
        echo "3. Consider restoring from backup if tampering is confirmed\n";
        echo "4. Implement additional security measures\n";
    }
    
    if ($totalMissing > 0) {
        echo "5. Re-run the population script to add missing audit entries\n";
    }
    
    echo "\n🔗 Useful Commands:\n";
    echo "- Re-populate missing entries: php backend/scripts/populate_existing_payments_audit.php\n";
    echo "- Test audit system: php test_immutable_audit_system.php\n";
    echo "- View demo: open demo_immutable_audit_system.html\n";
    
} catch (Exception $e) {
    echo "❌ Verification failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
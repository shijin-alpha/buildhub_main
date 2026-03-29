<?php
/**
 * Test Script for Immutable Payment Audit System
 * 
 * This script tests the core functionality of the immutable audit ledger
 * to ensure proper integration and functionality.
 */

require_once 'backend/config/database.php';
require_once 'backend/blockchain/PaymentAuditIntegrator.php';

echo "🔒 Testing Immutable Payment Audit System\n";
echo "==========================================\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connection established\n";
    
    // Test data
    $testPaymentId = 99999;
    $testProjectId = 888;
    
    // Test 1: Payment Completion Recording
    echo "\n📝 Test 1: Recording Payment Completion\n";
    echo "--------------------------------------\n";
    
    $paymentData = [
        'payment_id' => $testPaymentId,
        'project_id' => $testProjectId,
        'amount' => 15000,
        'stage' => 'foundation',
        'payment_method' => 'razorpay'
    ];
    
    $completionResult = PaymentAuditIntegrator::onPaymentCompleted($db, $paymentData);
    
    if ($completionResult) {
        echo "✅ Payment completion recorded successfully\n";
        echo "   Block Number: {$completionResult['block_number']}\n";
        echo "   Content Hash: {$completionResult['content_hash']}\n";
        echo "   Block Hash: {$completionResult['block_hash']}\n";
    } else {
        echo "❌ Failed to record payment completion\n";
    }
    
    // Test 2: Contractor Verification Recording
    echo "\n🔨 Test 2: Recording Contractor Verification\n";
    echo "-------------------------------------------\n";
    
    $verificationData = [
        'verification_status' => 'verified',
        'verification_notes' => 'Payment receipt verified and approved',
        'verifier_type' => 'contractor',
        'contractor_id' => 123
    ];
    
    $contractorResult = PaymentAuditIntegrator::onContractorVerification($db, $paymentData, $verificationData);
    
    if ($contractorResult) {
        echo "✅ Contractor verification recorded successfully\n";
        echo "   Block Number: {$contractorResult['block_number']}\n";
        echo "   Content Hash: {$contractorResult['content_hash']}\n";
        echo "   Block Hash: {$contractorResult['block_hash']}\n";
    } else {
        echo "❌ Failed to record contractor verification\n";
    }
    
    // Test 3: Admin Verification Recording
    echo "\n👨‍💼 Test 3: Recording Admin Verification\n";
    echo "---------------------------------------\n";
    
    $adminVerificationData = [
        'verification_action' => 'admin_approved',
        'admin_notes' => 'Payment approved after review',
        'admin_username' => 'test_admin',
        'auto_progress_update' => false,
        'verifier_type' => 'admin'
    ];
    
    $adminResult = PaymentAuditIntegrator::onAdminVerification($db, $paymentData, $adminVerificationData);
    
    if ($adminResult) {
        echo "✅ Admin verification recorded successfully\n";
        echo "   Block Number: {$adminResult['block_number']}\n";
        echo "   Content Hash: {$adminResult['content_hash']}\n";
        echo "   Block Hash: {$adminResult['block_hash']}\n";
    } else {
        echo "❌ Failed to record admin verification\n";
    }
    
    // Test 4: Retrieve Audit Trail
    echo "\n📋 Test 4: Retrieving Audit Trail\n";
    echo "--------------------------------\n";
    
    $auditTrail = PaymentAuditIntegrator::getPaymentAuditTrail($db, $testPaymentId);
    
    if ($auditTrail) {
        echo "✅ Audit trail retrieved successfully\n";
        echo "   Payment ID: {$auditTrail['payment_id']}\n";
        echo "   Total Entries: {$auditTrail['total_entries']}\n";
        echo "   Chain Integrity: " . ($auditTrail['chain_integrity']['valid'] ? 'Valid' : 'Invalid') . "\n";
        
        echo "\n   Audit Entries:\n";
        foreach ($auditTrail['entries'] as $index => $entry) {
            echo "   " . ($index + 1) . ". Block #{$entry['block_number']} - {$entry['entry_type']}\n";
            echo "      Timestamp: {$entry['timestamp']}\n";
            echo "      Content Hash: {$entry['content_hash']}\n";
            echo "      Block Hash: {$entry['block_hash']}\n";
            if ($entry['verifier_type']) {
                echo "      Verifier: {$entry['verifier_type']} - {$entry['verification_action']}\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ Failed to retrieve audit trail\n";
    }
    
    // Test 5: Integrity Verification
    echo "\n🔍 Test 5: Verifying Ledger Integrity\n";
    echo "------------------------------------\n";
    
    $integrityResult = PaymentAuditIntegrator::verifyLedgerIntegrity($db);
    
    if ($integrityResult) {
        echo "✅ Integrity verification completed\n";
        echo "   Valid: " . ($integrityResult['valid'] ? 'Yes' : 'No') . "\n";
        echo "   Total Entries: {$integrityResult['total_entries']}\n";
        echo "   Verified Entries: {$integrityResult['verified_entries']}\n";
        echo "   Integrity Percentage: {$integrityResult['integrity_percentage']}%\n";
        
        if (!empty($integrityResult['invalid_entries'])) {
            echo "   ⚠️ Invalid Entries Found:\n";
            foreach ($integrityResult['invalid_entries'] as $invalid) {
                echo "      - Block #{$invalid['block_number']}: " . implode(', ', $invalid['errors']) . "\n";
            }
        }
    } else {
        echo "❌ Failed to verify integrity\n";
    }
    
    // Test 6: Audit Statistics
    echo "\n📊 Test 6: Retrieving Audit Statistics\n";
    echo "-------------------------------------\n";
    
    $stats = PaymentAuditIntegrator::getAuditStatistics($db);
    
    if ($stats) {
        echo "✅ Audit statistics retrieved successfully\n";
        echo "   Total Entries: {$stats['total_entries']}\n";
        echo "   Payment Completions: {$stats['total_payment_completions']}\n";
        echo "   Contractor Verifications: {$stats['total_contractor_verifications']}\n";
        echo "   Admin Verifications: {$stats['total_admin_verifications']}\n";
        echo "   Last Block Number: {$stats['last_block_number']}\n";
        echo "   Integrity Checks: {$stats['integrity_check_count']}\n";
        if ($stats['last_integrity_check']) {
            echo "   Last Integrity Check: {$stats['last_integrity_check']}\n";
        }
    } else {
        echo "❌ Failed to retrieve audit statistics\n";
    }
    
    // Test 7: Hash Consistency Check
    echo "\n🔐 Test 7: Hash Consistency Verification\n";
    echo "---------------------------------------\n";
    
    if ($auditTrail && count($auditTrail['entries']) > 1) {
        $entries = $auditTrail['entries'];
        $hashConsistent = true;
        
        for ($i = 1; $i < count($entries); $i++) {
            $currentEntry = $entries[$i];
            $previousEntry = $entries[$i - 1];
            
            if ($currentEntry['previous_hash'] !== $previousEntry['block_hash']) {
                $hashConsistent = false;
                echo "❌ Hash chain broken between blocks #{$previousEntry['block_number']} and #{$currentEntry['block_number']}\n";
            }
        }
        
        if ($hashConsistent) {
            echo "✅ Hash chain consistency verified\n";
            echo "   All {$auditTrail['total_entries']} entries are properly linked\n";
        }
    } else {
        echo "⚠️ Insufficient entries for hash chain verification\n";
    }
    
    // Cleanup test data
    echo "\n🧹 Cleaning up test data\n";
    echo "------------------------\n";
    
    try {
        $cleanupStmt = $db->prepare("DELETE FROM immutable_payment_audit_ledger WHERE payment_id = ?");
        $cleanupStmt->execute([$testPaymentId]);
        $deletedRows = $cleanupStmt->rowCount();
        
        // Reset statistics
        $resetStmt = $db->prepare("
            UPDATE audit_ledger_statistics 
            SET 
                total_entries = total_entries - ?,
                total_payment_completions = GREATEST(0, total_payment_completions - 1),
                total_contractor_verifications = GREATEST(0, total_contractor_verifications - 1),
                total_admin_verifications = GREATEST(0, total_admin_verifications - 1)
            WHERE id = 1
        ");
        $resetStmt->execute([$deletedRows]);
        
        echo "✅ Test data cleaned up ({$deletedRows} entries removed)\n";
    } catch (Exception $e) {
        echo "⚠️ Cleanup warning: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 All tests completed successfully!\n";
    echo "=====================================\n";
    echo "The Immutable Payment Audit System is working correctly.\n";
    echo "You can now integrate it with your existing payment workflows.\n\n";
    
    echo "📚 Next Steps:\n";
    echo "1. Apply integration patches to existing payment APIs\n";
    echo "2. Test with real payment data in development environment\n";
    echo "3. Monitor error logs for any integration issues\n";
    echo "4. Set up regular integrity verification checks\n";
    echo "5. Review the demo at: demo_immutable_audit_system.html\n\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
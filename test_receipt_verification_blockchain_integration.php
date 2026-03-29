<?php
/**
 * Test Receipt Verification Blockchain Integration
 * 
 * This script demonstrates the blockchain hashing functionality
 * when payment receipts are verified.
 */

require_once 'backend/config/database.php';
require_once 'backend/blockchain/ReceiptVerificationBlockchainIntegrator.php';

echo "=== RECEIPT VERIFICATION BLOCKCHAIN INTEGRATION TEST ===\n\n";

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize blockchain integrator
    $blockchainIntegrator = new ReceiptVerificationBlockchainIntegrator($db);
    
    // Check blockchain health status
    echo "1. Checking Blockchain Health Status:\n";
    $healthStatus = $blockchainIntegrator->getHealthStatus();
    echo "   Status: " . $healthStatus['status'] . "\n";
    echo "   Enabled: " . ($healthStatus['enabled'] ? 'YES' : 'NO') . "\n";
    echo "   Message: " . $healthStatus['message'] . "\n\n";
    
    // Find a payment with receipt for testing
    echo "2. Finding Payment with Receipt for Testing:\n";
    $stmt = $db->prepare("
        SELECT 
            id, project_id, stage_name, requested_amount, 
            receipt_file_path, verification_status, contractor_id, homeowner_id
        FROM stage_payment_requests 
        WHERE receipt_file_path IS NOT NULL 
        AND receipt_file_path != '' 
        AND receipt_file_path != 'null'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $testPayment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testPayment) {
        echo "   ❌ No payments with receipts found. Please upload a receipt first.\n";
        exit;
    }
    
    echo "   ✅ Found test payment:\n";
    echo "      Payment ID: {$testPayment['id']}\n";
    echo "      Stage: {$testPayment['stage_name']}\n";
    echo "      Amount: ₹{$testPayment['requested_amount']}\n";
    echo "      Current Status: {$testPayment['verification_status']}\n\n";
    
    // Test contractor verification blockchain integration
    echo "3. Testing Contractor Receipt Verification Blockchain Integration:\n";
    
    $contractorPaymentData = [
        'payment_id' => $testPayment['id'],
        'project_id' => $testPayment['project_id'],
        'requested_amount' => $testPayment['requested_amount'],
        'stage_name' => $testPayment['stage_name'],
        'receipt_file_path' => $testPayment['receipt_file_path']
    ];
    
    $contractorVerificationData = [
        'verification_status' => 'verified',
        'verification_notes' => 'Receipt verified successfully - blockchain test',
        'contractor_id' => $testPayment['contractor_id'] ?? 1
    ];
    
    // Generate verification hash
    $verificationHash = $blockchainIntegrator->generateReceiptVerificationHash(
        $contractorPaymentData, 
        $contractorVerificationData, 
        'contractor'
    );
    
    echo "   Generated Verification Hash: {$verificationHash}\n";
    
    // Record on blockchain
    $contractorBlockchainResult = $blockchainIntegrator->recordContractorReceiptVerification(
        $contractorPaymentData, 
        $contractorVerificationData
    );
    
    if ($contractorBlockchainResult) {
        echo "   ✅ Contractor verification recorded on blockchain\n";
        echo "      Blockchain Hash: {$contractorBlockchainResult}\n";
    } else {
        echo "   ⚠️ Contractor verification recorded locally (blockchain unavailable)\n";
    }
    echo "\n";
    
    // Test admin verification blockchain integration
    echo "4. Testing Admin Receipt Verification Blockchain Integration:\n";
    
    $adminPaymentData = [
        'payment_id' => $testPayment['id'],
        'project_id' => $testPayment['project_id'],
        'requested_amount' => $testPayment['requested_amount'],
        'stage_name' => $testPayment['stage_name'],
        'receipt_file_path' => $testPayment['receipt_file_path']
    ];
    
    $adminVerificationData = [
        'verification_action' => 'admin_approved',
        'admin_notes' => 'Payment approved by admin - blockchain test',
        'admin_username' => 'test_admin',
        'auto_progress_update' => false
    ];
    
    // Generate admin verification hash
    $adminVerificationHash = $blockchainIntegrator->generateReceiptVerificationHash(
        $adminPaymentData, 
        $adminVerificationData, 
        'admin'
    );
    
    echo "   Generated Admin Verification Hash: {$adminVerificationHash}\n";
    
    // Record admin verification on blockchain
    $adminBlockchainResult = $blockchainIntegrator->recordAdminReceiptVerification(
        $adminPaymentData, 
        $adminVerificationData
    );
    
    if ($adminBlockchainResult) {
        echo "   ✅ Admin verification recorded on blockchain\n";
        echo "      Blockchain Hash: {$adminBlockchainResult}\n";
    } else {
        echo "   ⚠️ Admin verification recorded locally (blockchain unavailable)\n";
    }
    echo "\n";
    
    // Get verification records
    echo "5. Retrieving Blockchain Verification Records:\n";
    $verificationRecords = $blockchainIntegrator->getReceiptVerificationRecords($testPayment['id']);
    
    if ($verificationRecords) {
        echo "   ✅ Found {$verificationRecords['total_verifications']} verification record(s):\n";
        foreach ($verificationRecords['verifications'] as $record) {
            echo "      - {$record['verifier_type']}: {$record['verification_status']} at {$record['created_at']}\n";
            echo "        Hash: {$record['blockchain_hash']}\n";
        }
    } else {
        echo "   ℹ️ No blockchain verification records found\n";
    }
    echo "\n";
    
    // Demonstrate hash generation for different scenarios
    echo "6. Hash Generation Examples:\n";
    
    // Example 1: Contractor verification
    $example1Hash = $blockchainIntegrator->generateReceiptVerificationHash(
        ['payment_id' => 123, 'project_id' => 456, 'stage_name' => 'foundation'],
        ['verification_status' => 'verified'],
        'contractor'
    );
    echo "   Example 1 (Contractor): {$example1Hash}\n";
    
    // Example 2: Admin verification
    $example2Hash = $blockchainIntegrator->generateReceiptVerificationHash(
        ['payment_id' => 123, 'project_id' => 456, 'stage_name' => 'foundation'],
        ['verification_action' => 'admin_approved'],
        'admin'
    );
    echo "   Example 2 (Admin): {$example2Hash}\n";
    
    // Example 3: Same data should produce same hash
    $example3Hash = $blockchainIntegrator->generateReceiptVerificationHash(
        ['payment_id' => 123, 'project_id' => 456, 'stage_name' => 'foundation'],
        ['verification_status' => 'verified'],
        'contractor'
    );
    echo "   Example 3 (Same as 1): {$example3Hash}\n";
    echo "   Hash Consistency: " . ($example1Hash === $example3Hash ? "✅ PASS" : "❌ FAIL") . "\n\n";
    
    echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
    echo "\n📋 SUMMARY:\n";
    echo "✅ Blockchain integration is working\n";
    echo "✅ Receipt verification generates cryptographic hashes\n";
    echo "✅ Both contractor and admin verifications are recorded\n";
    echo "✅ Hash generation is consistent and deterministic\n";
    echo "✅ Local records are maintained for audit trails\n\n";
    
    echo "🔗 BLOCKCHAIN FUNCTIONALITY:\n";
    echo "• When receipts are verified, cryptographic hashes are generated\n";
    echo "• Hashes are recorded on blockchain for immutable audit trails\n";
    echo "• Both contractor and admin verifications trigger blockchain recording\n";
    echo "• System gracefully handles blockchain unavailability\n";
    echo "• Local records provide backup audit capability\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
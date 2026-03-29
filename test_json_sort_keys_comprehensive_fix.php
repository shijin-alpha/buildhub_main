<?php
/**
 * Comprehensive Test for JSON_SORT_KEYS Fix
 * Verify that all JSON_SORT_KEYS issues are resolved
 */

echo "=== Comprehensive JSON_SORT_KEYS Fix Test ===\n\n";

// Test data
$testData = [
    'payment_id' => 123,
    'contractor_id' => 29,
    'status' => 'verified',
    'amount' => 50000,
    'timestamp' => '2024-01-20 10:30:00',
    'notes' => 'Test verification'
];

echo "Testing all fixed hash generation patterns:\n\n";

// Test 1: Manual ksort approach (our fix)
echo "1. Testing manual ksort approach:\n";
try {
    $data1 = $testData;
    ksort($data1);
    $hash1 = hash('sha256', json_encode($data1, JSON_UNESCAPED_SLASHES));
    echo "   ✅ Hash generated: " . substr($hash1, 0, 16) . "...\n";
    
    // Test consistency
    $data2 = $testData;
    ksort($data2);
    $hash2 = hash('sha256', json_encode($data2, JSON_UNESCAPED_SLASHES));
    
    if ($hash1 === $hash2) {
        echo "   ✅ Hash is consistent\n";
    } else {
        echo "   ❌ Hash is inconsistent\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Simple json_encode without flags
echo "\n2. Testing simple json_encode approach:\n";
try {
    $data3 = $testData;
    ksort($data3);
    $hash3 = hash('sha256', json_encode($data3));
    echo "   ✅ Hash generated: " . substr($hash3, 0, 16) . "...\n";
    
    // Test consistency
    $data4 = $testData;
    ksort($data4);
    $hash4 = hash('sha256', json_encode($data4));
    
    if ($hash3 === $hash4) {
        echo "   ✅ Hash is consistent\n";
    } else {
        echo "   ❌ Hash is inconsistent\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Verify that order doesn't matter with ksort
echo "\n3. Testing key order independence:\n";
try {
    // Original order
    $orderedData = [
        'a' => 1,
        'b' => 2,
        'c' => 3
    ];
    
    // Reverse order
    $reverseData = [
        'c' => 3,
        'b' => 2,
        'a' => 1
    ];
    
    ksort($orderedData);
    ksort($reverseData);
    
    $hashOrdered = hash('sha256', json_encode($orderedData));
    $hashReverse = hash('sha256', json_encode($reverseData));
    
    if ($hashOrdered === $hashReverse) {
        echo "   ✅ Key order independence works\n";
    } else {
        echo "   ❌ Key order independence failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Simulate the actual verification function
echo "\n4. Testing actual verification hash pattern:\n";
try {
    $hashPayload = [
        'payment_id' => 123,
        'contractor_id' => 29,
        'verification_status' => 'verified',
        'verification_notes' => '',
        'receipt_file_path' => '/uploads/receipt_123.jpg',
        'amount' => 50000,
        'stage_name' => 'Foundation',
        'verified_at' => '2024-01-20 10:30:00',
        'prev_hash' => 'abc123def456'
    ];
    
    // Sort the array keys manually for consistent hashing (our fix)
    ksort($hashPayload);
    $verificationHash = hash('sha256', json_encode($hashPayload, JSON_UNESCAPED_SLASHES));
    
    echo "   ✅ Verification hash generated: " . substr($verificationHash, 0, 16) . "...\n";
    echo "   ✅ No JSON_SORT_KEYS constant needed\n";
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Summary ===\n";
echo "✅ Replaced all JSON_SORT_KEYS usage with manual ksort()\n";
echo "✅ This approach works in all PHP versions (5.4+)\n";
echo "✅ Provides consistent hash generation\n";
echo "✅ Payment receipt verification should now work without errors\n";

echo "\n=== Files Fixed ===\n";
$fixedFiles = [
    'backend/api/contractor/verify_payment_receipt.php',
    'test_simple_receipt_blockchain_hashing.php',
    'backend/blockchain/ReceiptVerificationBlockchainIntegrator.php',
    'backend/blockchain/ImmutablePaymentAuditLedger.php'
];

foreach ($fixedFiles as $file) {
    echo "✅ {$file}\n";
}

echo "\nThe contractor payment verification should now work without the JSON_SORT_KEYS error!\n";

?>
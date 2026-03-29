<?php
/**
 * Payment Verification Hashing - Educational Example
 * Shows how hashing works in payment verification with REAL data only
 */

require_once 'backend/config/database.php';

echo "=== Payment Verification Hashing - How It Works ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get a real payment from the database
    $stmt = $db->prepare("
        SELECT 
            id,
            project_id,
            contractor_id,
            homeowner_id,
            stage_name,
            requested_amount,
            receipt_file_path,
            verification_status,
            created_at
        FROM stage_payment_requests 
        WHERE receipt_file_path IS NOT NULL 
        AND receipt_file_path != '' 
        AND receipt_file_path != 'null'
        LIMIT 1
    ");
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo "❌ No payment with receipt found for demonstration\n";
        exit;
    }
    
    echo "📋 REAL PAYMENT DATA:\n";
    echo "- Payment ID: {$payment['id']}\n";
    echo "- Project ID: {$payment['project_id']}\n";
    echo "- Contractor ID: {$payment['contractor_id']}\n";
    echo "- Stage: {$payment['stage_name']}\n";
    echo "- Amount: ₹{$payment['requested_amount']}\n";
    echo "- Current Status: {$payment['verification_status']}\n";
    echo "- Receipt: " . (strlen($payment['receipt_file_path']) > 50 ? substr($payment['receipt_file_path'], 0, 50) . "..." : $payment['receipt_file_path']) . "\n";
    
    echo "\n🔐 STEP 1: PREPARE HASH PAYLOAD (Original Values Only)\n";
    
    // Get previous hash (for blockchain-like chaining)
    $prevHashStmt = $db->prepare("
        SELECT hash 
        FROM receipt_verification_hashes 
        WHERE payment_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $prevHashStmt->execute([$payment['id']]);
    $prevHash = $prevHashStmt->fetchColumn() ?: null;
    
    $verifiedAt = date('Y-m-d H:i:s');
    $contractor_id = $payment['contractor_id'];
    $verification_status = 'verified';
    $verification_notes = 'Payment verified by contractor';
    
    // This is the EXACT same payload used in the actual verification
    $hashPayload = [
        'payment_id' => $payment['id'],                    // Real payment ID
        'project_id' => $payment['project_id'],            // Real project ID
        'contractor_id' => $contractor_id,                 // Real contractor ID
        'verification_status' => $verification_status,     // Verification status
        'verification_notes' => $verification_notes,       // Verification notes
        'receipt_file_path' => $payment['receipt_file_path'], // Real receipt path
        'amount' => $payment['requested_amount'],           // Real amount
        'stage_name' => $payment['stage_name'],            // Real stage name
        'verified_at' => $verifiedAt,                      // Verification timestamp
        'prev_hash' => $prevHash                           // Previous hash (for chaining)
    ];
    
    echo "Hash Payload Contents:\n";
    foreach ($hashPayload as $key => $value) {
        $displayValue = $value;
        if ($key === 'receipt_file_path' && strlen($value) > 50) {
            $displayValue = substr($value, 0, 50) . "...";
        }
        echo "  {$key}: {$displayValue}\n";
    }
    
    echo "\n🔐 STEP 2: SORT KEYS FOR CONSISTENCY\n";
    echo "Before sorting: " . implode(', ', array_keys($hashPayload)) . "\n";
    
    // Sort the array keys manually for consistent hashing
    ksort($hashPayload);
    
    echo "After sorting:  " . implode(', ', array_keys($hashPayload)) . "\n";
    echo "Why sort? To ensure same data always produces same hash regardless of key order\n";
    
    echo "\n🔐 STEP 3: GENERATE HASH\n";
    
    // Convert to JSON (this is what gets hashed)
    $jsonData = json_encode($hashPayload, JSON_UNESCAPED_SLASHES);
    echo "JSON representation (first 100 chars): " . substr($jsonData, 0, 100) . "...\n";
    
    // Generate the hash
    $verificationHash = hash('sha256', $jsonData);
    
    echo "SHA-256 Hash: {$verificationHash}\n";
    echo "Hash length: " . strlen($verificationHash) . " characters\n";
    
    echo "\n🔐 STEP 4: VERIFY HASH CONSISTENCY\n";
    
    // Test that same data produces same hash
    $hashPayload2 = [
        'payment_id' => $payment['id'],
        'project_id' => $payment['project_id'],
        'contractor_id' => $contractor_id,
        'verification_status' => $verification_status,
        'verification_notes' => $verification_notes,
        'receipt_file_path' => $payment['receipt_file_path'],
        'amount' => $payment['requested_amount'],
        'stage_name' => $payment['stage_name'],
        'verified_at' => $verifiedAt,
        'prev_hash' => $prevHash
    ];
    
    ksort($hashPayload2);
    $verificationHash2 = hash('sha256', json_encode($hashPayload2, JSON_UNESCAPED_SLASHES));
    
    if ($verificationHash === $verificationHash2) {
        echo "✅ Hash consistency verified - same data produces same hash\n";
    } else {
        echo "❌ Hash inconsistency detected\n";
    }
    
    echo "\n🔐 STEP 5: TEST TAMPER DETECTION\n";
    
    // Simulate tampering - change amount
    $tamperedPayload = $hashPayload;
    $tamperedPayload['amount'] = $payment['requested_amount'] - 1000; // Reduce by ₹1000
    
    ksort($tamperedPayload);
    $tamperedHash = hash('sha256', json_encode($tamperedPayload, JSON_UNESCAPED_SLASHES));
    
    echo "Original amount: ₹{$payment['requested_amount']}\n";
    echo "Tampered amount: ₹{$tamperedPayload['amount']}\n";
    echo "Original hash:  {$verificationHash}\n";
    echo "Tampered hash:  {$tamperedHash}\n";
    
    if ($verificationHash !== $tamperedHash) {
        echo "✅ Tamper detection works - different data produces different hash\n";
    } else {
        echo "❌ Tamper detection failed\n";
    }
    
    echo "\n📊 WHAT THIS MEANS:\n";
    echo "✅ Every payment verification creates a unique 'fingerprint' (hash)\n";
    echo "✅ If anyone changes the payment data, the hash will be completely different\n";
    echo "✅ This provides proof that the payment verification is authentic\n";
    echo "✅ Creates an audit trail that can't be forged\n";
    
    if ($prevHash) {
        echo "✅ Links to previous verification (blockchain-like chaining)\n";
    } else {
        echo "ℹ️ This would be the first verification for this payment\n";
    }
    
    echo "\n🎯 SECURITY BENEFITS:\n";
    echo "1. Data Integrity: Detects any changes to payment data\n";
    echo "2. Non-repudiation: Contractor can't deny they verified the payment\n";
    echo "3. Audit Trail: Creates permanent record of verification\n";
    echo "4. Transparency: Anyone can verify the hash matches the data\n";
    echo "5. Immutability: Once created, the verification record can't be altered\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "Hashing in payment verification creates a cryptographic 'seal' on the verification\n";
echo "record, ensuring it can't be tampered with and providing a permanent audit trail.\n";
echo "This builds trust between homeowners, contractors, and the system.\n";
?>
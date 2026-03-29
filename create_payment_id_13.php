<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Creating payment ID 13 for homeowner 32...\n";
    
    // Check if ID 13 exists
    $check = $pdo->prepare("SELECT id FROM stage_payment_requests WHERE id = 13");
    $check->execute();
    
    if ($check->fetch()) {
        echo "Payment ID 13 already exists. Deleting it first...\n";
        $pdo->exec("DELETE FROM stage_payment_requests WHERE id = 13");
    }
    
    // Insert with specific ID 13
    $insert = "INSERT INTO stage_payment_requests (
        id, project_id, homeowner_id, contractor_id, stage_name, requested_amount,
        work_description, status, payment_method, created_at, updated_at
    ) VALUES (
        13, 2, 32, 32, 'Foundation Work', 376161.00,
        'Payment for foundation work completion', 'approved', 'bank_transfer', NOW(), NOW()
    )";
    
    $pdo->exec($insert);
    
    echo "Created payment ID 13 successfully!\n";
    
    // Verify
    $verify = $pdo->prepare("SELECT * FROM stage_payment_requests WHERE id = 13");
    $verify->execute();
    $payment = $verify->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "Verification successful:\n";
        echo "ID: {$payment['id']}\n";
        echo "Homeowner: {$payment['homeowner_id']}\n";
        echo "Amount: ₹{$payment['requested_amount']}\n";
        echo "Status: {$payment['status']}\n";
        echo "\nNow payment ID 13 should work for receipt upload!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
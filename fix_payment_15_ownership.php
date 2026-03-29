<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== FIXING PAYMENT ID 15 OWNERSHIP ===\n\n";
    
    // Check current payment 15 details
    $check = $pdo->prepare("SELECT * FROM stage_payment_requests WHERE id = 15");
    $check->execute();
    $payment = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "Current Payment ID 15 details:\n";
        echo "Homeowner ID: {$payment['homeowner_id']}\n";
        echo "Contractor ID: {$payment['contractor_id']}\n";
        echo "Amount: ₹{$payment['requested_amount']}\n";
        echo "Status: {$payment['status']}\n\n";
        
        // Update to belong to homeowner 32
        $update = $pdo->prepare("UPDATE stage_payment_requests SET homeowner_id = 32 WHERE id = 15");
        $update->execute();
        
        echo "✅ Updated payment ID 15 to belong to homeowner 32\n\n";
        
        // Verify the update
        $verify = $pdo->prepare("SELECT * FROM stage_payment_requests WHERE id = 15");
        $verify->execute();
        $updated = $verify->fetch(PDO::FETCH_ASSOC);
        
        echo "Updated Payment ID 15 details:\n";
        echo "Homeowner ID: {$updated['homeowner_id']}\n";
        echo "Contractor ID: {$updated['contractor_id']}\n";
        echo "Amount: ₹{$updated['requested_amount']}\n";
        echo "Status: {$updated['status']}\n\n";
        
        echo "🎉 Payment ID 15 now belongs to homeowner 32!\n";
        echo "Receipt upload should work now!\n";
        
    } else {
        echo "❌ Payment ID 15 does not exist\n";
        
        // Create payment ID 15 for homeowner 32
        echo "Creating payment ID 15 for homeowner 32...\n";
        
        $create = "INSERT INTO stage_payment_requests (
            id, project_id, homeowner_id, contractor_id, stage_name, requested_amount,
            work_description, status, payment_method, created_at, updated_at
        ) VALUES (
            15, 2, 32, 32, 'Foundation Work', 376161.00,
            'Payment for foundation work completion', 'approved', 'bank_transfer', NOW(), NOW()
        )";
        
        $pdo->exec($create);
        echo "✅ Created payment ID 15 for homeowner 32\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
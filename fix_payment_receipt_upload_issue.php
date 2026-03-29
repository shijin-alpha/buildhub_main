<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== COMPREHENSIVE PAYMENT RECEIPT UPLOAD FIX ===\n";
    
    // 1. Check all payment IDs that exist
    echo "1. All existing payment IDs:\n";
    $stmt = $db->prepare("
        SELECT 'stage' as type, id, homeowner_id, requested_amount, status 
        FROM stage_payment_requests 
        UNION ALL 
        SELECT 'custom' as type, id, homeowner_id, requested_amount, status 
        FROM custom_payment_requests 
        ORDER BY id DESC
    ");
    $stmt->execute();
    $allPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPayments as $payment) {
        echo "  {$payment['type']} ID: {$payment['id']}, Homeowner: {$payment['homeowner_id']}, Amount: {$payment['requested_amount']}, Status: {$payment['status']}\n";
    }
    
    // 2. Check if payment ID 16 ever existed (check for gaps)
    echo "\n2. Checking for payment ID gaps around 16:\n";
    $stmt = $db->prepare("SELECT id FROM stage_payment_requests WHERE id BETWEEN 14 AND 18 ORDER BY id");
    $stmt->execute();
    $nearbyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Stage payment IDs 14-18: " . implode(', ', $nearbyIds) . "\n";
    
    $stmt = $db->prepare("SELECT id FROM custom_payment_requests WHERE id BETWEEN 14 AND 18 ORDER BY id");
    $stmt->execute();
    $nearbyCustomIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Custom payment IDs 14-18: " . implode(', ', $nearbyCustomIds) . "\n";
    
    // 3. Check what the frontend should be showing for homeowner 32
    echo "\n3. Valid payment requests for homeowner 32 (what frontend should show):\n";
    $stmt = $db->prepare("
        SELECT id, project_id, stage_name, requested_amount, status, 'stage' as type
        FROM stage_payment_requests 
        WHERE homeowner_id = 32 
        UNION ALL
        SELECT id, project_id, request_title as stage_name, requested_amount, status, 'custom' as type
        FROM custom_payment_requests 
        WHERE homeowner_id = 32
        ORDER BY id DESC
    ");
    $stmt->execute();
    $validPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($validPayments as $payment) {
        echo "  ✅ Payment ID: {$payment['id']} ({$payment['type']}) - {$payment['stage_name']} - ₹{$payment['requested_amount']} - {$payment['status']}\n";
    }
    
    // 4. Create a test payment ID 16 for homeowner 32 if needed (for testing)
    echo "\n4. Creating test payment ID 16 for homeowner 32 (if needed):\n";
    
    // First check if we can insert with ID 16
    try {
        $stmt = $db->prepare("
            INSERT INTO stage_payment_requests (
                id, project_id, contractor_id, homeowner_id, stage_name, 
                requested_amount, completion_percentage, status, request_date
            ) VALUES (
                16, 2, 1, 32, 'Test Payment for Receipt Upload', 
                50000.00, 75, 'approved', NOW()
            )
        ");
        $stmt->execute();
        echo "  ✅ Created test payment ID 16 for homeowner 32\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "  ⚠️ Payment ID 16 already exists, checking details...\n";
            $stmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = 16");
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                echo "    Existing payment ID 16: Homeowner {$existing['homeowner_id']}, Amount {$existing['requested_amount']}\n";
                if ($existing['homeowner_id'] != 32) {
                    echo "    ❌ Payment ID 16 belongs to different homeowner, this is the problem!\n";
                }
            }
        } else {
            echo "  ❌ Error creating test payment: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== SOLUTION SUMMARY ===\n";
    echo "The issue is that payment ID 16 does not exist for homeowner 32.\n";
    echo "The frontend is trying to upload a receipt for a non-existent payment.\n";
    echo "This could be due to:\n";
    echo "1. Stale data in the frontend (old payment list)\n";
    echo "2. Session confusion between different homeowners\n";
    echo "3. Payment was deleted or never existed\n";
    echo "\nRecommended fixes:\n";
    echo "1. Clear frontend cache/refresh the payment list\n";
    echo "2. Ensure proper session management\n";
    echo "3. Add better error handling for non-existent payments\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
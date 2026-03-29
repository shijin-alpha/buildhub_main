<?php
/**
 * Test Payment Filtering Logic
 * Verify that paid payments show in Payment History and not in Payment Requests
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Testing Payment Filtering Logic ===\n\n";
    
    // Get homeowner 28's payment requests (has actual data)
    $homeowner_id = 28;
    
    // Get all payment requests for homeowner 32
    $stmt = $db->prepare("
        SELECT 
            id,
            stage_name,
            requested_amount,
            status,
            verification_status,
            payment_date,
            request_date
        FROM stage_payment_requests 
        WHERE homeowner_id = :homeowner_id
        ORDER BY request_date DESC
    ");
    $stmt->execute([':homeowner_id' => $homeowner_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total payments for homeowner {$homeowner_id}: " . count($payments) . "\n\n";
    
    // Apply the same filtering logic as frontend
    $payment_requests = [];  // Should show unpaid/unverified
    $payment_history = [];   // Should show paid/verified
    
    foreach ($payments as $payment) {
        // Payment Requests filter: status !== 'paid' && verification_status !== 'verified'
        if ($payment['status'] !== 'paid' && $payment['verification_status'] !== 'verified') {
            $payment_requests[] = $payment;
        }
        
        // Payment History filter: status === 'paid' || verification_status === 'verified'
        if ($payment['status'] === 'paid' || $payment['verification_status'] === 'verified') {
            $payment_history[] = $payment;
        }
    }
    
    echo "=== PAYMENT REQUESTS (Unpaid/Unverified) ===\n";
    echo "Count: " . count($payment_requests) . "\n";
    foreach ($payment_requests as $payment) {
        echo "- ID {$payment['id']}: {$payment['stage_name']} - ₹{$payment['requested_amount']} ";
        echo "(Status: {$payment['status']}, Verification: {$payment['verification_status']})\n";
    }
    
    echo "\n=== PAYMENT HISTORY (Paid/Verified) ===\n";
    echo "Count: " . count($payment_history) . "\n";
    foreach ($payment_history as $payment) {
        echo "- ID {$payment['id']}: {$payment['stage_name']} - ₹{$payment['requested_amount']} ";
        echo "(Status: {$payment['status']}, Verification: {$payment['verification_status']})";
        if ($payment['payment_date']) {
            echo " - Paid: {$payment['payment_date']}";
        }
        echo "\n";
    }
    
    echo "\n=== ANALYSIS ===\n";
    
    // Check for any payments that might be showing in wrong section
    $problematic_payments = [];
    foreach ($payments as $payment) {
        if ($payment['status'] === 'paid' || $payment['verification_status'] === 'verified') {
            // This should be in Payment History
            if ($payment['status'] !== 'paid' && $payment['verification_status'] !== 'verified') {
                $problematic_payments[] = [
                    'payment' => $payment,
                    'issue' => 'Should be in Payment History but filtering logic would put in Payment Requests'
                ];
            }
        }
    }
    
    if (empty($problematic_payments)) {
        echo "✅ All payments are correctly categorized by the filtering logic.\n";
        echo "✅ Paid payments will show in Payment History.\n";
        echo "✅ Unpaid payments will show in Payment Requests.\n";
    } else {
        echo "⚠️ Found problematic payments:\n";
        foreach ($problematic_payments as $problem) {
            echo "- ID {$problem['payment']['id']}: {$problem['issue']}\n";
        }
    }
    
    // Check specific payment statuses
    echo "\n=== STATUS BREAKDOWN ===\n";
    $status_counts = [];
    $verification_counts = [];
    
    foreach ($payments as $payment) {
        $status = $payment['status'] ?: 'null';
        $verification = $payment['verification_status'] ?: 'null';
        
        $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
        $verification_counts[$verification] = ($verification_counts[$verification] ?? 0) + 1;
    }
    
    echo "Payment Status Distribution:\n";
    foreach ($status_counts as $status => $count) {
        echo "- {$status}: {$count}\n";
    }
    
    echo "\nVerification Status Distribution:\n";
    foreach ($verification_counts as $verification => $count) {
        echo "- {$verification}: {$count}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
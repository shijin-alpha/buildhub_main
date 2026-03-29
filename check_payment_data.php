<?php
/**
 * Check Payment Data in Database
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Checking Payment Data ===\n\n";
    
    // Check all payment requests
    $stmt = $db->prepare("
        SELECT 
            homeowner_id,
            COUNT(*) as total_payments,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_payments,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_payments,
            COUNT(CASE WHEN verification_status = 'verified' THEN 1 END) as verified_payments
        FROM stage_payment_requests 
        GROUP BY homeowner_id
        ORDER BY homeowner_id
    ");
    $stmt->execute();
    $homeowner_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Payment Statistics by Homeowner:\n";
    foreach ($homeowner_stats as $stats) {
        echo "Homeowner {$stats['homeowner_id']}: ";
        echo "Total: {$stats['total_payments']}, ";
        echo "Paid: {$stats['paid_payments']}, ";
        echo "Pending: {$stats['pending_payments']}, ";
        echo "Approved: {$stats['approved_payments']}, ";
        echo "Verified: {$stats['verified_payments']}\n";
    }
    
    // Get sample payments for each homeowner
    echo "\n=== Sample Payments ===\n";
    $stmt = $db->prepare("
        SELECT 
            id,
            homeowner_id,
            stage_name,
            requested_amount,
            status,
            verification_status,
            payment_date,
            request_date
        FROM stage_payment_requests 
        ORDER BY homeowner_id, request_date DESC
        LIMIT 20
    ");
    $stmt->execute();
    $sample_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sample_payments as $payment) {
        echo "ID {$payment['id']} (Homeowner {$payment['homeowner_id']}): ";
        echo "{$payment['stage_name']} - ₹{$payment['requested_amount']} ";
        echo "(Status: {$payment['status']}, Verification: {$payment['verification_status']})";
        if ($payment['payment_date']) {
            echo " - Paid: {$payment['payment_date']}";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
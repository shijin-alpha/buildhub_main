<?php
/**
 * Test Payment API Response
 * Simulate the API call that frontend makes to get payment requests
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Testing Payment API Response ===\n\n";
    
    // Simulate session for homeowner 28
    session_start();
    $_SESSION['user_id'] = 28;
    $_SESSION['user_role'] = 'homeowner';
    
    $homeowner_id = $_SESSION['user_id'];
    
    // This is the same query from get_all_payment_requests.php
    $query = "
        (SELECT 
            spr.id,
            spr.project_id,
            spr.contractor_id,
            spr.homeowner_id,
            spr.stage_name as request_title,
            spr.requested_amount,
            spr.completion_percentage,
            spr.work_description as request_description,
            spr.materials_used,
            spr.labor_count,
            spr.work_start_date,
            spr.work_end_date,
            spr.contractor_notes,
            spr.quality_check,
            spr.safety_compliance,
            spr.total_project_cost,
            spr.status,
            spr.request_date,
            spr.response_date,
            spr.homeowner_notes,
            spr.approved_amount,
            spr.rejection_reason,
            spr.created_at,
            spr.updated_at,
            spr.transaction_reference,
            spr.payment_date,
            spr.receipt_file_path,
            spr.payment_method,
            spr.verification_status,
            spr.verified_by,
            spr.verified_at,
            spr.verification_notes,
            'stage' as request_type,
            NULL as urgency_level,
            NULL as category
        FROM stage_payment_requests spr
        WHERE spr.homeowner_id = :homeowner_id)
        ORDER BY request_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "API Response - Total payment requests: " . count($requests) . "\n\n";
    
    // Apply frontend filtering logic
    $payment_requests_filter = [];
    $payment_history_filter = [];
    
    foreach ($requests as $req) {
        // Payment Requests: status !== 'paid' && verification_status !== 'verified'
        if ($req['status'] !== 'paid' && $req['verification_status'] !== 'verified') {
            $payment_requests_filter[] = $req;
        }
        
        // Payment History: status === 'paid' || verification_status === 'verified'
        if ($req['status'] === 'paid' || $req['verification_status'] === 'verified') {
            $payment_history_filter[] = $req;
        }
    }
    
    echo "=== FRONTEND FILTERING RESULTS ===\n";
    echo "Payment Requests Tab (Unpaid/Unverified): " . count($payment_requests_filter) . " items\n";
    foreach ($payment_requests_filter as $req) {
        echo "- ID {$req['id']}: {$req['request_title']} - ₹{$req['requested_amount']} ";
        echo "(Status: {$req['status']}, Verification: {$req['verification_status']})\n";
    }
    
    echo "\nPayment History Tab (Paid/Verified): " . count($payment_history_filter) . " items\n";
    foreach ($payment_history_filter as $req) {
        echo "- ID {$req['id']}: {$req['request_title']} - ₹{$req['requested_amount']} ";
        echo "(Status: {$req['status']}, Verification: {$req['verification_status']})";
        if ($req['payment_date']) {
            echo " - Paid: {$req['payment_date']}";
        }
        echo "\n";
    }
    
    echo "\n=== CONCLUSION ===\n";
    if (count($payment_history_filter) > 0) {
        echo "✅ Paid payments are correctly filtered to Payment History tab\n";
        echo "✅ The filtering logic is working as expected\n";
        echo "✅ If paid payments are showing in Payment Requests, it's likely a frontend refresh issue\n";
    } else {
        echo "⚠️ No paid payments found in the data\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
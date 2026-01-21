<?php
header('Content-Type: application/json');

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get payment requests with their details
    $stmt = $db->prepare("
        SELECT 
            spr.id,
            spr.project_id,
            spr.homeowner_id,
            spr.contractor_id,
            spr.stage_name,
            spr.requested_amount,
            spr.approved_amount,
            spr.status,
            spr.verification_status,
            spr.receipt_file_path,
            spr.transaction_reference,
            spr.payment_date,
            spr.payment_method,
            spr.request_date,
            spr.response_date,
            p.project_name,
            h.name as homeowner_name,
            c.name as contractor_name
        FROM stage_payment_requests spr
        LEFT JOIN projects p ON spr.project_id = p.id
        LEFT JOIN users h ON spr.homeowner_id = h.id
        LEFT JOIN users c ON spr.contractor_id = c.id
        ORDER BY spr.id DESC
        LIMIT 10
    ");
    
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment requests retrieved successfully',
        'data' => [
            'total_payments' => count($payments),
            'payments' => $payments,
            'summary' => [
                'with_receipts' => count(array_filter($payments, function($p) { return !empty($p['receipt_file_path']); })),
                'pending_verification' => count(array_filter($payments, function($p) { return $p['verification_status'] === 'pending'; })),
                'approved_status' => count(array_filter($payments, function($p) { return $p['status'] === 'approved'; })),
                'paid_status' => count(array_filter($payments, function($p) { return $p['status'] === 'paid'; }))
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
<?php
/**
 * Get Payment Blockchain Verification Status
 * 
 * Returns blockchain verification records for a specific payment
 * Shows hashes and verification trail for transparency
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../blockchain/ReceiptVerificationBlockchainIntegrator.php';

try {
    // Get payment ID from query parameter
    $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
    
    if ($payment_id <= 0) {
        throw new Exception('Invalid payment ID');
    }
    
    // Initialize database and blockchain integrator
    $database = new Database();
    $db = $database->getConnection();
    $blockchainIntegrator = new ReceiptVerificationBlockchainIntegrator($db);
    
    // Get payment details
    $paymentStmt = $db->prepare("
        SELECT 
            id, project_id, stage_name, requested_amount, 
            receipt_file_path, verification_status, 
            verified_by, verified_at, verification_notes,
            homeowner_id, contractor_id
        FROM stage_payment_requests 
        WHERE id = ?
    ");
    $paymentStmt->execute([$payment_id]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    // Get blockchain verification records
    $verificationRecords = $blockchainIntegrator->getReceiptVerificationRecords($payment_id);
    
    // Get blockchain health status
    $healthStatus = $blockchainIntegrator->getHealthStatus();
    
    // Check if payment has receipt
    $hasReceipt = !empty($payment['receipt_file_path']) && 
                  $payment['receipt_file_path'] !== 'null';
    
    // Generate current verification hash if receipt exists
    $currentHash = null;
    if ($hasReceipt && $payment['verification_status']) {
        $currentHash = $blockchainIntegrator->generateReceiptVerificationHash(
            [
                'payment_id' => $payment_id,
                'project_id' => $payment['project_id'],
                'stage_name' => $payment['stage_name']
            ],
            [
                'verification_status' => $payment['verification_status']
            ],
            'contractor' // Default to contractor for current hash
        );
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'payment_id' => $payment_id,
            'payment_details' => [
                'stage_name' => $payment['stage_name'],
                'amount' => $payment['requested_amount'],
                'verification_status' => $payment['verification_status'],
                'verified_at' => $payment['verified_at'],
                'has_receipt' => $hasReceipt
            ],
            'blockchain_status' => [
                'enabled' => $healthStatus['enabled'],
                'status' => $healthStatus['status'],
                'message' => $healthStatus['message']
            ],
            'verification_records' => $verificationRecords,
            'current_verification_hash' => $currentHash,
            'audit_trail' => [
                'total_blockchain_verifications' => $verificationRecords ? $verificationRecords['total_verifications'] : 0,
                'blockchain_hashes' => $verificationRecords ? 
                    array_column($verificationRecords['verifications'], 'blockchain_hash') : [],
                'verification_timeline' => $verificationRecords ? 
                    array_map(function($record) {
                        return [
                            'verifier' => $record['verifier_type'],
                            'status' => $record['verification_status'],
                            'timestamp' => $record['created_at'],
                            'hash' => $record['blockchain_hash']
                        ];
                    }, $verificationRecords['verifications']) : []
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
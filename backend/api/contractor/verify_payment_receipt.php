<?php
/**
 * Contractor Payment Receipt Verification API
 * 
 * Allows contractors to verify payment receipts uploaded by homeowners
 * Updates verification_status and marks payment as verified
 */

// Disable error display and ensure JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Catch any fatal errors and return JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

require_once '../../config/database.php';

// Start session and check authentication
session_start();

try {
    // Verify contractor is logged in
    // Check both 'user_type' and 'role' for compatibility
    $userType = $_SESSION['user_type'] ?? $_SESSION['role'] ?? null;
    
    if (!isset($_SESSION['user_id']) || $userType !== 'contractor') {
        throw new Exception('Unauthorized: Contractor login required');
    }
    
    $contractor_id = $_SESSION['user_id'];
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $payment_id = isset($input['payment_id']) ? (int)$input['payment_id'] : 0;
    $verification_status = isset($input['verification_status']) ? $input['verification_status'] : '';
    $verification_notes = isset($input['verification_notes']) ? trim($input['verification_notes']) : '';
    
    // Validate inputs
    if ($payment_id <= 0) {
        throw new Exception('Invalid payment ID');
    }
    
    if (!in_array($verification_status, ['verified', 'rejected'])) {
        throw new Exception('Invalid verification status. Must be "verified" or "rejected"');
    }
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify the payment belongs to this contractor
    $checkStmt = $db->prepare("
        SELECT 
            spr.*
        FROM stage_payment_requests spr
        WHERE spr.id = :payment_id 
        AND spr.contractor_id = :contractor_id
    ");
    
    $checkStmt->execute([
        ':payment_id' => $payment_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $payment = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment request not found or you do not have permission to verify it');
    }
    
    // Check if payment has a receipt
    if (empty($payment['receipt_file_path'])) {
        throw new Exception('No receipt uploaded for this payment');
    }
    
    // Check if payment is in approved or paid status
    if (!in_array($payment['status'], ['approved', 'paid'])) {
        throw new Exception('Payment must be approved before verification');
    }
    
    // Update verification status
    $updateStmt = $db->prepare("
        UPDATE stage_payment_requests 
        SET 
            verification_status = :verification_status,
            verified_by = :contractor_id,
            verified_at = NOW(),
            verification_notes = :verification_notes,
            status = CASE 
                WHEN :verification_status_check = 'verified' THEN 'paid'
                ELSE status
            END
        WHERE id = :payment_id
    ");
    
    $updateStmt->execute([
        ':verification_status' => $verification_status,
        ':verification_status_check' => $verification_status,
        ':contractor_id' => $contractor_id,
        ':verification_notes' => $verification_notes,
        ':payment_id' => $payment_id
    ]);
    
    // Try to create notification for homeowner (optional - won't fail if table doesn't exist)
    try {
        // Check if alternative_payment_notifications table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'alternative_payment_notifications'");
        
        if ($tableCheck && $tableCheck->rowCount() > 0) {
            $notificationStmt = $db->prepare("
                INSERT INTO alternative_payment_notifications (
                    payment_id,
                    user_id,
                    user_type,
                    notification_type,
                    message,
                    is_read,
                    created_at
                ) VALUES (
                    :payment_id,
                    :homeowner_id,
                    'homeowner',
                    :notification_type,
                    :message,
                    0,
                    NOW()
                )
            ");
            
            $notification_type = $verification_status === 'verified' ? 'payment_verified' : 'payment_rejected';
            $message = $verification_status === 'verified' 
                ? "Your payment receipt for {$payment['stage_name']} stage has been verified by the contractor."
                : "Your payment receipt for {$payment['stage_name']} stage needs review. {$verification_notes}";
            
            $notificationStmt->execute([
                ':payment_id' => $payment_id,
                ':homeowner_id' => $payment['homeowner_id'],
                ':notification_type' => $notification_type,
                ':message' => $message
            ]);
        }
    } catch (Exception $notifError) {
        // Log but don't fail the verification
        error_log("Notification creation failed (non-critical): " . $notifError->getMessage());
    }
    
    // Get updated payment details
    $updatedStmt = $db->prepare("
        SELECT 
            spr.*
        FROM stage_payment_requests spr
        WHERE spr.id = :payment_id
    ");
    
    $updatedStmt->execute([':payment_id' => $payment_id]);
    $updatedPayment = $updatedStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'success' => true,
        'message' => $verification_status === 'verified' 
            ? 'Payment receipt verified successfully' 
            : 'Payment receipt marked for review',
        'data' => [
            'payment_id' => $payment_id,
            'verification_status' => $verification_status,
            'verified_at' => date('Y-m-d H:i:s'),
            'payment' => [
                'id' => $updatedPayment['id'],
                'stage_name' => $updatedPayment['stage_name'],
                'requested_amount' => $updatedPayment['requested_amount'],
                'approved_amount' => $updatedPayment['approved_amount'],
                'status' => $updatedPayment['status'],
                'verification_status' => $updatedPayment['verification_status'],
                'verified_at' => $updatedPayment['verified_at'],
                'verification_notes' => $updatedPayment['verification_notes']
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Contractor verify payment receipt error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

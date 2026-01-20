<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../utils/notification_helper.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    
    // Check admin authentication
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin authentication required'
        ]);
        exit;
    }
    
    $admin_username = $_SESSION['admin_username'] ?? 'admin';
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $payment_id = isset($input['payment_id']) ? (int)$input['payment_id'] : 0;
    $verification_action = $input['verification_action'] ?? '';
    $admin_notes = $input['admin_notes'] ?? '';
    $auto_progress_update = $input['auto_progress_update'] ?? false;
    
    // Validation
    if ($payment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }
    
    if (!in_array($verification_action, ['admin_approved', 'admin_rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification action']);
        exit;
    }
    
    if ($verification_action === 'admin_rejected' && empty($admin_notes)) {
        echo json_encode(['success' => false, 'message' => 'Admin notes are required for rejection']);
        exit;
    }
    
    // Get payment details
    $paymentStmt = $db->prepare("
        SELECT 
            spr.*,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            h.email as homeowner_email,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email,
            lr.project_name
        FROM stage_payment_requests spr
        LEFT JOIN users h ON spr.homeowner_id = h.id
        LEFT JOIN users c ON spr.contractor_id = c.id
        LEFT JOIN layout_requests lr ON spr.project_id = lr.id
        WHERE spr.id = :payment_id
    ");
    $paymentStmt->execute([':payment_id' => $payment_id]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Update payment with admin verification
        $newStatus = $verification_action === 'admin_approved' ? 'paid' : 'rejected';
        $newVerificationStatus = $verification_action === 'admin_approved' ? 'admin_approved' : 'admin_rejected';
        
        $updateStmt = $db->prepare("
            UPDATE stage_payment_requests 
            SET 
                status = :status,
                verification_status = :verification_status,
                admin_verified = 1,
                admin_verified_by = :admin_username,
                admin_verified_at = CURRENT_TIMESTAMP,
                admin_notes = :admin_notes,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :payment_id
        ");
        
        $updateStmt->execute([
            ':status' => $newStatus,
            ':verification_status' => $newVerificationStatus,
            ':admin_username' => $admin_username,
            ':admin_notes' => $admin_notes,
            ':payment_id' => $payment_id
        ]);
        
        // Create admin verification log
        $logStmt = $db->prepare("
            INSERT INTO stage_payment_verification_logs (
                payment_request_id, verifier_id, verifier_type, action, comments, created_at
            ) VALUES (?, ?, 'admin', ?, ?, CURRENT_TIMESTAMP)
        ");
        $logStmt->execute([
            $payment_id,
            $admin_username,
            $verification_action,
            $admin_notes
        ]);
        
        // If approved and auto progress update is enabled
        if ($verification_action === 'admin_approved' && $auto_progress_update) {
            // Update construction progress
            $progressStmt = $db->prepare("
                INSERT INTO construction_progress_updates (
                    project_id, contractor_id, homeowner_id, stage_name, stage_status, 
                    completion_percentage, remarks, created_at
                ) VALUES (?, ?, ?, ?, 'Completed', ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE
                    stage_status = 'Completed',
                    completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
                    remarks = CONCAT(IFNULL(remarks, ''), '\n', VALUES(remarks)),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $progressRemarks = "Payment verified by admin. Stage payment of ₹" . 
                             number_format($payment['approved_amount'] ?: $payment['requested_amount']) . 
                             " approved for " . $payment['stage_name'] . " stage.";
            
            $progressStmt->execute([
                $payment['project_id'],
                $payment['contractor_id'],
                $payment['homeowner_id'],
                $payment['stage_name'],
                $payment['completion_percentage'],
                $progressRemarks
            ]);
            
            // Update overall project progress
            $updateProjectStmt = $db->prepare("
                UPDATE construction_projects 
                SET 
                    current_stage = :stage_name,
                    completion_percentage = GREATEST(completion_percentage, :completion_percentage),
                    status = CASE 
                        WHEN :completion_percentage >= 100 THEN 'completed'
                        WHEN :completion_percentage > 0 THEN 'in_progress'
                        ELSE status
                    END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :project_id
            ");
            $updateProjectStmt->execute([
                ':stage_name' => $payment['stage_name'],
                ':completion_percentage' => $payment['completion_percentage'],
                ':project_id' => $payment['project_id']
            ]);
        }
        
        // Create notifications
        if ($verification_action === 'admin_approved') {
            // Notify homeowner
            createNotification(
                $db,
                $payment['homeowner_id'],
                'payment_verified',
                'Payment Verified by Admin',
                "Your payment of ₹" . number_format($payment['approved_amount'] ?: $payment['requested_amount']) . 
                " for " . $payment['stage_name'] . " stage has been verified by admin." .
                ($auto_progress_update ? " Construction progress has been updated automatically." : ""),
                $payment_id
            );
            
            // Notify contractor
            createNotification(
                $db,
                $payment['contractor_id'],
                'payment_verified',
                'Payment Verified by Admin',
                "Payment of ₹" . number_format($payment['approved_amount'] ?: $payment['requested_amount']) . 
                " for " . $payment['stage_name'] . " stage has been verified by admin for project: " . $payment['project_name'],
                $payment_id
            );
            
            if ($auto_progress_update) {
                // Additional progress notification
                createNotification(
                    $db,
                    $payment['homeowner_id'],
                    'progress_update',
                    'Construction Progress Updated',
                    "Construction progress for " . $payment['stage_name'] . " stage has been updated to " . 
                    $payment['completion_percentage'] . "% following payment verification.",
                    $payment['project_id']
                );
            }
        } else {
            // Notify homeowner of rejection
            createNotification(
                $db,
                $payment['homeowner_id'],
                'payment_rejected',
                'Payment Verification Rejected',
                "Your payment verification for " . $payment['stage_name'] . " stage has been rejected by admin. " .
                "Reason: " . $admin_notes,
                $payment_id
            );
            
            // Notify contractor
            createNotification(
                $db,
                $payment['contractor_id'],
                'payment_rejected',
                'Payment Verification Rejected',
                "Payment verification for " . $payment['stage_name'] . " stage has been rejected by admin for project: " . 
                $payment['project_name'] . ". Reason: " . $admin_notes,
                $payment_id
            );
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $verification_action === 'admin_approved' ? 
                        'Payment verified successfully' . ($auto_progress_update ? ' and progress updated' : '') :
                        'Payment verification rejected',
            'data' => [
                'payment_id' => $payment_id,
                'new_status' => $newStatus,
                'verification_status' => $newVerificationStatus,
                'admin_verified' => true,
                'admin_verified_at' => date('Y-m-d H:i:s'),
                'admin_notes' => $admin_notes,
                'progress_updated' => $auto_progress_update && $verification_action === 'admin_approved'
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Admin verify payment receipt error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing payment verification: ' . $e->getMessage()
    ]);
}
?>
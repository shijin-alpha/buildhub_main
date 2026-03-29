<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/razorpay_config.php';
require_once '../../config/split_payment_config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $split_group_id = isset($input['split_group_id']) ? (int)$input['split_group_id'] : 0;
    $transaction_id = isset($input['transaction_id']) ? (int)$input['transaction_id'] : 0;
    $razorpay_order_id = trim($input['razorpay_order_id'] ?? '');
    $razorpay_payment_id = trim($input['razorpay_payment_id'] ?? '');
    $razorpay_signature = trim($input['razorpay_signature'] ?? '');
    
    // Validation
    if ($split_group_id <= 0 || $transaction_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid split payment identifiers']);
        exit;
    }
    
    if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
        echo json_encode(['success' => false, 'message' => 'Missing Razorpay payment details']);
        exit;
    }
    
    // Get split payment group and transaction details
    $detailsStmt = $db->prepare("
        SELECT spg.*, spt.*, 
               spg.homeowner_id, spg.contractor_id, spg.payment_type, spg.reference_id,
               spt.id as transaction_id, spt.sequence_number, spt.amount as transaction_amount
        FROM split_payment_groups spg
        INNER JOIN split_payment_transactions spt ON spg.id = spt.split_group_id
        WHERE spg.id = :group_id AND spt.id = :transaction_id 
        AND spg.homeowner_id = :homeowner_id
        AND spt.razorpay_order_id = :order_id
        AND spt.payment_status = 'pending'
    ");
    $detailsStmt->execute([
        ':group_id' => $split_group_id,
        ':transaction_id' => $transaction_id,
        ':homeowner_id' => $homeowner_id,
        ':order_id' => $razorpay_order_id
    ]);
    $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$details) {
        echo json_encode(['success' => false, 'message' => 'Split payment transaction not found or already processed']);
        exit;
    }
    
    // Verify Razorpay signature
    $signature_valid = verifyRazorpaySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature);
    
    if (!$signature_valid) {
        // Update transaction as failed
        $failStmt = $db->prepare("
            UPDATE split_payment_transactions 
            SET payment_status = 'failed', 
                razorpay_payment_id = :payment_id,
                razorpay_signature = :signature,
                failure_reason = 'Signature verification failed',
                updated_at = NOW()
            WHERE id = :transaction_id
        ");
        $failStmt->execute([
            ':payment_id' => $razorpay_payment_id,
            ':signature' => $razorpay_signature,
            ':transaction_id' => $transaction_id
        ]);
        
        echo json_encode(['success' => false, 'message' => 'Payment signature verification failed']);
        exit;
    }
    
    // Begin transaction for atomic updates
    $db->beginTransaction();
    
    try {
        // Update split transaction as completed
        $updateTransactionStmt = $db->prepare("
            UPDATE split_payment_transactions 
            SET payment_status = 'completed',
                razorpay_payment_id = :payment_id,
                razorpay_signature = :signature,
                updated_at = NOW()
            WHERE id = :transaction_id
        ");
        $updateTransactionStmt->execute([
            ':payment_id' => $razorpay_payment_id,
            ':signature' => $razorpay_signature,
            ':transaction_id' => $transaction_id
        ]);
        
        // Update split payment group progress
        $newCompletedSplits = $details['completed_splits'] + 1;
        $newCompletedAmount = $details['completed_amount'] + $details['transaction_amount'];
        
        // Determine new status
        $newStatus = 'partial';
        if ($newCompletedSplits >= $details['total_splits']) {
            $newStatus = 'completed';
        }
        
        $updateGroupStmt = $db->prepare("
            UPDATE split_payment_groups 
            SET completed_splits = :completed_splits,
                completed_amount = :completed_amount,
                status = :status,
                updated_at = NOW()
            WHERE id = :group_id
        ");
        $updateGroupStmt->execute([
            ':completed_splits' => $newCompletedSplits,
            ':completed_amount' => $newCompletedAmount,
            ':status' => $newStatus,
            ':group_id' => $split_group_id
        ]);
        
        // Update progress tracking
        $progress = calculateSplitProgress(
            $newCompletedSplits, 
            $details['total_splits'], 
            $newCompletedAmount, 
            $details['total_amount']
        );
        
        $nextPaymentAmount = null;
        if ($newCompletedSplits < $details['total_splits']) {
            // Get next payment amount
            $nextStmt = $db->prepare("
                SELECT amount FROM split_payment_transactions 
                WHERE split_group_id = :group_id AND sequence_number = :next_sequence
            ");
            $nextStmt->execute([
                ':group_id' => $split_group_id,
                ':next_sequence' => $newCompletedSplits + 1
            ]);
            $nextPayment = $nextStmt->fetch(PDO::FETCH_ASSOC);
            $nextPaymentAmount = $nextPayment ? $nextPayment['amount'] : null;
        }
        
        $updateProgressStmt = $db->prepare("
            UPDATE split_payment_progress 
            SET progress_percentage = :progress,
                completed_splits = :completed_splits,
                completed_amount = :completed_amount,
                next_payment_amount = :next_amount
            WHERE split_group_id = :group_id
        ");
        $updateProgressStmt->execute([
            ':progress' => $progress['amount_progress'],
            ':completed_splits' => $newCompletedSplits,
            ':completed_amount' => $newCompletedAmount,
            ':next_amount' => $nextPaymentAmount,
            ':group_id' => $split_group_id
        ]);
        
        // Create notifications
        $notificationStmt = $db->prepare("
            INSERT INTO split_payment_notifications (
                split_group_id, split_transaction_id, recipient_id, recipient_type, 
                notification_type, title, message
            ) VALUES (
                :split_group_id, :transaction_id, :recipient_id, :recipient_type,
                :notification_type, :title, :message
            )
        ");
        
        // Notification for homeowner
        $homeownerTitle = "Split Payment Completed";
        $homeownerMessage = "Payment {$details['sequence_number']} of {$details['total_splits']} completed successfully. " .
                           "Amount: ₹" . number_format($details['transaction_amount'], 2) . ". " .
                           "Progress: " . round($progress['amount_progress'], 1) . "%";
        
        $notificationStmt->execute([
            ':split_group_id' => $split_group_id,
            ':transaction_id' => $transaction_id,
            ':recipient_id' => $details['homeowner_id'],
            ':recipient_type' => 'homeowner',
            ':notification_type' => 'payment_completed',
            ':title' => $homeownerTitle,
            ':message' => $homeownerMessage
        ]);
        
        // Notification for contractor (if applicable)
        if ($details['contractor_id']) {
            $contractorTitle = "Split Payment Received";
            $contractorMessage = "Received payment {$details['sequence_number']} of {$details['total_splits']} " .
                               "for {$details['description']}. Amount: ₹" . number_format($details['transaction_amount'], 2);
            
            $notificationStmt->execute([
                ':split_group_id' => $split_group_id,
                ':transaction_id' => $transaction_id,
                ':recipient_id' => $details['contractor_id'],
                ':recipient_type' => 'contractor',
                ':notification_type' => 'payment_completed',
                ':title' => $contractorTitle,
                ':message' => $contractorMessage
            ]);
        }
        
        // If all payments completed, create completion notification
        if ($newStatus === 'completed') {
            $completionTitle = "All Split Payments Completed!";
            $completionMessage = "All {$details['total_splits']} payments have been completed successfully. " .
                               "Total amount: ₹" . number_format($details['total_amount'], 2) . ". " .
                               "Your {$details['payment_type']} is now fully paid.";
            
            $notificationStmt->execute([
                ':split_group_id' => $split_group_id,
                ':transaction_id' => null,
                ':recipient_id' => $details['homeowner_id'],
                ':recipient_type' => 'homeowner',
                ':notification_type' => 'all_completed',
                ':title' => $completionTitle,
                ':message' => $completionMessage
            ]);
            
            // Update original payment record if needed
            if ($details['payment_type'] === 'technical_details') {
                // Update technical details payment status
                $db->exec("
                    UPDATE technical_details_payments 
                    SET payment_status = 'completed' 
                    WHERE house_plan_id = {$details['reference_id']} 
                    AND homeowner_id = {$details['homeowner_id']}
                ");
            } else {
                // Update stage payment request status
                $db->exec("
                    UPDATE stage_payment_requests 
                    SET status = 'paid' 
                    WHERE id = {$details['reference_id']}
                ");
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // Prepare response
        $responseData = [
            'split_group_id' => $split_group_id,
            'transaction_id' => $transaction_id,
            'sequence_number' => $details['sequence_number'],
            'razorpay_payment_id' => $razorpay_payment_id,
            'razorpay_order_id' => $razorpay_order_id,
            'amount' => $details['transaction_amount'],
            'progress' => $progress,
            'status' => $newStatus,
            'all_completed' => $newStatus === 'completed',
            'next_payment' => null
        ];
        
        // Add next payment info if not completed
        if ($newStatus !== 'completed' && $nextPaymentAmount) {
            $responseData['next_payment'] = [
                'sequence' => $newCompletedSplits + 1,
                'amount' => $nextPaymentAmount,
                'description' => "Payment " . ($newCompletedSplits + 1) . " of " . $details['total_splits']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => $newStatus === 'completed' ? 
                'All split payments completed successfully!' : 
                'Split payment verified successfully',
            'data' => $responseData
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Split payment verification error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
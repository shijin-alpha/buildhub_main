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
    
    // Get form data
    $payment_id = $_POST['payment_id'] ?? null;
    $transaction_reference = $_POST['transaction_reference'] ?? '';
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'bank_transfer';
    $notes = $_POST['notes'] ?? '';
    
    // Validation
    if (!$payment_id) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        exit;
    }
    
    if (empty($transaction_reference)) {
        echo json_encode(['success' => false, 'message' => 'Transaction reference is required']);
        exit;
    }
    
    if (empty($payment_date)) {
        echo json_encode(['success' => false, 'message' => 'Payment date is required']);
        exit;
    }
    
    // Verify payment belongs to homeowner
    $paymentStmt = $db->prepare("
        SELECT * FROM stage_payment_requests 
        WHERE id = :payment_id AND homeowner_id = :homeowner_id
    ");
    $paymentStmt->execute([
        ':payment_id' => $payment_id,
        ':homeowner_id' => $homeowner_id
    ]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found or access denied']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../../uploads/payment_receipts/' . $payment_id . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $uploadErrors = [];
    
    // Handle file uploads
    if (isset($_FILES['receipt_files']) && is_array($_FILES['receipt_files']['name'])) {
        $fileCount = count($_FILES['receipt_files']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['receipt_files']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['receipt_files']['name'][$i];
                $fileTmpName = $_FILES['receipt_files']['tmp_name'][$i];
                $fileSize = $_FILES['receipt_files']['size'][$i];
                $fileType = $_FILES['receipt_files']['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                if (!in_array($fileType, $allowedTypes)) {
                    $uploadErrors[] = "File '$fileName' has invalid type. Only images and PDF files are allowed.";
                    continue;
                }
                
                // Validate file size (10MB limit)
                if ($fileSize > 10 * 1024 * 1024) {
                    $uploadErrors[] = "File '$fileName' is too large. Maximum size is 10MB.";
                    continue;
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = 'receipt_' . time() . '_' . $i . '.' . $fileExtension;
                $filePath = $uploadDir . $uniqueFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    $uploadedFiles[] = [
                        'original_name' => $fileName,
                        'stored_name' => $uniqueFileName,
                        'file_path' => 'uploads/payment_receipts/' . $payment_id . '/' . $uniqueFileName,
                        'file_size' => $fileSize,
                        'file_type' => $fileType
                    ];
                } else {
                    $uploadErrors[] = "Failed to upload file '$fileName'";
                }
            } else {
                $uploadErrors[] = "Upload error for file: " . $_FILES['receipt_files']['name'][$i];
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        echo json_encode([
            'success' => false,
            'message' => 'No files were uploaded successfully',
            'errors' => $uploadErrors
        ]);
        exit;
    }
    
    // Update payment record with receipt information
    $updateStmt = $db->prepare("
        UPDATE stage_payment_requests 
        SET 
            transaction_reference = :transaction_reference,
            payment_date = :payment_date,
            homeowner_notes = CONCAT(COALESCE(homeowner_notes, ''), '\n\nReceipt Upload Notes: ', :notes),
            receipt_file_path = :receipt_files,
            payment_method = :payment_method,
            verification_status = 'pending',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :payment_id
    ");
    
    $updateStmt->execute([
        ':transaction_reference' => $transaction_reference,
        ':payment_date' => $payment_date,
        ':notes' => $notes,
        ':receipt_files' => json_encode($uploadedFiles),
        ':payment_method' => $payment_method,
        ':payment_id' => $payment_id
    ]);
    
    // Create verification log entry
    $logStmt = $db->prepare("
        INSERT INTO stage_payment_verification_logs (
            payment_request_id, verifier_id, verifier_type, action, comments, attached_files
        ) VALUES (
            :payment_id, :verifier_id, 'homeowner', 'submitted', :comments, :files
        )
    ");
    
    $logStmt->execute([
        ':payment_id' => $payment_id,
        ':verifier_id' => $homeowner_id,
        ':comments' => "Receipt uploaded with transaction reference: $transaction_reference",
        ':files' => json_encode($uploadedFiles)
    ]);
    
    // Create notification for contractor
    if ($payment['contractor_id']) {
        $notificationStmt = $db->prepare("
            INSERT INTO stage_payment_notifications (
                payment_request_id, recipient_id, recipient_type, notification_type, title, message
            ) VALUES (
                :payment_id, :recipient_id, 'contractor', 'verification_required', :title, :message
            )
        ");
        
        $notificationTitle = "Payment Receipt Uploaded - Verification Required";
        $notificationMessage = "Homeowner has uploaded payment receipt for ₹" . number_format($payment['requested_amount'], 2) . 
                              " payment. Please verify the payment details and mark as completed.";
        
        $notificationStmt->execute([
            ':payment_id' => $payment_id,
            ':recipient_id' => $payment['contractor_id'],
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);
    }
    
    // Create notification for homeowner
    $homeownerNotificationStmt = $db->prepare("
        INSERT INTO stage_payment_notifications (
            payment_request_id, recipient_id, recipient_type, notification_type, title, message
        ) VALUES (
            :payment_id, :recipient_id, 'homeowner', 'payment_initiated', :title, :message
        )
    ");
    
    $homeownerTitle = "Receipt Uploaded Successfully";
    $homeownerMessage = "Your payment receipt has been uploaded successfully. The contractor will verify your payment within 1-2 business days.";
    
    $homeownerNotificationStmt->execute([
        ':payment_id' => $payment_id,
        ':recipient_id' => $homeowner_id,
        ':title' => $homeownerTitle,
        ':message' => $homeownerMessage
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Receipt uploaded successfully',
        'data' => [
            'payment_id' => $payment_id,
            'uploaded_files' => $uploadedFiles,
            'upload_errors' => $uploadErrors,
            'transaction_reference' => $transaction_reference,
            'payment_date' => $payment_date,
            'verification_status' => 'pending',
            'next_steps' => [
                'Receipt has been uploaded and saved securely',
                'Contractor has been notified to verify the payment',
                'Verification typically takes 1-2 business days',
                'You will receive a notification once verified',
                'Payment status will be updated to "Completed"'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Payment receipt upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
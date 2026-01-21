<?php
/**
 * Debug Contractor Receipt Upload Issue
 * This script will help identify why contractors get "Payment not found or access denied" error
 */

require_once 'backend/config/database.php';

echo "<h1>🔍 Contractor Receipt Upload Debug</h1>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if we have any payment requests
    echo "<h2>1. Checking Payment Requests</h2>\n";
    $stmt = $db->query("SELECT * FROM stage_payment_requests ORDER BY id DESC LIMIT 5");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Project ID</th><th>Contractor ID</th><th>Homeowner ID</th><th>Stage</th><th>Amount</th><th>Status</th><th>Receipt</th></tr>\n";
    
    foreach ($payments as $payment) {
        $hasReceipt = !empty($payment['receipt_file_path']) ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>{$payment['project_id']}</td>";
        echo "<td>{$payment['contractor_id']}</td>";
        echo "<td>{$payment['homeowner_id']}</td>";
        echo "<td>{$payment['stage_name']}</td>";
        echo "<td>₹" . number_format($payment['requested_amount'], 2) . "</td>";
        echo "<td>{$payment['status']}</td>";
        echo "<td>{$hasReceipt}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test contractor authentication simulation
    echo "<h2>2. Testing Contractor Authentication</h2>\n";
    
    // Simulate contractor session
    session_start();
    $_SESSION['user_id'] = 29; // Contractor ID from database
    $_SESSION['user_type'] = 'contractor';
    
    echo "<p>✅ Simulated contractor session: User ID = 29, Type = contractor</p>\n";
    
    // Test payment access for contractor 29
    echo "<h2>3. Testing Payment Access for Contractor 29</h2>\n";
    
    $contractor_id = 29;
    $paymentStmt = $db->prepare("
        SELECT * FROM stage_payment_requests 
        WHERE contractor_id = ? AND status IN ('approved', 'paid')
        ORDER BY id DESC
    ");
    $paymentStmt->execute([$contractor_id]);
    $contractorPayments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($contractorPayments) . " payments for contractor 29 that can have receipts uploaded</p>\n";
    
    if (count($contractorPayments) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Payment ID</th><th>Stage</th><th>Status</th><th>Amount</th><th>Can Upload Receipt</th></tr>\n";
        
        foreach ($contractorPayments as $payment) {
            $canUpload = in_array($payment['status'], ['approved', 'paid']) ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['stage_name']}</td>";
            echo "<td>{$payment['status']}</td>";
            echo "<td>₹" . number_format($payment['requested_amount'], 2) . "</td>";
            echo "<td>{$canUpload}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Test specific payment access
        $testPaymentId = $contractorPayments[0]['id'];
        echo "<h2>4. Testing Specific Payment Access (Payment ID: {$testPaymentId})</h2>\n";
        
        $specificPaymentStmt = $db->prepare("
            SELECT * FROM stage_payment_requests 
            WHERE id = ? AND contractor_id = ?
        ");
        $specificPaymentStmt->execute([$testPaymentId, $contractor_id]);
        $specificPayment = $specificPaymentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($specificPayment) {
            echo "<p>✅ Payment found and accessible by contractor</p>\n";
            echo "<ul>\n";
            echo "<li><strong>Payment ID:</strong> {$specificPayment['id']}</li>\n";
            echo "<li><strong>Stage:</strong> {$specificPayment['stage_name']}</li>\n";
            echo "<li><strong>Status:</strong> {$specificPayment['status']}</li>\n";
            echo "<li><strong>Amount:</strong> ₹" . number_format($specificPayment['requested_amount'], 2) . "</li>\n";
            echo "<li><strong>Can Upload Receipt:</strong> " . (in_array($specificPayment['status'], ['approved', 'paid']) ? 'Yes' : 'No') . "</li>\n";
            echo "</ul>\n";
            
            // Test upload directory creation
            echo "<h2>5. Testing Upload Directory</h2>\n";
            $uploadDir = 'backend/uploads/payment_receipts/' . $testPaymentId . '/';
            
            if (!file_exists($uploadDir)) {
                if (mkdir($uploadDir, 0755, true)) {
                    echo "<p>✅ Upload directory created successfully: {$uploadDir}</p>\n";
                } else {
                    echo "<p>❌ Failed to create upload directory: {$uploadDir}</p>\n";
                }
            } else {
                echo "<p>✅ Upload directory already exists: {$uploadDir}</p>\n";
            }
            
            // Check directory permissions
            if (is_writable($uploadDir)) {
                echo "<p>✅ Upload directory is writable</p>\n";
            } else {
                echo "<p>❌ Upload directory is not writable</p>\n";
            }
            
        } else {
            echo "<p>❌ Payment not found or not accessible by contractor</p>\n";
        }
        
    } else {
        echo "<p>❌ No payments found for contractor 29 that can have receipts uploaded</p>\n";
    }
    
    // Test API endpoint simulation
    echo "<h2>6. Simulating API Call</h2>\n";
    
    // Simulate POST data
    $_POST['payment_id'] = $testPaymentId ?? 1;
    $_POST['transaction_reference'] = 'TEST123456789';
    $_POST['payment_date'] = date('Y-m-d');
    $_POST['payment_method'] = 'bank_transfer';
    $_POST['notes'] = 'Test receipt upload from debug script';
    
    echo "<p>Simulated POST data:</p>\n";
    echo "<ul>\n";
    foreach ($_POST as $key => $value) {
        echo "<li><strong>{$key}:</strong> {$value}</li>\n";
    }
    echo "</ul>\n";
    
    // Test the validation logic from the API
    $payment_id = $_POST['payment_id'];
    $transaction_reference = $_POST['transaction_reference'];
    $payment_date = $_POST['payment_date'];
    
    if (!$payment_id) {
        echo "<p>❌ Validation failed: Payment ID is required</p>\n";
    } else {
        echo "<p>✅ Payment ID validation passed</p>\n";
    }
    
    if (empty($transaction_reference)) {
        echo "<p>❌ Validation failed: Transaction reference is required</p>\n";
    } else {
        echo "<p>✅ Transaction reference validation passed</p>\n";
    }
    
    if (empty($payment_date)) {
        echo "<p>❌ Validation failed: Payment date is required</p>\n";
    } else {
        echo "<p>✅ Payment date validation passed</p>\n";
    }
    
    // Test payment verification
    $paymentVerifyStmt = $db->prepare("
        SELECT * FROM stage_payment_requests 
        WHERE id = ? AND contractor_id = ?
    ");
    $paymentVerifyStmt->execute([$payment_id, $contractor_id]);
    $paymentToVerify = $paymentVerifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paymentToVerify) {
        echo "<p>❌ Payment verification failed: Payment not found or access denied</p>\n";
        echo "<p>This is likely the source of the error!</p>\n";
        
        // Check if payment exists at all
        $anyPaymentStmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = ?");
        $anyPaymentStmt->execute([$payment_id]);
        $anyPayment = $anyPaymentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($anyPayment) {
            echo "<p>Payment exists but belongs to contractor {$anyPayment['contractor_id']}, not {$contractor_id}</p>\n";
        } else {
            echo "<p>Payment ID {$payment_id} does not exist in database</p>\n";
        }
    } else {
        echo "<p>✅ Payment verification passed</p>\n";
        
        // Check status
        if (!in_array($paymentToVerify['status'], ['approved', 'paid'])) {
            echo "<p>❌ Status check failed: Receipt can only be uploaded for approved or paid payments. Current status: {$paymentToVerify['status']}</p>\n";
        } else {
            echo "<p>✅ Status check passed: Payment status is {$paymentToVerify['status']}</p>\n";
        }
    }
    
    echo "<h2>7. Summary</h2>\n";
    echo "<p>If you're getting 'Payment not found or access denied' error, check:</p>\n";
    echo "<ul>\n";
    echo "<li>1. Contractor is properly authenticated (session has user_id)</li>\n";
    echo "<li>2. Payment ID exists in stage_payment_requests table</li>\n";
    echo "<li>3. Payment belongs to the authenticated contractor</li>\n";
    echo "<li>4. Payment status is 'approved' or 'paid'</li>\n";
    echo "<li>5. All required form fields are provided</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
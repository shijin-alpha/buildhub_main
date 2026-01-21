<?php
// Test contractor receipt upload API
session_start();
$_SESSION['user_id'] = 29; // Set contractor ID

header('Content-Type: application/json');

try {
    // Test data
    $payment_id = 16; // Structure payment from our earlier test
    
    echo "=== CONTRACTOR RECEIPT UPLOAD TEST ===\n\n";
    
    // 1. Check if payment exists and belongs to contractor
    $pdo = new PDO("mysql:host=localhost;dbname=buildhub;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM stage_payment_requests WHERE id = ? AND contractor_id = ?");
    $stmt->execute([$payment_id, 29]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "✅ Payment found:\n";
        echo "   ID: {$payment['id']}\n";
        echo "   Stage: {$payment['stage_name']}\n";
        echo "   Amount: ₹{$payment['requested_amount']}\n";
        echo "   Status: {$payment['status']}\n";
        echo "   Contractor ID: {$payment['contractor_id']}\n\n";
    } else {
        echo "❌ Payment not found or access denied\n\n";
        exit;
    }
    
    // 2. Test the API with form data (simulate POST request)
    $_POST = [
        'payment_id' => $payment_id,
        'transaction_reference' => 'TXN' . time(),
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'bank_transfer',
        'notes' => 'Test receipt upload from contractor'
    ];
    
    // Create a mock file upload (since we can't actually upload files in this test)
    $mockFile = [
        'name' => ['test_receipt.jpg'],
        'type' => ['image/jpeg'],
        'size' => [1024000],
        'tmp_name' => ['/tmp/mock_file'],
        'error' => [UPLOAD_ERR_OK]
    ];
    
    echo "2. Testing API parameters:\n";
    echo "   Payment ID: {$_POST['payment_id']}\n";
    echo "   Transaction Ref: {$_POST['transaction_reference']}\n";
    echo "   Payment Date: {$_POST['payment_date']}\n";
    echo "   Payment Method: {$_POST['payment_method']}\n";
    echo "   Notes: {$_POST['notes']}\n\n";
    
    // 3. Test validation logic (without actual file upload)
    echo "3. Testing validation:\n";
    
    // Check authentication
    if (!$_SESSION['user_id']) {
        echo "❌ Authentication: Failed\n";
    } else {
        echo "✅ Authentication: Passed (Contractor ID: {$_SESSION['user_id']})\n";
    }
    
    // Check required fields
    $required_fields = ['payment_id', 'transaction_reference', 'payment_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo "❌ Required field '$field': Missing\n";
        } else {
            echo "✅ Required field '$field': Present\n";
        }
    }
    
    // Check payment ownership
    if ($payment['contractor_id'] == $_SESSION['user_id']) {
        echo "✅ Payment ownership: Verified\n";
    } else {
        echo "❌ Payment ownership: Failed\n";
    }
    
    // Check payment status
    if (in_array($payment['status'], ['approved', 'paid'])) {
        echo "✅ Payment status: Valid for receipt upload ({$payment['status']})\n";
    } else {
        echo "❌ Payment status: Invalid for receipt upload ({$payment['status']})\n";
    }
    
    echo "\n4. API Response Simulation:\n";
    
    if ($payment['contractor_id'] == $_SESSION['user_id'] && 
        in_array($payment['status'], ['approved', 'paid']) &&
        !empty($_POST['transaction_reference'])) {
        
        echo "✅ All validations passed - Receipt upload would succeed\n";
        echo "Expected response:\n";
        echo json_encode([
            'success' => true,
            'message' => 'Receipt uploaded successfully',
            'data' => [
                'payment_id' => $payment_id,
                'transaction_reference' => $_POST['transaction_reference'],
                'payment_date' => $_POST['payment_date'],
                'verification_status' => 'contractor_uploaded',
                'payment_status' => 'paid'
            ]
        ], JSON_PRETTY_PRINT);
        
    } else {
        echo "❌ Validation failed - Receipt upload would fail\n";
        echo "Expected error response:\n";
        echo json_encode([
            'success' => false,
            'message' => 'Payment not found or access denied'
        ], JSON_PRETTY_PRINT);
    }
    
    echo "\n\n5. Upload Directory Check:\n";
    $uploadDir = "uploads/payment_receipts/{$payment_id}/";
    if (!file_exists($uploadDir)) {
        echo "📁 Creating upload directory: {$uploadDir}\n";
        if (mkdir($uploadDir, 0755, true)) {
            echo "✅ Upload directory created successfully\n";
        } else {
            echo "❌ Failed to create upload directory\n";
        }
    } else {
        echo "✅ Upload directory already exists: {$uploadDir}\n";
    }
    
    echo "\n🎉 Contractor Receipt Upload Test Complete!\n";
    echo "\nTo test the actual upload:\n";
    echo "1. Use the contractor payment history interface\n";
    echo "2. Select an approved payment\n";
    echo "3. Click 'Upload Receipt' button\n";
    echo "4. Fill in transaction details and upload files\n";
    echo "5. Verify payment status changes to 'Paid'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
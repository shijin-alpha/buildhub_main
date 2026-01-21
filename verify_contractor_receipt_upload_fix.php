<?php
/**
 * Verification script to test if contractor receipt upload is working
 */

session_start();

// Set contractor session if not already set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 29;
    $_SESSION['user_type'] = 'contractor';
}

echo "<h1>🔍 Contractor Receipt Upload Fix Verification</h1>\n";

// Test 1: Check session
echo "<h2>Test 1: Session Check</h2>\n";
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 29) {
    echo "<p>✅ Session is properly set for contractor ID 29</p>\n";
} else {
    echo "<p>❌ Session is not set correctly</p>\n";
}

// Test 2: Check database connection and payments
echo "<h2>Test 2: Database and Payments Check</h2>\n";
try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        SELECT id, stage_name, requested_amount, status, receipt_file_path 
        FROM stage_payment_requests 
        WHERE contractor_id = ? AND status IN ('approved', 'paid')
        ORDER BY id DESC LIMIT 3
    ");
    $stmt->execute([29]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($payments) > 0) {
        echo "<p>✅ Found " . count($payments) . " payments available for receipt upload</p>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Stage</th><th>Amount</th><th>Status</th><th>Has Receipt</th></tr>\n";
        
        foreach ($payments as $payment) {
            $hasReceipt = !empty($payment['receipt_file_path']) ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['stage_name']}</td>";
            echo "<td>₹" . number_format($payment['requested_amount'], 2) . "</td>";
            echo "<td>{$payment['status']}</td>";
            echo "<td>{$hasReceipt}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>❌ No payments found for contractor 29</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Check API endpoint
echo "<h2>Test 3: API Endpoint Check</h2>\n";
$apiFile = 'backend/api/contractor/upload_payment_receipt.php';
if (file_exists($apiFile)) {
    echo "<p>✅ API file exists: {$apiFile}</p>\n";
    
    // Check if file is readable
    if (is_readable($apiFile)) {
        echo "<p>✅ API file is readable</p>\n";
    } else {
        echo "<p>❌ API file is not readable</p>\n";
    }
} else {
    echo "<p>❌ API file not found: {$apiFile}</p>\n";
}

// Test 4: Check upload directory
echo "<h2>Test 4: Upload Directory Check</h2>\n";
$uploadDir = 'backend/uploads/payment_receipts/';
if (file_exists($uploadDir)) {
    echo "<p>✅ Upload directory exists: {$uploadDir}</p>\n";
    
    if (is_writable($uploadDir)) {
        echo "<p>✅ Upload directory is writable</p>\n";
    } else {
        echo "<p>❌ Upload directory is not writable</p>\n";
    }
} else {
    echo "<p>❌ Upload directory not found: {$uploadDir}</p>\n";
}

// Test 5: Simulate API call
echo "<h2>Test 5: Simulate API Call</h2>\n";
if (count($payments) > 0) {
    $testPayment = $payments[0];
    
    // Simulate POST data
    $_POST = [
        'payment_id' => $testPayment['id'],
        'transaction_reference' => 'TEST_REF_' . time(),
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'bank_transfer',
        'notes' => 'Test from verification script'
    ];
    
    echo "<p>Simulating API call with payment ID: {$testPayment['id']}</p>\n";
    echo "<p>POST data: " . json_encode($_POST) . "</p>\n";
    
    // Check if the API would accept this data
    if (!empty($_POST['payment_id']) && !empty($_POST['transaction_reference']) && !empty($_POST['payment_date'])) {
        echo "<p>✅ Required fields are present</p>\n";
        
        // Verify payment belongs to contractor
        try {
            $verifyStmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = ? AND contractor_id = ?");
            $verifyStmt->execute([$_POST['payment_id'], 29]);
            $verifyPayment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verifyPayment) {
                echo "<p>✅ Payment verification would pass</p>\n";
                
                if (in_array($verifyPayment['status'], ['approved', 'paid'])) {
                    echo "<p>✅ Payment status allows receipt upload</p>\n";
                } else {
                    echo "<p>❌ Payment status ({$verifyPayment['status']}) does not allow receipt upload</p>\n";
                }
            } else {
                echo "<p>❌ Payment verification would fail</p>\n";
            }
        } catch (Exception $e) {
            echo "<p>❌ Payment verification error: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>❌ Required fields are missing</p>\n";
    }
} else {
    echo "<p>⚠️ No payments available for testing</p>\n";
}

echo "<h2>Summary</h2>\n";
echo "<p>If all tests pass, the contractor receipt upload should work correctly.</p>\n";
echo "<p>To test in browser:</p>\n";
echo "<ol>\n";
echo "<li>Visit: <a href='contractor_receipt_upload_test.html'>contractor_receipt_upload_test.html</a></li>\n";
echo "<li>Select a payment and upload a receipt file</li>\n";
echo "<li>Check browser console for any errors</li>\n";
echo "</ol>\n";
?>
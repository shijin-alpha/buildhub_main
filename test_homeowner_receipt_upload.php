<?php
/**
 * Test homeowner receipt upload API directly
 */

// Start session and set homeowner session
session_start();
$_SESSION['user_id'] = 28; // Homeowner ID from database
$_SESSION['user_type'] = 'homeowner';

echo "<h1>🧪 Homeowner Receipt Upload Test</h1>\n";

// Check available payment requests for homeowner
try {
    $db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Available Payment Requests for Homeowner 28</h2>\n";
    $stmt = $db->prepare("
        SELECT id, stage_name, requested_amount, status, contractor_id, homeowner_id
        FROM stage_payment_requests 
        WHERE homeowner_id = 28 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $stmt->execute();
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>ID</th><th>Stage</th><th>Amount</th><th>Status</th><th>Contractor ID</th><th>Homeowner ID</th></tr>\n";
    
    $payments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payments[] = $row;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['stage_name']}</td>";
        echo "<td>₹" . number_format($row['requested_amount'], 2) . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['contractor_id']}</td>";
        echo "<td>{$row['homeowner_id']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    if (count($payments) > 0) {
        $testPayment = $payments[0];
        echo "<h2>Test Receipt Upload for Payment ID: {$testPayment['id']}</h2>\n";
        
        // Simulate API call
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['payment_id'] = $testPayment['id'];
        $_POST['transaction_reference'] = 'TEST_HOMEOWNER_' . time();
        $_POST['payment_date'] = date('Y-m-d');
        $_POST['payment_method'] = 'bank_transfer';
        $_POST['notes'] = 'Test receipt upload from homeowner';
        
        // Create a fake file upload
        $_FILES['receipt_files'] = [
            'name' => ['test_receipt.txt'],
            'type' => ['text/plain'],
            'tmp_name' => [tempnam(sys_get_temp_dir(), 'test_receipt')],
            'error' => [UPLOAD_ERR_OK],
            'size' => [1024]
        ];
        
        // Create test file content
        file_put_contents($_FILES['receipt_files']['tmp_name'][0], 'Test homeowner receipt file content');
        
        echo "<h3>Simulated POST Data:</h3>\n";
        echo "<pre>" . json_encode($_POST, JSON_PRETTY_PRINT) . "</pre>\n";
        
        echo "<h3>API Response:</h3>\n";
        echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>\n";
        
        // Capture API output
        ob_start();
        try {
            include 'backend/api/homeowner/upload_payment_receipt.php';
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
        }
        $apiOutput = ob_get_clean();
        
        echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>\n";
        echo "</div>\n";
        
        // Clean up temp file
        if (file_exists($_FILES['receipt_files']['tmp_name'][0])) {
            unlink($_FILES['receipt_files']['tmp_name'][0]);
        }
        
    } else {
        echo "<p>❌ No payment requests found for homeowner 28</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
<?php
// Debug contractor bank transfer receipt upload issue
session_start();
$_SESSION['user_id'] = 29; // Set contractor ID

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=buildhub;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 Contractor Bank Transfer Receipt Upload Debug</h1>";
    
    // 1. Check contractor session
    echo "<h2>1. Session Check</h2>";
    echo "<p><strong>Contractor ID:</strong> " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
    
    // 2. Find contractor's payments
    echo "<h2>2. Contractor's Payment Requests</h2>";
    $stmt = $pdo->prepare("
        SELECT id, project_id, stage_name, requested_amount, status, homeowner_id 
        FROM stage_payment_requests 
        WHERE contractor_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([29]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Project</th><th>Stage</th><th>Amount</th><th>Status</th><th>Actions</th></tr>";
    
    foreach ($payments as $payment) {
        $canUpload = in_array($payment['status'], ['approved', 'paid']) ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>{$payment['project_id']}</td>";
        echo "<td>{$payment['stage_name']}</td>";
        echo "<td>₹" . number_format($payment['requested_amount']) . "</td>";
        echo "<td>{$payment['status']}</td>";
        echo "<td>{$canUpload} Can Upload Receipt</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test contractor API with a specific payment
    if (!empty($payments)) {
        $testPayment = $payments[0]; // Use first payment
        echo "<h2>3. Testing Contractor API with Payment ID {$testPayment['id']}</h2>";
        
        // Simulate the API call
        $_POST = [
            'payment_id' => $testPayment['id'],
            'transaction_reference' => 'BANK_TRANSFER_' . time(),
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'bank_transfer',
            'notes' => 'Test bank transfer receipt upload'
        ];
        
        echo "<p><strong>Test Parameters:</strong></p>";
        echo "<ul>";
        echo "<li>Payment ID: {$_POST['payment_id']}</li>";
        echo "<li>Transaction Ref: {$_POST['transaction_reference']}</li>";
        echo "<li>Payment Date: {$_POST['payment_date']}</li>";
        echo "<li>Payment Method: {$_POST['payment_method']}</li>";
        echo "</ul>";
        
        // Test the contractor API
        ob_start();
        try {
            include 'backend/api/contractor/upload_payment_receipt.php';
            $api_response = ob_get_clean();
            
            echo "<h3>✅ Contractor API Response:</h3>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
            echo htmlspecialchars($api_response);
            echo "</pre>";
            
            $response_data = json_decode($api_response, true);
            if ($response_data && $response_data['success']) {
                echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> Contractor API is working correctly!</p>";
            } else {
                echo "<p style='color: red;'><strong>❌ FAILED:</strong> " . ($response_data['message'] ?? 'Unknown error') . "</p>";
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p style='color: red;'><strong>❌ API ERROR:</strong> " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. Test homeowner API (should fail)
    echo "<h2>4. Testing Homeowner API (Should Fail)</h2>";
    if (!empty($payments)) {
        ob_start();
        try {
            include 'backend/api/homeowner/upload_payment_receipt.php';
            $homeowner_response = ob_get_clean();
            
            echo "<h3>Homeowner API Response:</h3>";
            echo "<pre style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
            echo htmlspecialchars($homeowner_response);
            echo "</pre>";
            
            $homeowner_data = json_decode($homeowner_response, true);
            if ($homeowner_data && !$homeowner_data['success']) {
                echo "<p style='color: orange;'><strong>✅ EXPECTED FAILURE:</strong> Homeowner API correctly rejected contractor</p>";
            } else {
                echo "<p style='color: red;'><strong>❌ UNEXPECTED:</strong> Homeowner API should have failed</p>";
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p style='color: orange;'><strong>✅ EXPECTED ERROR:</strong> " . $e->getMessage() . "</p>";
        }
    }
    
    // 5. Check which frontend component is being used
    echo "<h2>5. Frontend Component Analysis</h2>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Possible Issues:</h3>";
    echo "<ol>";
    echo "<li><strong>Wrong Component:</strong> User might be using PaymentMethodSelector (homeowner component) instead of PaymentHistory (contractor component)</li>";
    echo "<li><strong>Session Issue:</strong> Contractor session might not be properly set</li>";
    echo "<li><strong>Payment Status:</strong> Payment might not be in 'approved' or 'paid' status</li>";
    echo "<li><strong>API Endpoint:</strong> Frontend might still be calling homeowner API</li>";
    echo "</ol>";
    
    echo "<h3>Solutions:</h3>";
    echo "<ul>";
    echo "<li>✅ Use PaymentHistory component for contractor receipt uploads</li>";
    echo "<li>✅ Ensure contractor is logged in with correct session</li>";
    echo "<li>✅ Verify payment status is 'approved' before upload</li>";
    echo "<li>✅ Use ContractorReceiptUpload component or updated PaymentReceiptUpload</li>";
    echo "</ul>";
    echo "</div>";
    
    // 6. Create a direct test link
    echo "<h2>6. Direct Test</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🧪 Test Receipt Upload Now:</h3>";
    
    if (!empty($payments)) {
        $approvedPayments = array_filter($payments, function($p) {
            return in_array($p['status'], ['approved', 'paid']);
        });
        
        if (!empty($approvedPayments)) {
            $testPayment = array_values($approvedPayments)[0];
            echo "<p>Use this payment for testing:</p>";
            echo "<ul>";
            echo "<li><strong>Payment ID:</strong> {$testPayment['id']}</li>";
            echo "<li><strong>Stage:</strong> {$testPayment['stage_name']}</li>";
            echo "<li><strong>Amount:</strong> ₹" . number_format($testPayment['requested_amount']) . "</li>";
            echo "<li><strong>Status:</strong> {$testPayment['status']}</li>";
            echo "</ul>";
            
            echo "<p><strong>Steps to test:</strong></p>";
            echo "<ol>";
            echo "<li>Go to Contractor Dashboard → Payment History</li>";
            echo "<li>Select Project {$testPayment['project_id']}</li>";
            echo "<li>Find {$testPayment['stage_name']} payment</li>";
            echo "<li>Click 'Upload Receipt' button</li>";
            echo "<li>Fill in bank transfer details</li>";
            echo "<li>Upload receipt file</li>";
            echo "</ol>";
            
        } else {
            echo "<p style='color: orange;'>⚠️ No approved payments found. Create an approved payment first.</p>";
        }
    }
    echo "</div>";
    
    // 7. Create a sample approved payment for testing
    echo "<h2>7. Create Test Payment (if needed)</h2>";
    echo "<form method='post' style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Create Sample Approved Payment for Testing:</h3>";
    echo "<p>";
    echo "<label>Project ID: <input type='number' name='project_id' value='37' required></label><br><br>";
    echo "<label>Stage: <select name='stage_name' required>";
    echo "<option value='Foundation'>Foundation</option>";
    echo "<option value='Structure'>Structure</option>";
    echo "<option value='Brickwork'>Brickwork</option>";
    echo "<option value='Roofing'>Roofing</option>";
    echo "</select></label><br><br>";
    echo "<label>Amount: <input type='number' name='amount' value='50000' required></label><br><br>";
    echo "<input type='submit' name='create_payment' value='Create Test Payment' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px;'>";
    echo "</p>";
    echo "</form>";
    
    // Handle form submission
    if (isset($_POST['create_payment'])) {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO stage_payment_requests 
                (project_id, contractor_id, homeowner_id, stage_name, requested_amount, 
                 completion_percentage, work_description, status, created_at, updated_at)
                VALUES (?, 29, 28, ?, ?, 20, 'Test payment for receipt upload', 'approved', NOW(), NOW())
            ");
            
            $insert_stmt->execute([
                $_POST['project_id'],
                $_POST['stage_name'], 
                $_POST['amount']
            ]);
            
            $new_payment_id = $pdo->lastInsertId();
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Test payment created successfully!</strong><br>";
            echo "Payment ID: {$new_payment_id}<br>";
            echo "Status: approved<br>";
            echo "You can now test receipt upload with this payment.";
            echo "</div>";
            
            // Refresh the page to show new payment
            echo "<script>setTimeout(() => location.reload(), 2000);</script>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ <strong>Error creating test payment:</strong> " . $e->getMessage();
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
}
?>
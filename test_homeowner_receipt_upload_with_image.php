<?php
/**
 * Test homeowner receipt upload with proper image file
 */

// Start session and set homeowner session
session_start();
$_SESSION['user_id'] = 28; // Homeowner ID from database
$_SESSION['user_type'] = 'homeowner';

echo "<h1>🧪 Homeowner Receipt Upload Test with Image</h1>\n";

try {
    $db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get an approved payment for testing
    $stmt = $db->prepare("
        SELECT id, stage_name, requested_amount, status 
        FROM stage_payment_requests 
        WHERE homeowner_id = 28 AND status = 'approved'
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo "<p>❌ No approved payments found for testing</p>\n";
        exit;
    }
    
    echo "<h2>Testing with Payment ID: {$payment['id']} ({$payment['stage_name']} - ₹{$payment['requested_amount']})</h2>\n";
    
    // Simulate API call with proper image
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['payment_id'] = $payment['id'];
    $_POST['transaction_reference'] = 'HOMEOWNER_BANK_REF_' . time();
    $_POST['payment_date'] = date('Y-m-d');
    $_POST['payment_method'] = 'bank_transfer';
    $_POST['notes'] = 'Test receipt upload from homeowner with image';
    
    // Create a simple 1x1 pixel PNG image for testing
    $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77zgAAAABJRU5ErkJggg==');
    $tempImageFile = tempnam(sys_get_temp_dir(), 'test_receipt') . '.png';
    file_put_contents($tempImageFile, $imageData);
    
    $_FILES['receipt_files'] = [
        'name' => ['homeowner_receipt.png'],
        'type' => ['image/png'],
        'tmp_name' => [$tempImageFile],
        'error' => [UPLOAD_ERR_OK],
        'size' => [strlen($imageData)]
    ];
    
    echo "<h3>POST Data:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Payment ID:</strong> {$_POST['payment_id']}</li>\n";
    echo "<li><strong>Transaction Reference:</strong> {$_POST['transaction_reference']}</li>\n";
    echo "<li><strong>Payment Date:</strong> {$_POST['payment_date']}</li>\n";
    echo "<li><strong>Payment Method:</strong> {$_POST['payment_method']}</li>\n";
    echo "<li><strong>File Type:</strong> {$_FILES['receipt_files']['type'][0]}</li>\n";
    echo "<li><strong>File Size:</strong> {$_FILES['receipt_files']['size'][0]} bytes</li>\n";
    echo "</ul>\n";
    
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
    
    // Parse response to check success
    $response = json_decode($apiOutput, true);
    if ($response && $response['success']) {
        echo "<h3>✅ SUCCESS! Receipt uploaded successfully</h3>\n";
        echo "<p>The homeowner receipt upload API is working correctly.</p>\n";
        
        // Check if file was actually uploaded
        $uploadDir = "backend/uploads/payment_receipts/{$payment['id']}/";
        if (file_exists($uploadDir)) {
            $files = scandir($uploadDir);
            $uploadedFiles = array_filter($files, function($file) {
                return $file !== '.' && $file !== '..';
            });
            
            if (count($uploadedFiles) > 0) {
                echo "<p>✅ File uploaded to: {$uploadDir}</p>\n";
                echo "<p>Uploaded files: " . implode(', ', $uploadedFiles) . "</p>\n";
            }
        }
    } else {
        echo "<h3>❌ FAILED</h3>\n";
        echo "<p>Error: " . ($response['message'] ?? 'Unknown error') . "</p>\n";
    }
    
    // Clean up temp file
    if (file_exists($tempImageFile)) {
        unlink($tempImageFile);
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
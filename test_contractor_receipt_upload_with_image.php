<?php
/**
 * Test Contractor Receipt Upload with Proper Image File
 */

// Start session first
session_start();

// Set contractor session
$_SESSION['user_id'] = 29;
$_SESSION['user_type'] = 'contractor';

echo "<h1>🧪 Contractor Receipt Upload Test with Image</h1>\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['payment_id'] = 15; // Foundation payment that's approved
$_POST['transaction_reference'] = 'BANK_REF_' . time();
$_POST['payment_date'] = date('Y-m-d');
$_POST['payment_method'] = 'bank_transfer';
$_POST['notes'] = 'Test receipt upload with proper image file';

// Create a simple 1x1 pixel PNG image for testing
$imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU77zgAAAABJRU5ErkJggg==');
$tempImageFile = tempnam(sys_get_temp_dir(), 'test_receipt') . '.png';
file_put_contents($tempImageFile, $imageData);

// Create proper file upload structure
$_FILES['receipt_files'] = [
    'name' => ['test_receipt.png'],
    'type' => ['image/png'],
    'tmp_name' => [$tempImageFile],
    'error' => [UPLOAD_ERR_OK],
    'size' => [strlen($imageData)]
];

echo "<h2>Test Parameters:</h2>\n";
echo "<ul>\n";
echo "<li><strong>Session User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</li>\n";
echo "<li><strong>Session User Type:</strong> " . ($_SESSION['user_type'] ?? 'Not set') . "</li>\n";
echo "<li><strong>Payment ID:</strong> {$_POST['payment_id']}</li>\n";
echo "<li><strong>Transaction Reference:</strong> {$_POST['transaction_reference']}</li>\n";
echo "<li><strong>Payment Date:</strong> {$_POST['payment_date']}</li>\n";
echo "<li><strong>Payment Method:</strong> {$_POST['payment_method']}</li>\n";
echo "<li><strong>File Type:</strong> {$_FILES['receipt_files']['type'][0]}</li>\n";
echo "<li><strong>File Size:</strong> {$_FILES['receipt_files']['size'][0]} bytes</li>\n";
echo "</ul>\n";

echo "<h2>API Response:</h2>\n";
echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>\n";

// Capture the API output
ob_start();

try {
    // Include the API file
    include 'backend/api/contractor/upload_payment_receipt.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

$apiOutput = ob_get_clean();

// Display the output
echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>\n";
echo "</div>\n";

// Clean up temp file
if (file_exists($tempImageFile)) {
    unlink($tempImageFile);
}

echo "<h2>Analysis:</h2>\n";
$response = json_decode($apiOutput, true);
if ($response && $response['success']) {
    echo "<p>✅ <strong>SUCCESS!</strong> The contractor receipt upload API is working correctly.</p>\n";
    echo "<p>The issue is likely in the frontend or session management in the browser.</p>\n";
} else {
    echo "<p>❌ <strong>FAILED:</strong> " . ($response['message'] ?? 'Unknown error') . "</p>\n";
}
?>
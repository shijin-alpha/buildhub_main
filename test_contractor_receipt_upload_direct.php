<?php
/**
 * Direct Test of Contractor Receipt Upload API
 * This will test the actual API endpoint with proper session setup
 */

// Start session first
session_start();

// Set contractor session
$_SESSION['user_id'] = 29;
$_SESSION['user_type'] = 'contractor';

echo "<h1>🧪 Direct Contractor Receipt Upload API Test</h1>\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['payment_id'] = 15; // Foundation payment that's approved
$_POST['transaction_reference'] = 'TEST_BANK_REF_' . time();
$_POST['payment_date'] = date('Y-m-d');
$_POST['payment_method'] = 'bank_transfer';
$_POST['notes'] = 'Test receipt upload - direct API test';

// Create a fake file upload for testing
$_FILES['receipt_files'] = [
    'name' => ['test_receipt.txt'],
    'type' => ['text/plain'],
    'tmp_name' => [tempnam(sys_get_temp_dir(), 'test_receipt')],
    'error' => [UPLOAD_ERR_OK],
    'size' => [1024]
];

// Create the test file content
file_put_contents($_FILES['receipt_files']['tmp_name'][0], 'This is a test receipt file for contractor upload testing.');

echo "<h2>Test Parameters:</h2>\n";
echo "<ul>\n";
echo "<li><strong>Session User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</li>\n";
echo "<li><strong>Session User Type:</strong> " . ($_SESSION['user_type'] ?? 'Not set') . "</li>\n";
echo "<li><strong>Payment ID:</strong> {$_POST['payment_id']}</li>\n";
echo "<li><strong>Transaction Reference:</strong> {$_POST['transaction_reference']}</li>\n";
echo "<li><strong>Payment Date:</strong> {$_POST['payment_date']}</li>\n";
echo "<li><strong>Payment Method:</strong> {$_POST['payment_method']}</li>\n";
echo "<li><strong>Notes:</strong> {$_POST['notes']}</li>\n";
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
if (file_exists($_FILES['receipt_files']['tmp_name'][0])) {
    unlink($_FILES['receipt_files']['tmp_name'][0]);
}

echo "<h2>Next Steps:</h2>\n";
echo "<p>If the API test shows success, the issue might be:</p>\n";
echo "<ul>\n";
echo "<li>1. Session not being maintained in the browser</li>\n";
echo "<li>2. CORS issues preventing proper authentication</li>\n";
echo "<li>3. Frontend not sending the correct payment ID</li>\n";
echo "<li>4. File upload issues in the frontend</li>\n";
echo "</ul>\n";
?>
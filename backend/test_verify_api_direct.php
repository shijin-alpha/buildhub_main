<?php
/**
 * Direct test of verify payment API
 */

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];

// Set up session
session_start();
$_SESSION['user_id'] = 29; // Contractor ID from test
$_SESSION['user_type'] = 'contractor';

// Simulate POST data
$postData = json_encode([
    'payment_id' => 13,
    'verification_status' => 'verified',
    'verification_notes' => 'Test verification'
]);

// Temporarily store in php://input simulation
file_put_contents('php://memory', $postData);

echo "=== Testing Verify Payment API Directly ===\n\n";
echo "Session User ID: " . $_SESSION['user_id'] . "\n";
echo "Session User Type: " . $_SESSION['user_type'] . "\n";
echo "Payment ID: 13\n";
echo "Verification Status: verified\n\n";

echo "Calling API...\n\n";

// Capture output
ob_start();

try {
    // Include the API file
    include 'api/contractor/verify_payment_receipt.php';
    
    $output = ob_get_clean();
    
    echo "API Response:\n";
    echo $output . "\n\n";
    
    // Try to parse as JSON
    $json = json_decode($output, true);
    if ($json) {
        echo "✓ Valid JSON response\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['message'])) {
            echo "Message: " . $json['message'] . "\n";
        }
    } else {
        echo "✗ Invalid JSON response\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

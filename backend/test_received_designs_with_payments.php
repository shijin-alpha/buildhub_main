<?php
/**
 * Test the received designs API with payment integration
 */

// Simulate session
session_start();
$_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026
$_SESSION['role'] = 'homeowner';

echo "=== Testing Received Designs API with Payments ===\n\n";

// Set up environment to capture the API output
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture any errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Capture the API output
    ob_start();
    include 'api/homeowner/get_received_designs.php';
    $apiOutput = ob_get_clean();

    echo "API Output:\n";
    echo $apiOutput . "\n";

    // Try to decode as JSON
    $decoded = json_decode($apiOutput, true);
    if ($decoded) {
        echo "\nDecoded JSON Summary:\n";
        echo "Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
        
        if (isset($decoded['message'])) {
            echo "Message: " . $decoded['message'] . "\n";
        }
        
        if (isset($decoded['designs'])) {
            echo "Designs count: " . count($decoded['designs']) . "\n";
            
            foreach ($decoded['designs'] as $design) {
                echo "- ID: " . $design['id'] . "\n";
                echo "  Type: " . ($design['source_type'] ?? 'design') . "\n";
                echo "  Title: " . $design['design_title'] . "\n";
                if ($design['source_type'] === 'house_plan') {
                    echo "  Unlock Price: ₹" . ($design['unlock_price'] ?? 'N/A') . "\n";
                    echo "  Is Unlocked: " . ($design['is_technical_details_unlocked'] ? 'Yes' : 'No') . "\n";
                    echo "  Payment Status: " . ($design['payment_status'] ?? 'None') . "\n";
                }
                echo "\n";
            }
        }
    } else {
        echo "\nFailed to decode JSON response\n";
        echo "Raw output length: " . strlen($apiOutput) . " characters\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
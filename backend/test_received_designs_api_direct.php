<?php
/**
 * Test the received designs API directly
 */

// Simulate session
session_start();
$_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();

try {
    include 'api/homeowner/get_received_designs.php';
    $output = ob_get_clean();
    
    echo "=== API Response ===\n";
    echo $output . "\n";
    
    // Try to decode JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "\n=== Parsed Response ===\n";
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['designs'])) {
            echo "Number of designs: " . count($data['designs']) . "\n";
            foreach ($data['designs'] as $design) {
                if ($design['source_type'] === 'house_plan') {
                    echo "- House Plan: " . $design['design_title'] . "\n";
                    echo "  Unlock Price: ₹" . $design['unlock_price'] . "\n";
                    echo "  Is Unlocked: " . ($design['is_technical_details_unlocked'] ? 'Yes' : 'No') . "\n";
                    echo "  Payment Status: " . ($design['payment_status'] ?? 'None') . "\n";
                }
            }
        }
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    } else {
        echo "Failed to parse JSON response\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
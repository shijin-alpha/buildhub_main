<?php
/**
 * Test the modified received designs API
 */

// Simulate session
session_start();
$_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026
$_SESSION['role'] = 'homeowner';

echo "=== Testing Modified Received Designs API ===\n\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n\n";

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
    
    if (isset($decoded['designs'])) {
        $designs = $decoded['designs'];
        echo "Total designs: " . count($designs) . "\n";
        
        $regularDesigns = array_filter($designs, function($d) { return ($d['source_type'] ?? 'design') === 'design'; });
        $housePlans = array_filter($designs, function($d) { return ($d['source_type'] ?? 'design') === 'house_plan'; });
        
        echo "Regular designs: " . count($regularDesigns) . "\n";
        echo "House plans: " . count($housePlans) . "\n\n";
        
        foreach ($designs as $design) {
            echo "- ID: " . $design['id'] . "\n";
            echo "  Title: " . $design['design_title'] . "\n";
            echo "  Type: " . ($design['source_type'] ?? 'design') . "\n";
            echo "  Architect: " . ($design['architect']['name'] ?? 'Unknown') . "\n";
            echo "  Files: " . (count($design['files'] ?? [])) . "\n";
            echo "  Technical Details: " . (empty($design['technical_details']) ? 'No' : 'Yes') . "\n";
            if ($design['source_type'] === 'house_plan') {
                echo "  Plot Dimensions: " . ($design['plot_dimensions'] ?? 'N/A') . "\n";
                echo "  Total Area: " . ($design['total_area'] ?? 0) . " sq ft\n";
                echo "  House Plan Status: " . ($design['house_plan_status'] ?? 'N/A') . "\n";
            }
            echo "\n";
        }
    }
    
    if (isset($decoded['message'])) {
        echo "Message: " . $decoded['message'] . "\n";
    }
} else {
    echo "\nFailed to decode JSON response\n";
}
?>
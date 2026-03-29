<?php
// Test the actual API endpoint
$testContractorId = 1;

// Simulate the API call
$_GET['contractor_id'] = $testContractorId;

// Capture output
ob_start();
include 'api/contractor/get_my_estimates.php';
$apiResponse = ob_get_clean();

echo "API Response:\n";
echo "=============\n";
echo $apiResponse . "\n\n";

// Parse and analyze
$apiData = json_decode($apiResponse, true);
if ($apiData) {
    echo "Parsed Response:\n";
    echo "================\n";
    echo "Success: " . ($apiData['success'] ? 'true' : 'false') . "\n";
    echo "Total estimates: " . count($apiData['estimates'] ?? []) . "\n";
    echo "New estimates: " . ($apiData['new_estimates_count'] ?? 0) . "\n";
    echo "Legacy estimates: " . ($apiData['legacy_estimates_count'] ?? 0) . "\n";
    
    if (isset($apiData['estimates']) && count($apiData['estimates']) > 0) {
        echo "\nEstimate Details:\n";
        foreach ($apiData['estimates'] as $est) {
            echo "- ID: {$est['id']} | Project: " . ($est['project_name'] ?? 'N/A') . " | Total: ₹" . number_format($est['total_cost'] ?? 0, 0) . " | Source: " . ($est['source_table'] ?? 'unknown') . "\n";
        }
    }
} else {
    echo "Failed to parse JSON response\n";
}
?>
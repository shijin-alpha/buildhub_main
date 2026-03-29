<?php
session_start();

// Set homeowner session for testing
$_SESSION['user_id'] = 28;
$_SESSION['role'] = 'homeowner';
$_SESSION['username'] = 'SHIJIN THOMAS MCA2024-2026';
$_SESSION['email'] = 'shijinthomas2026@mca.ajce.in';

echo "=== TESTING INSPECTION REPORTS API DIRECTLY ===\n\n";

echo "Session established for user ID: " . $_SESSION['user_id'] . " (Role: " . $_SESSION['role'] . ")\n\n";

// Capture the API response
ob_start();
include 'backend/api/homeowner/get_inspection_reports.php';
$apiResponse = ob_get_clean();

echo "Raw API Response:\n";
echo $apiResponse . "\n\n";

// Parse JSON response
$apiData = json_decode($apiResponse, true);
if ($apiData) {
    echo "Parsed API Data:\n";
    echo "Success: " . ($apiData['success'] ? 'true' : 'false') . "\n";
    
    if ($apiData['success']) {
        echo "Reports Count: " . count($apiData['reports']) . "\n";
        echo "Statistics: " . json_encode($apiData['statistics'], JSON_PRETTY_PRINT) . "\n";
        
        if (!empty($apiData['reports'])) {
            echo "\nFirst Report Details:\n";
            $firstReport = $apiData['reports'][0];
            echo "- ID: " . $firstReport['id'] . "\n";
            echo "- Project: " . $firstReport['project']['name'] . "\n";
            echo "- Inspector: " . $firstReport['inspector']['name'] . "\n";
            echo "- Date: " . $firstReport['inspection']['date'] . "\n";
            echo "- Status: " . $firstReport['inspection']['status'] . "\n";
            echo "- Quality Score: " . ($firstReport['inspection']['quality_score'] ?? 'N/A') . "\n";
        }
    } else {
        echo "Error Message: " . ($apiData['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "Failed to parse JSON response\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
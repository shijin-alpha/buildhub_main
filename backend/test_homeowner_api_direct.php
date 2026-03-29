<?php
/**
 * Test the homeowner API directly by simulating a session
 */

// Start session and simulate logged in homeowner
session_start();
$_SESSION['user_id'] = 28; // SHIJIN THOMAS MCA2024-2026
$_SESSION['role'] = 'homeowner';

echo "=== Testing Homeowner API Direct Call ===\n\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n\n";

// Capture the API output
ob_start();
include 'api/homeowner/get_house_plans.php';
$apiOutput = ob_get_clean();

echo "API Output:\n";
echo $apiOutput . "\n";

// Try to decode as JSON
$decoded = json_decode($apiOutput, true);
if ($decoded) {
    echo "\nDecoded JSON:\n";
    echo "Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
    if (isset($decoded['plans'])) {
        echo "Plans count: " . count($decoded['plans']) . "\n";
        foreach ($decoded['plans'] as $plan) {
            echo "- Plan ID: " . $plan['id'] . ", Name: " . $plan['plan_name'] . ", Status: " . $plan['status'] . "\n";
        }
    }
    if (isset($decoded['message'])) {
        echo "Message: " . $decoded['message'] . "\n";
    }
} else {
    echo "\nFailed to decode JSON response\n";
}
?>
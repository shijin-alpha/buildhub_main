<?php
// Test admin API directly by including it
session_start();

// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "<h1>Direct Admin API Test</h1>";

// Capture the output of the admin API
ob_start();
include 'api/admin/get_support_issues.php';
$output = ob_get_clean();

echo "<h2>API Output:</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Parse the JSON
$data = json_decode($output, true);
if ($data) {
    echo "<h2>Parsed Data:</h2>";
    echo "<p>Success: " . ($data['success'] ? 'true' : 'false') . "</p>";
    if (isset($data['issues'])) {
        echo "<p>Issues count: " . count($data['issues']) . "</p>";
        if (count($data['issues']) > 0) {
            echo "<h3>First issue:</h3>";
            echo "<pre>" . print_r($data['issues'][0], true) . "</pre>";
        }
    }
    if (isset($data['message'])) {
        echo "<p>Message: " . htmlspecialchars($data['message']) . "</p>";
    }
} else {
    echo "<p>Failed to parse JSON output</p>";
}
?>
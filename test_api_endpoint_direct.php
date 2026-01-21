<?php
// Start session and simulate contractor login
session_start();
$_SESSION['user_id'] = 29;
$_SESSION['role'] = 'contractor';
$_SESSION['email'] = 'shijinthomas248@gmail.com';

echo "Testing API endpoint directly...\n\n";

// Capture the API output
ob_start();
include 'backend/api/contractor/get_sent_reports.php';
$apiOutput = ob_get_clean();

echo "API Response:\n";
echo $apiOutput . "\n";

// Parse the JSON response
$response = json_decode($apiOutput, true);

if ($response) {
    echo "\n=== Parsed Response ===\n";
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    
    if ($response['success']) {
        $data = $response['data'];
        echo "Total reports: " . count($data['reports']) . "\n";
        echo "Daily reports: " . count($data['grouped_reports']['daily']) . "\n";
        echo "Weekly reports: " . count($data['grouped_reports']['weekly']) . "\n";
        echo "Monthly reports: " . count($data['grouped_reports']['monthly']) . "\n";
        
        echo "\nStatistics:\n";
        foreach ($data['statistics'] as $key => $value) {
            echo "- {$key}: {$value}\n";
        }
        
        if (!empty($data['reports'])) {
            echo "\nFirst report details:\n";
            $report = $data['reports'][0];
            echo "- ID: {$report['id']}\n";
            echo "- Project: {$report['project_name']}\n";
            echo "- Type: {$report['report_type']}\n";
            echo "- Status: {$report['status']}\n";
        }
    } else {
        echo "Error: " . $response['message'] . "\n";
    }
} else {
    echo "Failed to parse JSON response\n";
}
?>
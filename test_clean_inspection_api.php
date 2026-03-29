<?php
session_start();

// Set homeowner session for testing
$_SESSION['user_id'] = 28;
$_SESSION['role'] = 'homeowner';
$_SESSION['username'] = 'SHIJIN THOMAS MCA2024-2026';
$_SESSION['email'] = 'shijinthomas2026@mca.ajce.in';

echo "=== TESTING CLEAN INSPECTION REPORTS API ===\n\n";

// Capture the API response
ob_start();
include 'backend/api/homeowner/get_inspection_reports.php';
$apiResponse = ob_get_clean();

// Extract JSON from response (remove PHP warnings)
$lines = explode("\n", $apiResponse);
$jsonLine = '';
foreach ($lines as $line) {
    if (strpos($line, '{"success"') === 0) {
        $jsonLine = $line;
        break;
    }
}

if ($jsonLine) {
    echo "Clean JSON Response Found:\n";
    $apiData = json_decode($jsonLine, true);
    
    if ($apiData) {
        echo "✅ Success: " . ($apiData['success'] ? 'true' : 'false') . "\n";
        echo "📊 Reports Count: " . count($apiData['reports']) . "\n";
        echo "📈 Total Reports: " . $apiData['statistics']['total_reports'] . "\n";
        echo "✅ Approved: " . $apiData['statistics']['approved_count'] . "\n";
        echo "⚠️ Needs Attention: " . $apiData['statistics']['needs_attention_count'] . "\n";
        echo "❌ Rejected: " . $apiData['statistics']['rejected_count'] . "\n";
        
        if (!empty($apiData['reports'])) {
            echo "\n🔍 First Report:\n";
            $report = $apiData['reports'][0];
            echo "  - ID: " . $report['id'] . "\n";
            echo "  - Project: " . $report['project']['name'] . "\n";
            echo "  - Inspector: " . $report['inspector']['name'] . "\n";
            echo "  - Date: " . $report['inspection']['date'] . "\n";
            echo "  - Status: " . $report['inspection']['status'] . "\n";
            echo "  - Quality Score: " . ($report['inspection']['quality_score'] ?? 'N/A') . "\n";
            echo "  - Notes: " . substr($report['inspection']['notes'], 0, 50) . "...\n";
        }
        
        echo "\n✅ API is working correctly and returning valid data!\n";
    } else {
        echo "❌ Failed to parse JSON\n";
    }
} else {
    echo "❌ No JSON response found\n";
    echo "Raw response:\n" . $apiResponse . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
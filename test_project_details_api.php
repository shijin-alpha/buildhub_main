<?php
/**
 * Test Project Details API
 */

echo "🔍 Testing Project Details API...\n\n";

// Test with project ID 3
$_GET['project_id'] = 3;
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();
include 'backend/api/inspector/get_project_details.php';
$response = ob_get_clean();

echo "📡 API Response:\n";
echo "================\n";

if (empty($response)) {
    echo "❌ No response from API\n";
} else {
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . substr($response, 0, 500) . "\n";
    } else {
        if ($data['success']) {
            echo "✅ API Success!\n\n";
            
            $project = $data['project'];
            echo "📋 Project Details:\n";
            echo "   ID: {$project['project_id']}\n";
            echo "   Name: {$project['project_name']}\n";
            echo "   Status: {$project['project_status']}\n";
            echo "   Current Stage: {$project['current_stage']}\n";
            echo "   Stored Progress: {$project['completion_percentage']}%\n";
            echo "   Real Progress: {$project['real_completion_percentage']}%\n";
            echo "   Completed Stages: {$project['completed_stages']}/{$project['total_stages']}\n";
            echo "   Homeowner: {$project['homeowner_name']}\n";
            echo "   Contractor: {$project['contractor_name']}\n";
            
            if (isset($data['assignment']) && $data['assignment']) {
                echo "\n🔧 Assignment Info:\n";
                echo "   Assigned At: {$data['assignment']['assigned_at']}\n";
                echo "   Status: {$data['assignment']['assignment_status']}\n";
                echo "   Notes: {$data['assignment']['assignment_notes']}\n";
            }
            
            echo "\n💰 Stage Payments (" . count($data['stage_payments']) . "):\n";
            foreach ($data['stage_payments'] as $payment) {
                $status_icon = $payment['status'] === 'paid' ? '✅' : 
                              ($payment['status'] === 'approved' ? '🟡' : '⏳');
                echo "   {$status_icon} {$payment['stage_name']}: {$payment['completion_percentage']}% - ₹" . 
                     number_format($payment['requested_amount']) . " ({$payment['status']})\n";
            }
            
            echo "\n📊 Progress Calculation:\n";
            $calc = $data['progress_calculation'];
            echo "   Method: {$calc['method']}\n";
            echo "   Real Progress: {$calc['real_progress']}%\n";
            echo "   Stored Progress: {$calc['stored_progress']}%\n";
            echo "   Difference: {$calc['difference']}%\n";
            
            echo "\n📈 Progress Updates: " . count($data['progress_updates']) . "\n";
            echo "🔍 Inspection Reports: " . count($data['inspection_reports']) . "\n";
            
            $stats = $data['inspection_stats'];
            echo "\n📋 Inspection Statistics:\n";
            echo "   Total: {$stats['total_inspections']}\n";
            echo "   Approved: {$stats['approved_inspections']}\n";
            echo "   Rejected: {$stats['rejected_inspections']}\n";
            echo "   Needs Attention: {$stats['attention_needed']}\n";
            echo "   Pending: {$stats['pending_inspections']}\n";
            
        } else {
            echo "❌ API Error: {$data['message']}\n";
            if (isset($data['error'])) {
                echo "Details: {$data['error']}\n";
            }
        }
    }
}

echo "\n✅ Project Details API Test Complete!\n";
?>
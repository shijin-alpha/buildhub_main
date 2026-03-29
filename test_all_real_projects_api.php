<?php
/**
 * Test All Real Projects API
 */

echo "🔍 Testing All Real Projects API...\n\n";

// Test the API
$apiUrl = 'http://localhost/buildhub/backend/api/inspector/get_all_real_projects.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "❌ Failed to call API\n";
    exit(1);
}

$result = json_decode($response, true);

if (!$result) {
    echo "❌ Invalid JSON response\n";
    echo "Raw response: " . $response . "\n";
    exit(1);
}

if ($result['success']) {
    echo "✅ All Real Projects API successful!\n\n";
    
    echo "🏗️  All Real Construction Projects:\n";
    echo "==================================\n";
    
    foreach ($result['projects'] as $project) {
        echo "📋 Project {$project['id']}: {$project['project_name']}\n";
        echo "   📝 Description: {$project['project_description']}\n";
        echo "   📊 Status: {$project['status']}\n";
        echo "   🏗️  PROGRESS COMPARISON:\n";
        echo "      Real Progress: {$project['real_completion_percentage']}%\n";
        echo "      Stored Progress: {$project['stored_completion_percentage']}%\n";
        echo "      Difference: " . $project['progress_calculation']['progress_difference'] . "%\n";
        echo "   🏗️  STAGE COMPARISON:\n";
        echo "      Stored Stage: {$project['stored_stage']}\n";
        echo "      Actual Stage: {$project['actual_current_stage']}\n";
        echo "   📍 Location: " . ($project['project_location'] ?: 'Not specified') . "\n";
        echo "   👤 Homeowner: {$project['homeowner']['name']} ({$project['homeowner']['email']})\n";
        echo "   🔨 Contractor: {$project['contractor']['name']} ({$project['contractor']['email']})\n";
        echo "   📅 Start Date: {$project['dates']['start_date']}\n";
        echo "   📅 Expected Completion: {$project['dates']['expected_completion']}\n";
        echo "   💰 Total Cost: ₹" . ($project['financial']['total_cost'] ? number_format($project['financial']['total_cost']) : 'Not specified') . "\n";
        echo "   ⏱️  Timeline: {$project['financial']['timeline']}\n";
        echo "   📈 Stage Progress: {$project['statistics']['completed_stages']}/{$project['statistics']['total_stages']} stages completed\n";
        
        if ($project['latest_stage_payment']) {
            $payment = $project['latest_stage_payment'];
            echo "   💰 Latest Payment: {$payment['stage_name']} - {$payment['status']} ({$payment['completion_percentage']}%)\n";
            echo "      Amount: ₹" . number_format($payment['amount']) . "\n";
            echo "      Date: {$payment['request_date']}\n";
            if ($payment['payment_date']) {
                echo "      Paid: {$payment['payment_date']}\n";
            }
            if ($payment['work_description']) {
                echo "      Work: " . substr($payment['work_description'], 0, 100) . "...\n";
            }
        }
        
        echo "   🔧 Inspector Assignment: " . ($project['inspector_assignment']['is_assigned'] ? 'Assigned' : 'Not assigned') . "\n";
        if ($project['inspector_assignment']['is_assigned'] && $project['inspector_assignment']['details']) {
            $assignment = $project['inspector_assignment']['details'];
            echo "      Assigned at: {$assignment['assigned_at']}\n";
            echo "      Notes: {$assignment['notes']}\n";
        }
        
        echo "   🔧 Calculation: {$project['progress_calculation']['method']}\n";
        echo "\n";
    }
    
    echo "📊 Real Projects Statistics:\n";
    echo "===========================\n";
    echo "Total Projects: {$result['statistics']['total_projects']}\n";
    echo "Active Projects: {$result['statistics']['active_projects']}\n";
    echo "Completed Projects: {$result['statistics']['completed_projects']}\n";
    echo "Assigned to Inspector: {$result['statistics']['assigned_projects']}\n";
    echo "Average Real Progress: {$result['statistics']['avg_real_completion']}%\n";
    
    echo "\n💰 Stage Payments by Project:\n";
    echo "============================\n";
    foreach ($result['stage_payments_by_project'] as $projectId => $payments) {
        echo "🏗️ Project {$projectId}:\n";
        foreach ($payments as $payment) {
            $status_icon = $payment['status'] === 'paid' ? '✅' : 
                          ($payment['status'] === 'approved' ? '🟡' : '⏳');
            echo "   {$status_icon} {$payment['stage_name']}: {$payment['completion_percentage']}% - ₹" . 
                 number_format($payment['requested_amount']) . " ({$payment['status']})\n";
            echo "      Date: {$payment['request_date']}" . 
                 ($payment['payment_date'] ? " | Paid: {$payment['payment_date']}" : "") . "\n";
        }
        echo "\n";
    }
    
    echo "🔧 Project Info:\n";
    echo "===============\n";
    echo "Data Source: {$result['project_info']['data_source']}\n";
    echo "Calculation Method: {$result['project_info']['calculation_method']}\n";
    
} else {
    echo "❌ All Real Projects API failed\n";
    echo "Error: {$result['message']}\n";
    if (isset($result['error'])) {
        echo "Details: {$result['error']}\n";
    }
}
?>
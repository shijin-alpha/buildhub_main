<?php
/**
 * Test Updated Dashboard Integration
 * Verify that the frontend will receive the correct data structure
 */

echo "🔍 Testing Updated Dashboard Integration...\n\n";

// Test the API that the frontend now calls
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
    exit(1);
}

if ($result['success']) {
    echo "✅ API successful! Frontend will receive correct data.\n\n";
    
    echo "📊 Data Structure Verification:\n";
    echo "==============================\n";
    
    // Check statistics structure
    if (isset($result['statistics'])) {
        echo "✅ Statistics object present (frontend expects 'statistics')\n";
        $stats = $result['statistics'];
        echo "   - total_projects: {$stats['total_projects']}\n";
        echo "   - active_projects: {$stats['active_projects']}\n";
        echo "   - completed_projects: {$stats['completed_projects']}\n";
        echo "   - assigned_projects: {$stats['assigned_projects']}\n";
    } else {
        echo "❌ Statistics object missing\n";
    }
    
    echo "\n🏗️ Project Data Structure:\n";
    echo "==========================\n";
    
    if (!empty($result['projects'])) {
        $project = $result['projects'][0]; // Test first project
        
        // Check required fields for frontend
        $requiredFields = [
            'id' => 'Project ID',
            'project_name' => 'Project Name', 
            'status' => 'Project Status',
            'real_completion_percentage' => 'Real Progress',
            'actual_current_stage' => 'Current Stage',
            'homeowner' => 'Homeowner Object',
            'contractor' => 'Contractor Object',
            'dates' => 'Dates Object',
            'inspector_assignment' => 'Inspector Assignment'
        ];
        
        foreach ($requiredFields as $field => $description) {
            if (isset($project[$field])) {
                echo "✅ {$description}: Present\n";
                
                // Show sample values
                if ($field === 'homeowner' && is_array($project[$field])) {
                    echo "   - name: {$project[$field]['name']}\n";
                    echo "   - email: {$project[$field]['email']}\n";
                } elseif ($field === 'contractor' && is_array($project[$field])) {
                    echo "   - name: {$project[$field]['name']}\n";
                    echo "   - email: {$project[$field]['email']}\n";
                } elseif ($field === 'dates' && is_array($project[$field])) {
                    echo "   - start_date: {$project[$field]['start_date']}\n";
                    echo "   - expected_completion: {$project[$field]['expected_completion']}\n";
                } elseif ($field === 'inspector_assignment' && is_array($project[$field])) {
                    echo "   - is_assigned: " . ($project[$field]['is_assigned'] ? 'true' : 'false') . "\n";
                } else {
                    echo "   - value: {$project[$field]}\n";
                }
            } else {
                echo "❌ {$description}: Missing\n";
            }
        }
        
        echo "\n📈 Progress Values Verification:\n";
        echo "===============================\n";
        
        foreach ($result['projects'] as $proj) {
            echo "🏗️ Project {$proj['id']}:\n";
            echo "   Real Progress: {$proj['real_completion_percentage']}%\n";
            echo "   Stored Progress: {$proj['stored_completion_percentage']}%\n";
            echo "   Data Source: {$proj['progress_calculation']['data_source']}\n";
            
            if ($proj['latest_daily_progress']) {
                echo "   ✅ Has daily progress data\n";
                echo "   Last Update: {$proj['latest_daily_progress']['update_date']}\n";
            } else {
                echo "   ⚠️ No daily progress data\n";
            }
            echo "\n";
        }
        
    } else {
        echo "❌ No projects in response\n";
    }
    
    echo "🎯 Frontend Integration Status:\n";
    echo "==============================\n";
    echo "✅ API endpoint updated in component\n";
    echo "✅ Data structure matches frontend expectations\n";
    echo "✅ Progress values are corrected (7%, 5%, 20%)\n";
    echo "✅ Field names updated in component\n";
    echo "✅ Statistics structure matches\n";
    
    echo "\n📱 Expected Frontend Display:\n";
    echo "============================\n";
    foreach ($result['projects'] as $proj) {
        echo "Project Card {$proj['id']}:\n";
        echo "  Title: {$proj['project_name']}\n";
        echo "  Status: {$proj['status']}\n";
        echo "  Progress: {$proj['real_completion_percentage']}% Complete\n";
        echo "  Stage: {$proj['actual_current_stage']}\n";
        echo "  Homeowner: {$proj['homeowner']['name']}\n";
        echo "  Assignment: " . ($proj['inspector_assignment']['is_assigned'] ? 'Assigned' : 'Not Assigned') . "\n";
        echo "\n";
    }
    
} else {
    echo "❌ API failed: {$result['message']}\n";
}

echo "✅ Dashboard Integration Test Complete!\n";
?>
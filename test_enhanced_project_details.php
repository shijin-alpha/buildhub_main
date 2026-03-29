<?php
/**
 * Test Enhanced Project Details API
 * This script tests the comprehensive project data retrieval
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Testing Enhanced Project Details API ===\n\n";
    
    // Get available projects
    $projects_query = "SELECT id, project_name, homeowner_name FROM construction_projects LIMIT 5";
    $projects_stmt = $db->prepare($projects_query);
    $projects_stmt->execute();
    $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available Projects:\n";
    foreach ($projects as $project) {
        echo "- ID: {$project['id']}, Name: {$project['project_name']}, Homeowner: {$project['homeowner_name']}\n";
    }
    
    if (empty($projects)) {
        echo "No projects found in database.\n";
        exit;
    }
    
    // Test with first project
    $test_project_id = $projects[0]['id'];
    echo "\n=== Testing with Project ID: $test_project_id ===\n";
    
    // Simulate API call
    $_GET['project_id'] = $test_project_id;
    
    // Start session for admin access
    session_start();
    $_SESSION['admin_logged_in'] = true;
    
    // Capture output
    ob_start();
    include 'backend/api/inspector/get_project_details.php';
    $api_response = ob_get_clean();
    
    $response_data = json_decode($api_response, true);
    
    if ($response_data && $response_data['success']) {
        echo "✅ API Response: SUCCESS\n\n";
        
        $project = $response_data['project'];
        
        echo "📋 Project Information:\n";
        echo "- Name: " . ($project['project_name'] ?? 'N/A') . "\n";
        echo "- Description: " . ($project['project_description'] ?? 'N/A') . "\n";
        echo "- Location: " . ($project['project_location'] ?? 'N/A') . "\n";
        echo "- Status: " . ($project['status'] ?? 'N/A') . "\n";
        echo "- Progress: " . ($project['real_completion_percentage'] ?? 0) . "%\n";
        echo "- Current Stage: " . ($project['actual_current_stage'] ?? 'N/A') . "\n";
        
        echo "\n👤 Homeowner Information:\n";
        echo "- Name: " . ($project['homeowner']['name'] ?? 'N/A') . "\n";
        echo "- Email: " . ($project['homeowner']['email'] ?? 'N/A') . "\n";
        echo "- Phone: " . ($project['homeowner']['phone'] ?? 'N/A') . "\n";
        echo "- Address: " . ($project['homeowner']['address'] ?? 'N/A') . "\n";
        echo "- City: " . ($project['homeowner']['city'] ?? 'N/A') . "\n";
        echo "- State: " . ($project['homeowner']['state'] ?? 'N/A') . "\n";
        
        echo "\n🏗️ Contractor Information:\n";
        echo "- Name: " . ($project['contractor']['name'] ?? 'N/A') . "\n";
        echo "- Email: " . ($project['contractor']['email'] ?? 'N/A') . "\n";
        echo "- Phone: " . ($project['contractor']['phone'] ?? 'N/A') . "\n";
        echo "- Company: " . ($project['contractor']['company'] ?? 'N/A') . "\n";
        echo "- License: " . ($project['contractor']['license'] ?? 'N/A') . "\n";
        echo "- Experience: " . ($project['contractor']['experience'] ?? 'N/A') . " years\n";
        
        echo "\n💰 Financial Summary:\n";
        echo "- Total Cost: ₹" . number_format($project['financial_summary']['total_cost'] ?? 0) . "\n";
        echo "- Total Requested: ₹" . number_format($project['financial_summary']['total_requested'] ?? 0) . "\n";
        echo "- Paid Amount: ₹" . number_format($project['financial_summary']['paid_amount'] ?? 0) . "\n";
        echo "- Pending Amount: ₹" . number_format($project['financial_summary']['pending_amount'] ?? 0) . "\n";
        echo "- Payment Completion: " . ($project['financial_summary']['payment_completion_rate'] ?? 0) . "%\n";
        
        echo "\n📊 Progress Summary:\n";
        echo "- Real Completion: " . ($project['progress_summary']['real_completion'] ?? 0) . "%\n";
        echo "- Completed Stages: " . ($project['progress_summary']['completed_stages'] ?? 0) . " of " . ($project['progress_summary']['total_stages'] ?? 0) . "\n";
        echo "- Latest Daily Progress: " . ($project['progress_summary']['latest_daily_progress'] ?? 0) . "%\n";
        echo "- Last Update: " . ($project['progress_summary']['last_update'] ?? 'Never') . "\n";
        
        echo "\n🔍 Inspector Assignment:\n";
        echo "- Is Assigned: " . ($project['inspector_assignment']['is_assigned'] ? 'YES' : 'NO') . "\n";
        if ($project['inspector_assignment']['is_assigned'] && $project['inspector_assignment']['details']) {
            $details = $project['inspector_assignment']['details'];
            echo "- Inspector: " . ($details['inspector_first_name'] ?? '') . " " . ($details['inspector_last_name'] ?? '') . "\n";
            echo "- Assigned Date: " . ($details['assigned_at'] ?? 'N/A') . "\n";
            echo "- Assigned By: " . ($details['assigned_by_first_name'] ?? '') . " " . ($details['assigned_by_last_name'] ?? '') . "\n";
            echo "- Notes: " . ($details['assignment_notes'] ?? 'No notes') . "\n";
        }
        
        echo "\n📈 Data Sources:\n";
        foreach ($response_data['data_sources'] as $source => $count) {
            echo "- " . ucfirst(str_replace('_', ' ', $source)) . ": $count\n";
        }
        
        echo "\n📋 Additional Data:\n";
        echo "- Stage Payments: " . count($response_data['stage_payments'] ?? []) . " records\n";
        echo "- Daily Progress Updates: " . count($response_data['daily_progress_updates'] ?? []) . " records\n";
        echo "- Construction Progress Updates: " . count($response_data['construction_progress_updates'] ?? []) . " records\n";
        echo "- Inspection Reports: " . count($response_data['inspection_reports'] ?? []) . " records\n";
        echo "- Progress Reports: " . count($response_data['progress_reports'] ?? []) . " records\n";
        
        if (!empty($response_data['inspection_statistics'])) {
            $stats = $response_data['inspection_statistics'];
            echo "\n🔍 Inspection Statistics:\n";
            echo "- Total Inspections: " . ($stats['total_inspections'] ?? 0) . "\n";
            echo "- Approved: " . ($stats['approved_inspections'] ?? 0) . "\n";
            echo "- Rejected: " . ($stats['rejected_inspections'] ?? 0) . "\n";
            echo "- Needs Attention: " . ($stats['needs_attention'] ?? 0) . "\n";
            echo "- Pending: " . ($stats['pending_inspections'] ?? 0) . "\n";
            echo "- Average Quality Score: " . ($stats['avg_quality_score'] ?? 0) . "\n";
            echo "- Last Inspection: " . ($stats['last_inspection_date'] ?? 'Never') . "\n";
            echo "- Safety Violations: " . ($stats['safety_violations'] ?? 0) . "\n";
            echo "- Follow-ups Required: " . ($stats['follow_ups_required'] ?? 0) . "\n";
        }
        
        echo "\n✅ Enhanced Project Details API is working correctly!\n";
        echo "All project information is now being retrieved comprehensively.\n";
        
    } else {
        echo "❌ API Response: FAILED\n";
        echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "\n";
        echo "Raw Response: " . $api_response . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>
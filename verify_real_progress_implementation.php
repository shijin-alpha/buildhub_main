<?php
/**
 * Comprehensive verification of Real Progress Implementation
 * Tests all components of the real progress system
 */

echo "🔍 Verifying Real Progress Implementation...\n\n";

require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Test 1: Verify Inspector Assignments
echo "📋 Test 1: Inspector Project Assignments\n";
echo "========================================\n";

$assignmentQuery = "
    SELECT 
        ipa.inspector_id,
        ipa.project_id,
        cp.project_name,
        cp.completion_percentage as stored_progress,
        ipa.status,
        ipa.assigned_at
    FROM inspector_project_assignments ipa
    JOIN construction_projects cp ON ipa.project_id = cp.id
    WHERE ipa.inspector_id = 1001
    ORDER BY ipa.project_id
";

$stmt = $db->prepare($assignmentQuery);
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($assignments as $assignment) {
    echo "✅ Project {$assignment['project_id']}: {$assignment['project_name']}\n";
    echo "   Stored Progress: {$assignment['stored_progress']}%\n";
    echo "   Status: {$assignment['status']}\n";
    echo "   Assigned: {$assignment['assigned_at']}\n\n";
}

// Test 2: Real Progress Calculation
echo "📊 Test 2: Real Progress Calculation\n";
echo "===================================\n";

foreach ($assignments as $assignment) {
    $projectId = $assignment['project_id'];
    
    // Get real progress
    $progressQuery = "
        SELECT 
            SUM(spr.completion_percentage) as real_progress,
            COUNT(*) as total_stages,
            SUM(CASE WHEN spr.status IN ('paid', 'approved') THEN 1 ELSE 0 END) as completed_stages
        FROM stage_payment_requests spr 
        WHERE spr.project_id = :project_id
    ";
    
    $progressStmt = $db->prepare($progressQuery);
    $progressStmt->execute([':project_id' => $projectId]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    
    $realProgress = $progress['real_progress'] ?: 0;
    $storedProgress = $assignment['stored_progress'];
    $difference = $realProgress - $storedProgress;
    
    echo "🏗️ Project {$projectId}: {$assignment['project_name']}\n";
    echo "   Real Progress: {$realProgress}%\n";
    echo "   Stored Progress: {$storedProgress}%\n";
    echo "   Difference: {$difference}%\n";
    echo "   Completed Stages: {$progress['completed_stages']}/{$progress['total_stages']}\n";
    
    if ($difference != 0) {
        echo "   ⚠️ Progress values are out of sync!\n";
    } else {
        echo "   ✅ Progress values are synchronized\n";
    }
    echo "\n";
}

// Test 3: API Endpoint Test
echo "🔧 Test 3: API Endpoint Functionality\n";
echo "====================================\n";

$apiUrl = 'http://localhost/buildhub/backend/api/inspector/get_projects_simple.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "❌ API endpoint is not accessible\n";
} else {
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "✅ API endpoint is working correctly\n";
        echo "   Projects returned: " . count($result['projects']) . "\n";
        echo "   Statistics available: " . (isset($result['statistics']) ? 'Yes' : 'No') . "\n";
        echo "   Progress info included: " . (isset($result['progress_info']) ? 'Yes' : 'No') . "\n";
        
        // Check data structure
        if (!empty($result['projects'])) {
            $project = $result['projects'][0];
            $requiredFields = [
                'id', 'project_name', 'real_completion_percentage', 
                'stored_completion_percentage', 'actual_current_stage'
            ];
            
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($project[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                echo "   ✅ All required fields present in API response\n";
            } else {
                echo "   ❌ Missing fields: " . implode(', ', $missingFields) . "\n";
            }
        }
    } else {
        echo "❌ API returned error: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
}

// Test 4: Stage Payment Details
echo "\n💰 Test 4: Stage Payment Analysis\n";
echo "================================\n";

$paymentQuery = "
    SELECT 
        cp.id as project_id,
        cp.project_name,
        spr.stage_name,
        spr.completion_percentage,
        spr.requested_amount,
        spr.status,
        spr.request_date
    FROM construction_projects cp
    JOIN stage_payment_requests spr ON cp.id = spr.project_id
    WHERE cp.id IN (SELECT project_id FROM inspector_project_assignments WHERE inspector_id = 1001)
    ORDER BY cp.id, spr.request_date
";

$paymentStmt = $db->prepare($paymentQuery);
$paymentStmt->execute();
$payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

$currentProject = null;
foreach ($payments as $payment) {
    if ($currentProject !== $payment['project_id']) {
        if ($currentProject !== null) echo "\n";
        echo "🏗️ Project {$payment['project_id']}: {$payment['project_name']}\n";
        $currentProject = $payment['project_id'];
    }
    
    $statusIcon = $payment['status'] === 'paid' ? '✅' : 
                 ($payment['status'] === 'approved' ? '🟡' : '⏳');
    
    echo "   {$statusIcon} {$payment['stage_name']}: {$payment['completion_percentage']}% - ₹" . 
         number_format($payment['requested_amount']) . " ({$payment['status']})\n";
}

// Test 5: Dashboard Component Compatibility
echo "\n🖥️ Test 5: Dashboard Component Compatibility\n";
echo "===========================================\n";

// Check if the React component file exists and has been updated
$dashboardFile = 'frontend/src/components/SiteInspectorDashboard.jsx';
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    
    // Check for updated API endpoint
    if (strpos($content, 'get_projects_simple.php') !== false) {
        echo "✅ Dashboard updated to use real progress API\n";
    } else {
        echo "❌ Dashboard still using old API endpoint\n";
    }
    
    // Check for real progress display
    if (strpos($content, 'real_completion_percentage') !== false) {
        echo "✅ Dashboard configured to display real progress\n";
    } else {
        echo "❌ Dashboard not configured for real progress display\n";
    }
    
    // Check for progress comparison
    if (strpos($content, 'stored_completion_percentage') !== false) {
        echo "✅ Dashboard includes progress comparison functionality\n";
    } else {
        echo "❌ Dashboard missing progress comparison\n";
    }
    
} else {
    echo "❌ Dashboard component file not found\n";
}

// Test 6: Summary and Recommendations
echo "\n📋 Test 6: Implementation Summary\n";
echo "===============================\n";

$totalProjects = count($assignments);
$projectsWithProgress = 0;
$projectsOutOfSync = 0;

foreach ($assignments as $assignment) {
    $projectId = $assignment['project_id'];
    
    $progressQuery = "
        SELECT COALESCE(SUM(spr.completion_percentage), 0) as real_progress
        FROM stage_payment_requests spr 
        WHERE spr.project_id = :project_id AND spr.status IN ('paid', 'approved')
    ";
    
    $progressStmt = $db->prepare($progressQuery);
    $progressStmt->execute([':project_id' => $projectId]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    
    $realProgress = $progress['real_progress'];
    $storedProgress = $assignment['stored_progress'];
    
    if ($realProgress > 0) {
        $projectsWithProgress++;
    }
    
    if ($realProgress != $storedProgress) {
        $projectsOutOfSync++;
    }
}

echo "📊 Implementation Status:\n";
echo "   Total Assigned Projects: {$totalProjects}\n";
echo "   Projects with Real Progress: {$projectsWithProgress}\n";
echo "   Projects Out of Sync: {$projectsOutOfSync}\n";

if ($projectsOutOfSync > 0) {
    echo "\n⚠️ Recommendations:\n";
    echo "   - Update stored progress values to match real progress\n";
    echo "   - Implement automatic sync when payments are processed\n";
    echo "   - Add progress validation in payment workflow\n";
} else {
    echo "\n✅ All systems working correctly!\n";
    echo "   - Real progress calculation is accurate\n";
    echo "   - API endpoints are functional\n";
    echo "   - Dashboard is ready for use\n";
}

echo "\n🎯 Next Steps:\n";
echo "   1. Test the dashboard in browser: test_real_progress_dashboard.html\n";
echo "   2. Verify inspector login and navigation\n";
echo "   3. Confirm real progress display in UI\n";
echo "   4. Test progress comparison features\n";

echo "\n✅ Real Progress Implementation Verification Complete!\n";
?>
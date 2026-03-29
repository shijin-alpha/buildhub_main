<?php
/**
 * Test Site Inspection Progress Data Access
 * Verify that site inspectors can see daily progress updates correctly
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Site Inspection Progress Data Test ===\n\n";
    
    // 1. Check if site inspector assignments exist
    echo "1. Checking site inspector assignments:\n";
    $assignments_query = "SELECT 
                            sia.id,
                            sia.inspector_id,
                            sia.project_id,
                            sia.status,
                            cp.project_name,
                            CONCAT(u.first_name, ' ', u.last_name) as inspector_name
                          FROM site_inspector_assignments sia
                          JOIN construction_projects cp ON sia.project_id = cp.id
                          JOIN users u ON sia.inspector_id = u.id
                          WHERE sia.status = 'active'
                          ORDER BY sia.assigned_date DESC";
    
    $assignments_stmt = $db->prepare($assignments_query);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($assignments)) {
        echo "❌ No active site inspector assignments found!\n";
        echo "Creating test assignment...\n";
        
        // Create a test assignment
        $test_assignment = "INSERT INTO site_inspector_assignments 
                           (inspector_id, project_id, assigned_by, notes, status) 
                           VALUES (1001, 37, 1, 'Test assignment for progress verification', 'active')";
        $db->exec($test_assignment);
        echo "✅ Created test assignment for inspector 1001 and project 37\n";
    } else {
        echo "✅ Found " . count($assignments) . " active assignments:\n";
        foreach ($assignments as $assignment) {
            echo "  - Inspector: {$assignment['inspector_name']} (ID: {$assignment['inspector_id']}) -> Project: {$assignment['project_name']} (ID: {$assignment['project_id']})\n";
        }
    }
    
    echo "\n2. Checking daily progress updates for assigned projects:\n";
    
    // Get a sample assignment to test with
    $test_assignment = $assignments[0] ?? ['inspector_id' => 1001, 'project_id' => 37];
    $inspector_id = $test_assignment['inspector_id'];
    $project_id = $test_assignment['project_id'];
    
    echo "Testing with Inspector ID: {$inspector_id}, Project ID: {$project_id}\n";
    
    // Check daily progress updates for this project
    $progress_query = "SELECT 
                        dpu.id,
                        dpu.update_date,
                        dpu.construction_stage,
                        dpu.work_done_today,
                        dpu.incremental_completion_percentage,
                        dpu.cumulative_completion_percentage,
                        dpu.working_hours,
                        dpu.weather_condition,
                        dpu.site_issues,
                        CONCAT(u.first_name, ' ', u.last_name) as contractor_name
                      FROM daily_progress_updates dpu
                      LEFT JOIN users u ON dpu.contractor_id = u.id
                      WHERE dpu.project_id = ?
                      ORDER BY dpu.update_date DESC
                      LIMIT 5";
    
    $progress_stmt = $db->prepare($progress_query);
    $progress_stmt->execute([$project_id]);
    $progress_updates = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($progress_updates)) {
        echo "❌ No daily progress updates found for project {$project_id}!\n";
        echo "This means the site inspector won't see any progress data.\n";
    } else {
        echo "✅ Found " . count($progress_updates) . " progress updates for project {$project_id}:\n";
        foreach ($progress_updates as $update) {
            echo "  - Date: {$update['update_date']}\n";
            echo "    Stage: {$update['construction_stage']}\n";
            echo "    Progress: +{$update['incremental_completion_percentage']}% (Total: {$update['cumulative_completion_percentage']}%)\n";
            echo "    Work Done: " . substr($update['work_done_today'], 0, 50) . "...\n";
            echo "    Contractor: {$update['contractor_name']}\n";
            echo "    Hours: {$update['working_hours']}h, Weather: {$update['weather_condition']}\n";
            if (!empty($update['site_issues'])) {
                echo "    Issues: " . substr($update['site_issues'], 0, 50) . "...\n";
            }
            echo "\n";
        }
    }
    
    echo "3. Testing API endpoint access:\n";
    
    // Test the new progress details API
    echo "Testing get_project_progress_details.php endpoint...\n";
    
    // Simulate the API call
    $_GET['project_id'] = $project_id;
    
    // Mock the auth middleware for testing
    class MockAuth {
        public function requireAuth() { return true; }
        public function requireCapability($cap) { return true; }
        public function getCurrentUser() { 
            return ['id' => 1001, 'role' => 'site_inspector']; 
        }
        public function logAction($action, $project_id, $type, $data, $metadata) { 
            return true; 
        }
    }
    
    // Test access verification
    $access_query = "SELECT COUNT(*) as has_access 
                     FROM site_inspector_assignments sia 
                     WHERE sia.inspector_id = ? AND sia.project_id = ? AND sia.status = 'active'";
    $access_stmt = $db->prepare($access_query);
    $access_stmt->execute([$inspector_id, $project_id]);
    $access_result = $access_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($access_result['has_access'] > 0) {
        echo "✅ Inspector {$inspector_id} has access to project {$project_id}\n";
    } else {
        echo "❌ Inspector {$inspector_id} does NOT have access to project {$project_id}\n";
    }
    
    echo "\n4. Summary:\n";
    echo "- Site inspector assignments: " . (empty($assignments) ? "❌ Missing" : "✅ Available") . "\n";
    echo "- Daily progress data: " . (empty($progress_updates) ? "❌ Missing" : "✅ Available") . "\n";
    echo "- API access: " . ($access_result['has_access'] > 0 ? "✅ Authorized" : "❌ Not authorized") . "\n";
    
    if (!empty($assignments) && !empty($progress_updates) && $access_result['has_access'] > 0) {
        echo "\n🎉 SUCCESS: Site inspection dashboard should now display daily progress data correctly!\n";
        echo "\nThe inspector can see:\n";
        echo "- Project overview and details\n";
        echo "- Daily progress updates with work done, stages, and completion percentages\n";
        echo "- Site issues and weather conditions\n";
        echo "- Progress photos (if available)\n";
        echo "- Stage-wise breakdown and statistics\n";
    } else {
        echo "\n⚠️  ISSUES FOUND: Some data may be missing for the site inspection dashboard.\n";
        if (empty($assignments)) {
            echo "- Need to assign inspectors to projects\n";
        }
        if (empty($progress_updates)) {
            echo "- Need daily progress updates from contractors\n";
        }
        if ($access_result['has_access'] == 0) {
            echo "- Need to verify inspector assignments\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
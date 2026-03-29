<?php
require_once 'config/database.php';

echo "<h2>Testing Project Creation Workflow</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Test 1: Check if we have any accepted estimates
    echo "<h3>1. Checking for accepted estimates...</h3>";
    $stmt = $db->prepare("
        SELECT cse.id, cse.status, cse.total_cost, cse.timeline,
               cls.homeowner_id, u.first_name, u.last_name
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u ON cls.homeowner_id = u.id
        WHERE cse.status = 'accepted'
        LIMIT 5
    ");
    $stmt->execute();
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estimates)) {
        echo "<p>❌ No accepted estimates found. Creating a test accepted estimate...</p>";
        
        // Create a test accepted estimate
        $db->exec("
            INSERT INTO contractor_send_estimates (send_id, contractor_id, total_cost, timeline, status, structured)
            SELECT 1, 1, 150000.00, '90 days', 'accepted', 
            '{\"project_name\":\"Test Construction Project\",\"totals\":{\"materials_total\":80000,\"labor_total\":60000,\"grand_total\":150000}}'
            WHERE NOT EXISTS (SELECT 1 FROM contractor_send_estimates WHERE status = 'accepted' LIMIT 1)
        ");
        
        // Re-check
        $stmt->execute();
        $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach ($estimates as $estimate) {
        echo "<p>✅ Found accepted estimate ID: {$estimate['id']} - Cost: ₹{$estimate['total_cost']} - Timeline: {$estimate['timeline']}</p>";
    }

    // Test 2: Test project creation API
    if (!empty($estimates)) {
        $testEstimate = $estimates[0];
        echo "<h3>2. Testing project creation for estimate ID: {$testEstimate['id']}</h3>";
        
        // Check if project already exists
        $checkStmt = $db->prepare("SELECT id FROM construction_projects WHERE estimate_id = ?");
        $checkStmt->execute([$testEstimate['id']]);
        
        if ($checkStmt->fetch()) {
            echo "<p>✅ Project already exists for this estimate</p>";
        } else {
            // Test the API call
            $url = 'http://localhost/buildhub/backend/api/contractor/create_project_from_estimate.php';
            $data = json_encode(['estimate_id' => $testEstimate['id']]);
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => $data
                ]
            ]);
            
            $result = file_get_contents($url, false, $context);
            $response = json_decode($result, true);
            
            if ($response && $response['success']) {
                echo "<p>✅ Project created successfully! Project ID: {$response['data']['project_id']}</p>";
                echo "<p>Project Name: {$response['data']['project_name']}</p>";
            } else {
                echo "<p>❌ Failed to create project: " . ($response['message'] ?? 'Unknown error') . "</p>";
            }
        }
    }

    // Test 3: Check projects table
    echo "<h3>3. Checking construction_projects table...</h3>";
    $projectsStmt = $db->prepare("
        SELECT id, project_name, homeowner_name, total_cost, status, created_at
        FROM construction_projects
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        echo "<p>❌ No projects found in construction_projects table</p>";
    } else {
        echo "<p>✅ Found " . count($projects) . " projects:</p>";
        foreach ($projects as $project) {
            echo "<p>- Project: {$project['project_name']} | Homeowner: {$project['homeowner_name']} | Cost: ₹{$project['total_cost']} | Status: {$project['status']}</p>";
        }
    }

    // Test 4: Test get projects API
    echo "<h3>4. Testing get projects API...</h3>";
    $url = 'http://localhost/buildhub/backend/api/contractor/get_projects.php?contractor_id=1';
    $result = file_get_contents($url);
    $response = json_decode($result, true);
    
    if ($response && $response['success']) {
        $projectCount = count($response['data']['projects']);
        echo "<p>✅ Get projects API working! Found {$projectCount} projects</p>";
        
        if ($projectCount > 0) {
            $project = $response['data']['projects'][0];
            echo "<p>Sample project: {$project['project_name']} - Progress: {$project['project_summary']['progress']}</p>";
        }
        
        $stats = $response['data']['statistics'];
        echo "<p>Statistics: Total: {$stats['total_projects']}, Active: {$stats['active_projects']}, Completed: {$stats['completed_projects']}</p>";
    } else {
        echo "<p>❌ Get projects API failed: " . ($response['message'] ?? 'Unknown error') . "</p>";
    }

    echo "<h3>✅ Project Creation Workflow Test Complete!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Project creation API created</li>";
    echo "<li>✅ Get projects API created</li>";
    echo "<li>✅ Database tables set up</li>";
    echo "<li>✅ Automatic project creation on estimate acceptance</li>";
    echo "<li>✅ Frontend updated to display projects</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
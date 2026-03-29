<?php
/**
 * Debug Inspector Projects
 * Check why projects are not showing in site inspection section
 */

require_once 'backend/config/database.php';

echo "🔍 Debugging Inspector Projects...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "1. Checking construction projects...\n";
    $projectsStmt = $db->prepare("SELECT id, project_name, status, current_stage, completion_percentage FROM construction_projects");
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "   Project ID: {$project['id']}, Name: {$project['project_name']}, Status: {$project['status']}, Stage: {$project['current_stage']}, Completion: {$project['completion_percentage']}%\n";
    }
    
    echo "\n2. Checking inspector user...\n";
    $inspectorStmt = $db->prepare("SELECT id, email, admin_scope FROM users WHERE email = 'inspector@buildhub.com'");
    $inspectorStmt->execute();
    $inspector = $inspectorStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($inspector) {
        echo "   Inspector ID: {$inspector['id']}, Email: {$inspector['email']}, Scope: {$inspector['admin_scope']}\n";
        $inspectorId = $inspector['id'];
    } else {
        echo "   ❌ Inspector not found\n";
        exit(1);
    }
    
    echo "\n3. Checking inspector project assignments...\n";
    $assignmentsStmt = $db->prepare("
        SELECT 
            ipa.*, 
            u.email as inspector_email, 
            cp.project_name,
            cp.status as project_status
        FROM inspector_project_assignments ipa 
        JOIN users u ON ipa.inspector_id = u.id 
        JOIN construction_projects cp ON ipa.project_id = cp.id
        WHERE ipa.inspector_id = :inspector_id
    ");
    $assignmentsStmt->execute([':inspector_id' => $inspectorId]);
    $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($assignments)) {
        echo "   ❌ No assignments found for inspector\n";
    } else {
        foreach ($assignments as $assignment) {
            echo "   Assignment ID: {$assignment['id']}, Project: {$assignment['project_name']}, Assignment Status: {$assignment['status']}, Project Status: {$assignment['project_status']}\n";
        }
    }
    
    echo "\n4. Testing the API query directly...\n";
    $apiQuery = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.project_description,
            cp.status,
            cp.current_stage,
            cp.completion_percentage,
            cp.project_location,
            cp.homeowner_name,
            cp.homeowner_email,
            cp.start_date,
            cp.expected_completion_date,
            cp.total_cost,
            cp.timeline,
            ipa.assigned_at,
            ipa.notes as assignment_notes,
            ipa.status as assignment_status,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email,
            c.phone as contractor_phone
        FROM inspector_project_assignments ipa
        JOIN construction_projects cp ON ipa.project_id = cp.id
        LEFT JOIN users c ON cp.contractor_id = c.id
        WHERE ipa.inspector_id = :inspector_id
        AND ipa.status = 'active'
    ";
    
    $apiStmt = $db->prepare($apiQuery);
    $apiStmt->execute([':inspector_id' => $inspectorId]);
    $apiResults = $apiStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($apiResults)) {
        echo "   ❌ API query returns no results\n";
        
        // Check if assignments exist but with different status
        $statusCheckStmt = $db->prepare("
            SELECT ipa.status, COUNT(*) as count 
            FROM inspector_project_assignments ipa 
            WHERE ipa.inspector_id = :inspector_id 
            GROUP BY ipa.status
        ");
        $statusCheckStmt->execute([':inspector_id' => $inspectorId]);
        $statusCounts = $statusCheckStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Assignment status breakdown:\n";
        foreach ($statusCounts as $statusCount) {
            echo "     Status: {$statusCount['status']}, Count: {$statusCount['count']}\n";
        }
        
    } else {
        echo "   ✅ API query returns " . count($apiResults) . " results:\n";
        foreach ($apiResults as $result) {
            echo "     Project: {$result['project_name']}, Status: {$result['status']}, Assignment Status: {$result['assignment_status']}\n";
        }
    }
    
    echo "\n5. Checking admin credentials for inspector...\n";
    $credStmt = $db->prepare("SELECT email, admin_scope, is_active FROM admin_credentials WHERE user_id = :user_id");
    $credStmt->execute([':user_id' => $inspectorId]);
    $credentials = $credStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($credentials) {
        echo "   ✅ Admin credentials found: Email: {$credentials['email']}, Scope: {$credentials['admin_scope']}, Active: " . ($credentials['is_active'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ❌ No admin credentials found for inspector\n";
    }
    
    echo "\n6. Recommendations:\n";
    if (empty($assignments)) {
        echo "   • Need to assign projects to inspector\n";
    } elseif (empty($apiResults)) {
        echo "   • Check assignment status - should be 'active'\n";
        echo "   • Verify project exists and is properly linked\n";
    }
    
    if (!$credentials) {
        echo "   • Need to create admin credentials for inspector\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
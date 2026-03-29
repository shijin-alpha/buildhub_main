<?php
/**
 * Simple Query Test
 * Test the database query directly
 */

require_once 'backend/config/database.php';

echo "🧪 Testing Simple Query...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $inspectorId = 1001; // Inspector user ID from our debug
    
    echo "Testing the exact query from the API...\n";
    
    $query = "
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
            ipa.assigned_at,
            ipa.notes as assignment_notes,
            ipa.status as assignment_status
        FROM inspector_project_assignments ipa
        JOIN construction_projects cp ON ipa.project_id = cp.id
        WHERE ipa.inspector_id = :inspector_id
        AND ipa.status = 'active'
        ORDER BY ipa.assigned_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':inspector_id' => $inspectorId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully\n";
    echo "📊 Found " . count($projects) . " projects\n\n";
    
    if (empty($projects)) {
        echo "❌ No projects found. Let's check why:\n\n";
        
        // Check if inspector exists
        $inspectorCheck = $db->prepare("SELECT id, email, admin_scope FROM users WHERE id = :id");
        $inspectorCheck->execute([':id' => $inspectorId]);
        $inspector = $inspectorCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($inspector) {
            echo "✅ Inspector exists: {$inspector['email']} (Scope: {$inspector['admin_scope']})\n";
        } else {
            echo "❌ Inspector not found with ID: $inspectorId\n";
        }
        
        // Check assignments
        $assignmentCheck = $db->prepare("SELECT * FROM inspector_project_assignments WHERE inspector_id = :id");
        $assignmentCheck->execute([':id' => $inspectorId]);
        $assignments = $assignmentCheck->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 Found " . count($assignments) . " assignments:\n";
        foreach ($assignments as $assignment) {
            echo "  - Project ID: {$assignment['project_id']}, Status: {$assignment['status']}\n";
        }
        
        // Check projects
        $projectCheck = $db->prepare("SELECT id, project_name, status FROM construction_projects");
        $projectCheck->execute();
        $allProjects = $projectCheck->fetchAll(PDO::FETCH_ASSOC);
        
        echo "🏗️ Found " . count($allProjects) . " total projects:\n";
        foreach ($allProjects as $project) {
            echo "  - ID: {$project['id']}, Name: {$project['project_name']}, Status: {$project['status']}\n";
        }
        
    } else {
        echo "📋 Projects found:\n";
        foreach ($projects as $project) {
            echo "  ✅ ID: {$project['id']}\n";
            echo "     Name: {$project['project_name']}\n";
            echo "     Status: {$project['status']}\n";
            echo "     Stage: {$project['current_stage']}\n";
            echo "     Completion: {$project['completion_percentage']}%\n";
            echo "     Location: {$project['project_location']}\n";
            echo "     Homeowner: {$project['homeowner_name']}\n";
            echo "     Assignment Status: {$project['assignment_status']}\n";
            echo "     Assigned At: {$project['assigned_at']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
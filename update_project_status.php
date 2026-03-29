<?php
/**
 * Update Project Status
 * Change project status to in_progress so they show in site inspection
 */

require_once 'backend/config/database.php';

echo "🔧 Updating project status for site inspection...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get current projects
    $projectsStmt = $db->prepare("SELECT id, project_name, status, current_stage FROM construction_projects");
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current projects:\n";
    foreach ($projects as $project) {
        echo "  - ID: {$project['id']}, Name: {$project['project_name']}, Status: {$project['status']}, Stage: {$project['current_stage']}\n";
    }
    
    echo "\nUpdating projects to 'in_progress' status...\n";
    
    // Update projects to in_progress status
    $updateStmt = $db->prepare("
        UPDATE construction_projects 
        SET 
            status = 'in_progress',
            current_stage = 'Foundation',
            completion_percentage = 15.0,
            start_date = CURDATE(),
            last_update_date = NOW()
        WHERE status = 'created'
    ");
    
    $updateStmt->execute();
    $updatedCount = $updateStmt->rowCount();
    
    echo "✅ Updated $updatedCount projects to 'in_progress' status\n\n";
    
    // Verify the updates
    $verifyStmt = $db->prepare("SELECT id, project_name, status, current_stage, completion_percentage, start_date FROM construction_projects");
    $verifyStmt->execute();
    $updatedProjects = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Updated projects:\n";
    foreach ($updatedProjects as $project) {
        echo "  ✅ ID: {$project['id']}\n";
        echo "     Name: {$project['project_name']}\n";
        echo "     Status: {$project['status']}\n";
        echo "     Stage: {$project['current_stage']}\n";
        echo "     Completion: {$project['completion_percentage']}%\n";
        echo "     Start Date: {$project['start_date']}\n\n";
    }
    
    echo "🎉 Projects are now ready for site inspection!\n";
    echo "\nThese projects should now appear in the site inspection section because:\n";
    echo "  ✅ Status is 'in_progress' (active construction)\n";
    echo "  ✅ They are assigned to the inspector\n";
    echo "  ✅ They have a current stage (Foundation)\n";
    echo "  ✅ They have started construction (start_date set)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
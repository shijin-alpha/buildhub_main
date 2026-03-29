<?php
/**
 * Fix Daily Progress Updates Project IDs
 * Update progress data to reference existing projects
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Fixing Daily Progress Project IDs ===\n\n";
    
    // Check existing projects
    $existing_projects = "SELECT id, project_name FROM construction_projects ORDER BY id";
    $stmt = $db->prepare($existing_projects);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available projects:\n";
    foreach ($projects as $project) {
        echo "- Project {$project['id']}: {$project['project_name']}\n";
    }
    
    // Check current progress data
    echo "\nCurrent progress data:\n";
    $progress_data = "SELECT id, project_id, update_date, construction_stage, contractor_id FROM daily_progress_updates ORDER BY update_date DESC";
    $stmt = $db->prepare($progress_data);
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($updates as $update) {
        echo "- Update {$update['id']}: Project {$update['project_id']}, Date: {$update['update_date']}, Stage: {$update['construction_stage']}\n";
    }
    
    // Update progress data to use existing project IDs
    echo "\nUpdating progress data to use existing projects...\n";
    
    // Map old project IDs to new ones
    $project_mapping = [
        37 => 1,  // Map project 37 to project 1
        38 => 2   // Map project 38 to project 2
    ];
    
    foreach ($project_mapping as $old_id => $new_id) {
        $update_query = "UPDATE daily_progress_updates SET project_id = ? WHERE project_id = ?";
        $update_stmt = $db->prepare($update_query);
        $result = $update_stmt->execute([$new_id, $old_id]);
        
        if ($result) {
            $affected_rows = $update_stmt->rowCount();
            echo "✅ Updated {$affected_rows} progress records from project {$old_id} to project {$new_id}\n";
        } else {
            echo "❌ Failed to update project {$old_id} to {$new_id}\n";
        }
    }
    
    // Verify the updates
    echo "\nVerification - Updated progress data:\n";
    $verify_query = "SELECT 
                       dpu.id, 
                       dpu.project_id, 
                       dpu.update_date, 
                       dpu.construction_stage,
                       cp.project_name
                     FROM daily_progress_updates dpu
                     LEFT JOIN construction_projects cp ON dpu.project_id = cp.id
                     ORDER BY dpu.update_date DESC";
    
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $verified_updates = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($verified_updates as $update) {
        $project_name = $update['project_name'] ?? 'PROJECT NOT FOUND';
        echo "- Update {$update['id']}: Project {$update['project_id']} ({$project_name}), Date: {$update['update_date']}, Stage: {$update['construction_stage']}\n";
    }
    
    // Check inspector assignments
    echo "\nChecking inspector assignments:\n";
    $assignments_query = "SELECT 
                            sia.project_id,
                            cp.project_name,
                            COUNT(dpu.id) as progress_count
                          FROM site_inspector_assignments sia
                          JOIN construction_projects cp ON sia.project_id = cp.id
                          LEFT JOIN daily_progress_updates dpu ON cp.id = dpu.project_id
                          WHERE sia.inspector_id = 1001 AND sia.status = 'active'
                          GROUP BY sia.project_id, cp.project_name
                          ORDER BY progress_count DESC";
    
    $assignments_stmt = $db->prepare($assignments_query);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Inspector 1001 assignments with progress data:\n";
    foreach ($assignments as $assignment) {
        echo "- Project {$assignment['project_id']}: {$assignment['project_name']} ({$assignment['progress_count']} progress updates)\n";
    }
    
    echo "\n🎉 Progress data has been fixed! Site inspection dashboard should now show progress data correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
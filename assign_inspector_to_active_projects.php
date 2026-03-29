<?php
/**
 * Assign Inspector to Projects with Progress Data
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Assigning Inspector to Active Projects ===\n\n";
    
    // Find projects with daily progress updates
    $projects_with_progress = "SELECT DISTINCT project_id, COUNT(*) as update_count
                              FROM daily_progress_updates 
                              GROUP BY project_id 
                              ORDER BY update_count DESC";
    
    $stmt = $db->prepare($projects_with_progress);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Projects with progress data:\n";
    foreach ($projects as $project) {
        echo "- Project {$project['project_id']}: {$project['update_count']} updates\n";
    }
    
    // Assign inspector 1001 to these projects
    $inspector_id = 1001;
    
    foreach ($projects as $project) {
        $project_id = $project['project_id'];
        
        // Check if assignment already exists
        $check_query = "SELECT id, status FROM site_inspector_assignments 
                       WHERE inspector_id = ? AND project_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$inspector_id, $project_id]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['status'] !== 'active') {
                // Update to active
                $update_query = "UPDATE site_inspector_assignments 
                               SET status = 'active', assigned_date = CURRENT_TIMESTAMP, 
                                   notes = 'Updated for progress data access', 
                                   updated_at = CURRENT_TIMESTAMP
                               WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$existing['id']]);
                echo "✅ Updated assignment for project {$project_id} to active\n";
            } else {
                echo "✅ Inspector already assigned to project {$project_id}\n";
            }
        } else {
            // Create new assignment
            $insert_query = "INSERT INTO site_inspector_assignments 
                           (inspector_id, project_id, assigned_by, notes, status) 
                           VALUES (?, ?, 1, 'Assigned for progress data access', 'active')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([$inspector_id, $project_id]);
            echo "✅ Created new assignment for project {$project_id}\n";
        }
    }
    
    echo "\n=== Verification ===\n";
    
    // Verify assignments
    $verify_query = "SELECT 
                       sia.project_id,
                       cp.project_name,
                       COUNT(dpu.id) as progress_count
                     FROM site_inspector_assignments sia
                     JOIN construction_projects cp ON sia.project_id = cp.id
                     LEFT JOIN daily_progress_updates dpu ON cp.id = dpu.project_id
                     WHERE sia.inspector_id = ? AND sia.status = 'active'
                     GROUP BY sia.project_id, cp.project_name
                     ORDER BY progress_count DESC";
    
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute([$inspector_id]);
    $assignments = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Inspector {$inspector_id} is now assigned to:\n";
    foreach ($assignments as $assignment) {
        echo "- Project {$assignment['project_id']}: {$assignment['project_name']} ({$assignment['progress_count']} progress updates)\n";
    }
    
    echo "\n🎉 Inspector assignment complete! The site inspection dashboard should now show progress data.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
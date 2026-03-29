<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Projects with Progress Updates and Their Contractor Assignments ===\n";
    
    $progress_projects = [1, 2, 37]; // From previous query
    
    foreach ($progress_projects as $project_id) {
        echo "\n--- Project ID: $project_id ---\n";
        
        // Check in construction_projects
        $construction_query = "SELECT id, project_name, contractor_id, homeowner_id, status FROM construction_projects WHERE id = ?";
        $construction_stmt = $pdo->prepare($construction_query);
        $construction_stmt->execute([$project_id]);
        $construction_result = $construction_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($construction_result) {
            echo "Construction Project Found:\n";
            echo "  Name: {$construction_result['project_name']}\n";
            echo "  Contractor ID: " . ($construction_result['contractor_id'] ?? 'NULL') . "\n";
            echo "  Homeowner ID: {$construction_result['homeowner_id']}\n";
            echo "  Status: {$construction_result['status']}\n";
        } else {
            echo "No construction project found with ID $project_id\n";
        }
        
        // Get progress update details
        $progress_query = "SELECT COUNT(*) as count, MIN(update_date) as first_update, MAX(update_date) as last_update, contractor_id FROM daily_progress_updates WHERE project_id = ? GROUP BY contractor_id";
        $progress_stmt = $pdo->prepare($progress_query);
        $progress_stmt->execute([$project_id]);
        $progress_results = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Progress Updates:\n";
        foreach ($progress_results as $progress) {
            echo "  Contractor ID: {$progress['contractor_id']}, Updates: {$progress['count']}, First: {$progress['first_update']}, Last: {$progress['last_update']}\n";
        }
    }
    
    echo "\n=== All Projects for Contractor ID 32 ===\n";
    $contractor_query = "SELECT id, project_name, homeowner_id, status, created_at FROM construction_projects WHERE contractor_id = 32 ORDER BY id";
    $contractor_stmt = $pdo->prepare($contractor_query);
    $contractor_stmt->execute();
    $contractor_projects = $contractor_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($contractor_projects)) {
        echo "No projects found for contractor ID 32\n";
    } else {
        foreach ($contractor_projects as $project) {
            echo "ID: {$project['id']}, Name: {$project['project_name']}, Homeowner: {$project['homeowner_id']}, Status: {$project['status']}, Created: {$project['created_at']}\n";
        }
    }
    
    echo "\n=== Checking if Contractor 32 has any progress updates ===\n";
    $contractor_progress_query = "SELECT DISTINCT project_id, COUNT(*) as count FROM daily_progress_updates WHERE contractor_id = 32 GROUP BY project_id";
    $contractor_progress_stmt = $pdo->prepare($contractor_progress_query);
    $contractor_progress_stmt->execute();
    $contractor_progress = $contractor_progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($contractor_progress)) {
        echo "No progress updates found for contractor ID 32\n";
    } else {
        foreach ($contractor_progress as $progress) {
            echo "Project ID: {$progress['project_id']}, Updates: {$progress['count']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
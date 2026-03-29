<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Projects with Daily Progress Updates ===\n";
    $progress_query = "SELECT DISTINCT project_id, COUNT(*) as update_count FROM daily_progress_updates GROUP BY project_id ORDER BY project_id";
    $progress_stmt = $pdo->prepare($progress_query);
    $progress_stmt->execute();
    $progress_projects = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($progress_projects as $project) {
        echo "Project ID: {$project['project_id']}, Updates: {$project['update_count']}\n";
    }
    
    echo "\n=== Contractor Projects for Contractor ID 32 ===\n";
    $contractor_query = "SELECT id, project_name, estimate_id FROM construction_projects WHERE contractor_id = 32 ORDER BY id";
    $contractor_stmt = $pdo->prepare($contractor_query);
    $contractor_stmt->execute();
    $contractor_projects = $contractor_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contractor_projects as $project) {
        echo "Project ID: {$project['id']}, Name: {$project['project_name']}, Estimate ID: " . ($project['estimate_id'] ?? 'NULL') . "\n";
    }
    
    echo "\n=== Layout Requests for Homeowner ID 32 ===\n";
    $layout_query = "SELECT id, project_name FROM layout_requests WHERE homeowner_id = 32 ORDER BY id";
    $layout_stmt = $pdo->prepare($layout_query);
    $layout_stmt->execute();
    $layout_projects = $layout_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($layout_projects as $project) {
        echo "Layout ID: {$project['id']}, Name: " . ($project['project_name'] ?? 'NULL') . "\n";
    }
    
    echo "\n=== Checking if Progress Updates Match Any Project ===\n";
    foreach ($progress_projects as $progress_project) {
        $project_id = $progress_project['project_id'];
        
        // Check in construction_projects
        $check_construction = "SELECT project_name FROM construction_projects WHERE id = ?";
        $check_stmt = $pdo->prepare($check_construction);
        $check_stmt->execute([$project_id]);
        $construction_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($construction_result) {
            echo "Progress Project ID $project_id matches Construction Project: {$construction_result['project_name']}\n";
        } else {
            echo "Progress Project ID $project_id does NOT match any Construction Project\n";
        }
        
        // Check in layout_requests
        $check_layout = "SELECT project_name FROM layout_requests WHERE id = ?";
        $check_layout_stmt = $pdo->prepare($check_layout);
        $check_layout_stmt->execute([$project_id]);
        $layout_result = $check_layout_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($layout_result) {
            echo "Progress Project ID $project_id matches Layout Request: " . ($layout_result['project_name'] ?? 'Unnamed') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
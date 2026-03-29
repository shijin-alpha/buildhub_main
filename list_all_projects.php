<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== All Construction Projects ===\n\n";
    
    $stmt = $db->query("SELECT id, project_name, homeowner_id, homeowner_name, status, current_stage, estimate_cost 
                        FROM construction_projects 
                        ORDER BY id DESC 
                        LIMIT 20");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        echo "No projects found!\n";
    } else {
        foreach ($projects as $project) {
            echo "ID: {$project['id']}\n";
            echo "Name: {$project['project_name']}\n";
            echo "Homeowner ID: {$project['homeowner_id']}\n";
            echo "Homeowner: {$project['homeowner_name']}\n";
            echo "Status: {$project['status']}\n";
            echo "Stage: {$project['current_stage']}\n";
            echo "Budget: ₹" . number_format($project['estimate_cost']) . "\n";
            echo "---\n";
        }
    }
    
    echo "\nTotal projects: " . count($projects) . "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

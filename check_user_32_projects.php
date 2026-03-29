<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== CHECKING USER 32 PROJECTS ===\n\n";
    
    // Check projects for user 32
    $stmt = $db->prepare('SELECT id, project_name, homeowner_id, homeowner_name FROM construction_projects WHERE homeowner_id = 32');
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($projects)) {
        echo "❌ No projects found for homeowner_id 32\n\n";
        
        echo "All projects in system:\n";
        $stmt = $db->prepare('SELECT id, project_name, homeowner_id, homeowner_name FROM construction_projects ORDER BY id');
        $stmt->execute();
        $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allProjects as $project) {
            echo "  Project {$project['id']}: {$project['project_name']} - Homeowner {$project['homeowner_id']} ({$project['homeowner_name']})\n";
        }
        
        echo "\nCurrent inspection report:\n";
        $stmt = $db->prepare('SELECT ir.id, ir.project_id, cp.homeowner_id, cp.project_name FROM inspection_reports ir JOIN construction_projects cp ON ir.project_id = cp.id');
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reports as $report) {
            echo "  Report {$report['id']} - Project {$report['project_id']} ({$report['project_name']}) - Homeowner {$report['homeowner_id']}\n";
        }
        
    } else {
        echo "✅ Projects for homeowner_id 32:\n";
        foreach ($projects as $project) {
            echo "  Project {$project['id']}: {$project['project_name']}\n";
        }
        
        // Check if there are inspection reports for these projects
        $projectIds = array_column($projects, 'id');
        $placeholders = str_repeat('?,', count($projectIds) - 1) . '?';
        $stmt = $db->prepare("SELECT * FROM inspection_reports WHERE project_id IN ($placeholders)");
        $stmt->execute($projectIds);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nInspection reports for user 32's projects:\n";
        if (empty($reports)) {
            echo "  ❌ No inspection reports found\n";
        } else {
            foreach ($reports as $report) {
                echo "  Report {$report['id']} - Project {$report['project_id']} - Date {$report['inspection_date']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
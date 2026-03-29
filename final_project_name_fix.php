<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FINAL PROJECT NAME FIX ===\n\n";
    
    // Get all construction projects grouped by homeowner
    $stmt = $pdo->query("
        SELECT id, project_name, homeowner_name, homeowner_id, total_cost, status, created_at, current_stage
        FROM construction_projects 
        ORDER BY homeowner_id, created_at
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by homeowner to add project numbers
    $homeownerProjects = [];
    foreach ($projects as $project) {
        $homeownerId = $project['homeowner_id'];
        if (!isset($homeownerProjects[$homeownerId])) {
            $homeownerProjects[$homeownerId] = [];
        }
        $homeownerProjects[$homeownerId][] = $project;
    }
    
    // Update each project with a unique name
    foreach ($homeownerProjects as $homeownerId => $homeownerProjectList) {
        $projectCount = count($homeownerProjectList);
        
        foreach ($homeownerProjectList as $index => $project) {
            $projectNumber = $index + 1;
            $newName = $project['homeowner_name'];
            
            // If homeowner has multiple projects, add project number
            if ($projectCount > 1) {
                $newName .= " - Project #{$projectNumber}";
            }
            
            // Add cost if available
            if ($project['total_cost'] > 0) {
                $costFormatted = number_format($project['total_cost'], 0);
                $newName .= " (₹{$costFormatted})";
            } else {
                // Add stage and date for projects without cost
                if ($project['current_stage']) {
                    $newName .= " - " . $project['current_stage'];
                }
                $date = date('M d, Y', strtotime($project['created_at']));
                $newName .= " [{$date}]";
            }
            
            // Update the project name
            $updateStmt = $pdo->prepare("UPDATE construction_projects SET project_name = ? WHERE id = ?");
            $updateStmt->execute([$newName, $project['id']]);
            
            echo "Updated Project ID {$project['id']}: {$newName}\n";
        }
    }
    
    echo "\n=== VERIFICATION ===\n\n";
    
    // Verify the changes
    $stmt = $pdo->query("SELECT id, project_name FROM construction_projects ORDER BY id");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $p) {
        echo "ID {$p['id']}: {$p['project_name']}\n";
    }
    
    echo "\nDone!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

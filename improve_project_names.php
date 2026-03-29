<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== IMPROVING PROJECT NAMES ===\n\n";
    
    // Get all construction projects
    $stmt = $pdo->query("
        SELECT id, project_name, homeowner_name, total_cost, status, created_at, current_stage
        FROM construction_projects 
        ORDER BY id
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        $newName = $project['homeowner_name'];
        
        // Add stage or status info for projects without cost
        if ($project['total_cost'] == 0) {
            if ($project['current_stage']) {
                $newName .= " - " . $project['current_stage'] . " Stage";
            } else {
                $newName .= " - " . ucfirst($project['status']);
            }
            
            // Add date to make it unique
            $date = date('M Y', strtotime($project['created_at']));
            $newName .= " ({$date})";
        } else {
            // For projects with cost, use a descriptive name
            $costFormatted = number_format($project['total_cost'], 0);
            $newName .= " Construction (₹{$costFormatted})";
        }
        
        // Update the project name
        $updateStmt = $pdo->prepare("UPDATE construction_projects SET project_name = ? WHERE id = ?");
        $updateStmt->execute([$newName, $project['id']]);
        
        echo "Updated Project ID {$project['id']}:\n";
        echo "  Old: {$project['project_name']}\n";
        echo "  New: {$newName}\n\n";
    }
    
    echo "=== VERIFICATION ===\n\n";
    
    // Verify the changes
    $stmt = $pdo->query("SELECT id, project_name, total_cost FROM construction_projects ORDER BY id");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $p) {
        echo "ID {$p['id']}: {$p['project_name']}\n";
    }
    
    echo "\nDone!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

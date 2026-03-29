<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FIXING DUPLICATE PROJECT NAMES ===\n\n";
    
    // Get all construction projects with the same name
    $stmt = $pdo->query("
        SELECT id, project_name, homeowner_name, total_cost, status, created_at
        FROM construction_projects 
        WHERE project_name = 'SHIJIN THOMAS MCA2024-2026 Construction'
        ORDER BY id
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($projects) . " projects with duplicate names.\n\n";
    
    // Update each project with a unique name
    foreach ($projects as $index => $project) {
        $projectNumber = $index + 1;
        $newName = "SHIJIN THOMAS MCA2024-2026 Construction - Project {$projectNumber}";
        
        // Add more context based on cost or status
        if ($project['total_cost'] > 0) {
            $costFormatted = number_format($project['total_cost'], 0);
            $newName = "SHIJIN THOMAS MCA2024-2026 Construction (₹{$costFormatted})";
        } else {
            $newName = "SHIJIN THOMAS MCA2024-2026 Construction - Project {$projectNumber}";
        }
        
        // Update the project name
        $updateStmt = $pdo->prepare("UPDATE construction_projects SET project_name = ? WHERE id = ?");
        $updateStmt->execute([$newName, $project['id']]);
        
        echo "Updated Project ID {$project['id']}: {$newName}\n";
    }
    
    echo "\n=== FIXING CONTRACTOR SEND ESTIMATES ===\n\n";
    
    // Get all send estimates with the same name
    $stmt = $pdo->query("
        SELECT id, structured, total_cost, status
        FROM contractor_send_estimates 
        WHERE status IN ('accepted', 'project_created')
        ORDER BY id
    ");
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estimates as $estimate) {
        $structured = json_decode($estimate['structured'], true);
        
        if ($structured && isset($structured['project_name']) && 
            $structured['project_name'] === 'SHIJIN THOMAS MCA2024-2026 Construction') {
            
            // Update the project name in structured data
            $costFormatted = number_format($estimate['total_cost'], 0);
            $structured['project_name'] = "SHIJIN THOMAS MCA2024-2026 Construction (₹{$costFormatted})";
            
            // Update the database
            $updateStmt = $pdo->prepare("UPDATE contractor_send_estimates SET structured = ? WHERE id = ?");
            $updateStmt->execute([json_encode($structured), $estimate['id']]);
            
            echo "Updated Send Estimate ID {$estimate['id']}: {$structured['project_name']}\n";
        }
    }
    
    echo "\n=== VERIFICATION ===\n\n";
    
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

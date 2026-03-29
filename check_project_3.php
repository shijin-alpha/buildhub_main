<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PROJECT 3 STATUS ===\n\n";
    
    $stmt = $pdo->query("SELECT id, project_name, contractor_id, status, total_cost FROM construction_projects WHERE id = 3");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "ID: {$project['id']}\n";
        echo "Name: {$project['project_name']}\n";
        echo "Contractor ID: {$project['contractor_id']}\n";
        echo "Status: {$project['status']}\n";
        echo "Cost: ₹" . number_format($project['total_cost'], 2) . "\n";
        
        // Check if it matches the query criteria
        if ($project['status'] === 'completed') {
            echo "\n⚠️ Project 3 has status 'completed' - it won't show in the API because the query filters for 'created' or 'in_progress' only.\n";
        }
    } else {
        echo "Project 3 not found.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

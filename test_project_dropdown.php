<?php
// Simulate what the API returns for contractor ID 32
$contractor_id = 32;

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get construction projects
    $stmt = $pdo->prepare("
        SELECT id, project_name, total_cost, status
        FROM construction_projects
        WHERE contractor_id = ?
        ORDER BY id
    ");
    $stmt->execute([$contractor_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== WHAT THE DROPDOWN WILL SHOW ===\n\n";
    
    foreach ($projects as $project) {
        $displayName = $project['project_name'];
        
        if ($project['total_cost'] > 0) {
            $displayName .= " (₹" . number_format($project['total_cost'], 0) . ")";
        }
        
        echo "Option: {$displayName}\n";
        echo "Value: {$project['id']}\n";
        echo "Status: {$project['status']}\n";
        echo "---\n";
    }
    
    // Also check send estimates
    $stmt = $pdo->prepare("
        SELECT 
            cse.id,
            cse.total_cost,
            cse.structured,
            cse.status
        FROM contractor_send_estimates cse
        WHERE cse.contractor_id = ?
        AND cse.status IN ('accepted', 'project_created')
        ORDER BY cse.id
    ");
    $stmt->execute([$contractor_id]);
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== SEND ESTIMATES ===\n\n";
    
    foreach ($estimates as $estimate) {
        $structured = json_decode($estimate['structured'], true);
        $projectName = "Project for Homeowner";
        
        if ($structured && isset($structured['project_name'])) {
            $projectName = $structured['project_name'];
        }
        
        $displayName = $projectName;
        if ($estimate['total_cost'] > 0) {
            $displayName .= " (₹" . number_format($estimate['total_cost'], 0) . ")";
        }
        
        echo "Option: {$displayName}\n";
        echo "Value: {$estimate['id']}\n";
        echo "Status: {$estimate['status']}\n";
        echo "---\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

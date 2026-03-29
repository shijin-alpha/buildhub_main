<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CONSTRUCTION PROJECTS ===\n\n";
    $stmt = $pdo->query("SELECT id, project_name, contractor_id, homeowner_id, total_cost FROM construction_projects ORDER BY id");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $p) {
        echo "Project ID: {$p['id']}\n";
        echo "Name: {$p['project_name']}\n";
        echo "Contractor ID: {$p['contractor_id']}\n";
        echo "Homeowner ID: {$p['homeowner_id']}\n";
        echo "Cost: ₹" . number_format($p['total_cost'], 2) . "\n";
        echo "---\n";
    }
    
    echo "\n=== CONTRACTOR SEND ESTIMATES ===\n\n";
    $stmt = $pdo->query("SELECT id, contractor_id, total_cost, status FROM contractor_send_estimates WHERE status IN ('accepted', 'project_created') ORDER BY id");
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estimates as $e) {
        echo "Estimate ID: {$e['id']}\n";
        echo "Contractor ID: {$e['contractor_id']}\n";
        echo "Cost: ₹" . number_format($e['total_cost'], 2) . "\n";
        echo "Status: {$e['status']}\n";
        echo "---\n";
    }
    
    echo "\n=== USERS (CONTRACTORS) ===\n\n";
    $stmt = $pdo->query("SELECT id, first_name, last_name, role FROM users WHERE role = 'contractor' ORDER BY id");
    $contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contractors as $c) {
        echo "Contractor ID: {$c['id']}\n";
        echo "Name: {$c['first_name']} {$c['last_name']}\n";
        echo "---\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

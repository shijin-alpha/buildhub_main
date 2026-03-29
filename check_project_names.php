<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CONSTRUCTION PROJECTS ===\n\n";
    $stmt = $pdo->query("SELECT id, project_name, homeowner_name, total_cost, status FROM construction_projects ORDER BY id");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        echo "No construction projects found.\n\n";
    } else {
        foreach ($projects as $p) {
            echo "ID: {$p['id']}\n";
            echo "Project Name: {$p['project_name']}\n";
            echo "Homeowner: {$p['homeowner_name']}\n";
            echo "Cost: ₹" . number_format($p['total_cost'], 2) . "\n";
            echo "Status: {$p['status']}\n";
            echo "---\n";
        }
    }
    
    echo "\n=== CONTRACTOR ESTIMATES ===\n\n";
    $stmt = $pdo->query("SELECT id, project_name, total_cost, status, homeowner_id FROM contractor_estimates WHERE status = 'accepted' ORDER BY id");
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estimates)) {
        echo "No accepted contractor estimates found.\n\n";
    } else {
        foreach ($estimates as $e) {
            echo "ID: {$e['id']}\n";
            echo "Project Name: {$e['project_name']}\n";
            echo "Cost: ₹" . number_format($e['total_cost'], 2) . "\n";
            echo "Status: {$e['status']}\n";
            echo "Homeowner ID: {$e['homeowner_id']}\n";
            echo "---\n";
        }
    }
    
    echo "\n=== CONTRACTOR SEND ESTIMATES ===\n\n";
    $stmt = $pdo->query("
        SELECT 
            cse.id, 
            cse.total_cost, 
            cse.status,
            cse.structured,
            cls.homeowner_id,
            u.first_name,
            u.last_name
        FROM contractor_send_estimates cse
        LEFT JOIN contractor_layout_sends cls ON cls.id = cse.send_id
        LEFT JOIN users u ON u.id = cls.homeowner_id
        WHERE cse.status IN ('accepted', 'project_created')
        ORDER BY cse.id
    ");
    $send_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($send_estimates)) {
        echo "No accepted send estimates found.\n";
    } else {
        foreach ($send_estimates as $se) {
            $homeowner_name = ($se['first_name'] ?? 'Unknown') . ' ' . ($se['last_name'] ?? '');
            $project_name = "Project for " . $homeowner_name;
            
            // Try to extract project name from structured data
            if ($se['structured']) {
                $structured = json_decode($se['structured'], true);
                if ($structured && isset($structured['project_name'])) {
                    $project_name = $structured['project_name'];
                }
            }
            
            echo "ID: {$se['id']}\n";
            echo "Project Name: {$project_name}\n";
            echo "Homeowner: {$homeowner_name}\n";
            echo "Cost: ₹" . number_format($se['total_cost'], 2) . "\n";
            echo "Status: {$se['status']}\n";
            echo "---\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

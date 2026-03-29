<?php
// Test what the API returns for contractor 29
$contractor_id = 29;

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== API RESPONSE FOR CONTRACTOR ID: {$contractor_id} ===\n\n";
    
    // Get construction projects (same query as in the API)
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            cp.id,
            cp.project_name,
            cp.total_cost as estimate_cost,
            cp.status
        FROM construction_projects cp
        WHERE cp.contractor_id = ? 
        AND cp.status IN ('created', 'in_progress')
        ORDER BY cp.created_at DESC
    ");
    
    $stmt->execute([$contractor_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Construction Projects:\n";
    foreach ($projects as $p) {
        $displayName = $p['project_name'];
        if ($p['estimate_cost'] > 0) {
            $displayName .= " (₹" . number_format($p['estimate_cost'], 0) . ")";
        }
        echo "  - {$displayName}\n";
    }
    
    // Get accepted estimates
    $stmt = $pdo->prepare("
        SELECT 
            cse.id,
            cse.total_cost as estimate_cost,
            cse.structured
        FROM contractor_send_estimates cse
        WHERE cse.contractor_id = ? 
        AND cse.status IN ('accepted', 'project_created')
        ORDER BY cse.created_at DESC
    ");
    
    $stmt->execute([$contractor_id]);
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAccepted Estimates:\n";
    foreach ($estimates as $e) {
        $structured = json_decode($e['structured'], true);
        $projectName = "Project for Homeowner";
        
        if ($structured && isset($structured['project_name'])) {
            $projectName = $structured['project_name'];
        }
        
        $displayName = $projectName;
        if ($e['estimate_cost'] > 0) {
            $displayName .= " (₹" . number_format($e['estimate_cost'], 0) . ")";
        }
        echo "  - {$displayName}\n";
    }
    
    echo "\n=== TOTAL PROJECTS: " . (count($projects) + count($estimates)) . " ===\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

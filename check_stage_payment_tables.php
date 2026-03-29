<?php
header('Content-Type: application/json');

try {
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $results = [];
    
    // Check if stage_payment_requests table exists and its structure
    try {
        $stmt = $pdo->query("DESCRIBE stage_payment_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['stage_payment_requests_structure'] = $columns;
        
        // Get sample data
        $stmt = $pdo->query("SELECT * FROM stage_payment_requests ORDER BY created_at DESC LIMIT 5");
        $results['stage_payment_requests_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM stage_payment_requests");
        $results['stage_payment_requests_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
    } catch (Exception $e) {
        $results['stage_payment_requests_error'] = $e->getMessage();
    }
    
    // Check other payment request tables
    $tables = ['enhanced_stage_payment_requests', 'project_stage_payment_requests', 'custom_payment_requests'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results[$table . '_structure'] = $columns;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $results[$table . '_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
        } catch (Exception $e) {
            $results[$table . '_error'] = $e->getMessage();
        }
    }
    
    // Check construction_projects table
    try {
        $stmt = $pdo->query("SELECT id, project_name, contractor_id, homeowner_id, status FROM construction_projects LIMIT 5");
        $results['construction_projects_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM construction_projects");
        $results['construction_projects_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
    } catch (Exception $e) {
        $results['construction_projects_error'] = $e->getMessage();
    }
    
    // Check contractor_estimates table
    try {
        $stmt = $pdo->query("SELECT id, project_name, contractor_id, homeowner_id, status FROM contractor_estimates WHERE status = 'accepted' LIMIT 5");
        $results['contractor_estimates_accepted'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contractor_estimates WHERE status = 'accepted'");
        $results['contractor_estimates_accepted_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
    } catch (Exception $e) {
        $results['contractor_estimates_error'] = $e->getMessage();
    }
    
    // Check contractor_send_estimates table
    try {
        $stmt = $pdo->query("SELECT cse.id, cse.contractor_id, cls.homeowner_id, cse.status FROM contractor_send_estimates cse INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id WHERE cse.status IN ('accepted', 'project_created') LIMIT 5");
        $results['contractor_send_estimates_accepted'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM contractor_send_estimates WHERE status IN ('accepted', 'project_created')");
        $results['contractor_send_estimates_accepted_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
    } catch (Exception $e) {
        $results['contractor_send_estimates_error'] = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
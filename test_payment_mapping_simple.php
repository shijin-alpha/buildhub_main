<?php
// Simple test to understand payment project mapping
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=buildhub;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = [];
    
    // 1. Check construction_projects for project 37
    $stmt = $pdo->prepare("SELECT id, project_name, estimate_id, homeowner_id, contractor_id FROM construction_projects WHERE id = 37 OR estimate_id = 37");
    $stmt->execute();
    $construction_project = $stmt->fetch(PDO::FETCH_ASSOC);
    $result['construction_project_37'] = $construction_project;
    
    // 2. Check payment requests for project 1 (where payments exist)
    $stmt = $pdo->prepare("SELECT project_id, contractor_id, homeowner_id, stage_name, status, requested_amount FROM stage_payment_requests WHERE project_id = 1");
    $stmt->execute();
    $payments_project_1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['payments_for_project_1'] = $payments_project_1;
    
    // 3. Check payment requests for project 37
    $stmt = $pdo->prepare("SELECT project_id, contractor_id, homeowner_id, stage_name, status, requested_amount FROM stage_payment_requests WHERE project_id = 37");
    $stmt->execute();
    $payments_project_37 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['payments_for_project_37'] = $payments_project_37;
    
    // 4. Check if there's a relationship between project 37 and estimate 1
    $stmt = $pdo->prepare("SELECT id, contractor_id, total_cost FROM contractor_send_estimates WHERE id = 1 OR id = 37");
    $stmt->execute();
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['estimates'] = $estimates;
    
    // 5. Check contractor projects API data for project 37
    $stmt = $pdo->prepare("SELECT id, estimate_id FROM construction_projects WHERE id = 37");
    $stmt->execute();
    $project_37_details = $stmt->fetch(PDO::FETCH_ASSOC);
    $result['project_37_details'] = $project_37_details;
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
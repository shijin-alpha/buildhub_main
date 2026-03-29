<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Checking Project 38 ===\n\n";
    
    // Check construction_projects table
    $stmt = $db->prepare("SELECT * FROM construction_projects WHERE id = 38");
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "Found Project 38 in construction_projects:\n";
        print_r($project);
    } else {
        echo "Project 38 NOT found in construction_projects\n\n";
    }
    
    // Check contractor_send_estimates table (source of the data)
    echo "\n=== Checking contractor_send_estimates ===\n\n";
    $stmt = $db->prepare("SELECT * FROM contractor_send_estimates WHERE id = 38");
    $stmt->execute();
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estimate) {
        echo "Found Estimate 38:\n";
        echo "ID: {$estimate['id']}\n";
        echo "Homeowner ID: {$estimate['homeowner_id']}\n";
        echo "Contractor ID: {$estimate['contractor_id']}\n";
        echo "Status: {$estimate['status']}\n";
        echo "Total Cost: ₹" . number_format($estimate['total_cost']) . "\n";
        echo "Needs Project Creation: " . ($estimate['needs_project_creation'] ?? 'N/A') . "\n";
    } else {
        echo "Estimate 38 NOT found\n";
    }
    
    // Check if there's a construction project linked to estimate 38
    echo "\n=== Checking for construction project with estimate_id = 38 ===\n\n";
    $stmt = $db->prepare("SELECT * FROM construction_projects WHERE estimate_id = 38");
    $stmt->execute();
    $linked_project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($linked_project) {
        echo "Found linked construction project:\n";
        echo "Project ID: {$linked_project['id']}\n";
        echo "Project Name: {$linked_project['project_name']}\n";
        echo "Status: {$linked_project['status']}\n";
        echo "Current Stage: {$linked_project['current_stage']}\n";
        echo "Completion: {$linked_project['completion_percentage']}%\n";
    } else {
        echo "No construction project linked to estimate 38\n";
        echo "This estimate needs to be converted to a construction project!\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

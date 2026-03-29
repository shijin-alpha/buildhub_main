<?php
/**
 * Test what the contractor API returns for project 3
 */

session_start();
$_SESSION['user_id'] = 29; // Contractor ID
$_SESSION['role'] = 'contractor';

require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $contractor_id = 29;
    
    echo "=== Testing Contractor API Response ===\n\n";
    
    // Test 1: Get ALL projects (including completed)
    echo "1. ALL PROJECTS (including completed):\n\n";
    $stmt = $pdo->prepare("
        SELECT 
            cp.id,
            cp.estimate_id,
            cp.project_name,
            cp.status,
            cp.current_stage,
            cp.completion_percentage,
            cp.total_cost,
            cp.updated_at
        FROM construction_projects cp
        WHERE cp.contractor_id = ?
        ORDER BY cp.id DESC
    ");
    $stmt->execute([$contractor_id]);
    $all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_projects as $p) {
        echo "ID: {$p['id']} (Estimate: {$p['estimate_id']})\n";
        echo "Name: {$p['project_name']}\n";
        echo "Status: {$p['status']}\n";
        echo "Stage: {$p['current_stage']}\n";
        echo "Completion: {$p['completion_percentage']}%\n";
        echo "Cost: ₹" . number_format($p['total_cost']) . "\n";
        echo "---\n";
    }
    
    // Test 2: Get ACTIVE projects only (what the API returns)
    echo "\n2. ACTIVE PROJECTS ONLY (API filter):\n\n";
    $stmt = $pdo->prepare("
        SELECT 
            cp.id,
            cp.estimate_id,
            cp.project_name,
            cp.status,
            cp.current_stage,
            cp.completion_percentage
        FROM construction_projects cp
        WHERE cp.contractor_id = ?
        AND cp.status IN ('created', 'in_progress')
        ORDER BY cp.id DESC
    ");
    $stmt->execute([$contractor_id]);
    $active_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($active_projects)) {
        echo "No active projects found (all are completed!)\n";
    } else {
        foreach ($active_projects as $p) {
            echo "ID: {$p['id']} - {$p['project_name']} - {$p['status']}\n";
        }
    }
    
    // Test 3: Check contractor_send_estimates
    echo "\n3. CONTRACTOR_SEND_ESTIMATES (what dropdown might show):\n\n";
    $stmt = $pdo->prepare("
        SELECT 
            cse.id,
            cse.status,
            cse.total_cost,
            cse.needs_project_creation,
            u.first_name,
            u.last_name,
            u.email
        FROM contractor_send_estimates cse
        LEFT JOIN users u ON u.id = cse.user_id
        WHERE cse.contractor_id = ?
        AND cse.id IN (38, 40)
        ORDER BY cse.id DESC
    ");
    $stmt->execute([$contractor_id]);
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estimates as $e) {
        echo "Estimate ID: {$e['id']}\n";
        echo "Status: {$e['status']}\n";
        echo "Cost: ₹" . number_format($e['total_cost']) . "\n";
        echo "Needs Project: " . ($e['needs_project_creation'] ?? 'N/A') . "\n";
        echo "Homeowner: {$e['first_name']} {$e['last_name']}\n";
        echo "---\n";
    }
    
    echo "\n=== CONCLUSION ===\n";
    echo "Project 3 is COMPLETED (100%) in the database.\n";
    echo "The API filters out completed projects from the active list.\n";
    echo "The frontend dropdown shows estimate IDs, not project IDs.\n";
    echo "Estimate 38 → Project 3 (COMPLETED)\n";
    echo "\nTo see the completed project, check the 'Completed Projects' section.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

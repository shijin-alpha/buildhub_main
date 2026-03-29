<?php
// Check Project 37 Complete Status - MySQL Version
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PROJECT 37 DETAILS ===\n\n";
    
    // Get project info
    $stmt = $db->query("SELECT * FROM projects WHERE project_id = 37");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "Project 37 not found! Creating test project...\n";
        
        // Create project 37 for testing
        $db->exec("INSERT INTO projects (project_id, project_name, homeowner_id, contractor_id, status, start_date, end_date, budget, created_at) 
                   VALUES (37, 'Complete Construction Test Project', 32, 29, 'in_progress', '2026-01-01', '2026-06-30', 5000000, NOW())");
        
        echo "Project 37 created!\n\n";
        $stmt = $db->query("SELECT * FROM projects WHERE project_id = 37");
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo "Project Name: " . $project['project_name'] . "\n";
    echo "Status: " . $project['status'] . "\n";
    echo "Homeowner ID: " . $project['homeowner_id'] . "\n";
    echo "Contractor ID: " . $project['contractor_id'] . "\n";
    echo "Start Date: " . $project['start_date'] . "\n";
    echo "End Date: " . $project['end_date'] . "\n";
    echo "Budget: " . $project['budget'] . "\n\n";
    
    // Get construction stages
    echo "=== CONSTRUCTION STAGES ===\n\n";
    $stmt = $db->query("SELECT * FROM construction_stages WHERE project_id = 37 ORDER BY stage_order");
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stages)) {
        echo "No stages found. Need to create construction stages.\n\n";
    } else {
        foreach ($stages as $stage) {
            echo "Stage: " . $stage['stage_name'] . "\n";
            echo "  Order: " . $stage['stage_order'] . "\n";
            echo "  Status: " . $stage['status'] . "\n";
            echo "  Progress: " . $stage['progress_percentage'] . "%\n";
            echo "  Start: " . $stage['start_date'] . "\n";
            echo "  End: " . $stage['end_date'] . "\n\n";
        }
    }
    
    // Get daily progress reports
    echo "=== DAILY PROGRESS REPORTS ===\n\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_progress WHERE project_id = 37");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Daily Reports: " . $count['count'] . "\n\n";
    
    // Get stage payments
    echo "=== STAGE PAYMENTS ===\n\n";
    $stmt = $db->query("SELECT * FROM stage_payments WHERE project_id = 37 ORDER BY stage_order");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "No stage payments found.\n\n";
    } else {
        foreach ($payments as $payment) {
            echo "Stage: " . $payment['stage_name'] . "\n";
            echo "  Amount: " . $payment['amount'] . "\n";
            echo "  Status: " . $payment['payment_status'] . "\n";
            echo "  Paid: " . ($payment['is_paid'] ? 'Yes' : 'No') . "\n\n";
        }
    }
    
    // Get inspection reports
    echo "=== INSPECTION REPORTS ===\n\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM inspection_reports WHERE project_id = 37");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Inspection Reports: " . $count['count'] . "\n\n";
    
    // Get contractor documents
    echo "=== CONTRACTOR DOCUMENTS ===\n\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM contractor_stage_documents WHERE project_id = 37");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Documents: " . $count['count'] . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

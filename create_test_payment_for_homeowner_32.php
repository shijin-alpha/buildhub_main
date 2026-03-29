<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Creating test payment request for homeowner ID 32...\n";
    
    // First, let's check if homeowner 32 has any projects
    $project_query = "SELECT id, project_name, contractor_id FROM construction_projects WHERE homeowner_id = 32 LIMIT 1";
    $project_stmt = $pdo->prepare($project_query);
    $project_stmt->execute();
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "No construction projects found for homeowner 32. Creating one...\n";
        
        // Create a construction project
        $create_project_query = "INSERT INTO construction_projects (
            homeowner_id, contractor_id, project_name, project_description, 
            status, total_cost, timeline, created_at, updated_at
        ) VALUES (
            32, 32, 'Test Project for Payment Receipt', 'Test project for payment receipt upload functionality',
            'in_progress', 500000.00, '6 months', NOW(), NOW()
        )";
        
        $pdo->exec($create_project_query);
        $project_id = $pdo->lastInsertId();
        echo "Created construction project with ID: $project_id\n";
        
        $project = [
            'id' => $project_id,
            'contractor_id' => 32
        ];
    } else {
        echo "Found existing project: ID {$project['id']}, Name: {$project['project_name']}\n";
    }
    
    // Create a stage payment request
    $payment_query = "INSERT INTO stage_payment_requests (
        project_id, homeowner_id, contractor_id, stage_name, requested_amount,
        work_description, status, payment_method, created_at, updated_at
    ) VALUES (
        ?, 32, ?, 'Foundation Work', 376161.00,
        'Payment for foundation work completion', 'approved', 'bank_transfer', NOW(), NOW()
    )";
    
    $payment_stmt = $pdo->prepare($payment_query);
    $payment_stmt->execute([$project['id'], $project['contractor_id']]);
    $payment_id = $pdo->lastInsertId();
    
    echo "Created stage payment request with ID: $payment_id\n";
    echo "Amount: ₹3,76,161\n";
    echo "Status: approved\n";
    echo "Payment Method: bank_transfer\n";
    
    // Verify the payment was created
    $verify_query = "SELECT * FROM stage_payment_requests WHERE id = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute([$payment_id]);
    $payment = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "\nPayment verification successful:\n";
        echo "ID: {$payment['id']}\n";
        echo "Homeowner ID: {$payment['homeowner_id']}\n";
        echo "Contractor ID: {$payment['contractor_id']}\n";
        echo "Amount: ₹{$payment['requested_amount']}\n";
        echo "Status: {$payment['status']}\n";
        echo "\nYou can now use Payment ID #{$payment_id} to test receipt upload.\n";
    } else {
        echo "Error: Payment verification failed\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
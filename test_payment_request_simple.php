<?php
// Test the payment request submission directly
session_start();

// Set up contractor session
$_SESSION['user_id'] = 29; // Contractor ID from the database
$_SESSION['user_type'] = 'contractor';

echo "Testing Stage Payment Request Submission\n";
echo "========================================\n";
echo "Contractor ID: " . $_SESSION['user_id'] . "\n\n";

// Include the database connection
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test data for payment request
    $input = [
        'project_id' => 1, // Using construction project ID 1
        'homeowner_id' => 28, // Homeowner ID from the database
        'stage_name' => 'Structure', // Different stage from the paid Foundation
        'requested_amount' => 75000,
        'work_description' => 'Structure work completed including column construction, beam work, and slab casting. All structural framework is in place according to approved plans and engineering specifications.',
        'completion_percentage' => 25,
        'labor_count' => 8,
        'total_project_cost' => 500000,
        'quality_check' => true,
        'safety_compliance' => true,
        'materials_used' => 'Steel rebar, concrete, cement, formwork materials',
        'contractor_notes' => 'Structure stage completed as per schedule with quality checks verified'
    ];
    
    $contractor_id = $_SESSION['user_id'];
    
    echo "Test Data:\n";
    echo json_encode($input, JSON_PRETTY_PRINT) . "\n\n";
    
    // Step 1: Check project access
    echo "Step 1: Checking project access...\n";
    
    $projectCheckQuery = "
        SELECT 
            'construction_project' as source, cp.homeowner_id, cp.total_cost
        FROM construction_projects cp
        WHERE cp.id = ? 
        AND cp.contractor_id = ? 
        AND cp.status IN ('created', 'in_progress')
        
        UNION ALL
        
        SELECT 
            'contractor_estimate' as source, ce.homeowner_id, ce.total_cost
        FROM contractor_estimates ce
        WHERE ce.id = ? 
        AND ce.contractor_id = ? 
        AND ce.status = 'accepted'
        
        UNION ALL
        
        SELECT 
            'contractor_send_estimate' as source, cls.homeowner_id, cse.total_cost
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        WHERE cse.id = ? 
        AND cse.contractor_id = ? 
        AND cse.status IN ('accepted', 'project_created')
        
        LIMIT 1
    ";
    
    $projectCheckStmt = $db->prepare($projectCheckQuery);
    $projectCheckStmt->execute([
        $input['project_id'], $contractor_id,  // construction_projects
        $input['project_id'], $contractor_id,  // contractor_estimates  
        $input['project_id'], $contractor_id   // contractor_send_estimates
    ]);
    
    $projectCheck = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projectCheck) {
        echo "❌ Project access denied!\n";
        echo "Project ID: " . $input['project_id'] . "\n";
        echo "Contractor ID: " . $contractor_id . "\n";
        exit;
    }
    
    echo "✅ Project access granted!\n";
    echo "Source: " . $projectCheck['source'] . "\n";
    echo "Homeowner ID: " . $projectCheck['homeowner_id'] . "\n\n";
    
    // Step 2: Check for existing requests
    echo "Step 2: Checking for existing payment requests...\n";
    
    $existingCheckQuery = "
        SELECT 'stage_payment_requests' as source, status
        FROM stage_payment_requests 
        WHERE project_id = ? 
        AND contractor_id = ? 
        AND stage_name = ? 
        AND status IN ('pending', 'approved', 'paid')
        
        LIMIT 1
    ";
    
    $existingCheckStmt = $db->prepare($existingCheckQuery);
    $existingCheckStmt->execute([
        $input['project_id'], $contractor_id, $input['stage_name']  // stage_payment_requests only
    ]);
    
    $existingCheck = $existingCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCheck) {
        echo "❌ Existing payment request found!\n";
        echo "Source: " . $existingCheck['source'] . "\n";
        echo "Status: " . $existingCheck['status'] . "\n";
        exit;
    }
    
    echo "✅ No existing payment requests for this stage\n\n";
    
    // Step 3: Insert payment request
    echo "Step 3: Inserting payment request...\n";
    
    $insertQuery = "
        INSERT INTO stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name, 
            requested_amount, completion_percentage, work_description,
            materials_used, labor_count, work_start_date, work_end_date,
            contractor_notes, quality_check, safety_compliance,
            total_project_cost, status, request_date, created_at, updated_at
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name,
            :requested_amount, :completion_percentage, :work_description,
            :materials_used, :labor_count, :work_start_date, :work_end_date,
            :contractor_notes, :quality_check, :safety_compliance,
            :total_project_cost, 'pending', NOW(), NOW(), NOW()
        )
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    $result = $insertStmt->execute([
        ':project_id' => $input['project_id'],
        ':contractor_id' => $contractor_id,
        ':homeowner_id' => $input['homeowner_id'],
        ':stage_name' => $input['stage_name'],
        ':requested_amount' => $input['requested_amount'],
        ':completion_percentage' => $input['completion_percentage'],
        ':work_description' => $input['work_description'],
        ':materials_used' => $input['materials_used'] ?? '',
        ':labor_count' => $input['labor_count'],
        ':work_start_date' => $input['work_start_date'] ?? null,
        ':work_end_date' => $input['work_end_date'] ?? null,
        ':contractor_notes' => $input['contractor_notes'] ?? '',
        ':quality_check' => $input['quality_check'] ? 1 : 0,
        ':safety_compliance' => $input['safety_compliance'] ? 1 : 0,
        ':total_project_cost' => $input['total_project_cost']
    ]);
    
    if ($result) {
        $payment_request_id = $db->lastInsertId();
        echo "✅ Payment request submitted successfully!\n";
        echo "Payment Request ID: " . $payment_request_id . "\n";
        echo "Stage: " . $input['stage_name'] . "\n";
        echo "Amount: ₹" . number_format($input['requested_amount']) . "\n";
        echo "Status: pending\n";
    } else {
        $errorInfo = $insertStmt->errorInfo();
        echo "❌ Failed to insert payment request\n";
        echo "SQL Error: " . ($errorInfo[2] ?? 'Unknown error') . "\n";
        echo "Error Code: " . ($errorInfo[1] ?? 'Unknown') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
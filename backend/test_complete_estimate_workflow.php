<?php
// Test the complete estimate acceptance workflow
require_once 'config/database.php';

echo "🔄 Testing Complete Estimate Acceptance Workflow\n";
echo "================================================\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connected successfully\n\n";
    
    // Test data
    $testContractorId = 1;
    $testHomeownerId = 2;
    
    echo "📊 Step 1: Create Test Estimate\n";
    echo "-------------------------------\n";
    
    // Create a test estimate in contractor_estimates table
    $testEstimate = [
        'contractor_id' => $testContractorId,
        'homeowner_id' => $testHomeownerId,
        'send_id' => 1,
        'project_name' => 'Complete Workflow Test Project',
        'location' => '123 Workflow Street, Test City',
        'client_name' => 'Test Homeowner',
        'timeline' => '120 days',
        'notes' => 'Test estimate for complete workflow verification',
        'status' => 'submitted'
    ];
    
    $totalsData = [
        'materials' => 80000,
        'labor' => 70000,
        'utilities' => 30000,
        'misc' => 20000,
        'grand' => 200000
    ];
    
    $insertStmt = $db->prepare("
        INSERT INTO contractor_estimates (
            contractor_id, homeowner_id, send_id, project_name, location, 
            client_name, timeline, notes, status, totals_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $insertStmt->execute([
        $testEstimate['contractor_id'],
        $testEstimate['homeowner_id'],
        $testEstimate['send_id'],
        $testEstimate['project_name'],
        $testEstimate['location'],
        $testEstimate['client_name'],
        $testEstimate['timeline'],
        $testEstimate['notes'],
        $testEstimate['status'],
        json_encode($totalsData)
    ]);
    
    $estimateId = $db->lastInsertId();
    echo "✅ Test estimate created with ID: {$estimateId}\n";
    echo "   - Project: {$testEstimate['project_name']}\n";
    echo "   - Total Cost: ₹" . number_format($totalsData['grand'], 0) . "\n";
    echo "   - Timeline: {$testEstimate['timeline']}\n\n";
    
    echo "📧 Step 2: Simulate Homeowner Accepting Estimate\n";
    echo "------------------------------------------------\n";
    
    // Simulate the homeowner acceptance API call
    $acceptanceData = [
        'homeowner_id' => $testHomeownerId,
        'estimate_id' => $estimateId,
        'action' => 'accept',
        'message' => 'I accept this estimate. Please proceed with the construction.'
    ];
    
    // Simulate the respond_to_estimate.php logic
    echo "🔍 Checking estimate details...\n";
    
    $q = $db->prepare("
        SELECT e.id, e.contractor_id, e.homeowner_id, e.total_cost, e.timeline, e.structured_data as structured,
               e.project_name, e.location, e.client_name, e.notes,
               c.first_name as contractor_first_name, c.last_name as contractor_last_name, c.email as contractor_email,
               h.first_name as homeowner_first_name, h.last_name as homeowner_last_name, h.email as homeowner_email,

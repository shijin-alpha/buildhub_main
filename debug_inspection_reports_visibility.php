<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== DEBUGGING INSPECTION REPORTS VISIBILITY ===\n\n";
    
    // 1. Check inspection reports table structure and data
    echo "1. INSPECTION REPORTS TABLE STRUCTURE:\n";
    $result = $db->query('DESCRIBE inspection_reports');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']} - {$row['Type']}\n";
    }
    
    echo "\n2. INSPECTION REPORTS DATA:\n";
    $result = $db->query('SELECT * FROM inspection_reports ORDER BY created_at DESC');
    $reports = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reports)) {
        echo "   ❌ NO INSPECTION REPORTS FOUND\n";
    } else {
        echo "   ✅ Found " . count($reports) . " inspection reports:\n";
        foreach ($reports as $report) {
            echo "   - ID: {$report['id']}, Project: {$report['project_id']}, Inspector: {$report['inspector_id']}, Date: {$report['inspection_date']}, Status: {$report['overall_status']}\n";
        }
    }
    
    // 2. Check projects table for homeowner mapping
    echo "\n3. PROJECTS AND HOMEOWNER MAPPING:\n";
    $result = $db->query('SELECT id, project_name, homeowner_id, homeowner_name FROM construction_projects ORDER BY id');
    $projects = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "   Project {$project['id']}: {$project['project_name']} - Homeowner ID: {$project['homeowner_id']} ({$project['homeowner_name']})\n";
    }
    
    // 3. Check users table for homeowner details
    echo "\n4. HOMEOWNER USERS:\n";
    $result = $db->query("SELECT id, first_name, last_name, email, role FROM users WHERE role = 'homeowner' ORDER BY id");
    $homeowners = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($homeowners as $homeowner) {
        echo "   Homeowner {$homeowner['id']}: {$homeowner['first_name']} {$homeowner['last_name']} ({$homeowner['email']})\n";
    }
    
    // 4. Test homeowner API directly
    echo "\n5. TESTING HOMEOWNER API:\n";
    
    // Simulate homeowner session for user ID 28 (from previous data)
    session_start();
    $_SESSION['user_id'] = 28;
    $_SESSION['role'] = 'homeowner';
    
    echo "   Simulating homeowner session for user ID: 28\n";
    
    // Test the API query manually
    $homeowner_id = 28;
    $testQuery = "
        SELECT 
            ir.id,
            ir.project_id,
            ir.inspector_id,
            ir.inspection_date,
            ir.inspection_stage,
            ir.inspection_type,
            ir.overall_status,
            ir.quality_score,
            ir.safety_compliance,
            ir.notes,
            ir.recommendations,
            ir.issues_identified,
            ir.corrective_actions_required,
            ir.created_at,
            -- Project details
            cp.project_name,
            cp.project_location,
            cp.status as project_status,
            cp.current_stage as project_current_stage,
            cp.completion_percentage as project_completion,
            -- Inspector details
            CONCAT(inspector.first_name, ' ', inspector.last_name) as inspector_name,
            inspector.email as inspector_email,
            inspector.phone as inspector_phone
        FROM inspection_reports ir
        JOIN construction_projects cp ON ir.project_id = cp.id
        JOIN users inspector ON ir.inspector_id = inspector.id
        WHERE cp.homeowner_id = ?
        ORDER BY ir.inspection_date DESC, ir.created_at DESC
    ";
    
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute([$homeowner_id]);
    $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testResults)) {
        echo "   ❌ NO REPORTS FOUND FOR HOMEOWNER ID 28\n";
        
        // Check if there are any reports for other homeowners
        $allReportsQuery = "
            SELECT ir.id, ir.project_id, cp.homeowner_id, cp.project_name 
            FROM inspection_reports ir 
            JOIN construction_projects cp ON ir.project_id = cp.id
        ";
        $allReportsStmt = $db->prepare($allReportsQuery);
        $allReportsStmt->execute();
        $allReports = $allReportsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   All reports in system:\n";
        foreach ($allReports as $report) {
            echo "     Report {$report['id']} - Project {$report['project_id']} ({$report['project_name']}) - Homeowner {$report['homeowner_id']}\n";
        }
    } else {
        echo "   ✅ Found " . count($testResults) . " reports for homeowner ID 28:\n";
        foreach ($testResults as $report) {
            echo "     Report {$report['id']}: {$report['project_name']} - {$report['inspection_type']} on {$report['inspection_date']}\n";
        }
    }
    
    // 5. Test the actual API endpoint
    echo "\n6. TESTING ACTUAL API ENDPOINT:\n";
    
    // Capture the API response
    ob_start();
    include 'backend/api/homeowner/get_inspection_reports.php';
    $apiResponse = ob_get_clean();
    
    echo "   API Response:\n";
    echo "   " . str_replace("\n", "\n   ", $apiResponse) . "\n";
    
    $apiData = json_decode($apiResponse, true);
    if ($apiData && $apiData['success']) {
        echo "   ✅ API Success - Found " . count($apiData['reports']) . " reports\n";
        echo "   Statistics: " . json_encode($apiData['statistics']) . "\n";
    } else {
        echo "   ❌ API Failed: " . ($apiData['message'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n=== DEBUGGING COMPLETE ===\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== CREATING MINIMAL INSPECTION FOR USER 32 ===\n\n";
    
    // Check if user 32 already has a project
    $stmt = $db->prepare('SELECT id, project_name FROM construction_projects WHERE homeowner_id = 32');
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "❌ No project found for user 32. Creating one first...\n";
        
        // Use existing estimate
        $stmt = $db->query('SELECT id FROM contractor_send_estimates LIMIT 1');
        $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
        $estimateId = $estimate['id'];
        
        // Create project
        $stmt = $db->prepare('
            INSERT INTO construction_projects (
                estimate_id, contractor_id, homeowner_id, project_name, homeowner_name,
                status, current_stage, completion_percentage, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        
        $stmt->execute([
            $estimateId, 19, 32, 'Amal Samuel Construction Project', 'Amal Samuel',
            'in_progress', 'Foundation', 20
        ]);
        
        $projectId = $db->lastInsertId();
        echo "✅ Created project {$projectId}\n";
    } else {
        $projectId = $project['id'];
        echo "✅ Using existing project {$projectId}: {$project['project_name']}\n";
    }
    
    // Create minimal inspection report
    $stmt = $db->prepare('
        INSERT INTO inspection_reports (
            project_id, inspector_id, inspection_date, inspection_stage, 
            inspection_type, overall_status, quality_score, safety_compliance,
            notes, recommendations, issues_identified, corrective_actions_required,
            weather_conditions, site_accessibility, safety_equipment_available,
            safety_violations_found, structural_integrity, workmanship_quality,
            code_compliance, environmental_impact, waste_management, site_cleanliness,
            follow_up_required, contractor_present, homeowner_notified,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([
        $projectId, // project_id
        1001, // inspector_id
        '2026-01-31', // inspection_date
        'Foundation', // inspection_stage
        'routine', // inspection_type
        'approved', // overall_status
        8.7, // quality_score
        'compliant', // safety_compliance
        'Foundation work is excellent. Quality standards maintained throughout.', // notes
        'Continue current practices. Monitor concrete curing.', // recommendations
        'Minor drainage issue at north corner.', // issues_identified
        'Install drainage pipes before next phase.', // corrective_actions_required
        'clear', // weather_conditions
        'good', // site_accessibility
        'yes', // safety_equipment_available
        'no', // safety_violations_found
        'excellent', // structural_integrity
        'excellent', // workmanship_quality
        'compliant', // code_compliance
        'minimal', // environmental_impact
        'excellent', // waste_management
        'excellent', // site_cleanliness
        'no', // follow_up_required
        'yes', // contractor_present
        'yes' // homeowner_notified
    ]);
    
    $reportId = $db->lastInsertId();
    echo "✅ Created inspection report {$reportId}\n\n";
    
    // Create some checklist items
    $checklistItems = [
        ['Foundation Quality', 'pass', 'critical', 'Foundation meets all specifications'],
        ['Safety Compliance', 'pass', 'critical', 'All safety protocols followed'],
        ['Material Quality', 'pass', 'normal', 'Materials are of good quality'],
        ['Drainage System', 'fail', 'normal', 'Minor drainage issue identified'],
        ['Site Cleanliness', 'pass', 'normal', 'Site is well maintained']
    ];
    
    foreach ($checklistItems as $item) {
        $stmt = $db->prepare('
            INSERT INTO inspection_checklist_items (
                inspection_report_id, item_name, status, priority, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$reportId, $item[0], $item[1], $item[2], $item[3]]);
    }
    
    echo "✅ Created " . count($checklistItems) . " checklist items\n\n";
    
    // Test the API
    echo "=== TESTING API FOR USER 32 ===\n";
    session_start();
    $_SESSION['user_id'] = 32;
    $_SESSION['role'] = 'homeowner';
    
    ob_start();
    include 'backend/api/homeowner/get_inspection_reports.php';
    $apiResponse = ob_get_clean();
    
    // Extract JSON
    $lines = explode("\n", $apiResponse);
    $jsonLine = '';
    foreach ($lines as $line) {
        if (strpos($line, '{"success"') === 0) {
            $jsonLine = $line;
            break;
        }
    }
    
    if ($jsonLine) {
        $data = json_decode($jsonLine, true);
        echo "✅ API Test Result:\n";
        echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "  Reports: " . count($data['reports']) . "\n";
        echo "  Total Reports: " . $data['statistics']['total_reports'] . "\n";
        echo "  Approved: " . $data['statistics']['approved_count'] . "\n";
        
        if (!empty($data['reports'])) {
            $report = $data['reports'][0];
            echo "  First Report: {$report['project']['name']} - {$report['inspection']['status']}\n";
        }
    } else {
        echo "❌ API test failed\n";
    }
    
    echo "\n🎉 SUCCESS! User 32 now has inspection reports!\n";
    echo "Refresh the frontend to see the data.\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}
?>
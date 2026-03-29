<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== CREATING SIMPLE PROJECT FOR USER 32 ===\n\n";
    
    // Get user 32 details
    $stmt = $db->prepare('SELECT * FROM users WHERE id = 32');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User 32 not found\n";
        exit;
    }
    
    echo "✅ User 32: {$user['first_name']} {$user['last_name']} ({$user['email']})\n\n";
    
    // Use an existing estimate or create a minimal one
    $stmt = $db->query('SELECT id FROM contractor_send_estimates LIMIT 1');
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estimate) {
        // Create a minimal estimate
        $stmt = $db->prepare('
            INSERT INTO contractor_send_estimates (
                send_id, contractor_id, materials, cost_breakdown, 
                total_cost, timeline, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            1, // send_id
            19, // contractor_id
            'Cement, Steel, Bricks', // materials
            '{"foundation": 150000, "structure": 200000}', // cost_breakdown
            350000, // total_cost
            '6 months', // timeline
            'approved' // status
        ]);
        
        $estimateId = $db->lastInsertId();
        echo "✅ Created estimate {$estimateId}\n";
    } else {
        $estimateId = $estimate['id'];
        echo "✅ Using existing estimate {$estimateId}\n";
    }
    
    // Now create the project
    $projectName = $user['first_name'] . ' ' . $user['last_name'] . ' Construction Project';
    $stmt = $db->prepare('
        INSERT INTO construction_projects (
            estimate_id, contractor_id, homeowner_id, project_name, homeowner_name,
            project_location, status, current_stage, completion_percentage, 
            total_cost, timeline, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([
        $estimateId, // estimate_id
        19, // contractor_id
        32, // homeowner_id
        $projectName, // project_name
        $user['first_name'] . ' ' . $user['last_name'], // homeowner_name
        'Test Location, City', // project_location
        'in_progress', // status
        'Foundation', // current_stage
        15, // completion_percentage
        350000, // total_cost
        '6 months' // timeline
    ]);
    
    $projectId = $db->lastInsertId();
    echo "✅ Created project {$projectId}: {$projectName}\n\n";
    
    // Create an inspection report
    $stmt = $db->prepare('
        INSERT INTO inspection_reports (
            project_id, inspector_id, inspection_date, inspection_time,
            inspection_stage, inspection_type, overall_status, quality_score,
            safety_compliance, notes, recommendations, issues_identified,
            corrective_actions_required, next_inspection_date, weather_conditions,
            temperature, site_accessibility, work_progress_since_last,
            safety_equipment_available, safety_violations_found, structural_integrity,
            workmanship_quality, code_compliance, environmental_impact,
            waste_management, site_cleanliness, follow_up_required,
            contractor_present, homeowner_notified, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([
        $projectId, // project_id
        1001, // inspector_id
        '2026-01-31', // inspection_date
        '15:00:00', // inspection_time
        'Foundation', // inspection_stage
        'routine', // inspection_type
        'approved', // overall_status
        8.7, // quality_score
        'compliant', // safety_compliance
        'Excellent foundation work. Quality standards are being maintained throughout the construction process.', // notes
        'Continue with current practices. Monitor concrete curing process closely.', // recommendations
        'Minor drainage issue near the north corner of the foundation.', // issues_identified
        'Install additional drainage pipes before next phase.', // corrective_actions_required
        '2026-02-10', // next_inspection_date
        'clear', // weather_conditions
        26.0, // temperature
        'good', // site_accessibility
        'Foundation work 70% complete. All structural elements are in place according to specifications.', // work_progress_since_last
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
    
    // Create checklist items
    $checklistItems = [
        ['Foundation Layout', 'pass', 'critical', 'Foundation layout matches approved drawings'],
        ['Concrete Quality', 'pass', 'critical', 'Concrete strength and mix quality approved'],
        ['Reinforcement', 'pass', 'normal', 'Steel reinforcement properly placed'],
        ['Safety Protocols', 'pass', 'critical', 'All safety measures in place'],
        ['Drainage', 'fail', 'normal', 'Minor drainage issue identified'],
        ['Site Organization', 'pass', 'normal', 'Site is well organized and clean'],
        ['Material Quality', 'pass', 'normal', 'All materials meet specifications'],
        ['Work Progress', 'pass', 'normal', 'Work is progressing as per schedule']
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
    
    // Verify
    echo "=== VERIFICATION ===\n";
    $stmt = $db->prepare('
        SELECT ir.id, ir.overall_status, ir.inspection_date, ir.quality_score,
               cp.project_name, cp.homeowner_name 
        FROM inspection_reports ir 
        JOIN construction_projects cp ON ir.project_id = cp.id 
        WHERE cp.homeowner_id = 32
    ');
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reports as $report) {
        echo "✅ Report {$report['id']}: {$report['project_name']}\n";
        echo "   Status: {$report['overall_status']}, Score: {$report['quality_score']}, Date: {$report['inspection_date']}\n";
    }
    
    echo "\n🎉 SUCCESS! User 32 now has a project with inspection report.\n";
    echo "Refresh the inspection reports tab to see the data!\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}
?>
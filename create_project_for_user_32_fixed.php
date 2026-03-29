<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== CREATING PROJECT AND INSPECTION FOR USER 32 ===\n\n";
    
    // First, get user 32 details
    $stmt = $db->prepare('SELECT * FROM users WHERE id = 32');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User 32 not found\n";
        exit;
    }
    
    echo "✅ User 32: {$user['first_name']} {$user['last_name']} ({$user['email']})\n\n";
    
    // Check the table structure first
    echo "Checking construction_projects table structure...\n";
    $stmt = $db->query('DESCRIBE construction_projects');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    echo "Columns: " . implode(', ', $columnNames) . "\n\n";
    
    // Create a minimal project for user 32 (only required fields)
    $projectName = $user['first_name'] . ' ' . $user['last_name'] . ' Construction Project';
    
    // Build INSERT query dynamically based on available columns
    $insertColumns = ['homeowner_id', 'project_name'];
    $insertValues = [32, $projectName];
    $placeholders = ['?', '?'];
    
    // Add optional columns if they exist
    if (in_array('homeowner_name', $columnNames)) {
        $insertColumns[] = 'homeowner_name';
        $insertValues[] = $user['first_name'] . ' ' . $user['last_name'];
        $placeholders[] = '?';
    }
    
    if (in_array('project_location', $columnNames)) {
        $insertColumns[] = 'project_location';
        $insertValues[] = 'Test Location, City';
        $placeholders[] = '?';
    }
    
    if (in_array('status', $columnNames)) {
        $insertColumns[] = 'status';
        $insertValues[] = 'in_progress';
        $placeholders[] = '?';
    }
    
    if (in_array('current_stage', $columnNames)) {
        $insertColumns[] = 'current_stage';
        $insertValues[] = 'Foundation';
        $placeholders[] = '?';
    }
    
    if (in_array('completion_percentage', $columnNames)) {
        $insertColumns[] = 'completion_percentage';
        $insertValues[] = 15;
        $placeholders[] = '?';
    }
    
    if (in_array('created_at', $columnNames)) {
        $insertColumns[] = 'created_at';
        $insertValues[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';
    }
    
    if (in_array('updated_at', $columnNames)) {
        $insertColumns[] = 'updated_at';
        $insertValues[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';
    }
    
    $sql = 'INSERT INTO construction_projects (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    echo "SQL: $sql\n";
    echo "Values: " . implode(', ', $insertValues) . "\n\n";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($insertValues);
    
    $projectId = $db->lastInsertId();
    echo "✅ Created project {$projectId}: {$projectName}\n\n";
    
    // Create an inspection report for this project
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
        1001, // inspector_id (existing inspector)
        '2026-01-31', // inspection_date
        '14:30:00', // inspection_time
        'Foundation', // inspection_stage
        'routine', // inspection_type
        'approved', // overall_status
        8.5, // quality_score
        'compliant', // safety_compliance
        'Foundation work is progressing well. Concrete quality is good and reinforcement is properly placed.', // notes
        'Continue with current quality standards. Ensure proper curing of concrete.', // recommendations
        'Minor issue with water drainage around the foundation area.', // issues_identified
        'Install proper drainage system before next inspection.', // corrective_actions_required
        '2026-02-07', // next_inspection_date
        'clear', // weather_conditions
        25.5, // temperature
        'good', // site_accessibility
        'Foundation excavation completed, reinforcement placed, concrete poured for 60% of foundation.', // work_progress_since_last
        'yes', // safety_equipment_available
        'no', // safety_violations_found
        'satisfactory', // structural_integrity
        'good', // workmanship_quality
        'compliant', // code_compliance
        'minimal', // environmental_impact
        'proper', // waste_management
        'good', // site_cleanliness
        'yes', // follow_up_required
        'yes', // contractor_present
        'yes' // homeowner_notified
    ]);
    
    $reportId = $db->lastInsertId();
    echo "✅ Created inspection report {$reportId} for project {$projectId}\n\n";
    
    // Create some checklist items for the inspection
    $checklistItems = [
        ['Foundation Excavation', 'pass', 'normal', 'Excavation depth and dimensions are correct'],
        ['Reinforcement Placement', 'pass', 'normal', 'Rebar placement follows structural drawings'],
        ['Concrete Quality', 'pass', 'normal', 'Concrete mix and pouring quality is good'],
        ['Safety Measures', 'pass', 'critical', 'All safety protocols are being followed'],
        ['Drainage System', 'fail', 'normal', 'Drainage around foundation needs improvement'],
        ['Site Cleanliness', 'pass', 'normal', 'Site is maintained clean and organized']
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
    
    // Verify the creation
    echo "=== VERIFICATION ===\n";
    $stmt = $db->prepare('
        SELECT ir.*, cp.project_name, cp.homeowner_name 
        FROM inspection_reports ir 
        JOIN construction_projects cp ON ir.project_id = cp.id 
        WHERE cp.homeowner_id = 32
    ');
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reports as $report) {
        echo "✅ Report {$report['id']}: {$report['project_name']} - {$report['overall_status']} - {$report['inspection_date']}\n";
    }
    
    echo "\n🎉 SUCCESS: User 32 now has a project with an inspection report!\n";
    echo "You can now refresh the inspection reports tab to see the data.\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    echo 'Stack trace: ' . $e->getTraceAsString() . "\n";
}
?>
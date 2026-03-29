<?php
/**
 * Create Sample Inspection Reports for Testing
 * This script creates sample inspection reports for the SHIJIN THOMAS project
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the project ID for SHIJIN THOMAS project
    $projectQuery = "SELECT id FROM construction_projects WHERE project_name LIKE '%SHIJIN THOMAS%' LIMIT 1";
    $projectStmt = $db->prepare($projectQuery);
    $projectStmt->execute();
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "Project not found. Please ensure the SHIJIN THOMAS project exists.\n";
        exit;
    }
    
    $projectId = $project['id'];
    echo "Found project ID: $projectId\n";
    
    // Get or create an inspector user
    $inspectorQuery = "SELECT id FROM users WHERE role = 'site_inspector' LIMIT 1";
    $inspectorStmt = $db->prepare($inspectorQuery);
    $inspectorStmt->execute();
    $inspector = $inspectorStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspector) {
        // Create a sample inspector
        $createInspectorQuery = "
            INSERT INTO users (first_name, last_name, email, password, role, status, created_at) 
            VALUES ('John', 'Inspector', 'inspector@buildhub.com', ?, 'site_inspector', 'approved', NOW())
        ";
        $hashedPassword = password_hash('inspector123', PASSWORD_DEFAULT);
        $createInspectorStmt = $db->prepare($createInspectorQuery);
        $createInspectorStmt->execute([$hashedPassword]);
        $inspectorId = $db->lastInsertId();
        echo "Created inspector with ID: $inspectorId\n";
    } else {
        $inspectorId = $inspector['id'];
        echo "Found inspector ID: $inspectorId\n";
    }
    
    // Check if inspection reports already exist for this project
    $existingQuery = "SELECT COUNT(*) as count FROM inspection_reports WHERE project_id = ?";
    $existingStmt = $db->prepare($existingQuery);
    $existingStmt->execute([$projectId]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing['count'] > 0) {
        echo "Inspection reports already exist for this project. Count: " . $existing['count'] . "\n";
        echo "Skipping creation to avoid duplicates.\n";
        
        // Show existing reports
        $showQuery = "
            SELECT ir.*, CONCAT(u.first_name, ' ', u.last_name) as inspector_name 
            FROM inspection_reports ir 
            LEFT JOIN users u ON ir.inspector_id = u.id 
            WHERE ir.project_id = ? 
            ORDER BY ir.inspection_date DESC
        ";
        $showStmt = $db->prepare($showQuery);
        $showStmt->execute([$projectId]);
        $reports = $showStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nExisting Inspection Reports:\n";
        foreach ($reports as $report) {
            echo "- ID: {$report['id']}, Date: {$report['inspection_date']}, Type: {$report['inspection_type']}, Status: {$report['overall_status']}, Inspector: {$report['inspector_name']}\n";
        }
        exit;
    }
    
    // Sample inspection reports data
    $sampleReports = [
        [
            'inspection_date' => '2026-01-25',
            'inspection_stage' => 'Foundation',
            'inspection_type' => 'milestone',
            'overall_status' => 'approved',
            'quality_score' => 8.5,
            'safety_compliance' => 'compliant',
            'notes' => 'Foundation work completed according to specifications. Concrete quality is excellent and curing is progressing well.',
            'recommendations' => 'Continue with the next phase. Ensure proper moisture control during curing period.',
            'issues_identified' => null,
            'corrective_actions_required' => null,
            'next_inspection_date' => '2026-02-05',
            'weather_conditions' => 'Clear, dry',
            'temperature' => 24.5,
            'site_accessibility' => 'good',
            'work_progress_since_last' => 'Foundation excavation and concrete pouring completed',
            'safety_equipment_available' => 'yes',
            'safety_violations_found' => 'no',
            'structural_integrity' => 'excellent',
            'workmanship_quality' => 'high',
            'code_compliance' => 'compliant',
            'follow_up_required' => 'no',
            'contractor_present' => 'yes',
            'contractor_representative' => 'Site Supervisor',
            'homeowner_notified' => 'yes'
        ],
        [
            'inspection_date' => '2026-01-20',
            'inspection_stage' => 'Site Preparation',
            'inspection_type' => 'routine',
            'overall_status' => 'approved',
            'quality_score' => 9.0,
            'safety_compliance' => 'compliant',
            'notes' => 'Site preparation completed successfully. All utilities marked and protected. Excavation boundaries clearly defined.',
            'recommendations' => 'Proceed with foundation work. Maintain current safety standards.',
            'issues_identified' => null,
            'corrective_actions_required' => null,
            'next_inspection_date' => '2026-01-25',
            'weather_conditions' => 'Partly cloudy',
            'temperature' => 22.0,
            'site_accessibility' => 'excellent',
            'work_progress_since_last' => 'Site clearing and leveling completed',
            'safety_equipment_available' => 'yes',
            'safety_violations_found' => 'no',
            'structural_integrity' => 'not_applicable',
            'workmanship_quality' => 'high',
            'code_compliance' => 'compliant',
            'follow_up_required' => 'no',
            'contractor_present' => 'yes',
            'contractor_representative' => 'Project Manager',
            'homeowner_notified' => 'yes'
        ],
        [
            'inspection_date' => '2026-01-15',
            'inspection_stage' => 'Pre-Construction',
            'inspection_type' => 'safety',
            'overall_status' => 'needs_attention',
            'quality_score' => 7.0,
            'safety_compliance' => 'partial',
            'notes' => 'Pre-construction safety inspection revealed some areas needing attention. Overall site setup is good but safety signage needs improvement.',
            'recommendations' => 'Install additional safety signage and ensure all workers have proper safety equipment before starting construction.',
            'issues_identified' => 'Insufficient safety signage around excavation areas. Some workers observed without hard hats.',
            'corrective_actions_required' => 'Install proper safety barriers and signage. Conduct safety briefing for all workers. Ensure 100% compliance with safety equipment requirements.',
            'next_inspection_date' => '2026-01-20',
            'weather_conditions' => 'Light rain',
            'temperature' => 19.5,
            'site_accessibility' => 'fair',
            'work_progress_since_last' => 'Initial site setup and equipment positioning',
            'safety_equipment_available' => 'partial',
            'safety_violations_found' => 'minor',
            'structural_integrity' => 'not_applicable',
            'workmanship_quality' => 'acceptable',
            'code_compliance' => 'partial',
            'follow_up_required' => 'yes',
            'contractor_present' => 'yes',
            'contractor_representative' => 'Safety Officer',
            'homeowner_notified' => 'yes'
        ]
    ];
    
    // Insert sample inspection reports
    $insertQuery = "
        INSERT INTO inspection_reports (
            project_id, inspector_id, inspection_date, inspection_stage, inspection_type,
            overall_status, quality_score, safety_compliance, notes, recommendations,
            issues_identified, corrective_actions_required, next_inspection_date,
            weather_conditions, temperature, site_accessibility, work_progress_since_last,
            safety_equipment_available, safety_violations_found, structural_integrity,
            workmanship_quality, code_compliance, follow_up_required, contractor_present,
            contractor_representative, homeowner_notified, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        )
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    
    foreach ($sampleReports as $report) {
        $insertStmt->execute([
            $projectId,
            $inspectorId,
            $report['inspection_date'],
            $report['inspection_stage'],
            $report['inspection_type'],
            $report['overall_status'],
            $report['quality_score'],
            $report['safety_compliance'],
            $report['notes'],
            $report['recommendations'],
            $report['issues_identified'],
            $report['corrective_actions_required'],
            $report['next_inspection_date'],
            $report['weather_conditions'],
            $report['temperature'],
            $report['site_accessibility'],
            $report['work_progress_since_last'],
            $report['safety_equipment_available'],
            $report['safety_violations_found'],
            $report['structural_integrity'],
            $report['workmanship_quality'],
            $report['code_compliance'],
            $report['follow_up_required'],
            $report['contractor_present'],
            $report['contractor_representative'],
            $report['homeowner_notified']
        ]);
        
        echo "Created inspection report: {$report['inspection_date']} - {$report['inspection_type']} - {$report['overall_status']}\n";
    }
    
    echo "\nSample inspection reports created successfully!\n";
    echo "You can now view them in the admin Site Inspection dashboard.\n";
    
    // Show created reports
    $showQuery = "
        SELECT ir.*, CONCAT(u.first_name, ' ', u.last_name) as inspector_name 
        FROM inspection_reports ir 
        LEFT JOIN users u ON ir.inspector_id = u.id 
        WHERE ir.project_id = ? 
        ORDER BY ir.inspection_date DESC
    ";
    $showStmt = $db->prepare($showQuery);
    $showStmt->execute([$projectId]);
    $reports = $showStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCreated Inspection Reports:\n";
    foreach ($reports as $report) {
        echo "- ID: {$report['id']}, Date: {$report['inspection_date']}, Type: {$report['inspection_type']}, Status: {$report['overall_status']}, Inspector: {$report['inspector_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
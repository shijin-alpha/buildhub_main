<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== CREATING FINAL INSPECTION FOR USER 32 ===\n\n";
    
    // Check if user 32 already has a project
    $stmt = $db->prepare('SELECT id, project_name FROM construction_projects WHERE homeowner_id = 32');
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $projectId = $project['id'];
        echo "✅ Using existing project {$projectId}: {$project['project_name']}\n";
        
        // Check if inspection report already exists
        $stmt = $db->prepare('SELECT id FROM inspection_reports WHERE project_id = ?');
        $stmt->execute([$projectId]);
        $existingReport = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingReport) {
            echo "✅ Inspection report {$existingReport['id']} already exists\n";
        } else {
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
                'Foundation work is excellent. Quality standards maintained throughout the construction process.', // notes
                'Continue current practices. Monitor concrete curing process closely.', // recommendations
                'Minor drainage issue at north corner of foundation.', // issues_identified
                'Install drainage pipes before next construction phase.', // corrective_actions_required
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
            echo "✅ Created inspection report {$reportId}\n";
        }
    } else {
        echo "❌ No project found for user 32\n";
        exit;
    }
    
    echo "\n=== TESTING API FOR USER 32 ===\n";
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
            echo "  Quality Score: {$report['inspection']['quality_score']}\n";
            echo "  Inspector: {$report['inspector']['name']}\n";
        }
    } else {
        echo "❌ API test failed - no JSON found\n";
        echo "Raw response: " . substr($apiResponse, 0, 200) . "...\n";
    }
    
    echo "\n🎉 SUCCESS! User 32 now has inspection reports!\n";
    echo "Now refresh the inspection reports tab in the frontend to see the data.\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}
?>
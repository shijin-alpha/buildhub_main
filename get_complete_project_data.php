<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $projectId = 1; // From the previous output, this is the SHIJIN THOMAS project
    
    echo "=== COMPLETE PROJECT DATA FOR PROJECT ID: $projectId ===\n\n";
    
    // 1. Construction Projects table
    echo "1. CONSTRUCTION PROJECTS:\n";
    $stmt = $pdo->prepare("SELECT * FROM construction_projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($project) {
        foreach($project as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    echo "\n2. CONTRACTOR SEND ESTIMATES (estimate_id = {$project['estimate_id']}):\n";
    $stmt = $pdo->prepare("SELECT * FROM contractor_send_estimates WHERE id = ?");
    $stmt->execute([$project['estimate_id']]);
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($estimate) {
        foreach($estimate as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    echo "\n3. CONTRACTOR ESTIMATES (contractor_id = {$project['contractor_id']}):\n";
    $stmt = $pdo->prepare("SELECT * FROM contractor_estimates WHERE contractor_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$project['contractor_id']]);
    $contractorEst = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($contractorEst) {
        foreach($contractorEst as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    echo "\n4. USERS TABLE - HOMEOWNER (id = {$project['homeowner_id']}):\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$project['homeowner_id']]);
    $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($homeowner) {
        foreach($homeowner as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    echo "\n5. USERS TABLE - CONTRACTOR (id = {$project['contractor_id']}):\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$project['contractor_id']]);
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($contractor) {
        foreach($contractor as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    echo "\n6. DAILY PROGRESS UPDATES:\n";
    $stmt = $pdo->prepare("SELECT * FROM daily_progress_updates WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $dailyUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Found " . count($dailyUpdates) . " daily updates\n";
    if (!empty($dailyUpdates)) {
        foreach($dailyUpdates as $update) {
            echo "  Update ID {$update['id']}: {$update['construction_stage']} - {$update['incremental_completion_percentage']}% on {$update['update_date']}\n";
        }
    }
    
    echo "\n7. WEEKLY PROGRESS SUMMARIES:\n";
    $stmt = $pdo->prepare("SELECT * FROM weekly_progress_summaries WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $weeklyUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Found " . count($weeklyUpdates) . " weekly summaries\n";
    
    echo "\n8. MONTHLY PROGRESS REPORTS:\n";
    $stmt = $pdo->prepare("SELECT * FROM monthly_progress_reports WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $monthlyReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Found " . count($monthlyReports) . " monthly reports\n";
    
    echo "\n9. PROJECT LOCATIONS:\n";
    $stmt = $pdo->prepare("SELECT * FROM project_locations WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Found " . count($locations) . " location records\n";
    if (!empty($locations)) {
        foreach($locations as $location) {
            echo "  Location: {$location['address']} ({$location['latitude']}, {$location['longitude']})\n";
        }
    }
    
    echo "\n10. CONSTRUCTION PROGRESS UPDATES:\n";
    $stmt = $pdo->prepare("SELECT * FROM construction_progress_updates WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $progressUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Found " . count($progressUpdates) . " construction progress updates\n";
    
    // Parse structured data for better understanding
    if ($project && $project['structured_data']) {
        echo "\n11. PARSED STRUCTURED DATA:\n";
        $structured = json_decode($project['structured_data'], true);
        if ($structured) {
            foreach($structured as $key => $value) {
                if (is_array($value)) {
                    echo "  $key: [complex data]\n";
                } else {
                    echo "  $key: " . (empty($value) ? 'EMPTY' : $value) . "\n";
                }
            }
        }
    }
    
    // Check if there are any related estimates
    echo "\n12. ALL RELATED ESTIMATES:\n";
    $stmt = $pdo->prepare("
        SELECT cse.*, ce.project_name, ce.location, ce.total_cost as ce_total_cost 
        FROM contractor_send_estimates cse 
        LEFT JOIN contractor_estimates ce ON ce.send_id = cse.send_id 
        WHERE cse.contractor_id = ? 
        ORDER BY cse.created_at DESC
    ");
    $stmt->execute([$project['contractor_id']]);
    $allEstimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Found " . count($allEstimates) . " estimates for this contractor\n";
    
    foreach($allEstimates as $est) {
        echo "  Estimate ID {$est['id']}: {$est['project_name']} - ₹{$est['total_cost']} ({$est['status']})\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
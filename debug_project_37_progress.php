<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $project_id = 37; // The project we're investigating
    
    echo "=== DEBUGGING PROJECT 37 PROGRESS ISSUE ===\n\n";
    
    // 1. Check daily_progress_updates for project 37
    echo "1. DAILY PROGRESS UPDATES FOR PROJECT 37:\n";
    $stmt = $pdo->prepare("SELECT * FROM daily_progress_updates WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $daily_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($daily_updates) {
        foreach ($daily_updates as $update) {
            echo "  Update ID: {$update['id']}\n";
            echo "  Date: {$update['update_date']}\n";
            echo "  Stage: {$update['construction_stage']}\n";
            echo "  Incremental: {$update['incremental_completion_percentage']}%\n";
            echo "  Cumulative: {$update['cumulative_completion_percentage']}%\n";
            echo "  Work Done: {$update['work_done_today']}\n";
            echo "  Created: {$update['created_at']}\n\n";
        }
    } else {
        echo "  No daily progress updates found for project 37\n\n";
    }
    
    // 2. Check construction_projects table
    echo "2. CONSTRUCTION PROJECTS TABLE:\n";
    $stmt = $pdo->prepare("SELECT * FROM construction_projects WHERE id = ? OR estimate_id = ?");
    $stmt->execute([$project_id, $project_id]);
    $construction_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($construction_projects) {
        foreach ($construction_projects as $project) {
            echo "  Project ID: {$project['id']}\n";
            echo "  Estimate ID: {$project['estimate_id']}\n";
            echo "  Project Name: {$project['project_name']}\n";
            echo "  Contractor ID: {$project['contractor_id']}\n";
            echo "  Homeowner ID: {$project['homeowner_id']}\n";
            echo "  Status: {$project['status']}\n";
            echo "  Completion %: {$project['completion_percentage']}\n";
            echo "  Current Stage: {$project['current_stage']}\n";
            echo "  Last Update: {$project['last_update_date']}\n\n";
        }
    } else {
        echo "  No construction projects found\n\n";
    }
    
    // 3. Check contractor_projects table (if exists)
    echo "3. CONTRACTOR PROJECTS TABLE:\n";
    try {
        $stmt = $pdo->prepare("SELECT * FROM contractor_projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $contractor_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($contractor_projects) {
            foreach ($contractor_projects as $project) {
                echo "  Found contractor project: {$project['id']}\n";
            }
        } else {
            echo "  No contractor projects found for ID 37\n";
        }
    } catch (Exception $e) {
        echo "  contractor_projects table doesn't exist or error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 4. Check contractor_send_estimates
    echo "4. CONTRACTOR SEND ESTIMATES:\n";
    $stmt = $pdo->prepare("SELECT * FROM contractor_send_estimates WHERE id = ?");
    $stmt->execute([$project_id]);
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estimate) {
        echo "  Estimate ID: {$estimate['id']}\n";
        echo "  Send ID: {$estimate['send_id']}\n";
        echo "  Contractor ID: {$estimate['contractor_id']}\n";
        echo "  Total Cost: {$estimate['total_cost']}\n";
        echo "  Status: {$estimate['status']}\n";
        echo "  Created: {$estimate['created_at']}\n\n";
    } else {
        echo "  No estimate found for ID 37\n\n";
    }
    
    // 5. Test the current API query
    echo "5. TESTING CURRENT API QUERY:\n";
    $query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.homeowner_id,
            cp.contractor_id,
            cp.estimate_id,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            cse.total_cost as project_budget,
            cse.timeline as project_timeline,
            lr.budget_range as original_budget_range
        FROM contractor_projects cp
        LEFT JOIN users h ON cp.homeowner_id = h.id
        LEFT JOIN users c ON cp.contractor_id = c.id
        LEFT JOIN contractor_send_estimates cse ON cp.estimate_id = cse.id
        LEFT JOIN layout_requests lr ON cse.send_id = lr.id
        WHERE cp.id = ?
    ";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$project_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "  API query found project data\n";
        } else {
            echo "  API query returned no results - THIS IS THE PROBLEM!\n";
        }
    } catch (Exception $e) {
        echo "  API query failed: " . $e->getMessage() . "\n";
    }
    
    // 6. Test corrected query using construction_projects
    echo "\n6. TESTING CORRECTED QUERY (using construction_projects):\n";
    $corrected_query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.homeowner_id,
            cp.contractor_id,
            cp.estimate_id,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            cse.total_cost as project_budget,
            cse.timeline as project_timeline,
            lr.budget_range as original_budget_range
        FROM construction_projects cp
        LEFT JOIN users h ON cp.homeowner_id = h.id
        LEFT JOIN users c ON cp.contractor_id = c.id
        LEFT JOIN contractor_send_estimates cse ON cp.estimate_id = cse.id
        LEFT JOIN layout_requests lr ON cse.send_id = lr.id
        WHERE cp.id = ? OR cp.estimate_id = ?
    ";
    
    $stmt = $pdo->prepare($corrected_query);
    $stmt->execute([$project_id, $project_id]);
    $corrected_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($corrected_result) {
        echo "  ✅ Corrected query found project data!\n";
        echo "  Project Name: {$corrected_result['project_name']}\n";
        echo "  Budget: {$corrected_result['project_budget']}\n";
        echo "  Homeowner: {$corrected_result['homeowner_name']}\n";
        echo "  Contractor: {$corrected_result['contractor_name']}\n";
    } else {
        echo "  ❌ Corrected query still no results\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
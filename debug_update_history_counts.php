<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $project_id = 37; // The project we're investigating
    
    echo "=== DEBUGGING UPDATE HISTORY COUNTS FOR PROJECT 37 ===\n\n";
    
    // 1. Check daily_progress_updates
    echo "1. DAILY PROGRESS UPDATES:\n";
    $daily_query = "SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = ?";
    $stmt = $pdo->prepare($daily_query);
    $stmt->execute([$project_id]);
    $daily_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Count: {$daily_count['count']}\n";
    
    // Show actual records
    $daily_records_query = "SELECT id, update_date, construction_stage, cumulative_completion_percentage, created_at FROM daily_progress_updates WHERE project_id = ?";
    $stmt = $pdo->prepare($daily_records_query);
    $stmt->execute([$project_id]);
    $daily_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($daily_records as $record) {
        echo "    ID: {$record['id']}, Date: {$record['update_date']}, Stage: {$record['construction_stage']}, Progress: {$record['cumulative_completion_percentage']}%, Created: {$record['created_at']}\n";
    }
    echo "\n";
    
    // 2. Check weekly_progress_summaries
    echo "2. WEEKLY PROGRESS SUMMARIES:\n";
    $weekly_query = "SELECT COUNT(*) as count FROM weekly_progress_summaries WHERE project_id = ?";
    $stmt = $pdo->prepare($weekly_query);
    $stmt->execute([$project_id]);
    $weekly_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Count: {$weekly_count['count']}\n\n";
    
    // 3. Check monthly_progress_reports
    echo "3. MONTHLY PROGRESS REPORTS:\n";
    $monthly_query = "SELECT COUNT(*) as count FROM monthly_progress_reports WHERE project_id = ?";
    $stmt = $pdo->prepare($monthly_query);
    $stmt->execute([$project_id]);
    $monthly_count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Count: {$monthly_count['count']}\n\n";
    
    // 4. Check what the frontend API is actually querying
    echo "4. TESTING FRONTEND API QUERIES:\n";
    
    // Check if the API is using the correct project ID
    echo "  Testing with project_id = 37:\n";
    echo "    Daily: {$daily_count['count']}\n";
    echo "    Weekly: {$weekly_count['count']}\n";
    echo "    Monthly: {$monthly_count['count']}\n\n";
    
    // Check if the API might be using construction_projects.id instead
    echo "  Testing with construction_projects.id (should be 2):\n";
    $construction_project_query = "SELECT id FROM construction_projects WHERE estimate_id = ?";
    $stmt = $pdo->prepare($construction_project_query);
    $stmt->execute([$project_id]);
    $construction_project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($construction_project) {
        $construction_id = $construction_project['id'];
        echo "    Construction project ID: {$construction_id}\n";
        
        // Test counts with construction project ID
        $stmt = $pdo->prepare($daily_query);
        $stmt->execute([$construction_id]);
        $daily_count_alt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare($weekly_query);
        $stmt->execute([$construction_id]);
        $weekly_count_alt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare($monthly_query);
        $stmt->execute([$construction_id]);
        $monthly_count_alt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "    Daily with construction ID: {$daily_count_alt['count']}\n";
        echo "    Weekly with construction ID: {$weekly_count_alt['count']}\n";
        echo "    Monthly with construction ID: {$monthly_count_alt['count']}\n\n";
    }
    
    // 5. Check the latest update timestamp
    echo "5. LATEST UPDATE TIMESTAMP:\n";
    $latest_query = "
        SELECT 
            MAX(created_at) as latest_daily,
            (SELECT MAX(created_at) FROM weekly_progress_summaries WHERE project_id = ?) as latest_weekly,
            (SELECT MAX(created_at) FROM monthly_progress_reports WHERE project_id = ?) as latest_monthly
        FROM daily_progress_updates 
        WHERE project_id = ?
    ";
    $stmt = $pdo->prepare($latest_query);
    $stmt->execute([$project_id, $project_id, $project_id]);
    $latest_times = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  Latest Daily: " . ($latest_times['latest_daily'] ?? 'None') . "\n";
    echo "  Latest Weekly: " . ($latest_times['latest_weekly'] ?? 'None') . "\n";
    echo "  Latest Monthly: " . ($latest_times['latest_monthly'] ?? 'None') . "\n\n";
    
    // 6. Check if there are any other project IDs that might be used
    echo "6. ALL PROJECT IDs IN DAILY UPDATES:\n";
    $all_projects_query = "SELECT DISTINCT project_id, COUNT(*) as count FROM daily_progress_updates GROUP BY project_id";
    $stmt = $pdo->prepare($all_projects_query);
    $stmt->execute();
    $all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_projects as $project) {
        echo "  Project ID {$project['project_id']}: {$project['count']} updates\n";
    }
    
    echo "\n7. CONCLUSION:\n";
    if ($daily_count['count'] > 0) {
        echo "  ✅ Daily updates exist for project 37\n";
        echo "  ❌ Frontend API is likely using wrong project ID or wrong query\n";
        echo "  🔧 Need to check the contractor projects API and update history logic\n";
    } else {
        echo "  ❌ No daily updates found for project 37\n";
        echo "  🔧 Need to check if project ID mapping is correct\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
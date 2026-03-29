<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING PROJECT-SPECIFIC DATA ===\n\n";
    
    // Get all construction projects
    $stmt = $pdo->query("
        SELECT id, project_name, total_cost, homeowner_id, contractor_id
        FROM construction_projects 
        ORDER BY id
    ");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "PROJECT ID: {$project['id']}\n";
        echo "Name: {$project['project_name']}\n";
        echo "Budget: ₹" . number_format($project['total_cost'], 2) . "\n";
        echo "Homeowner ID: {$project['homeowner_id']}\n";
        echo "Contractor ID: {$project['contractor_id']}\n";
        
        // Check daily progress reports for this project
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM daily_progress_updates 
            WHERE project_id = ?
        ");
        $stmt->execute([$project['id']]);
        $dailyCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Daily Reports: {$dailyCount['count']}\n";
        
        // Check weekly summaries
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM weekly_progress_summary 
            WHERE project_id = ?
        ");
        $stmt->execute([$project['id']]);
        $weeklyCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Weekly Summaries: {$weeklyCount['count']}\n";
        
        // Check monthly reports
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM monthly_progress_reports 
            WHERE project_id = ?
        ");
        $stmt->execute([$project['id']]);
        $monthlyCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Monthly Reports: {$monthlyCount['count']}\n";
        
        echo "---\n\n";
    }
    
    // Also check if there are any progress reports with wrong project IDs
    echo "\n=== CHECKING FOR MISMATCHED PROJECT IDs ===\n\n";
    
    $stmt = $pdo->query("
        SELECT DISTINCT project_id 
        FROM daily_progress_updates 
        ORDER BY project_id
    ");
    $progressProjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Project IDs in daily_progress_updates: " . implode(", ", $progressProjectIds) . "\n";
    
    $stmt = $pdo->query("
        SELECT DISTINCT id 
        FROM construction_projects 
        ORDER BY id
    ");
    $constructionProjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Project IDs in construction_projects: " . implode(", ", $constructionProjectIds) . "\n\n";
    
    // Check if there are orphaned progress reports
    $orphanedIds = array_diff($progressProjectIds, $constructionProjectIds);
    if (!empty($orphanedIds)) {
        echo "⚠️ WARNING: Found progress reports with non-existent project IDs: " . implode(", ", $orphanedIds) . "\n";
    } else {
        echo "✅ All progress reports have valid project IDs\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
// Debug what data each project has
$contractor_id = 29;

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PROJECT DATA COMPARISON ===\n\n";
    
    // Get all projects for this contractor
    $stmt = $pdo->prepare("
        SELECT 
            cp.id,
            cp.project_name,
            cp.total_cost,
            cp.estimate_id,
            cp.homeowner_id
        FROM construction_projects cp
        WHERE cp.contractor_id = ?
        ORDER BY cp.id
    ");
    $stmt->execute([$contractor_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "Project ID: {$project['id']}\n";
        echo "Name: {$project['project_name']}\n";
        echo "Cost: ₹" . number_format($project['total_cost'], 2) . "\n";
        echo "Estimate ID: {$project['estimate_id']}\n";
        echo "Homeowner ID: {$project['homeowner_id']}\n";
        
        // Check daily updates for this project
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = ?");
        $stmt->execute([$project['id']]);
        $daily = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Daily Updates: {$daily['count']}\n";
        
        // Also check with estimate_id
        if ($project['estimate_id']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = ?");
            $stmt->execute([$project['estimate_id']]);
            $daily_est = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Daily Updates (by estimate_id): {$daily_est['count']}\n";
        }
        
        // Check weekly summaries
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM weekly_progress_summary WHERE project_id = ?");
        $stmt->execute([$project['id']]);
        $weekly = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Weekly Summaries: {$weekly['count']}\n";
        
        // Check monthly reports
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM monthly_progress_reports WHERE project_id = ?");
        $stmt->execute([$project['id']]);
        $monthly = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Monthly Reports: {$monthly['count']}\n";
        
        echo "---\n\n";
    }
    
    // Also check send estimates
    echo "=== SEND ESTIMATES ===\n\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            cse.id,
            cse.total_cost,
            cse.structured
        FROM contractor_send_estimates cse
        WHERE cse.contractor_id = ?
        AND cse.status IN ('accepted', 'project_created')
        ORDER BY cse.id
    ");
    $stmt->execute([$contractor_id]);
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estimates as $estimate) {
        $structured = json_decode($estimate['structured'], true);
        $projectName = $structured['project_name'] ?? 'Unknown';
        
        echo "Estimate ID: {$estimate['id']}\n";
        echo "Name: {$projectName}\n";
        echo "Cost: ₹" . number_format($estimate['total_cost'], 2) . "\n";
        
        // Check daily updates for this estimate
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM daily_progress_updates WHERE project_id = ?");
        $stmt->execute([$estimate['id']]);
        $daily = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Daily Updates: {$daily['count']}\n";
        
        // Check weekly summaries
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM weekly_progress_summary WHERE project_id = ?");
        $stmt->execute([$estimate['id']]);
        $weekly = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Weekly Summaries: {$weekly['count']}\n";
        
        // Check monthly reports
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM monthly_progress_reports WHERE project_id = ?");
        $stmt->execute([$estimate['id']]);
        $monthly = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Monthly Reports: {$monthly['count']}\n";
        
        echo "---\n\n";
    }
    
    // Check what project_ids exist in daily_progress_updates
    echo "=== ALL DAILY PROGRESS PROJECT IDs ===\n\n";
    $stmt = $pdo->query("SELECT DISTINCT project_id, COUNT(*) as count FROM daily_progress_updates GROUP BY project_id ORDER BY project_id");
    $progress_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($progress_ids as $pid) {
        echo "Project ID: {$pid['project_id']} - {$pid['count']} updates\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

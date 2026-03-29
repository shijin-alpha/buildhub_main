<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Finding Real Projects with Actual Data\n";
echo "=======================================\n\n";

// Find projects with progress updates
$query = "
    SELECT 
        cp.id,
        cp.project_name,
        cp.estimated_cost,
        cp.total_cost,
        cp.status,
        cp.completion_percentage,
        cp.contractor_id,
        cp.homeowner_id,
        cp.created_at,
        COUNT(DISTINCT cpu.id) as progress_updates,
        COUNT(DISTINCT spr.id) as stage_payments,
        COUNT(DISTINCT cpr.id) as custom_payments,
        COALESCE(SUM(spr.requested_amount), 0) + COALESCE(SUM(cpr.requested_amount), 0) as total_spent
    FROM construction_projects cp
    LEFT JOIN construction_progress_updates cpu ON cp.id = cpu.project_id
    LEFT JOIN stage_payment_requests spr ON cp.id = spr.project_id AND spr.status IN ('paid', 'approved')
    LEFT JOIN custom_payment_requests cpr ON cp.id = cpr.project_id AND cpr.status IN ('paid', 'approved')
    WHERE cp.status IN ('in_progress', 'completed')
    GROUP BY cp.id
    HAVING progress_updates > 0 OR stage_payments > 0 OR custom_payments > 0
    ORDER BY progress_updates DESC, total_spent DESC
    LIMIT 20
";

$result = $conn->query($query);
$projects = $result->fetchAll(PDO::FETCH_ASSOC);

if (empty($projects)) {
    echo "No projects with real data found.\n";
    exit;
}

echo "Found " . count($projects) . " projects with real data:\n\n";

foreach ($projects as $project) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Project ID: {$project['id']}\n";
    echo "Name: {$project['project_name']}\n";
    echo "Status: {$project['status']}\n";
    echo "Progress: {$project['completion_percentage']}%\n";
    echo "Estimated Cost: ₹" . number_format($project['estimated_cost']) . "\n";
    echo "Total Spent: ₹" . number_format($project['total_spent']) . "\n";
    echo "Progress Updates: {$project['progress_updates']}\n";
    echo "Stage Payments: {$project['stage_payments']}\n";
    echo "Custom Payments: {$project['custom_payments']}\n";
    echo "Contractor ID: {$project['contractor_id']}\n";
    echo "Homeowner ID: {$project['homeowner_id']}\n";
    echo "Created: {$project['created_at']}\n";
    
    // Calculate risk indicators
    $cost_overrun_risk = 'Low';
    $time_risk = 'Low';
    
    if ($project['estimated_cost'] > 0) {
        $spend_percentage = ($project['total_spent'] / $project['estimated_cost']) * 100;
        $progress = floatval($project['completion_percentage']);
        
        // Cost risk: if spending more than progress indicates
        if ($progress > 0 && $spend_percentage > $progress + 20) {
            $cost_overrun_risk = 'High';
        } elseif ($progress > 0 && $spend_percentage > $progress + 10) {
            $cost_overrun_risk = 'Medium';
        }
        
        // Time risk: if progress is slow
        $days_elapsed = (strtotime('now') - strtotime($project['created_at'])) / 86400;
        $expected_progress = min(100, ($days_elapsed / 180) * 100); // Assume 180 days project
        
        if ($progress < $expected_progress - 20) {
            $time_risk = 'High';
        } elseif ($progress < $expected_progress - 10) {
            $time_risk = 'Medium';
        }
        
        echo "\n📊 Analysis:\n";
        echo "  Spend vs Progress: " . round($spend_percentage, 1) . "% spent, {$progress}% complete\n";
        echo "  Days Elapsed: " . round($days_elapsed) . " days\n";
        echo "  Expected Progress: " . round($expected_progress, 1) . "%\n";
        echo "  💰 Cost Risk: {$cost_overrun_risk}\n";
        echo "  ⏱️  Time Risk: {$time_risk}\n";
    }
    
    echo "\n";
}

echo "\n✅ These projects have real data and can be used for ML Analytics!\n";
?>

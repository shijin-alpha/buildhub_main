<?php
/**
 * Check Project 37 Status - Identify Missing or Incomplete Stages
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║         PROJECT 37 STATUS CHECK - DETAILED ANALYSIS          ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $projectId = 37;
    
    // Check project details
    $stmt = $db->query("SELECT * FROM construction_projects WHERE id = $projectId");
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        die("❌ Project 37 not found!\n");
    }
    
    echo "📋 PROJECT INFORMATION:\n";
    echo "   Name: {$project['project_name']}\n";
    echo "   Status: {$project['status']}\n";
    echo "   Current Stage: {$project['current_stage']}\n";
    echo "   Completion: {$project['completion_percentage']}%\n";
    echo "   Budget: ₹" . number_format($project['total_cost']) . "\n";
    echo "   Contractor ID: {$project['contractor_id']}\n";
    echo "   Homeowner ID: {$project['homeowner_id']}\n\n";
    
    // Define all required stages
    $requiredStages = [
        'Foundation',
        'Structure',
        'Brickwork',
        'Roofing',
        'Electrical',
        'Plumbing',
        'Finishing',
        'Painting',
        'Final Inspection'
    ];
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "CONSTRUCTION PROGRESS UPDATES (Stage Level)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $db->query("SELECT stage_name, stage_status, completion_percentage, 
                        DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created,
                        DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') as updated
                        FROM construction_progress_updates 
                        WHERE project_id = $projectId 
                        ORDER BY created_at");
    $stageUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $completedStages = [];
    foreach ($stageUpdates as $update) {
        $status = $update['completion_percentage'] == 100 ? '✅' : '⚠️';
        echo "$status {$update['stage_name']}: {$update['completion_percentage']}% ({$update['stage_status']})\n";
        echo "   Created: {$update['created']} | Updated: {$update['updated']}\n";
        $completedStages[] = $update['stage_name'];
    }
    
    // Check for missing stages
    $missingStages = array_diff($requiredStages, $completedStages);
    if (!empty($missingStages)) {
        echo "\n❌ MISSING STAGES:\n";
        foreach ($missingStages as $stage) {
            echo "   - $stage\n";
        }
    } else {
        echo "\n✅ All required stages present\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "DAILY PROGRESS UPDATES (Detailed Reports)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $db->query("SELECT construction_stage, COUNT(*) as count,
                        AVG(cumulative_completion_percentage) as avg_progress,
                        MAX(cumulative_completion_percentage) as max_progress
                        FROM daily_progress_updates 
                        WHERE project_id = $projectId 
                        GROUP BY construction_stage
                        ORDER BY MIN(update_date)");
    $dailyUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dailyUpdates)) {
        echo "❌ No daily progress updates found\n";
    } else {
        foreach ($dailyUpdates as $update) {
            $status = $update['max_progress'] == 100 ? '✅' : '⚠️';
            echo "$status {$update['construction_stage']}: {$update['count']} reports (Max: {$update['max_progress']}%)\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "STAGE PAYMENT REQUESTS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $db->query("SELECT stage_name, requested_amount, status, 
                        DATE_FORMAT(request_date, '%Y-%m-%d') as requested,
                        DATE_FORMAT(payment_date, '%Y-%m-%d') as paid
                        FROM project_stage_payment_requests 
                        WHERE project_id = $projectId 
                        ORDER BY request_date");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "❌ No payment requests found\n";
    } else {
        $totalPaid = 0;
        foreach ($payments as $payment) {
            $status = $payment['status'] == 'paid' ? '✅' : '⚠️';
            echo "$status {$payment['stage_name']}: ₹" . number_format($payment['requested_amount']) . " ({$payment['status']})\n";
            if ($payment['status'] == 'paid') {
                $totalPaid += $payment['requested_amount'];
            }
        }
        echo "\n   💰 Total Paid: ₹" . number_format($totalPaid) . "\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "INSPECTION REPORTS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $db->query("SELECT inspection_stage, overall_status, quality_score,
                        DATE_FORMAT(inspection_date, '%Y-%m-%d') as inspected
                        FROM inspection_reports 
                        WHERE project_id = $projectId 
                        ORDER BY inspection_date");
    $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($inspections)) {
        echo "❌ No inspection reports found\n";
    } else {
        foreach ($inspections as $inspection) {
            $status = $inspection['overall_status'] == 'approved' ? '✅' : '⚠️';
            $stars = str_repeat('⭐', (int)$inspection['quality_score']);
            echo "$status {$inspection['inspection_stage']}: {$inspection['overall_status']} $stars\n";
        }
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "CONTRACTOR DOCUMENTS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $db->query("SELECT stage_name, COUNT(*) as doc_count
                        FROM contractor_stage_documents 
                        WHERE project_id = $projectId 
                        GROUP BY stage_name
                        ORDER BY stage_name");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($documents)) {
        echo "❌ No contractor documents found\n";
    } else {
        foreach ($documents as $doc) {
            echo "✅ {$doc['stage_name']}: {$doc['doc_count']} documents\n";
        }
    }
    
    echo "\n╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                    SUMMARY & RECOMMENDATIONS                 ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $issues = [];
    
    // Check for incomplete stages
    foreach ($stageUpdates as $update) {
        if ($update['completion_percentage'] < 100) {
            $issues[] = "Stage '{$update['stage_name']}' is only {$update['completion_percentage']}% complete";
        }
    }
    
    // Check for missing stages
    if (!empty($missingStages)) {
        foreach ($missingStages as $stage) {
            $issues[] = "Stage '$stage' is completely missing";
        }
    }
    
    // Check for unpaid payments
    foreach ($payments as $payment) {
        if ($payment['status'] != 'paid') {
            $issues[] = "Payment for '{$payment['stage_name']}' is {$payment['status']}";
        }
    }
    
    if (empty($issues)) {
        echo "✅ PROJECT IS FULLY COMPLETE!\n";
        echo "   All stages are at 100%\n";
        echo "   All payments are processed\n";
        echo "   All inspections are approved\n\n";
    } else {
        echo "⚠️ ISSUES FOUND:\n";
        foreach ($issues as $i => $issue) {
            echo "   " . ($i + 1) . ". $issue\n";
        }
        echo "\n🔧 Run complete_project_37_final_fix.php to resolve all issues\n\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

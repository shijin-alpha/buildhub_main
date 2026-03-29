<?php
/**
 * Debug Stage Validation Issue
 * Test the 12.5% submission problem
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Debug: 12.5% Stage Completion Submission Issue</h2>\n";
    
    // Test with a specific project and contractor
    $project_id = 37; // Use existing project
    $contractor_id = 32; // Use existing contractor
    
    echo "<h3>1. Check existing progress for project $project_id</h3>\n";
    
    $progressCheck = $db->prepare("
        SELECT 
            id,
            update_date,
            construction_stage,
            incremental_completion_percentage,
            cumulative_completion_percentage,
            work_done_today
        FROM daily_progress_updates 
        WHERE project_id = :project_id AND contractor_id = :contractor_id
        ORDER BY update_date DESC
        LIMIT 10
    ");
    $progressCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $progressCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $progressCheck->execute();
    $progressUpdates = $progressCheck->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Date</th><th>Stage</th><th>Incremental %</th><th>Cumulative %</th><th>Work Done</th></tr>\n";
    
    $stageProgress = [];
    foreach ($progressUpdates as $update) {
        $stage = $update['construction_stage'];
        if (!isset($stageProgress[$stage])) {
            $stageProgress[$stage] = 0;
        }
        $stageProgress[$stage] += floatval($update['incremental_completion_percentage']);
        
        echo "<tr>";
        echo "<td>{$update['update_date']}</td>";
        echo "<td>{$update['construction_stage']}</td>";
        echo "<td>{$update['incremental_completion_percentage']}%</td>";
        echo "<td>{$update['cumulative_completion_percentage']}%</td>";
        echo "<td>" . substr($update['work_done_today'], 0, 50) . "...</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>2. Stage Progress Summary</h3>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Stage</th><th>Total Progress</th><th>Remaining (out of 12.5%)</th><th>Status</th></tr>\n";
    
    $stages = ['Foundation', 'Structure', 'Brickwork', 'Roofing', 'Electrical', 'Plumbing', 'Finishing', 'Final'];
    
    foreach ($stages as $stage) {
        $currentProgress = isset($stageProgress[$stage]) ? $stageProgress[$stage] : 0;
        $remaining = 12.5 - $currentProgress;
        $status = $currentProgress >= 12.5 ? 'COMPLETED' : ($currentProgress > 0 ? 'IN PROGRESS' : 'NOT STARTED');
        
        echo "<tr>";
        echo "<td>$stage</td>";
        echo "<td>" . number_format($currentProgress, 2) . "%</td>";
        echo "<td>" . number_format($remaining, 2) . "%</td>";
        echo "<td>$status</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>3. Test 12.5% Submission Scenarios</h3>\n";
    
    // Find a stage that's not completed
    $testStage = null;
    $testStageProgress = 0;
    
    foreach ($stages as $stage) {
        $currentProgress = isset($stageProgress[$stage]) ? $stageProgress[$stage] : 0;
        if ($currentProgress < 12.5) {
            $testStage = $stage;
            $testStageProgress = $currentProgress;
            break;
        }
    }
    
    if ($testStage) {
        echo "<p><strong>Testing stage: $testStage (current progress: " . number_format($testStageProgress, 2) . "%)</strong></p>\n";
        
        $scenarios = [
            ['increment' => 12.5, 'description' => 'Full 12.5% completion'],
            ['increment' => 12.5 - $testStageProgress, 'description' => 'Exact remaining amount'],
            ['increment' => 12.5 - $testStageProgress + 0.1, 'description' => 'Slightly over limit'],
            ['increment' => 5.0, 'description' => 'Safe 5% increment']
        ];
        
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Scenario</th><th>Increment %</th><th>New Total</th><th>Validation Result</th></tr>\n";
        
        foreach ($scenarios as $scenario) {
            $increment = $scenario['increment'];
            $newTotal = $testStageProgress + $increment;
            $isValid = $newTotal <= 12.5;
            $validationResult = $isValid ? 'PASS' : 'FAIL - Exceeds 12.5% limit';
            
            echo "<tr>";
            echo "<td>{$scenario['description']}</td>";
            echo "<td>" . number_format($increment, 2) . "%</td>";
            echo "<td>" . number_format($newTotal, 2) . "%</td>";
            echo "<td style='color: " . ($isValid ? 'green' : 'red') . "'>$validationResult</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<h3>4. Precision Issues Analysis</h3>\n";
        
        // Check for precision issues in existing data
        echo "<p><strong>Checking for decimal precision issues:</strong></p>\n";
        
        foreach ($stageProgress as $stage => $progress) {
            $rounded = round($progress, 2);
            $difference = abs($progress - $rounded);
            
            if ($difference > 0.001) {
                echo "<p style='color: orange;'>⚠️ $stage: Raw sum = $progress, Rounded = $rounded (diff: $difference)</p>\n";
            } else {
                echo "<p style='color: green;'>✓ $stage: Progress = " . number_format($progress, 2) . "% (no precision issues)</p>\n";
            }
        }
        
    } else {
        echo "<p style='color: red;'>All stages are completed for this project!</p>\n";
    }
    
    echo "<h3>5. Recommended Fix</h3>\n";
    echo "<div style='background: #f0f8ff; padding: 10px; border: 1px solid #ccc;'>\n";
    echo "<p><strong>The issue is likely caused by:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Frontend validation being too strict (exact 12.5% limit)</li>\n";
    echo "<li>Decimal precision issues from database DECIMAL(6,2) storage</li>\n";
    echo "<li>Cumulative rounding errors from multiple small updates</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Solutions:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Add tolerance to validation (allow up to 12.6% to account for rounding)</li>\n";
    echo "<li>Cap incremental values at stage limit in backend</li>\n";
    echo "<li>Show remaining percentage to user in real-time</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
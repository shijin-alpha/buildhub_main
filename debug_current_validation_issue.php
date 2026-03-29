<?php
/**
 * Debug Current Validation Issue
 * Check what's preventing daily progress submission
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔍 Debug Current Validation Issue</h2>\n";
    
    // Check recent failed submissions or validation issues
    echo "<h3>1. Check Recent Progress Updates</h3>\n";
    
    $recentUpdates = $db->prepare("
        SELECT 
            dpu.id,
            dpu.project_id,
            dpu.contractor_id,
            dpu.update_date,
            dpu.construction_stage,
            dpu.incremental_completion_percentage,
            dpu.cumulative_completion_percentage,
            dpu.work_done_today
        FROM daily_progress_updates dpu
        ORDER BY dpu.created_at DESC
        LIMIT 10
    ");
    $recentUpdates->execute();
    $updates = $recentUpdates->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($updates)) {
        echo "<p style='color: orange;'>⚠️ No recent progress updates found. This might be the issue!</p>\n";
        
        // Check if there are any projects at all
        $projectCheck = $db->prepare("SELECT COUNT(*) as count FROM contractor_estimates");
        $projectCheck->execute();
        $projectCount = $projectCheck->fetchColumn();
        
        echo "<p>Total projects in contractor_estimates: $projectCount</p>\n";
        
        if ($projectCount == 0) {
            echo "<p style='color: red;'>❌ No projects found! You need to create a project first.</p>\n";
        }
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Project</th><th>Contractor</th><th>Date</th><th>Stage</th><th>Increment %</th><th>Cumulative %</th></tr>\n";
        
        foreach ($updates as $update) {
            echo "<tr>";
            echo "<td>{$update['id']}</td>";
            echo "<td>{$update['project_id']}</td>";
            echo "<td>{$update['contractor_id']}</td>";
            echo "<td>{$update['update_date']}</td>";
            echo "<td>{$update['construction_stage']}</td>";
            echo "<td>{$update['incremental_completion_percentage']}%</td>";
            echo "<td>{$update['cumulative_completion_percentage']}%</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>2. Check Available Projects for Contractor</h3>\n";
    
    // Check what projects are available for a contractor
    $contractorId = 32; // Common contractor ID from previous tests
    
    $projectsCheck = $db->prepare("
        SELECT 
            ce.id as project_id,
            ce.homeowner_id,
            ce.contractor_id,
            ce.total_cost,
            ce.created_at
        FROM contractor_estimates ce
        WHERE ce.contractor_id = :contractor_id
        ORDER BY ce.created_at DESC
        LIMIT 5
    ");
    $projectsCheck->bindValue(':contractor_id', $contractorId, PDO::PARAM_INT);
    $projectsCheck->execute();
    $projects = $projectsCheck->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        echo "<p style='color: red;'>❌ No projects found for contractor $contractorId</p>\n";
        
        // Try to find any contractor with projects
        $anyContractorCheck = $db->prepare("
            SELECT DISTINCT contractor_id, COUNT(*) as project_count
            FROM contractor_estimates 
            GROUP BY contractor_id 
            ORDER BY project_count DESC 
            LIMIT 5
        ");
        $anyContractorCheck->execute();
        $contractors = $anyContractorCheck->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($contractors)) {
            echo "<p>Available contractors with projects:</p>\n";
            foreach ($contractors as $contractor) {
                echo "<p>- Contractor {$contractor['contractor_id']}: {$contractor['project_count']} projects</p>\n";
            }
        }
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Project ID</th><th>Homeowner</th><th>Cost</th><th>Created</th></tr>\n";
        
        foreach ($projects as $project) {
            echo "<tr>";
            echo "<td>{$project['project_id']}</td>";
            echo "<td>{$project['homeowner_id']}</td>";
            echo "<td>₹" . number_format($project['total_cost']) . "</td>";
            echo "<td>{$project['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>3. Test Stage Validation Logic</h3>\n";
    
    if (!empty($projects)) {
        $testProject = $projects[0];
        $projectId = $testProject['project_id'];
        
        echo "<p><strong>Testing with Project ID: $projectId</strong></p>\n";
        
        // Get current stage progress for this project
        $stageProgressCheck = $db->prepare("
            SELECT 
                construction_stage,
                SUM(incremental_completion_percentage) as total_stage_progress,
                COUNT(*) as update_count
            FROM daily_progress_updates 
            WHERE project_id = :project_id AND contractor_id = :contractor_id
            GROUP BY construction_stage
            ORDER BY construction_stage
        ");
        $stageProgressCheck->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stageProgressCheck->bindValue(':contractor_id', $contractorId, PDO::PARAM_INT);
        $stageProgressCheck->execute();
        $stageProgress = $stageProgressCheck->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($stageProgress)) {
            echo "<p style='color: green;'>✅ Fresh project - no existing progress. Should allow Foundation stage with 12.5%</p>\n";
            
            echo "<h4>🧪 Validation Test for Fresh Project:</h4>\n";
            echo "<ul>\n";
            echo "<li>Stage: Foundation</li>\n";
            echo "<li>Current Progress: 0%</li>\n";
            echo "<li>Requested Increment: 12.5%</li>\n";
            echo "<li>New Total: 12.5%</li>\n";
            echo "<li>Stage Limit: 12.5%</li>\n";
            echo "<li><strong style='color: green;'>Result: ✅ SHOULD PASS</strong></li>\n";
            echo "</ul>\n";
        } else {
            echo "<p>Current stage progress:</p>\n";
            echo "<table border='1' style='border-collapse: collapse;'>\n";
            echo "<tr><th>Stage</th><th>Total Progress</th><th>Updates</th><th>Remaining</th><th>Status</th></tr>\n";
            
            foreach ($stageProgress as $stage) {
                $remaining = 12.5 - floatval($stage['total_stage_progress']);
                $status = $remaining <= 0 ? '✅ COMPLETED' : '🔄 IN PROGRESS';
                
                echo "<tr>";
                echo "<td>{$stage['construction_stage']}</td>";
                echo "<td>{$stage['total_stage_progress']}%</td>";
                echo "<td>{$stage['update_count']}</td>";
                echo "<td>" . number_format($remaining, 2) . "%</td>";
                echo "<td>$status</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
    
    echo "<h3>4. Common Issues & Solutions</h3>\n";
    echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #ccc; border-radius: 8px;'>\n";
    echo "<h4>🔧 Possible Issues:</h4>\n";
    echo "<ol>\n";
    echo "<li><strong>No Project Selected:</strong> Make sure you've selected a valid project</li>\n";
    echo "<li><strong>Stage Already Complete:</strong> If a stage has 12.5% progress, it's complete</li>\n";
    echo "<li><strong>Wrong Stage Order:</strong> Complete stages in order (Foundation → Structure → etc.)</li>\n";
    echo "<li><strong>Missing Required Fields:</strong> All fields must be filled</li>\n";
    echo "<li><strong>Photo Requirement:</strong> Progress ≥10% requires photos</li>\n";
    echo "</ol>\n";
    
    echo "<h4>✅ Quick Fixes:</h4>\n";
    echo "<ul>\n";
    echo "<li>Try submitting a smaller percentage (e.g., 5% instead of 12.5%)</li>\n";
    echo "<li>Check if you're on the correct stage</li>\n";
    echo "<li>Upload photos for progress ≥10%</li>\n";
    echo "<li>Make sure all required fields are filled</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
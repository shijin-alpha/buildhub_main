<?php
/**
 * Get Correct Daily Progress Values
 */

echo "🔍 Getting Correct Daily Progress Values...\n\n";

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all projects and their latest daily progress
    echo "📊 Real Daily Progress for Each Project:\n";
    echo "=======================================\n";
    
    $projects_query = "SELECT id, project_name, completion_percentage as stored_progress FROM construction_projects ORDER BY id";
    $stmt = $db->prepare($projects_query);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "🏗️ Project {$project['id']}: {$project['project_name']}\n";
        echo "   📊 Stored Progress: {$project['stored_progress']}%\n";
        
        // Get latest daily progress update
        $daily_query = "SELECT 
                          update_date,
                          construction_stage,
                          work_done_today,
                          incremental_completion_percentage,
                          cumulative_completion_percentage,
                          working_hours,
                          weather_condition,
                          created_at
                        FROM daily_progress_updates 
                        WHERE project_id = ? 
                        ORDER BY update_date DESC, created_at DESC 
                        LIMIT 1";
        
        $stmt = $db->prepare($daily_query);
        $stmt->execute([$project['id']]);
        $latest_daily = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($latest_daily) {
            echo "   📅 Latest Daily Progress: {$latest_daily['cumulative_completion_percentage']}%\n";
            echo "      Date: {$latest_daily['update_date']}\n";
            echo "      Stage: {$latest_daily['construction_stage']}\n";
            echo "      Incremental: {$latest_daily['incremental_completion_percentage']}%\n";
            echo "      Work Done: " . substr($latest_daily['work_done_today'], 0, 100) . "...\n";
            echo "      Hours: {$latest_daily['working_hours']}\n";
            echo "      Weather: {$latest_daily['weather_condition']}\n";
            echo "      Updated: {$latest_daily['created_at']}\n";
            
            // Compare with stored progress
            $difference = $latest_daily['cumulative_completion_percentage'] - $project['stored_progress'];
            if ($difference != 0) {
                echo "      ⚠️ PROGRESS MISMATCH: Daily shows {$latest_daily['cumulative_completion_percentage']}% but stored shows {$project['stored_progress']}% (difference: {$difference}%)\n";
            } else {
                echo "      ✅ Progress values match\n";
            }
        } else {
            echo "   📅 Latest Daily Progress: No daily updates found\n";
        }
        
        // Get all daily progress updates for this project
        $all_daily_query = "SELECT 
                              update_date,
                              construction_stage,
                              incremental_completion_percentage,
                              cumulative_completion_percentage
                            FROM daily_progress_updates 
                            WHERE project_id = ? 
                            ORDER BY update_date ASC";
        
        $stmt = $db->prepare($all_daily_query);
        $stmt->execute([$project['id']]);
        $all_daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($all_daily)) {
            echo "   📈 Daily Progress History:\n";
            foreach ($all_daily as $daily) {
                echo "      {$daily['update_date']}: {$daily['construction_stage']} - Incremental: {$daily['incremental_completion_percentage']}%, Cumulative: {$daily['cumulative_completion_percentage']}%\n";
            }
        }
        
        echo "\n";
    }
    
    // Get construction progress updates as well
    echo "🏗️ Construction Progress Updates:\n";
    echo "=================================\n";
    
    $construction_query = "SELECT 
                             cpu.project_id,
                             cp.project_name,
                             cpu.stage_name,
                             cpu.stage_status,
                             cpu.completion_percentage,
                             cpu.remarks,
                             cpu.created_at
                           FROM construction_progress_updates cpu
                           JOIN construction_projects cp ON cpu.project_id = cp.id
                           WHERE cpu.project_id IN (1, 2, 3)
                           ORDER BY cpu.project_id, cpu.created_at DESC";
    
    $stmt = $db->prepare($construction_query);
    $stmt->execute();
    $construction_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($construction_updates)) {
        echo "No construction progress updates found for projects 1, 2, 3\n";
    } else {
        $current_project = null;
        foreach ($construction_updates as $update) {
            if ($current_project !== $update['project_id']) {
                if ($current_project !== null) echo "\n";
                echo "🏗️ Project {$update['project_id']}: {$update['project_name']}\n";
                $current_project = $update['project_id'];
            }
            
            echo "   📊 {$update['stage_name']}: {$update['completion_percentage']}% ({$update['stage_status']})\n";
            echo "      Remarks: " . substr($update['remarks'] ?: 'No remarks', 0, 100) . "\n";
            echo "      Date: {$update['created_at']}\n";
        }
    }
    
    // Summary of correct progress values
    echo "\n\n🎯 CORRECT PROGRESS VALUES TO USE:\n";
    echo "==================================\n";
    
    foreach ($projects as $project) {
        $daily_query = "SELECT cumulative_completion_percentage 
                        FROM daily_progress_updates 
                        WHERE project_id = ? 
                        ORDER BY update_date DESC, created_at DESC 
                        LIMIT 1";
        
        $stmt = $db->prepare($daily_query);
        $stmt->execute([$project['id']]);
        $latest_progress = $stmt->fetchColumn();
        
        if ($latest_progress !== false) {
            echo "✅ Project {$project['id']}: Use {$latest_progress}% (from daily progress) instead of {$project['stored_progress']}%\n";
        } else {
            echo "⚠️ Project {$project['id']}: No daily progress data, keep stored {$project['stored_progress']}%\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Correct Daily Progress Analysis Complete!\n";
?>
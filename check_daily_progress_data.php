<?php
/**
 * Check Daily Progress Data to get correct progress values
 */

echo "🔍 Checking Daily Progress Data...\n\n";

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Check daily progress updates table
    echo "📊 1. Daily Progress Updates:\n";
    echo "============================\n";
    
    try {
        $daily_query = "SELECT 
                          dpu.id,
                          dpu.project_id,
                          cp.project_name,
                          dpu.date,
                          dpu.stage_name,
                          dpu.completion_percentage,
                          dpu.work_description,
                          dpu.materials_used,
                          dpu.labor_count,
                          dpu.created_at,
                          dpu.updated_at
                        FROM daily_progress_updates dpu
                        JOIN construction_projects cp ON dpu.project_id = cp.id
                        ORDER BY dpu.project_id, dpu.date DESC";
        
        $stmt = $db->prepare($daily_query);
        $stmt->execute();
        $daily_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($daily_progress)) {
            echo "No daily progress updates found.\n";
        } else {
            echo "Found " . count($daily_progress) . " daily progress updates:\n\n";
            
            $current_project = null;
            foreach ($daily_progress as $progress) {
                if ($current_project !== $progress['project_id']) {
                    if ($current_project !== null) echo "\n";
                    echo "🏗️ Project {$progress['project_id']}: {$progress['project_name']}\n";
                    $current_project = $progress['project_id'];
                }
                
                echo "   📅 {$progress['date']}: {$progress['stage_name']} - {$progress['completion_percentage']}%\n";
                echo "      Work: " . substr($progress['work_description'] ?: 'No description', 0, 100) . "\n";
                echo "      Materials: " . substr($progress['materials_used'] ?: 'Not specified', 0, 50) . "\n";
                echo "      Labor: {$progress['labor_count']} workers\n";
                echo "      Updated: {$progress['updated_at']}\n";
            }
        }
    } catch (Exception $e) {
        echo "Daily progress updates table error: " . $e->getMessage() . "\n";
    }
    
    // 2. Check construction progress updates table
    echo "\n\n📈 2. Construction Progress Updates:\n";
    echo "===================================\n";
    
    try {
        $construction_query = "SELECT 
                                 cpu.id,
                                 cpu.project_id,
                                 cp.project_name,
                                 cpu.stage_name,
                                 cpu.completion_percentage,
                                 cpu.description,
                                 cpu.photos,
                                 cpu.created_at,
                                 cpu.updated_at
                               FROM construction_progress_updates cpu
                               JOIN construction_projects cp ON cpu.project_id = cp.id
                               ORDER BY cpu.project_id, cpu.created_at DESC";
        
        $stmt = $db->prepare($construction_query);
        $stmt->execute();
        $construction_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($construction_progress)) {
            echo "No construction progress updates found.\n";
        } else {
            echo "Found " . count($construction_progress) . " construction progress updates:\n\n";
            
            $current_project = null;
            foreach ($construction_progress as $progress) {
                if ($current_project !== $progress['project_id']) {
                    if ($current_project !== null) echo "\n";
                    echo "🏗️ Project {$progress['project_id']}: {$progress['project_name']}\n";
                    $current_project = $progress['project_id'];
                }
                
                echo "   📊 {$progress['stage_name']}: {$progress['completion_percentage']}%\n";
                echo "      Description: " . substr($progress['description'] ?: 'No description', 0, 100) . "\n";
                echo "      Photos: " . ($progress['photos'] ? 'Yes' : 'No') . "\n";
                echo "      Created: {$progress['created_at']}\n";
            }
        }
    } catch (Exception $e) {
        echo "Construction progress updates table error: " . $e->getMessage() . "\n";
    }
    
    // 3. Check monthly progress reports
    echo "\n\n📋 3. Monthly Progress Reports:\n";
    echo "==============================\n";
    
    try {
        $monthly_query = "SELECT 
                            mpr.id,
                            mpr.project_id,
                            cp.project_name,
                            mpr.month,
                            mpr.year,
                            mpr.overall_completion_percentage,
                            mpr.stage_wise_progress,
                            mpr.created_at
                          FROM monthly_progress_reports mpr
                          JOIN construction_projects cp ON mpr.project_id = cp.id
                          ORDER BY mpr.project_id, mpr.year DESC, mpr.month DESC";
        
        $stmt = $db->prepare($monthly_query);
        $stmt->execute();
        $monthly_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($monthly_progress)) {
            echo "No monthly progress reports found.\n";
        } else {
            echo "Found " . count($monthly_progress) . " monthly progress reports:\n\n";
            
            foreach ($monthly_progress as $progress) {
                echo "🏗️ Project {$progress['project_id']}: {$progress['project_name']}\n";
                echo "   📅 {$progress['month']}/{$progress['year']}: {$progress['overall_completion_percentage']}% overall\n";
                echo "   📊 Stage Progress: {$progress['stage_wise_progress']}\n";
                echo "   📅 Created: {$progress['created_at']}\n\n";
            }
        }
    } catch (Exception $e) {
        echo "Monthly progress reports table error: " . $e->getMessage() . "\n";
    }
    
    // 4. Check progress reports table
    echo "\n📊 4. Progress Reports:\n";
    echo "======================\n";
    
    try {
        $reports_query = "SELECT 
                            pr.id,
                            pr.project_id,
                            cp.project_name,
                            pr.report_date,
                            pr.completion_percentage,
                            pr.current_stage,
                            pr.work_completed,
                            pr.issues_faced,
                            pr.next_steps,
                            pr.created_at
                          FROM progress_reports pr
                          JOIN construction_projects cp ON pr.project_id = cp.id
                          ORDER BY pr.project_id, pr.report_date DESC";
        
        $stmt = $db->prepare($reports_query);
        $stmt->execute();
        $progress_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($progress_reports)) {
            echo "No progress reports found.\n";
        } else {
            echo "Found " . count($progress_reports) . " progress reports:\n\n";
            
            $current_project = null;
            foreach ($progress_reports as $report) {
                if ($current_project !== $report['project_id']) {
                    if ($current_project !== null) echo "\n";
                    echo "🏗️ Project {$report['project_id']}: {$report['project_name']}\n";
                    $current_project = $report['project_id'];
                }
                
                echo "   📅 {$report['report_date']}: {$report['completion_percentage']}% - {$report['current_stage']}\n";
                echo "      Work: " . substr($report['work_completed'] ?: 'No details', 0, 100) . "\n";
                echo "      Issues: " . substr($report['issues_faced'] ?: 'None', 0, 50) . "\n";
                echo "      Next: " . substr($report['next_steps'] ?: 'Not specified', 0, 50) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Progress reports table error: " . $e->getMessage() . "\n";
    }
    
    // 5. Get latest actual progress for each project
    echo "\n\n🎯 5. Latest Actual Progress Summary:\n";
    echo "====================================\n";
    
    $projects_query = "SELECT id, project_name, completion_percentage as stored_progress FROM construction_projects ORDER BY id";
    $stmt = $db->prepare($projects_query);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "🏗️ Project {$project['id']}: {$project['project_name']}\n";
        echo "   📊 Stored Progress: {$project['stored_progress']}%\n";
        
        // Try to get latest daily progress
        try {
            $latest_daily_query = "SELECT completion_percentage, date, stage_name 
                                   FROM daily_progress_updates 
                                   WHERE project_id = ? 
                                   ORDER BY date DESC, updated_at DESC 
                                   LIMIT 1";
            $stmt = $db->prepare($latest_daily_query);
            $stmt->execute([$project['id']]);
            $latest_daily = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($latest_daily) {
                echo "   📅 Latest Daily Progress: {$latest_daily['completion_percentage']}% ({$latest_daily['stage_name']}) on {$latest_daily['date']}\n";
            } else {
                echo "   📅 Latest Daily Progress: No daily updates found\n";
            }
        } catch (Exception $e) {
            echo "   📅 Latest Daily Progress: Error - " . $e->getMessage() . "\n";
        }
        
        // Try to get latest construction progress
        try {
            $latest_construction_query = "SELECT completion_percentage, created_at, stage_name 
                                          FROM construction_progress_updates 
                                          WHERE project_id = ? 
                                          ORDER BY created_at DESC 
                                          LIMIT 1";
            $stmt = $db->prepare($latest_construction_query);
            $stmt->execute([$project['id']]);
            $latest_construction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($latest_construction) {
                echo "   🏗️ Latest Construction Progress: {$latest_construction['completion_percentage']}% ({$latest_construction['stage_name']}) on {$latest_construction['created_at']}\n";
            } else {
                echo "   🏗️ Latest Construction Progress: No construction updates found\n";
            }
        } catch (Exception $e) {
            echo "   🏗️ Latest Construction Progress: Error - " . $e->getMessage() . "\n";
        }
        
        // Try to get latest progress report
        try {
            $latest_report_query = "SELECT completion_percentage, report_date, current_stage 
                                    FROM progress_reports 
                                    WHERE project_id = ? 
                                    ORDER BY report_date DESC 
                                    LIMIT 1";
            $stmt = $db->prepare($latest_report_query);
            $stmt->execute([$project['id']]);
            $latest_report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($latest_report) {
                echo "   📋 Latest Progress Report: {$latest_report['completion_percentage']}% ({$latest_report['current_stage']}) on {$latest_report['report_date']}\n";
            } else {
                echo "   📋 Latest Progress Report: No reports found\n";
            }
        } catch (Exception $e) {
            echo "   📋 Latest Progress Report: Error - " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "✅ Daily Progress Data Analysis Complete!\n";
?>
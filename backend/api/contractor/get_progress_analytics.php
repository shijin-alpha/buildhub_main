<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


/**
 * Get Progress Analytics API
 * Provides data for graphs and visualizations
 */

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get parameters
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $homeowner_id = isset($_GET['homeowner_id']) ? (int)$_GET['homeowner_id'] : 0;
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;

    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing project_id']);
        exit;
    }

    // Verify access permissions
    $accessCheck = $db->prepare("
        SELECT cse.id, cse.contractor_id, cse.homeowner_id 
        FROM contractor_send_estimates cse 
        WHERE cse.id = :project_id 
        AND (cse.contractor_id = :contractor_id OR cse.homeowner_id = :homeowner_id OR :contractor_id = 0)
        LIMIT 1
    ");
    $accessCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $accessCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $accessCheck->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $accessCheck->execute();
    $project = $accessCheck->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or access denied']);
        exit;
    }

    // Build date filter
    $dateFilter = '';
    $dateParams = [];
    if ($date_from && $date_to) {
        $dateFilter = ' AND dp.update_date BETWEEN :date_from AND :date_to';
        $dateParams = [':date_from' => $date_from, ':date_to' => $date_to];
    }

    // 1. Overall Progress Timeline (Daily Progress)
    $progressTimelineQuery = $db->prepare("
        SELECT 
            dp.update_date,
            dp.construction_stage,
            dp.cumulative_completion_percentage,
            dp.incremental_completion_percentage,
            dp.working_hours,
            dp.weather_condition,
            COUNT(lt.id) as labour_entries,
            SUM(lt.worker_count) as total_workers
        FROM daily_progress_updates dp
        LEFT JOIN daily_labour_tracking lt ON dp.id = lt.daily_progress_id
        WHERE dp.project_id = :project_id {$dateFilter}
        GROUP BY dp.id
        ORDER BY dp.update_date ASC
    ");
    
    $progressTimelineQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    foreach ($dateParams as $key => $value) {
        $progressTimelineQuery->bindValue($key, $value, PDO::PARAM_STR);
    }
    $progressTimelineQuery->execute();
    $progressTimeline = $progressTimelineQuery->fetchAll(PDO::FETCH_ASSOC);

    // 2. Stage-wise Progress Chart
    $stageProgressQuery = $db->prepare("
        SELECT 
            dp.construction_stage,
            MIN(dp.update_date) as stage_start_date,
            MAX(dp.update_date) as stage_last_update,
            MAX(dp.cumulative_completion_percentage) as max_progress_in_stage,
            COUNT(dp.id) as days_worked,
            SUM(dp.incremental_completion_percentage) as total_incremental_progress,
            AVG(dp.working_hours) as avg_working_hours
        FROM daily_progress_updates dp
        WHERE dp.project_id = :project_id {$dateFilter}
        GROUP BY dp.construction_stage
        ORDER BY MIN(dp.update_date) ASC
    ");
    
    $stageProgressQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    foreach ($dateParams as $key => $value) {
        $stageProgressQuery->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stageProgressQuery->execute();
    $stageProgress = $stageProgressQuery->fetchAll(PDO::FETCH_ASSOC);

    // 3. Labour Utilization Graph
    $labourUtilizationQuery = $db->prepare("
        SELECT 
            dp.update_date,
            lt.worker_type,
            SUM(lt.worker_count) as daily_worker_count,
            SUM(lt.hours_worked) as daily_hours,
            SUM(lt.overtime_hours) as daily_overtime,
            SUM(lt.absent_count) as daily_absent
        FROM daily_progress_updates dp
        INNER JOIN daily_labour_tracking lt ON dp.id = lt.daily_progress_id
        WHERE dp.project_id = :project_id {$dateFilter}
        GROUP BY dp.update_date, lt.worker_type
        ORDER BY dp.update_date ASC, lt.worker_type ASC
    ");
    
    $labourUtilizationQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    foreach ($dateParams as $key => $value) {
        $labourUtilizationQuery->bindValue($key, $value, PDO::PARAM_STR);
    }
    $labourUtilizationQuery->execute();
    $labourUtilization = $labourUtilizationQuery->fetchAll(PDO::FETCH_ASSOC);

    // 4. Weather Impact Analysis
    $weatherImpactQuery = $db->prepare("
        SELECT 
            dp.weather_condition,
            COUNT(dp.id) as days_count,
            AVG(dp.incremental_completion_percentage) as avg_daily_progress,
            AVG(dp.working_hours) as avg_working_hours,
            SUM(lt.worker_count) as total_workers_affected
        FROM daily_progress_updates dp
        LEFT JOIN daily_labour_tracking lt ON dp.id = lt.daily_progress_id
        WHERE dp.project_id = :project_id {$dateFilter}
        GROUP BY dp.weather_condition
        ORDER BY days_count DESC
    ");
    
    $weatherImpactQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    foreach ($dateParams as $key => $value) {
        $weatherImpactQuery->bindValue($key, $value, PDO::PARAM_STR);
    }
    $weatherImpactQuery->execute();
    $weatherImpact = $weatherImpactQuery->fetchAll(PDO::FETCH_ASSOC);

    // 5. Weekly and Monthly Summaries
    $weeklySummariesQuery = $db->prepare("
        SELECT 
            ws.week_start_date,
            ws.week_end_date,
            ws.start_progress_percentage,
            ws.end_progress_percentage,
            ws.stages_worked,
            ws.total_labour_used,
            ws.delays_and_reasons
        FROM weekly_progress_summary ws
        WHERE ws.project_id = :project_id
        ORDER BY ws.week_start_date ASC
    ");
    $weeklySummariesQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $weeklySummariesQuery->execute();
    $weeklySummaries = $weeklySummariesQuery->fetchAll(PDO::FETCH_ASSOC);

    $monthlySummariesQuery = $db->prepare("
        SELECT 
            mr.report_year,
            mr.report_month,
            mr.planned_progress_percentage,
            mr.actual_progress_percentage,
            mr.milestones_achieved,
            mr.labour_summary,
            mr.material_summary
        FROM monthly_progress_report mr
        WHERE mr.project_id = :project_id
        ORDER BY mr.report_year ASC, mr.report_month ASC
    ");
    $monthlySummariesQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $monthlySummariesQuery->execute();
    $monthlySummaries = $monthlySummariesQuery->fetchAll(PDO::FETCH_ASSOC);

    // 6. Milestone Progress
    $milestonesQuery = $db->prepare("
        SELECT 
            pm.milestone_name,
            pm.milestone_stage,
            pm.planned_completion_date,
            pm.actual_completion_date,
            pm.planned_progress_percentage,
            pm.status
        FROM progress_milestones pm
        WHERE pm.project_id = :project_id
        ORDER BY pm.planned_completion_date ASC
    ");
    $milestonesQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $milestonesQuery->execute();
    $milestones = $milestonesQuery->fetchAll(PDO::FETCH_ASSOC);

    // 7. Summary Statistics
    $summaryStatsQuery = $db->prepare("
        SELECT 
            COUNT(DISTINCT dp.update_date) as total_working_days,
            MAX(dp.cumulative_completion_percentage) as current_progress,
            AVG(dp.incremental_completion_percentage) as avg_daily_progress,
            SUM(dp.working_hours) as total_working_hours,
            COUNT(DISTINCT dp.construction_stage) as stages_worked_count,
            COUNT(DISTINCT CASE WHEN dp.site_issues IS NOT NULL AND dp.site_issues != '' THEN dp.update_date END) as days_with_issues
        FROM daily_progress_updates dp
        WHERE dp.project_id = :project_id {$dateFilter}
    ");
    
    $summaryStatsQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    foreach ($dateParams as $key => $value) {
        $summaryStatsQuery->bindValue($key, $value, PDO::PARAM_STR);
    }
    $summaryStatsQuery->execute();
    $summaryStats = $summaryStatsQuery->fetch(PDO::FETCH_ASSOC);

    // Format data for frontend consumption
    $analytics = [
        'progress_timeline' => array_map(function($item) {
            return [
                'date' => $item['update_date'],
                'stage' => $item['construction_stage'],
                'cumulative_progress' => (float)$item['cumulative_completion_percentage'],
                'daily_progress' => (float)$item['incremental_completion_percentage'],
                'working_hours' => (float)$item['working_hours'],
                'weather' => $item['weather_condition'],
                'total_workers' => (int)$item['total_workers']
            ];
        }, $progressTimeline),
        
        'stage_progress' => array_map(function($item) {
            return [
                'stage' => $item['construction_stage'],
                'start_date' => $item['stage_start_date'],
                'last_update' => $item['stage_last_update'],
                'max_progress' => (float)$item['max_progress_in_stage'],
                'days_worked' => (int)$item['days_worked'],
                'total_progress_added' => (float)$item['total_incremental_progress'],
                'avg_working_hours' => round((float)$item['avg_working_hours'], 1)
            ];
        }, $stageProgress),
        
        'labour_utilization' => $labourUtilization,
        
        'weather_impact' => array_map(function($item) {
            return [
                'weather' => $item['weather_condition'],
                'days_count' => (int)$item['days_count'],
                'avg_progress' => round((float)$item['avg_daily_progress'], 2),
                'avg_hours' => round((float)$item['avg_working_hours'], 1),
                'total_workers' => (int)$item['total_workers_affected']
            ];
        }, $weatherImpact),
        
        'weekly_summaries' => array_map(function($item) {
            return [
                'week_start' => $item['week_start_date'],
                'week_end' => $item['week_end_date'],
                'start_progress' => (float)$item['start_progress_percentage'],
                'end_progress' => (float)$item['end_progress_percentage'],
                'progress_change' => (float)$item['end_progress_percentage'] - (float)$item['start_progress_percentage'],
                'stages_worked' => json_decode($item['stages_worked'], true),
                'labour_summary' => json_decode($item['total_labour_used'], true),
                'has_delays' => !empty($item['delays_and_reasons'])
            ];
        }, $weeklySummaries),
        
        'monthly_summaries' => array_map(function($item) {
            return [
                'year' => (int)$item['report_year'],
                'month' => (int)$item['report_month'],
                'month_name' => date('F', mktime(0, 0, 0, $item['report_month'], 1)),
                'planned_progress' => (float)$item['planned_progress_percentage'],
                'actual_progress' => (float)$item['actual_progress_percentage'],
                'variance' => (float)$item['actual_progress_percentage'] - (float)$item['planned_progress_percentage'],
                'milestones_achieved' => json_decode($item['milestones_achieved'], true),
                'labour_summary' => json_decode($item['labour_summary'], true),
                'material_summary' => json_decode($item['material_summary'], true)
            ];
        }, $monthlySummaries),
        
        'milestones' => array_map(function($item) {
            return [
                'name' => $item['milestone_name'],
                'stage' => $item['milestone_stage'],
                'planned_date' => $item['planned_completion_date'],
                'actual_date' => $item['actual_completion_date'],
                'planned_progress' => (float)$item['planned_progress_percentage'],
                'status' => $item['status'],
                'is_completed' => $item['status'] === 'Completed',
                'is_delayed' => $item['status'] === 'Delayed' || 
                              ($item['planned_completion_date'] < date('Y-m-d') && $item['status'] !== 'Completed')
            ];
        }, $milestones),
        
        'summary_stats' => [
            'total_working_days' => (int)$summaryStats['total_working_days'],
            'current_progress' => (float)$summaryStats['current_progress'],
            'avg_daily_progress' => round((float)$summaryStats['avg_daily_progress'], 2),
            'total_working_hours' => (float)$summaryStats['total_working_hours'],
            'stages_worked_count' => (int)$summaryStats['stages_worked_count'],
            'days_with_issues' => (int)$summaryStats['days_with_issues'],
            'avg_hours_per_day' => $summaryStats['total_working_days'] > 0 ? 
                                   round((float)$summaryStats['total_working_hours'] / (int)$summaryStats['total_working_days'], 1) : 0
        ]
    ];

    echo json_encode([
        'success' => true,
        'data' => $analytics,
        'project_info' => [
            'project_id' => $project_id,
            'contractor_id' => $project['contractor_id'],
            'homeowner_id' => $project['homeowner_id'],
            'date_range' => [
                'from' => $date_from,
                'to' => $date_to
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Progress analytics error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}
?>
<?php
/**
 * Submit Monthly Progress Report API
 * Handles comprehensive monthly progress reporting with analytics
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    $project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
    $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $report_month = isset($input['report_month']) ? (int)$input['report_month'] : 0;
    $report_year = isset($input['report_year']) ? (int)$input['report_year'] : 0;
    $planned_progress_percentage = isset($input['planned_progress_percentage']) ? (float)$input['planned_progress_percentage'] : 0;
    $milestones_achieved = $input['milestones_achieved'] ?? [];
    $delay_explanation = trim($input['delay_explanation'] ?? '');
    $contractor_remarks = trim($input['contractor_remarks'] ?? '');

    // Validation
    if ($project_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing project_id or contractor_id']);
        exit;
    }

    if ($report_month < 1 || $report_month > 12 || $report_year < 2020 || $report_year > 2050) {
        echo json_encode(['success' => false, 'message' => 'Invalid month or year']);
        exit;
    }

    if (empty($contractor_remarks)) {
        echo json_encode(['success' => false, 'message' => 'Contractor remarks are required']);
        exit;
    }

    // Verify contractor is assigned to this project
    $projectCheck = $db->prepare("
        SELECT cse.id, cse.homeowner_id, cse.contractor_id 
        FROM contractor_send_estimates cse 
        WHERE cse.id = :project_id AND cse.contractor_id = :contractor_id
        LIMIT 1
    ");
    $projectCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $projectCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $projectCheck->execute();
    $project = $projectCheck->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or contractor not assigned']);
        exit;
    }

    $homeowner_id = $project['homeowner_id'];

    // Check if monthly report already exists
    $existingCheck = $db->prepare("
        SELECT id FROM monthly_progress_report 
        WHERE project_id = :project_id AND contractor_id = :contractor_id 
        AND report_year = :report_year AND report_month = :report_month
    ");
    $existingCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':report_year', $report_year, PDO::PARAM_INT);
    $existingCheck->bindValue(':report_month', $report_month, PDO::PARAM_INT);
    $existingCheck->execute();

    if ($existingCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Monthly report already exists for this month']);
        exit;
    }

    // Calculate month date range
    $month_start = sprintf('%04d-%02d-01', $report_year, $report_month);
    $month_end = date('Y-m-t', strtotime($month_start)); // Last day of month

    // Get actual progress for the month
    $actualProgressQuery = $db->prepare("
        SELECT 
            MIN(cumulative_completion_percentage) as month_start_progress,
            MAX(cumulative_completion_percentage) as month_end_progress,
            COUNT(*) as daily_updates_count
        FROM daily_progress_updates 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id 
        AND update_date BETWEEN :month_start AND :month_end
    ");
    $actualProgressQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $actualProgressQuery->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $actualProgressQuery->bindValue(':month_start', $month_start, PDO::PARAM_STR);
    $actualProgressQuery->bindValue(':month_end', $month_end, PDO::PARAM_STR);
    $actualProgressQuery->execute();
    $progressData = $actualProgressQuery->fetch(PDO::FETCH_ASSOC);

    $actual_progress_percentage = $progressData['month_end_progress'] ?? 0;

    // Get labour summary for the month
    $labourSummaryQuery = $db->prepare("
        SELECT 
            lt.worker_type,
            SUM(lt.worker_count) as total_workers,
            SUM(lt.hours_worked) as total_hours,
            SUM(lt.overtime_hours) as total_overtime,
            SUM(lt.absent_count) as total_absent,
            AVG(lt.worker_count) as avg_daily_workers,
            COUNT(DISTINCT dp.update_date) as working_days
        FROM daily_labour_tracking lt
        INNER JOIN daily_progress_updates dp ON lt.daily_progress_id = dp.id
        WHERE dp.project_id = :project_id 
        AND dp.contractor_id = :contractor_id 
        AND dp.update_date BETWEEN :month_start AND :month_end
        GROUP BY lt.worker_type
    ");
    $labourSummaryQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $labourSummaryQuery->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $labourSummaryQuery->bindValue(':month_start', $month_start, PDO::PARAM_STR);
    $labourSummaryQuery->bindValue(':month_end', $month_end, PDO::PARAM_STR);
    $labourSummaryQuery->execute();
    $labourData = $labourSummaryQuery->fetchAll(PDO::FETCH_ASSOC);

    // Format labour summary
    $labour_summary = [];
    $total_workers = 0;
    $total_hours = 0;
    $total_overtime = 0;
    $total_working_days = 0;

    foreach ($labourData as $labour) {
        $labour_summary[$labour['worker_type']] = [
            'total_workers' => (int)$labour['total_workers'],
            'total_hours' => (float)$labour['total_hours'],
            'total_overtime' => (float)$labour['total_overtime'],
            'total_absent' => (int)$labour['total_absent'],
            'avg_daily_workers' => round((float)$labour['avg_daily_workers'], 1),
            'working_days' => (int)$labour['working_days']
        ];
        $total_workers += (int)$labour['total_workers'];
        $total_hours += (float)$labour['total_hours'];
        $total_overtime += (float)$labour['total_overtime'];
        $total_working_days = max($total_working_days, (int)$labour['working_days']);
    }

    // Add summary totals
    $labour_summary['monthly_totals'] = [
        'total_workers_all_types' => $total_workers,
        'total_hours_all_types' => $total_hours,
        'total_overtime_all_types' => $total_overtime,
        'total_working_days' => $total_working_days,
        'daily_updates_count' => (int)$progressData['daily_updates_count']
    ];

    // Get material summary for the month
    $materialSummaryQuery = $db->prepare("
        SELECT 
            materials_used,
            COUNT(*) as usage_count,
            GROUP_CONCAT(DISTINCT construction_stage) as stages_used
        FROM daily_progress_updates 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id 
        AND update_date BETWEEN :month_start AND :month_end
        AND materials_used IS NOT NULL 
        AND materials_used != ''
        GROUP BY materials_used
    ");
    $materialSummaryQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $materialSummaryQuery->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $materialSummaryQuery->bindValue(':month_start', $month_start, PDO::PARAM_STR);
    $materialSummaryQuery->bindValue(':month_end', $month_end, PDO::PARAM_STR);
    $materialSummaryQuery->execute();
    $materialData = $materialSummaryQuery->fetchAll(PDO::FETCH_ASSOC);

    // Format material summary
    $material_summary = [
        'materials_used' => [],
        'summary' => [
            'total_material_entries' => count($materialData),
            'total_usage_days' => 0
        ]
    ];

    foreach ($materialData as $material) {
        $material_summary['materials_used'][] = [
            'material' => $material['materials_used'],
            'usage_count' => (int)$material['usage_count'],
            'stages_used' => explode(',', $material['stages_used'])
        ];
        $material_summary['summary']['total_usage_days'] += (int)$material['usage_count'];
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Insert monthly progress report
        $stmt = $db->prepare("
            INSERT INTO monthly_progress_report (
                project_id, contractor_id, homeowner_id, report_month, report_year,
                planned_progress_percentage, actual_progress_percentage, milestones_achieved,
                labour_summary, material_summary, delay_explanation, contractor_remarks
            ) VALUES (
                :project_id, :contractor_id, :homeowner_id, :report_month, :report_year,
                :planned_progress, :actual_progress, :milestones_achieved,
                :labour_summary, :material_summary, :delay_explanation, :contractor_remarks
            )
        ");

        $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $stmt->bindValue(':report_month', $report_month, PDO::PARAM_INT);
        $stmt->bindValue(':report_year', $report_year, PDO::PARAM_INT);
        $stmt->bindValue(':planned_progress', $planned_progress_percentage, PDO::PARAM_STR);
        $stmt->bindValue(':actual_progress', $actual_progress_percentage, PDO::PARAM_STR);
        $stmt->bindValue(':milestones_achieved', json_encode($milestones_achieved), PDO::PARAM_STR);
        $stmt->bindValue(':labour_summary', json_encode($labour_summary), PDO::PARAM_STR);
        $stmt->bindValue(':material_summary', json_encode($material_summary), PDO::PARAM_STR);
        $stmt->bindValue(':delay_explanation', $delay_explanation, PDO::PARAM_STR);
        $stmt->bindValue(':contractor_remarks', $contractor_remarks, PDO::PARAM_STR);

        $stmt->execute();
        $monthly_report_id = $db->lastInsertId();

        // Create notification for homeowner
        $progress_vs_planned = $actual_progress_percentage - $planned_progress_percentage;
        $month_name = date('F', mktime(0, 0, 0, $report_month, 1));
        
        $notification_title = "Monthly Progress Report - {$month_name} {$report_year}";
        $notification_message = "Contractor has submitted monthly progress report. ";
        $notification_message .= "Planned: {$planned_progress_percentage}%, Actual: {$actual_progress_percentage}% ";
        $notification_message .= "(" . ($progress_vs_planned >= 0 ? "+" : "") . "{$progress_vs_planned}% vs planned). ";
        $notification_message .= "Milestones achieved: " . count($milestones_achieved);

        $notificationStmt = $db->prepare("
            INSERT INTO enhanced_progress_notifications (
                project_id, contractor_id, homeowner_id, notification_type, 
                reference_id, title, message
            ) VALUES (
                :project_id, :contractor_id, :homeowner_id, 'monthly_report',
                :reference_id, :title, :message
            )
        ");

        $notificationStmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':reference_id', $monthly_report_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Monthly progress report submitted successfully',
            'data' => [
                'monthly_report_id' => $monthly_report_id,
                'planned_progress' => $planned_progress_percentage,
                'actual_progress' => $actual_progress_percentage,
                'progress_variance' => $progress_vs_planned,
                'milestones_achieved' => count($milestones_achieved),
                'labour_summary' => $labour_summary['monthly_totals'],
                'material_summary' => $material_summary['summary'],
                'report_period' => "{$month_name} {$report_year}"
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Monthly progress report error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}
?>
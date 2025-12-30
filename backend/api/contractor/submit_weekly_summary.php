<?php
/**
 * Submit Weekly Progress Summary API
 * Handles weekly progress summary with aggregated data
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
    $week_start_date = trim($input['week_start_date'] ?? '');
    $week_end_date = trim($input['week_end_date'] ?? '');
    $stages_worked = $input['stages_worked'] ?? [];
    $delays_and_reasons = trim($input['delays_and_reasons'] ?? '');
    $weekly_remarks = trim($input['weekly_remarks'] ?? '');

    // Validation
    if ($project_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing project_id or contractor_id']);
        exit;
    }

    if (empty($week_start_date) || empty($week_end_date) || empty($weekly_remarks)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: week_start_date, week_end_date, weekly_remarks']);
        exit;
    }

    if (empty($stages_worked)) {
        echo json_encode(['success' => false, 'message' => 'At least one construction stage must be specified']);
        exit;
    }

    // Validate date format and range
    $start_date = DateTime::createFromFormat('Y-m-d', $week_start_date);
    $end_date = DateTime::createFromFormat('Y-m-d', $week_end_date);
    
    if (!$start_date || !$end_date) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    if ($start_date >= $end_date) {
        echo json_encode(['success' => false, 'message' => 'Week end date must be after start date']);
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

    // Check if weekly summary already exists for this week
    $existingCheck = $db->prepare("
        SELECT id FROM weekly_progress_summary 
        WHERE project_id = :project_id AND contractor_id = :contractor_id AND week_start_date = :week_start_date
    ");
    $existingCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':week_start_date', $week_start_date, PDO::PARAM_STR);
    $existingCheck->execute();

    if ($existingCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Weekly summary already exists for this week period']);
        exit;
    }

    // Get progress data for the week from daily updates
    $weeklyDataQuery = $db->prepare("
        SELECT 
            MIN(cumulative_completion_percentage) as start_progress,
            MAX(cumulative_completion_percentage) as end_progress,
            COUNT(*) as daily_updates_count
        FROM daily_progress_updates 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id 
        AND update_date BETWEEN :week_start_date AND :week_end_date
    ");
    $weeklyDataQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $weeklyDataQuery->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $weeklyDataQuery->bindValue(':week_start_date', $week_start_date, PDO::PARAM_STR);
    $weeklyDataQuery->bindValue(':week_end_date', $week_end_date, PDO::PARAM_STR);
    $weeklyDataQuery->execute();
    $weeklyData = $weeklyDataQuery->fetch(PDO::FETCH_ASSOC);

    $start_progress = $weeklyData['start_progress'] ?? 0;
    $end_progress = $weeklyData['end_progress'] ?? 0;

    // Get labour summary for the week
    $labourSummaryQuery = $db->prepare("
        SELECT 
            lt.worker_type,
            SUM(lt.worker_count) as total_workers,
            SUM(lt.hours_worked) as total_hours,
            SUM(lt.overtime_hours) as total_overtime,
            SUM(lt.absent_count) as total_absent,
            AVG(lt.worker_count) as avg_daily_workers
        FROM daily_labour_tracking lt
        INNER JOIN daily_progress_updates dp ON lt.daily_progress_id = dp.id
        WHERE dp.project_id = :project_id 
        AND dp.contractor_id = :contractor_id 
        AND dp.update_date BETWEEN :week_start_date AND :week_end_date
        GROUP BY lt.worker_type
    ");
    $labourSummaryQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $labourSummaryQuery->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $labourSummaryQuery->bindValue(':week_start_date', $week_start_date, PDO::PARAM_STR);
    $labourSummaryQuery->bindValue(':week_end_date', $week_end_date, PDO::PARAM_STR);
    $labourSummaryQuery->execute();
    $labourSummary = $labourSummaryQuery->fetchAll(PDO::FETCH_ASSOC);

    // Format labour data
    $total_labour_used = [];
    $total_workers = 0;
    $total_hours = 0;
    $total_overtime = 0;

    foreach ($labourSummary as $labour) {
        $total_labour_used[$labour['worker_type']] = [
            'total_workers' => (int)$labour['total_workers'],
            'total_hours' => (float)$labour['total_hours'],
            'total_overtime' => (float)$labour['total_overtime'],
            'total_absent' => (int)$labour['total_absent'],
            'avg_daily_workers' => round((float)$labour['avg_daily_workers'], 1)
        ];
        $total_workers += (int)$labour['total_workers'];
        $total_hours += (float)$labour['total_hours'];
        $total_overtime += (float)$labour['total_overtime'];
    }

    // Add summary totals
    $total_labour_used['summary'] = [
        'total_workers_all_types' => $total_workers,
        'total_hours_all_types' => $total_hours,
        'total_overtime_all_types' => $total_overtime,
        'daily_updates_count' => (int)$weeklyData['daily_updates_count']
    ];

    // Begin transaction
    $db->beginTransaction();

    try {
        // Insert weekly progress summary
        $stmt = $db->prepare("
            INSERT INTO weekly_progress_summary (
                project_id, contractor_id, homeowner_id, week_start_date, week_end_date,
                stages_worked, start_progress_percentage, end_progress_percentage,
                total_labour_used, delays_and_reasons, weekly_remarks
            ) VALUES (
                :project_id, :contractor_id, :homeowner_id, :week_start_date, :week_end_date,
                :stages_worked, :start_progress, :end_progress,
                :total_labour_used, :delays_and_reasons, :weekly_remarks
            )
        ");

        $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $stmt->bindValue(':week_start_date', $week_start_date, PDO::PARAM_STR);
        $stmt->bindValue(':week_end_date', $week_end_date, PDO::PARAM_STR);
        $stmt->bindValue(':stages_worked', json_encode($stages_worked), PDO::PARAM_STR);
        $stmt->bindValue(':start_progress', $start_progress, PDO::PARAM_STR);
        $stmt->bindValue(':end_progress', $end_progress, PDO::PARAM_STR);
        $stmt->bindValue(':total_labour_used', json_encode($total_labour_used), PDO::PARAM_STR);
        $stmt->bindValue(':delays_and_reasons', $delays_and_reasons, PDO::PARAM_STR);
        $stmt->bindValue(':weekly_remarks', $weekly_remarks, PDO::PARAM_STR);

        $stmt->execute();
        $weekly_summary_id = $db->lastInsertId();

        // Create notification for homeowner
        $progress_change = $end_progress - $start_progress;
        $notification_title = "Weekly Progress Summary - Week of {$week_start_date}";
        $notification_message = "Contractor has submitted weekly progress summary. ";
        $notification_message .= "Progress this week: +{$progress_change}% (from {$start_progress}% to {$end_progress}%). ";
        $notification_message .= "Stages worked: " . implode(', ', $stages_worked);

        $notificationStmt = $db->prepare("
            INSERT INTO enhanced_progress_notifications (
                project_id, contractor_id, homeowner_id, notification_type, 
                reference_id, title, message
            ) VALUES (
                :project_id, :contractor_id, :homeowner_id, 'weekly_summary',
                :reference_id, :title, :message
            )
        ");

        $notificationStmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':reference_id', $weekly_summary_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Weekly progress summary submitted successfully',
            'data' => [
                'weekly_summary_id' => $weekly_summary_id,
                'start_progress' => $start_progress,
                'end_progress' => $end_progress,
                'progress_change' => $progress_change,
                'stages_worked' => $stages_worked,
                'total_labour_summary' => $total_labour_used['summary'],
                'daily_updates_included' => (int)$weeklyData['daily_updates_count']
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Weekly progress summary error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}
?>
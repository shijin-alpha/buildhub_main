<?php
/**
 * Update Planned Schedule API - Contractor Only
 * 
 * Allows contractors to set planned_start_date and planned_end_date
 * Only works if actual_start_date has not been set (schedule not locked)
 * Backward compatible - does not affect existing functionality
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
session_start();

try {
    // Verify contractor authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Only contractors can update planned schedules.'
        ]);
        exit();
    }

    $contractor_id = $_SESSION['user_id'];
    
    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['project_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit();
    }

    $project_id = intval($input['project_id']);
    $planned_start_date = $input['planned_start_date'] ?? null;
    $planned_end_date = $input['planned_end_date'] ?? null;
    $change_reason = $input['change_reason'] ?? 'Schedule update by contractor';

    // Validate dates if provided
    if ($planned_start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $planned_start_date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid planned_start_date format. Use YYYY-MM-DD'
        ]);
        exit();
    }

    if ($planned_end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $planned_end_date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid planned_end_date format. Use YYYY-MM-DD'
        ]);
        exit();
    }

    // Validate end date is after start date
    if ($planned_start_date && $planned_end_date && $planned_end_date <= $planned_start_date) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Planned end date must be after planned start date'
        ]);
        exit();
    }

    $db = Database::getInstance()->getConnection();

    // Verify contractor owns this project and check if schedule is locked
    $check_stmt = $db->prepare("
        SELECT 
            id, 
            project_name,
            contractor_id,
            actual_start_date,
            schedule_locked,
            planned_start_date AS current_planned_start,
            planned_end_date AS current_planned_end,
            status
        FROM construction_projects
        WHERE id = ? AND contractor_id = ?
    ");
    $check_stmt->execute([$project_id, $contractor_id]);
    $project = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or you do not have permission to update it'
        ]);
        exit();
    }

    // Check if schedule is locked
    if ($project['schedule_locked'] == 1 || $project['actual_start_date'] !== null) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot modify planned dates. Actual work has already begun.',
            'locked' => true,
            'actual_start_date' => $project['actual_start_date']
        ]);
        exit();
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Update planned dates
        $update_stmt = $db->prepare("
            UPDATE construction_projects
            SET 
                planned_start_date = ?,
                planned_end_date = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND contractor_id = ?
        ");
        
        $update_stmt->execute([
            $planned_start_date,
            $planned_end_date,
            $project_id,
            $contractor_id
        ]);

        // Log changes to audit table
        if ($planned_start_date !== $project['current_planned_start']) {
            $audit_stmt = $db->prepare("
                INSERT INTO schedule_change_audit 
                (project_id, changed_by_user_id, changed_by_role, field_changed, old_value, new_value, change_reason)
                VALUES (?, ?, 'contractor', 'planned_start_date', ?, ?, ?)
            ");
            $audit_stmt->execute([
                $project_id,
                $contractor_id,
                $project['current_planned_start'],
                $planned_start_date,
                $change_reason
            ]);
        }

        if ($planned_end_date !== $project['current_planned_end']) {
            $audit_stmt = $db->prepare("
                INSERT INTO schedule_change_audit 
                (project_id, changed_by_user_id, changed_by_role, field_changed, old_value, new_value, change_reason)
                VALUES (?, ?, 'contractor', 'planned_end_date', ?, ?, ?)
            ");
            $audit_stmt->execute([
                $project_id,
                $contractor_id,
                $project['current_planned_end'],
                $planned_end_date,
                $change_reason
            ]);
        }

        $db->commit();

        // Calculate planned duration
        $planned_duration = null;
        if ($planned_start_date && $planned_end_date) {
            $start = new DateTime($planned_start_date);
            $end = new DateTime($planned_end_date);
            $planned_duration = $start->diff($end)->days;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Planned schedule updated successfully',
            'data' => [
                'project_id' => $project_id,
                'project_name' => $project['project_name'],
                'planned_start_date' => $planned_start_date,
                'planned_end_date' => $planned_end_date,
                'planned_duration_days' => $planned_duration,
                'schedule_locked' => false,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

<?php
/**
 * Update Actual Dates API - Contractor Only
 * 
 * Allows contractors to set actual_start_date and actual_end_date
 * Setting actual_start_date locks planned dates
 * Setting actual_end_date triggers automatic time overrun calculation
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
            'message' => 'Unauthorized. Only contractors can update actual dates.'
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
    $actual_start_date = $input['actual_start_date'] ?? null;
    $actual_end_date = $input['actual_end_date'] ?? null;
    $change_reason = $input['change_reason'] ?? 'Actual date update by contractor';

    // At least one date must be provided
    if (!$actual_start_date && !$actual_end_date) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'At least one actual date (start or end) must be provided'
        ]);
        exit();
    }

    // Validate date formats
    if ($actual_start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $actual_start_date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid actual_start_date format. Use YYYY-MM-DD'
        ]);
        exit();
    }

    if ($actual_end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $actual_end_date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid actual_end_date format. Use YYYY-MM-DD'
        ]);
        exit();
    }

    $db = Database::getInstance()->getConnection();

    // Get current project data
    $check_stmt = $db->prepare("
        SELECT 
            id, 
            project_name,
            contractor_id,
            actual_start_date AS current_actual_start,
            actual_end_date AS current_actual_end,
            planned_start_date,
            planned_end_date,
            schedule_locked,
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

    // Validate actual_end_date requires actual_start_date
    $final_actual_start = $actual_start_date ?? $project['current_actual_start'];
    if ($actual_end_date && !$final_actual_start) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot set actual_end_date without actual_start_date'
        ]);
        exit();
    }

    // Validate end date is after start date
    if ($actual_end_date && $final_actual_start && $actual_end_date <= $final_actual_start) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Actual end date must be after actual start date'
        ]);
        exit();
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Build update query dynamically
        $update_fields = [];
        $update_params = [];

        if ($actual_start_date !== null) {
            $update_fields[] = "actual_start_date = ?";
            $update_params[] = $actual_start_date;
            
            // Lock schedule if setting actual_start_date for first time
            if ($project['current_actual_start'] === null) {
                $update_fields[] = "schedule_locked = 1";
            }
        }

        if ($actual_end_date !== null) {
            $update_fields[] = "actual_end_date = ?";
            $update_params[] = $actual_end_date;
            
            // Auto-update status to completed if not already
            if ($project['status'] !== 'completed') {
                $update_fields[] = "status = 'completed'";
            }
        }

        $update_fields[] = "updated_at = CURRENT_TIMESTAMP";
        $update_params[] = $project_id;
        $update_params[] = $contractor_id;

        $update_sql = "
            UPDATE construction_projects
            SET " . implode(', ', $update_fields) . "
            WHERE id = ? AND contractor_id = ?
        ";

        $update_stmt = $db->prepare($update_sql);
        $update_stmt->execute($update_params);

        // Log changes to audit table
        if ($actual_start_date !== null && $actual_start_date !== $project['current_actual_start']) {
            $audit_stmt = $db->prepare("
                INSERT INTO schedule_change_audit 
                (project_id, changed_by_user_id, changed_by_role, field_changed, old_value, new_value, change_reason)
                VALUES (?, ?, 'contractor', 'actual_start_date', ?, ?, ?)
            ");
            $audit_stmt->execute([
                $project_id,
                $contractor_id,
                $project['current_actual_start'],
                $actual_start_date,
                $change_reason
            ]);
        }

        if ($actual_end_date !== null && $actual_end_date !== $project['current_actual_end']) {
            $audit_stmt = $db->prepare("
                INSERT INTO schedule_change_audit 
                (project_id, changed_by_user_id, changed_by_role, field_changed, old_value, new_value, change_reason)
                VALUES (?, ?, 'contractor', 'actual_end_date', ?, ?, ?)
            ");
            $audit_stmt->execute([
                $project_id,
                $contractor_id,
                $project['current_actual_end'],
                $actual_end_date,
                $change_reason
            ]);
        }

        // Calculate time overrun if project is completed
        if ($actual_end_date !== null) {
            $calc_stmt = $db->prepare("CALL calculate_time_overrun(?)");
            $calc_stmt->execute([$project_id]);
        }

        // Get updated project data
        $result_stmt = $db->prepare("
            SELECT 
                id,
                project_name,
                planned_start_date,
                planned_end_date,
                actual_start_date,
                actual_end_date,
                schedule_locked,
                actual_time_overrun_percentage,
                status,
                CASE 
                    WHEN planned_start_date IS NOT NULL AND planned_end_date IS NOT NULL
                    THEN DATEDIFF(planned_end_date, planned_start_date)
                    ELSE NULL
                END AS planned_duration_days,
                CASE 
                    WHEN actual_start_date IS NOT NULL AND actual_end_date IS NOT NULL
                    THEN DATEDIFF(actual_end_date, actual_start_date)
                    ELSE NULL
                END AS actual_duration_days,
                CASE 
                    WHEN planned_end_date IS NOT NULL AND actual_end_date IS NOT NULL
                    THEN DATEDIFF(actual_end_date, planned_end_date)
                    ELSE NULL
                END AS delay_days
            FROM construction_projects
            WHERE id = ?
        ");
        $result_stmt->execute([$project_id]);
        $updated_project = $result_stmt->fetch(PDO::FETCH_ASSOC);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Actual dates updated successfully',
            'data' => $updated_project,
            'schedule_locked' => $updated_project['schedule_locked'] == 1,
            'overrun_calculated' => $actual_end_date !== null
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

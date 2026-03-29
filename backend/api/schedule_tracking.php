<?php
/**
 * SCHEDULE TRACKING API
 * Backward-compatible schedule tracking for construction projects
 * Handles planned vs actual schedule management with role-based access control
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
session_start();

// Database connection
$database = new Database();
$db = $database->getConnection();

/**
 * Log schedule changes for audit trail
 */
function logScheduleChange($db, $projectId, $userId, $userRole, $field, $oldValue, $newValue, $reason = null) {
    $query = "INSERT INTO project_schedule_audit 
              (project_id, changed_by_user_id, user_role, field_changed, old_value, new_value, change_reason, ip_address) 
              VALUES (:project_id, :user_id, :user_role, :field, :old_value, :new_value, :reason, :ip)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':user_role', $userRole);
    $stmt->bindParam(':field', $field);
    $stmt->bindParam(':old_value', $oldValue);
    $stmt->bindParam(':new_value', $newValue);
    $stmt->bindParam(':reason', $reason);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt->bindParam(':ip', $ip);
    
    return $stmt->execute();
}

/**
 * Calculate time overrun percentage
 */
function calculateTimeOverrun($plannedStart, $plannedEnd, $actualStart, $actualEnd) {
    if (!$plannedStart || !$plannedEnd || !$actualStart || !$actualEnd) {
        return null;
    }
    
    $plannedStartDate = new DateTime($plannedStart);
    $plannedEndDate = new DateTime($plannedEnd);
    $actualStartDate = new DateTime($actualStart);
    $actualEndDate = new DateTime($actualEnd);
    
    $plannedDuration = $plannedStartDate->diff($plannedEndDate)->days;
    $actualDuration = $actualStartDate->diff($actualEndDate)->days;
    
    if ($plannedDuration == 0) {
        return null;
    }
    
    $overrunPercentage = (($actualDuration - $plannedDuration) / $plannedDuration) * 100;
    return round($overrunPercentage, 2);
}

// ============================================================================
// GET SCHEDULE DATA
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $projectId = $_GET['project_id'] ?? null;
    
    if (!$projectId) {
        echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        exit();
    }
    
    $query = "SELECT 
                id,
                project_name,
                status,
                planned_start_date,
                planned_end_date,
                actual_start_date,
                actual_end_date,
                actual_time_overrun_percentage,
                planned_dates_locked,
                start_date,
                expected_completion_date,
                actual_completion_date
              FROM construction_projects 
              WHERE id = :project_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit();
    }
    
    // Calculate delay in days if applicable
    $delayDays = null;
    if ($project['planned_end_date'] && $project['actual_end_date']) {
        $plannedEnd = new DateTime($project['planned_end_date']);
        $actualEnd = new DateTime($project['actual_end_date']);
        $delayDays = $plannedEnd->diff($actualEnd)->days;
        if ($actualEnd < $plannedEnd) {
            $delayDays = -$delayDays; // Negative means completed early
        }
    } elseif ($project['planned_end_date'] && !$project['actual_end_date'] && $project['status'] !== 'completed') {
        // Calculate current delay for in-progress projects
        $plannedEnd = new DateTime($project['planned_end_date']);
        $today = new DateTime();
        if ($today > $plannedEnd) {
            $delayDays = $plannedEnd->diff($today)->days;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project' => $project,
            'delay_days' => $delayDays,
            'is_delayed' => $delayDays !== null && $delayDays > 0,
            'is_early' => $delayDays !== null && $delayDays < 0
        ]
    ]);
    exit();
}

// ============================================================================
// UPDATE PLANNED DATES (Contractor Only)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_planned_dates') {
    $projectId = $_POST['project_id'] ?? null;
    $plannedStartDate = $_POST['planned_start_date'] ?? null;
    $plannedEndDate = $_POST['planned_end_date'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? null;
    
    // Validation
    if (!$projectId || !$plannedStartDate || !$plannedEndDate || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Role check - only contractors can set planned dates
    if ($userRole !== 'contractor') {
        echo json_encode(['success' => false, 'message' => 'Only contractors can set planned dates']);
        exit();
    }
    
    // Check if project exists and belongs to this contractor
    $query = "SELECT contractor_id, planned_dates_locked, actual_start_date, 
                     planned_start_date, planned_end_date 
              FROM construction_projects 
              WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit();
    }
    
    if ($project['contractor_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Project does not belong to this contractor']);
        exit();
    }
    
    // Check if planned dates are locked
    if ($project['planned_dates_locked'] == 1) {
        echo json_encode([
            'success' => false, 
            'message' => 'Planned dates are locked because actual start date has been recorded'
        ]);
        exit();
    }
    
    // Validate date logic
    if (strtotime($plannedEndDate) <= strtotime($plannedStartDate)) {
        echo json_encode(['success' => false, 'message' => 'Planned end date must be after planned start date']);
        exit();
    }
    
    // Update planned dates
    $updateQuery = "UPDATE construction_projects 
                    SET planned_start_date = :planned_start,
                        planned_end_date = :planned_end,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :project_id";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':planned_start', $plannedStartDate);
    $updateStmt->bindParam(':planned_end', $plannedEndDate);
    $updateStmt->bindParam(':project_id', $projectId);
    
    if ($updateStmt->execute()) {
        // Log changes
        if ($project['planned_start_date'] !== $plannedStartDate) {
            logScheduleChange($db, $projectId, $userId, $userRole, 'planned_start_date', 
                            $project['planned_start_date'], $plannedStartDate, 'Contractor updated planned schedule');
        }
        if ($project['planned_end_date'] !== $plannedEndDate) {
            logScheduleChange($db, $projectId, $userId, $userRole, 'planned_end_date', 
                            $project['planned_end_date'], $plannedEndDate, 'Contractor updated planned schedule');
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Planned dates updated successfully',
            'data' => [
                'planned_start_date' => $plannedStartDate,
                'planned_end_date' => $plannedEndDate
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update planned dates']);
    }
    exit();
}

// ============================================================================
// UPDATE ACTUAL START DATE (Contractor Only)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_actual_start') {
    $projectId = $_POST['project_id'] ?? null;
    $actualStartDate = $_POST['actual_start_date'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? null;
    
    if (!$projectId || !$actualStartDate || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    if ($userRole !== 'contractor') {
        echo json_encode(['success' => false, 'message' => 'Only contractors can set actual start date']);
        exit();
    }
    
    // Get project details
    $query = "SELECT contractor_id, actual_start_date, planned_start_date 
              FROM construction_projects 
              WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project || $project['contractor_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Update actual start date and lock planned dates
    $updateQuery = "UPDATE construction_projects 
                    SET actual_start_date = :actual_start,
                        planned_dates_locked = 1,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :project_id";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':actual_start', $actualStartDate);
    $updateStmt->bindParam(':project_id', $projectId);
    
    if ($updateStmt->execute()) {
        logScheduleChange($db, $projectId, $userId, $userRole, 'actual_start_date', 
                        $project['actual_start_date'], $actualStartDate, 'Project actually started - planned dates now locked');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Actual start date recorded. Planned dates are now locked.',
            'data' => [
                'actual_start_date' => $actualStartDate,
                'planned_dates_locked' => true
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update actual start date']);
    }
    exit();
}

// ============================================================================
// UPDATE ACTUAL END DATE AND CALCULATE OVERRUN (Contractor/System)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_actual_end') {
    $projectId = $_POST['project_id'] ?? null;
    $actualEndDate = $_POST['actual_end_date'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? null;
    
    if (!$projectId || !$actualEndDate || !$userId) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    if ($userRole !== 'contractor' && $userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only contractors or admins can set actual end date']);
        exit();
    }
    
    // Get project details
    $query = "SELECT contractor_id, planned_start_date, planned_end_date, 
                     actual_start_date, actual_end_date 
              FROM construction_projects 
              WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit();
    }
    
    if ($userRole === 'contractor' && $project['contractor_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Validate actual end date is after actual start date
    if ($project['actual_start_date'] && strtotime($actualEndDate) < strtotime($project['actual_start_date'])) {
        echo json_encode(['success' => false, 'message' => 'Actual end date cannot be before actual start date']);
        exit();
    }
    
    // Calculate time overrun if all required dates exist
    $overrunPercentage = calculateTimeOverrun(
        $project['planned_start_date'],
        $project['planned_end_date'],
        $project['actual_start_date'],
        $actualEndDate
    );
    
    // Update actual end date and overrun percentage
    $updateQuery = "UPDATE construction_projects 
                    SET actual_end_date = :actual_end,
                        actual_time_overrun_percentage = :overrun,
                        status = 'completed',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :project_id";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':actual_end', $actualEndDate);
    $updateStmt->bindParam(':overrun', $overrunPercentage);
    $updateStmt->bindParam(':project_id', $projectId);
    
    if ($updateStmt->execute()) {
        logScheduleChange($db, $projectId, $userId, $userRole, 'actual_end_date', 
                        $project['actual_end_date'], $actualEndDate, 'Project completed');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Project completion recorded successfully',
            'data' => [
                'actual_end_date' => $actualEndDate,
                'actual_time_overrun_percentage' => $overrunPercentage,
                'status' => 'completed'
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update actual end date']);
    }
    exit();
}

// ============================================================================
// GET SCHEDULE AUDIT LOG
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'audit_log') {
    $projectId = $_GET['project_id'] ?? null;
    
    if (!$projectId) {
        echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        exit();
    }
    
    $query = "SELECT 
                psa.*,
                u.first_name,
                u.last_name,
                u.email
              FROM project_schedule_audit psa
              LEFT JOIN users u ON psa.changed_by_user_id = u.id
              WHERE psa.project_id = :project_id
              ORDER BY psa.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $projectId);
    $stmt->execute();
    
    $auditLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $auditLog
    ]);
    exit();
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>

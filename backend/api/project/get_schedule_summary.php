<?php
/**
 * Get Schedule Summary API
 * 
 * Returns schedule tracking information for a project
 * Accessible by contractors, homeowners, and admins
 * Backward compatible - returns null for projects without schedule data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php');
session_start();

try {
    // Verify authentication
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in.'
        ]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Get project ID from query parameter
    if (!isset($_GET['project_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit();
    }

    $project_id = intval($_GET['project_id']);

    $db = Database::getInstance()->getConnection();

    // Build query based on user role
    $access_condition = "";
    $params = [$project_id];

    switch ($user_role) {
        case 'contractor':
            $access_condition = "AND cp.contractor_id = ?";
            $params[] = $user_id;
            break;
        case 'homeowner':
            $access_condition = "AND cp.homeowner_id = ?";
            $params[] = $user_id;
            break;
        case 'admin':
        case 'inspector':
            // Admins and inspectors can view all projects
            break;
        default:
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit();
    }

    // Get schedule summary using the view
    $stmt = $db->prepare("
        SELECT 
            project_id,
            project_name,
            contractor_id,
            homeowner_id,
            status,
            planned_start_date,
            planned_end_date,
            actual_start_date,
            actual_end_date,
            schedule_locked,
            planned_duration_days,
            actual_duration_days,
            delay_days,
            actual_time_overrun_percentage,
            schedule_status,
            created_at,
            updated_at
        FROM project_schedule_summary
        WHERE project_id = ?
        $access_condition
    ");
    
    $stmt->execute($params);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or access denied'
        ]);
        exit();
    }

    // Format response with additional computed fields
    $response = [
        'success' => true,
        'data' => [
            'project_id' => (int)$schedule['project_id'],
            'project_name' => $schedule['project_name'],
            'status' => $schedule['status'],
            'schedule_status' => $schedule['schedule_status'],
            
            // Planned schedule
            'planned' => [
                'start_date' => $schedule['planned_start_date'],
                'end_date' => $schedule['planned_end_date'],
                'duration_days' => $schedule['planned_duration_days'] ? (int)$schedule['planned_duration_days'] : null,
                'is_set' => $schedule['planned_start_date'] !== null && $schedule['planned_end_date'] !== null
            ],
            
            // Actual schedule
            'actual' => [
                'start_date' => $schedule['actual_start_date'],
                'end_date' => $schedule['actual_end_date'],
                'duration_days' => $schedule['actual_duration_days'] ? (int)$schedule['actual_duration_days'] : null,
                'is_started' => $schedule['actual_start_date'] !== null,
                'is_completed' => $schedule['actual_end_date'] !== null
            ],
            
            // Performance metrics
            'performance' => [
                'delay_days' => $schedule['delay_days'] ? (int)$schedule['delay_days'] : null,
                'time_overrun_percentage' => $schedule['actual_time_overrun_percentage'] ? 
                    round((float)$schedule['actual_time_overrun_percentage'], 2) : null,
                'is_delayed' => $schedule['delay_days'] !== null && (int)$schedule['delay_days'] > 0,
                'is_on_time' => $schedule['delay_days'] !== null && (int)$schedule['delay_days'] <= 0
            ],
            
            // Access control
            'permissions' => [
                'can_edit_planned' => $user_role === 'contractor' && $schedule['schedule_locked'] == 0,
                'can_edit_actual' => $user_role === 'contractor',
                'schedule_locked' => $schedule['schedule_locked'] == 1
            ],
            
            'timestamps' => [
                'created_at' => $schedule['created_at'],
                'updated_at' => $schedule['updated_at']
            ]
        ]
    ];

    // Add user-friendly messages
    if ($schedule['schedule_status'] === 'Not Scheduled') {
        $response['data']['message'] = 'No schedule has been set for this project yet.';
    } elseif ($schedule['schedule_status'] === 'Delayed') {
        $delay_days = abs((int)$schedule['delay_days']);
        $response['data']['message'] = "Project is delayed by $delay_days day(s).";
    } elseif ($schedule['schedule_status'] === 'Completed') {
        if ($schedule['delay_days'] && (int)$schedule['delay_days'] > 0) {
            $response['data']['message'] = "Project completed with a delay of " . abs((int)$schedule['delay_days']) . " day(s).";
        } elseif ($schedule['delay_days'] && (int)$schedule['delay_days'] < 0) {
            $response['data']['message'] = "Project completed " . abs((int)$schedule['delay_days']) . " day(s) ahead of schedule!";
        } else {
            $response['data']['message'] = "Project completed on schedule.";
        }
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

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

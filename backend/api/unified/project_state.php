<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get project state for current user
        getProjectState($db, $user_id, $user_role);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update project state
        updateProjectState($db, $user_id, $user_role);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function getProjectState($db, $user_id, $user_role) {
    $projects = [];

    switch ($user_role) {
        case 'homeowner':
            // Get homeowner's requests and their current states
            $query = "SELECT 
                        lr.id as request_id,
                        lr.plot_size,
                        lr.budget_range,
                        lr.location,
                        lr.timeline,
                        lr.status as request_status,
                        lr.created_at,
                        COUNT(DISTINCT lra.id) as total_assignments,
                        COUNT(DISTINCT CASE WHEN lra.status = 'sent' THEN lra.id END) as pending_assignments,
                        COUNT(DISTINCT CASE WHEN lra.status = 'accepted' THEN lra.id END) as accepted_assignments,
                        COUNT(DISTINCT CASE WHEN lra.status = 'declined' THEN lra.id END) as declined_assignments,
                        COUNT(DISTINCT hp.id) as house_plans_count,
                        COUNT(DISTINCT CASE WHEN hp.status = 'submitted' THEN hp.id END) as submitted_plans,
                        MAX(hp.created_at) as latest_plan_date
                      FROM layout_requests lr
                      LEFT JOIN layout_request_assignments lra ON lr.id = lra.layout_request_id
                      LEFT JOIN house_plans hp ON lr.id = hp.layout_request_id
                      WHERE lr.user_id = :user_id AND lr.status != 'deleted'
                      GROUP BY lr.id
                      ORDER BY lr.created_at DESC";
            break;

        case 'architect':
            // Get architect's assigned requests and their states
            $query = "SELECT 
                        lr.id as request_id,
                        lr.plot_size,
                        lr.budget_range,
                        lr.location,
                        lr.timeline,
                        lr.status as request_status,
                        lr.created_at,
                        lra.status as assignment_status,
                        lra.created_at as assigned_at,
                        CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
                        COUNT(DISTINCT hp.id) as house_plans_count,
                        COUNT(DISTINCT CASE WHEN hp.status = 'submitted' THEN hp.id END) as submitted_plans,
                        MAX(hp.created_at) as latest_plan_date
                      FROM layout_request_assignments lra
                      JOIN layout_requests lr ON lr.id = lra.layout_request_id
                      JOIN users u ON lr.user_id = u.id
                      LEFT JOIN house_plans hp ON lr.id = hp.layout_request_id AND hp.architect_id = :user_id
                      WHERE lra.architect_id = :user_id AND lr.status != 'deleted'
                      GROUP BY lr.id, lra.id
                      ORDER BY lra.created_at DESC";
            break;

        case 'contractor':
            // Get contractor's projects
            $query = "SELECT 
                        lr.id as request_id,
                        lr.plot_size,
                        lr.budget_range,
                        lr.location,
                        lr.timeline,
                        lr.status as request_status,
                        lr.created_at,
                        CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
                        'construction' as project_phase
                      FROM layout_requests lr
                      JOIN users u ON lr.user_id = u.id
                      JOIN contractor_layout_sends cls ON lr.id = cls.layout_request_id
                      WHERE cls.contractor_id = :user_id AND lr.status != 'deleted'
                      ORDER BY cls.created_at DESC";
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid user role']);
            return;
    }

    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $project = [
            'request_id' => $row['request_id'],
            'plot_size' => $row['plot_size'],
            'budget_range' => $row['budget_range'],
            'location' => $row['location'],
            'timeline' => $row['timeline'],
            'request_status' => $row['request_status'],
            'created_at' => $row['created_at']
        ];

        // Determine project phase and status
        switch ($user_role) {
            case 'homeowner':
                $project['phase'] = determineHomeownerProjectPhase($row);
                $project['assignments'] = [
                    'total' => (int)$row['total_assignments'],
                    'pending' => (int)$row['pending_assignments'],
                    'accepted' => (int)$row['accepted_assignments'],
                    'declined' => (int)$row['declined_assignments']
                ];
                $project['house_plans'] = [
                    'total' => (int)$row['house_plans_count'],
                    'submitted' => (int)$row['submitted_plans'],
                    'latest_date' => $row['latest_plan_date']
                ];
                break;

            case 'architect':
                $project['phase'] = determineArchitectProjectPhase($row);
                $project['assignment_status'] = $row['assignment_status'];
                $project['assigned_at'] = $row['assigned_at'];
                $project['homeowner_name'] = $row['homeowner_name'];
                $project['house_plans'] = [
                    'total' => (int)$row['house_plans_count'],
                    'submitted' => (int)$row['submitted_plans'],
                    'latest_date' => $row['latest_plan_date']
                ];
                break;

            case 'contractor':
                $project['phase'] = 'construction';
                $project['homeowner_name'] = $row['homeowner_name'];
                break;
        }

        $projects[] = $project;
    }

    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'user_role' => $user_role,
        'total_count' => count($projects)
    ]);
}

function determineHomeownerProjectPhase($row) {
    if ($row['request_status'] === 'pending') {
        return 'awaiting_approval';
    } elseif ($row['request_status'] === 'rejected') {
        return 'rejected';
    } elseif ($row['accepted_assignments'] > 0) {
        if ($row['submitted_plans'] > 0) {
            return 'design_review';
        } else {
            return 'design_in_progress';
        }
    } elseif ($row['total_assignments'] > 0) {
        return 'awaiting_architect_response';
    } else {
        return 'ready_for_assignment';
    }
}

function determineArchitectProjectPhase($row) {
    if ($row['assignment_status'] === 'sent') {
        return 'pending_response';
    } elseif ($row['assignment_status'] === 'declined') {
        return 'declined';
    } elseif ($row['assignment_status'] === 'accepted') {
        if ($row['submitted_plans'] > 0) {
            return 'design_submitted';
        } else {
            return 'design_in_progress';
        }
    }
    return 'unknown';
}

function updateProjectState($db, $user_id, $user_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
    $action = isset($input['action']) ? $input['action'] : '';

    if ($request_id <= 0 || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Request ID and action are required']);
        return;
    }

    switch ($user_role) {
        case 'architect':
            handleArchitectAction($db, $user_id, $request_id, $action, $input);
            break;
        case 'homeowner':
            handleHomeownerAction($db, $user_id, $request_id, $action, $input);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action not allowed for this role']);
    }
}

function handleArchitectAction($db, $architect_id, $request_id, $action, $input) {
    switch ($action) {
        case 'accept_assignment':
        case 'decline_assignment':
            $assignment_id = isset($input['assignment_id']) ? (int)$input['assignment_id'] : 0;
            if ($assignment_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
                return;
            }

            $newStatus = $action === 'accept_assignment' ? 'accepted' : 'declined';
            $stmt = $db->prepare("UPDATE layout_request_assignments SET status = :status WHERE id = :id AND architect_id = :architect_id");
            $result = $stmt->execute([':status' => $newStatus, ':id' => $assignment_id, ':architect_id' => $architect_id]);

            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => "Assignment {$newStatus}", 'new_status' => $newStatus]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update assignment']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}

function handleHomeownerAction($db, $homeowner_id, $request_id, $action, $input) {
    switch ($action) {
        case 'assign_architects':
            $architect_ids = isset($input['architect_ids']) ? $input['architect_ids'] : [];
            // This would call the unified assign_architect.php logic
            echo json_encode(['success' => false, 'message' => 'Use assign_architect.php endpoint']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}
?>
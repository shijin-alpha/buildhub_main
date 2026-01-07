<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    $homeowner_id = $_SESSION['user_id'] ?? null;

    if (!$homeowner_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Verify user is homeowner
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $homeowner_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'homeowner') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $layout_request_id = isset($_GET['layout_request_id']) ? (int)$_GET['layout_request_id'] : null;
    $status = $_GET['status'] ?? null;

    // Build query to get house plans for this homeowner's requests
    $whereConditions = ["lr.user_id = :homeowner_id"];
    $params = [':homeowner_id' => $homeowner_id];

    if ($layout_request_id) {
        $whereConditions[] = "hp.layout_request_id = :layout_request_id";
        $params[':layout_request_id'] = $layout_request_id;
    }

    if ($status && in_array($status, ['draft', 'submitted', 'approved', 'rejected'])) {
        $whereConditions[] = "hp.status = :status";
        $params[':status'] = $status;
    }

    // Only show submitted plans to homeowners (not drafts)
    $whereConditions[] = "hp.status IN ('submitted', 'approved', 'rejected')";

    $whereClause = implode(' AND ', $whereConditions);

    $query = "
        SELECT 
            hp.*,
            lr.plot_size as request_plot_size,
            lr.budget_range,
            lr.requirements,
            CONCAT(u.first_name, ' ', u.last_name) as architect_name,
            u.email as architect_email,
            u.specialization as architect_specialization,
            hpr.status as review_status,
            hpr.feedback as review_feedback,
            hpr.reviewed_at
        FROM house_plans hp
        INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
        INNER JOIN users u ON hp.architect_id = u.id
        LEFT JOIN house_plan_reviews hpr ON hp.id = hpr.house_plan_id AND hpr.homeowner_id = :homeowner_id_review
        WHERE {$whereClause}
        ORDER BY hp.updated_at DESC
    ";

    // Add the second homeowner_id parameter for the LEFT JOIN
    $params[':homeowner_id_review'] = $homeowner_id;

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $plans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse plan_data JSON
        $plan_data = json_decode($row['plan_data'], true) ?? [];
        
        // Parse technical_details JSON
        $technical_details = json_decode($row['technical_details'], true) ?? [];
        
        $plans[] = [
            'id' => (int)$row['id'],
            'plan_name' => $row['plan_name'],
            'layout_request_id' => (int)$row['layout_request_id'],
            'plot_width' => (float)$row['plot_width'],
            'plot_height' => (float)$row['plot_height'],
            'total_area' => (float)$row['total_area'],
            'status' => $row['status'],
            'version' => (int)$row['version'],
            'notes' => $row['notes'],
            'plan_data' => $plan_data,
            'technical_details' => $technical_details,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            // Architect details
            'architect_info' => [
                'name' => $row['architect_name'],
                'email' => $row['architect_email'],
                'specialization' => $row['architect_specialization']
            ],
            // Request details
            'request_info' => [
                'plot_size' => $row['request_plot_size'],
                'budget_range' => $row['budget_range'],
                'requirements' => $row['requirements']
            ],
            // Review status
            'review_info' => [
                'status' => $row['review_status'],
                'feedback' => $row['review_feedback'],
                'reviewed_at' => $row['reviewed_at']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'plans' => $plans,
        'total' => count($plans)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
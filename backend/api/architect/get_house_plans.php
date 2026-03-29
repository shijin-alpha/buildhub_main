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
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $layout_request_id = isset($_GET['layout_request_id']) ? (int)$_GET['layout_request_id'] : null;
    $status = $_GET['status'] ?? null;

    // Build query
    $whereConditions = ["hp.architect_id = :architect_id"];
    $params = [':architect_id' => $architect_id];

    if ($layout_request_id) {
        $whereConditions[] = "hp.layout_request_id = :layout_request_id";
        $params[':layout_request_id'] = $layout_request_id;
    }

    if ($status && in_array($status, ['draft', 'submitted', 'approved', 'rejected'])) {
        $whereConditions[] = "hp.status = :status";
        $params[':status'] = $status;
    }

    $whereClause = implode(' AND ', $whereConditions);

    $query = "
        SELECT 
            hp.*,
            lr.plot_size as request_plot_size,
            lr.budget_range,
            lr.requirements,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
            u.email as homeowner_email
        FROM house_plans hp
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        LEFT JOIN users u ON lr.user_id = u.id
        WHERE {$whereClause}
        ORDER BY hp.updated_at DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $plans = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse plan_data JSON
        $plan_data = json_decode($row['plan_data'], true) ?? [];
        
        $plans[] = [
            'id' => (int)$row['id'],
            'plan_name' => $row['plan_name'],
            'layout_request_id' => $row['layout_request_id'] ? (int)$row['layout_request_id'] : null,
            'plot_width' => (float)$row['plot_width'],
            'plot_height' => (float)$row['plot_height'],
            'total_area' => (float)$row['total_area'],
            'status' => $row['status'],
            'version' => (int)$row['version'],
            'notes' => $row['notes'],
            'plan_data' => $plan_data,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            // Request details if linked
            'request_info' => $row['layout_request_id'] ? [
                'plot_size' => $row['request_plot_size'],
                'budget_range' => $row['budget_range'],
                'requirements' => $row['requirements'],
                'homeowner_name' => $row['homeowner_name'],
                'homeowner_email' => $row['homeowner_email']
            ] : null
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
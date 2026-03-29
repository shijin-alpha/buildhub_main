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

    $plan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
        exit;
    }

    // Get house plan
    $stmt = $db->prepare("
        SELECT hp.*, lr.homeowner_id, lr.site_details, lr.requirements
        FROM house_plans hp
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        WHERE hp.id = :id AND hp.architect_id = :aid
    ");
    
    $stmt->execute([':id' => $plan_id, ':aid' => $architect_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    // Parse plan_data if it's a JSON string
    if (is_string($plan['plan_data'])) {
        $plan['plan_data'] = json_decode($plan['plan_data'], true);
    }

    // Parse site_details if it's a JSON string
    if (is_string($plan['site_details'])) {
        $plan['site_details'] = json_decode($plan['site_details'], true);
    }

    // Parse requirements if it's a JSON string
    if (is_string($plan['requirements'])) {
        $plan['requirements'] = json_decode($plan['requirements'], true);
    }

    echo json_encode([
        'success' => true,
        'plan' => $plan
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
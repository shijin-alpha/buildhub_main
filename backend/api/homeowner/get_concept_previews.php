<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

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

    // Get concept previews for this homeowner's projects
    $previewsStmt = $db->prepare("
        SELECT 
            cp.*,
            lr.plot_size,
            lr.budget_range,
            CONCAT(arch.first_name, ' ', arch.last_name) as architect_name,
            arch.email as architect_email
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users arch ON cp.architect_id = arch.id
        WHERE lr.homeowner_id = :homeowner_id 
        AND cp.status = 'completed'
        ORDER BY cp.created_at DESC
    ");
    
    $previewsStmt->execute([':homeowner_id' => $homeowner_id]);
    $previews = $previewsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each preview to ensure proper URLs
    foreach ($previews as &$preview) {
        // Ensure image URLs are absolute
        if ($preview['image_url'] && !filter_var($preview['image_url'], FILTER_VALIDATE_URL)) {
            $preview['image_url'] = '/buildhub/' . ltrim($preview['image_url'], '/');
        }
    }

    echo json_encode([
        'success' => true,
        'previews' => $previews
    ]);

} catch (Exception $e) {
    error_log("Get homeowner concept previews error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
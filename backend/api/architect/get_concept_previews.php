<?php
// Ensure we always return JSON, even on fatal errors
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $error['message'],
            'error_type' => 'fatal_error'
        ]);
    }
});

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
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Verify user is architect
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $architect_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'architect') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    // Get concept previews for this architect
    $previewsStmt = $db->prepare("
        SELECT 
            cp.*,
            lr.plot_size,
            lr.budget_range,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users u ON lr.homeowner_id = u.id
        WHERE cp.architect_id = :architect_id 
        ORDER BY cp.created_at DESC
    ");
    
    $previewsStmt->execute([':architect_id' => $architect_id]);
    $previews = $previewsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each preview to add additional info
    foreach ($previews as &$preview) {
        // Add time elapsed for processing previews
        if ($preview['status'] === 'processing' || $preview['status'] === 'generating') {
            $createdTime = new DateTime($preview['created_at']);
            $now = new DateTime();
            $elapsed = $now->diff($createdTime);
            
            $preview['elapsed_minutes'] = $elapsed->i + ($elapsed->h * 60);
            $preview['elapsed_display'] = $elapsed->format('%i minutes ago');
            
            // Determine if generation might be stuck (over 10 minutes)
            $preview['possibly_stuck'] = $preview['elapsed_minutes'] > 10;
        }

        // Ensure image URLs are absolute
        if ($preview['image_url'] && !filter_var($preview['image_url'], FILTER_VALIDATE_URL)) {
            // Only add /buildhub/ if it's not already there
            if (strpos($preview['image_url'], '/buildhub/') !== 0) {
                $preview['image_url'] = '/buildhub/' . ltrim($preview['image_url'], '/');
            }
        }
    }

    echo json_encode([
        'success' => true,
        'previews' => $previews
    ]);

} catch (Exception $e) {
    error_log("Get concept previews error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
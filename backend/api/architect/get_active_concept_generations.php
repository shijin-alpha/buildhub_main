<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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

    // Verify user is architect
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $architect_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'architect') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    // Get active concept generations (processing or generating status)
    $activeStmt = $db->prepare("
        SELECT 
            cp.*,
            lr.plot_size,
            lr.budget_range,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users u ON lr.homeowner_id = u.id
        WHERE cp.architect_id = :architect_id 
        AND cp.status IN ('processing', 'generating')
        ORDER BY cp.created_at DESC
    ");
    
    $activeStmt->execute([':architect_id' => $architect_id]);
    $activeGenerations = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add time elapsed for each generation
    foreach ($activeGenerations as &$generation) {
        $createdTime = new DateTime($generation['created_at']);
        $now = new DateTime();
        $elapsed = $now->diff($createdTime);
        
        $generation['elapsed_minutes'] = $elapsed->i + ($elapsed->h * 60);
        $generation['elapsed_display'] = $elapsed->format('%i minutes ago');
        
        // Determine if generation might be stuck (over 5 minutes)
        $generation['possibly_stuck'] = $generation['elapsed_minutes'] > 5;
    }

    echo json_encode([
        'success' => true,
        'active_generations' => $activeGenerations,
        'count' => count($activeGenerations)
    ]);

} catch (Exception $e) {
    error_log("Get active concept generations error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>
<?php
/**
 * Get Simple Receipts API
 * Returns all receipts for a project - accessible by both contractor and homeowner
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? '';
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    // Verify user has access to this project
    if ($user_role === 'contractor') {
        $access_check = $db->prepare("
            SELECT id FROM contractor_send_estimates 
            WHERE id = ? AND contractor_id = ?
        ");
        $access_check->execute([$project_id, $user_id]);
    } else if ($user_role === 'homeowner') {
        $access_check = $db->prepare("
            SELECT id FROM contractor_send_estimates 
            WHERE id = ? AND homeowner_id = ?
        ");
        $access_check->execute([$project_id, $user_id]);
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid user role']);
        exit;
    }
    
    if (!$access_check->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this project']);
        exit;
    }
    
    // Get all receipts for the project
    $receipts_stmt = $db->prepare("
        SELECT 
            sr.id,
            sr.receipt_title,
            sr.description,
            sr.file_path,
            sr.original_filename,
            sr.file_size,
            sr.mime_type,
            sr.upload_date,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name
        FROM simple_receipts sr
        LEFT JOIN users u ON sr.contractor_id = u.id
        WHERE sr.project_id = ?
        ORDER BY sr.upload_date DESC
    ");
    
    $receipts_stmt->execute([$project_id]);
    $receipts = $receipts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format file sizes and add additional info
    foreach ($receipts as &$receipt) {
        $receipt['file_size_formatted'] = formatFileSize($receipt['file_size']);
        $receipt['contractor_name'] = $receipt['contractor_first_name'] . ' ' . $receipt['contractor_last_name'];
        $receipt['upload_date_formatted'] = date('M j, Y g:i A', strtotime($receipt['upload_date']));
        
        // Check if file exists
        $full_path = __DIR__ . '/../' . $receipt['file_path'];
        $receipt['file_exists'] = file_exists($full_path);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'receipts' => $receipts,
            'total_count' => count($receipts)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get simple receipts error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
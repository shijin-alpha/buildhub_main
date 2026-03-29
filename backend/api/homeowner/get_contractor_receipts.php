<?php
/**
 * Get Contractor Receipts API for Homeowners
 * Retrieves all receipts uploaded by contractors for homeowner's projects
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get all contractor receipts for this homeowner's projects
    $stmt = $db->prepare("
        SELECT 
            cr.id,
            cr.project_id,
            cr.contractor_id,
            cr.file_path,
            cr.original_filename,
            cr.file_size,
            cr.mime_type,
            cr.description,
            cr.upload_date,
            cp.project_name,
            cp.project_location,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name,
            u.email as contractor_email
        FROM contractor_receipts cr
        LEFT JOIN construction_projects cp ON cr.project_id = cp.id
        LEFT JOIN users u ON cr.contractor_id = u.id
        WHERE cr.homeowner_id = ?
        ORDER BY cr.upload_date DESC
    ");
    
    $stmt->execute([$homeowner_id]);
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format file sizes and dates
    foreach ($receipts as &$receipt) {
        $receipt['file_size_formatted'] = formatFileSize($receipt['file_size']);
        $receipt['upload_date_formatted'] = date('M j, Y g:i A', strtotime($receipt['upload_date']));
        $receipt['contractor_name'] = $receipt['contractor_first_name'] . ' ' . $receipt['contractor_last_name'];
        
        // Add file type icon
        $receipt['file_icon'] = getFileIcon($receipt['mime_type']);
    }
    
    // Get statistics
    $total_receipts = count($receipts);
    $total_size = array_sum(array_column($receipts, 'file_size'));
    $projects_with_receipts = count(array_unique(array_column($receipts, 'project_id')));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'receipts' => $receipts,
            'statistics' => [
                'total_receipts' => $total_receipts,
                'total_size' => $total_size,
                'total_size_formatted' => formatFileSize($total_size),
                'projects_with_receipts' => $projects_with_receipts
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get contractor receipts error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch receipts: ' . $e->getMessage()
    ]);
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

function getFileIcon($mime_type) {
    switch ($mime_type) {
        case 'application/pdf':
            return 'pdf';
        case 'image/jpeg':
        case 'image/jpg':
        case 'image/png':
            return 'image';
        default:
            return 'file';
    }
}
?>
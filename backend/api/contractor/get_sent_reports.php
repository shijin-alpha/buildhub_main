<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once __DIR__ . '/../../config/database.php';
    
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }
    
    $contractor_id = $_SESSION['user_id'];
    
    // Get query parameters
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
    $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Build base query to fetch daily progress updates
    $query = "
        SELECT 
            dpu.*,
            COALESCE(
                CONCAT(lr.plot_size, ' - ', lr.preferred_style, ' Style'),
                CONCAT('Project ', dpu.project_id)
            ) as project_name,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
            u.email as homeowner_email,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email
        FROM daily_progress_updates dpu
        LEFT JOIN layout_requests lr ON dpu.project_id = lr.id AND lr.status != 'deleted'
        LEFT JOIN users u ON dpu.homeowner_id = u.id
        LEFT JOIN users c ON dpu.contractor_id = c.id
        WHERE dpu.contractor_id = :contractor_id
    ";
    
    $params = [':contractor_id' => $contractor_id];
    
    // Add filters
    if ($project_id) {
        $query .= " AND dpu.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    
    // For report_type filter, we'll treat all daily updates as "daily" reports
    if ($report_type && $report_type !== 'daily') {
        // If they're looking for weekly/monthly, return empty results for now
        $query .= " AND 1 = 0";
    }
    
    $query .= " ORDER BY dpu.update_date DESC, dpu.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM daily_progress_updates dpu
        LEFT JOIN layout_requests lr ON dpu.project_id = lr.id AND lr.status != 'deleted'
        WHERE dpu.contractor_id = :contractor_id
    ";
    
    $countParams = [':contractor_id' => $contractor_id];
    
    if ($project_id) {
        $countQuery .= " AND dpu.project_id = :project_id";
        $countParams[':project_id'] = $project_id;
    }
    
    if ($report_type && $report_type !== 'daily') {
        $countQuery .= " AND 1 = 0";
    }
    
    $countStmt = $db->prepare($countQuery);
    foreach ($countParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process updates into report format
    $processedReports = [];
    foreach ($updates as $update) {
        // Parse progress photos if they exist
        $photos = [];
        if (!empty($update['progress_photos'])) {
            $photoData = json_decode($update['progress_photos'], true);
            if (is_array($photoData)) {
                $photos = $photoData;
            }
        }
        
        $processedReports[] = [
            'id' => $update['id'],
            'project_id' => $update['project_id'],
            'project_name' => substr($update['project_name'], 0, 50) . (strlen($update['project_name']) > 50 ? '...' : ''),
            'homeowner_name' => $update['homeowner_name'],
            'homeowner_email' => $update['homeowner_email'],
            'contractor_name' => $update['contractor_name'],
            'report_type' => 'daily', // All daily progress updates are daily reports
            'period_start' => $update['update_date'],
            'period_end' => $update['update_date'],
            'created_at' => $update['created_at'],
            'updated_at' => $update['updated_at'],
            'status' => 'sent', // Daily updates are considered sent
            'homeowner_viewed_at' => null,
            'homeowner_acknowledged_at' => null,
            'acknowledgment_notes' => null,
            
            // Summary data from daily update
            'summary' => [
                'total_days' => 1,
                'total_workers' => 1, // Could be calculated from working_hours
                'total_hours' => floatval($update['working_hours'] ?: 0),
                'progress_percentage' => floatval($update['incremental_completion_percentage'] ?: 0),
                'cumulative_progress' => floatval($update['cumulative_completion_percentage'] ?: 0),
                'total_wages' => 0, // Not available in daily updates
                'photos_count' => count($photos),
                'geo_photos_count' => $update['location_verified'] ? count($photos) : 0
            ],
            
            // Work details
            'work_details' => [
                'construction_stage' => $update['construction_stage'],
                'work_done_today' => $update['work_done_today'],
                'weather_condition' => $update['weather_condition'],
                'site_issues' => $update['site_issues'],
                'working_hours' => floatval($update['working_hours'] ?: 0)
            ],
            
            // Location data
            'location' => [
                'latitude' => $update['latitude'],
                'longitude' => $update['longitude'],
                'verified' => (bool)$update['location_verified']
            ],
            
            // Photos
            'photos' => $photos,
            
            // Cost summary (placeholder - not available in daily updates)
            'costs' => [
                'labour_cost' => 0,
                'material_cost' => 0,
                'equipment_cost' => 0,
                'total_cost' => 0
            ],
            
            // Quality metrics (placeholder)
            'quality' => [
                'safety_score' => 4,
                'quality_score' => 4,
                'schedule_adherence' => 85
            ]
        ];
    }
    
    // Group reports by type for easier frontend handling
    $groupedReports = [
        'daily' => $processedReports, // All are daily reports
        'weekly' => [],
        'monthly' => []
    ];
    
    // Calculate statistics
    $stats = [
        'total_reports' => $totalCount,
        'daily_count' => count($processedReports),
        'weekly_count' => 0,
        'monthly_count' => 0,
        'sent_count' => count($processedReports), // All daily updates are considered sent
        'viewed_count' => 0, // Not tracked for daily updates
        'acknowledged_count' => 0 // Not tracked for daily updates
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'reports' => $processedReports,
            'grouped_reports' => $groupedReports,
            'statistics' => $stats,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get sent reports error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ]);
}
?>
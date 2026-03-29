<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is a homeowner
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'homeowner') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $homeowner_id = $_SESSION['user_id'];
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
    
    // Build query
    $query = "
        SELECT pr.*, 
               c.first_name as contractor_first_name, 
               c.last_name as contractor_last_name,
               lr.requirements as project_name
        FROM progress_reports pr
        LEFT JOIN users c ON pr.contractor_id = c.id
        LEFT JOIN layout_requests lr ON pr.project_id = lr.id
        WHERE pr.homeowner_id = ?
    ";
    
    $params = [$homeowner_id];
    
    if ($project_id) {
        $query .= " AND pr.project_id = ?";
        $params[] = $project_id;
    }
    
    $query .= " ORDER BY pr.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process reports
    $processedReports = [];
    foreach ($reports as $report) {
        $reportData = json_decode($report['report_data'], true);
        
        $processedReports[] = [
            'id' => $report['id'],
            'project_id' => $report['project_id'],
            'contractor_id' => $report['contractor_id'],
            'contractor_name' => trim($report['contractor_first_name'] . ' ' . $report['contractor_last_name']),
            'project_name' => $reportData['project']['name'] ?? substr($report['project_name'], 0, 50) . '...',
            'report_type' => $report['report_type'],
            'period_start' => $report['report_period_start'],
            'period_end' => $report['report_period_end'],
            'created_at' => $report['created_at'],
            'status' => $report['status'],
            'viewed_at' => $report['homeowner_viewed_at'],
            'acknowledged_at' => $report['homeowner_acknowledged_at'],
            'summary' => $reportData['summary'] ?? [],
            'has_photos' => isset($reportData['photos']) && count($reportData['photos']) > 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'reports' => $processedReports,
            'total_count' => count($processedReports)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get progress reports error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
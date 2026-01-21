<?php
// Simulate the API environment
$_SERVER['REQUEST_METHOD'] = 'GET';

// Start session and login as contractor
session_start();
$_SESSION['user_id'] = 29;
$_SESSION['role'] = 'contractor';
$_SESSION['email'] = 'shijinthomas248@gmail.com';

// Capture output without headers
ob_start();

// Include the API logic without headers
require_once 'backend/config/database.php';

try {
    // Check if user is logged in
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
    
    // Build base query
    $query = "
        SELECT 
            pr.*,
            COALESCE(
                CONCAT(lr.plot_size, ' - ', lr.preferred_style, ' Style'),
                CONCAT('Project ', pr.project_id)
            ) as project_name,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
            u.email as homeowner_email
        FROM progress_reports pr
        LEFT JOIN layout_requests lr ON pr.project_id = lr.id AND lr.status != 'deleted'
        LEFT JOIN users u ON pr.homeowner_id = u.id
        WHERE pr.contractor_id = :contractor_id
    ";
    
    $params = [':contractor_id' => $contractor_id];
    
    // Add filters
    if ($project_id) {
        $query .= " AND pr.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    
    if ($report_type && in_array($report_type, ['daily', 'weekly', 'monthly'])) {
        $query .= " AND pr.report_type = :report_type";
        $params[':report_type'] = $report_type;
    }
    
    $query .= " ORDER BY pr.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process reports
    $processedReports = [];
    foreach ($reports as $report) {
        $reportData = json_decode($report['report_data'], true) ?: [];
        
        $processedReports[] = [
            'id' => $report['id'],
            'project_id' => $report['project_id'],
            'project_name' => substr($report['project_name'], 0, 50) . (strlen($report['project_name']) > 50 ? '...' : ''),
            'homeowner_name' => $report['homeowner_name'],
            'homeowner_email' => $report['homeowner_email'],
            'report_type' => $report['report_type'],
            'period_start' => $report['report_period_start'],
            'period_end' => $report['report_period_end'],
            'created_at' => $report['created_at'],
            'updated_at' => $report['updated_at'],
            'status' => $report['status'],
            'homeowner_viewed_at' => $report['homeowner_viewed_at'],
            'homeowner_acknowledged_at' => $report['homeowner_acknowledged_at'],
            'acknowledgment_notes' => $report['acknowledgment_notes'],
            
            // Summary data from report_data JSON
            'summary' => [
                'total_days' => $reportData['summary']['total_days'] ?? 1,
                'total_workers' => $reportData['summary']['total_workers'] ?? 5,
                'total_hours' => $reportData['summary']['total_hours'] ?? 8,
                'progress_percentage' => $reportData['summary']['progress_percentage'] ?? 15,
                'total_wages' => $reportData['summary']['total_wages'] ?? 2500,
                'photos_count' => $reportData['summary']['photos_count'] ?? 3,
                'geo_photos_count' => $reportData['summary']['geo_photos_count'] ?? 3
            ],
            
            // Cost summary
            'costs' => [
                'labour_cost' => $reportData['costs']['labour_cost'] ?? 2500,
                'material_cost' => $reportData['costs']['material_cost'] ?? 1500,
                'equipment_cost' => $reportData['costs']['equipment_cost'] ?? 500,
                'total_cost' => $reportData['costs']['total_cost'] ?? 4500
            ]
        ];
    }
    
    // Group reports by type for easier frontend handling
    $groupedReports = [
        'daily' => [],
        'weekly' => [],
        'monthly' => []
    ];
    
    foreach ($processedReports as $report) {
        $groupedReports[$report['report_type']][] = $report;
    }
    
    // Calculate statistics
    $stats = [
        'total_reports' => count($processedReports),
        'daily_count' => count($groupedReports['daily']),
        'weekly_count' => count($groupedReports['weekly']),
        'monthly_count' => count($groupedReports['monthly']),
        'sent_count' => count(array_filter($processedReports, fn($r) => $r['status'] === 'sent')),
        'viewed_count' => count(array_filter($processedReports, fn($r) => $r['homeowner_viewed_at'] !== null)),
        'acknowledged_count' => count(array_filter($processedReports, fn($r) => $r['homeowner_acknowledged_at'] !== null))
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'reports' => $processedReports,
            'grouped_reports' => $groupedReports,
            'statistics' => $stats,
            'pagination' => [
                'total' => count($processedReports),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => false
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ]);
}

$output = ob_get_clean();

echo "=== API Test Results ===\n";
echo "Session User ID: " . $_SESSION['user_id'] . "\n";
echo "Session Role: " . $_SESSION['role'] . "\n\n";

echo "API Response:\n";
echo $output . "\n\n";

// Parse and display results
$data = json_decode($output, true);
if ($data && $data['success']) {
    echo "=== Parsed Results ===\n";
    echo "Success: true\n";
    echo "Total reports: " . count($data['data']['reports']) . "\n";
    echo "Daily reports: " . count($data['data']['grouped_reports']['daily']) . "\n";
    echo "Statistics: " . json_encode($data['data']['statistics'], JSON_PRETTY_PRINT) . "\n";
    
    if (!empty($data['data']['reports'])) {
        echo "\nFirst report:\n";
        $report = $data['data']['reports'][0];
        echo "- ID: {$report['id']}\n";
        echo "- Project: {$report['project_name']}\n";
        echo "- Type: {$report['report_type']}\n";
        echo "- Status: {$report['status']}\n";
        echo "- Period: {$report['period_start']} to {$report['period_end']}\n";
    }
} else {
    echo "API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}
?>
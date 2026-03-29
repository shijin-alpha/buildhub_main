<?php
/**
 * Get Inspection Reports for Inspector
 * Returns all inspection reports created by the current inspector
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthorizationMiddleware.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user is logged in as admin or inspector
    session_start();
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    
    if (!$isAdmin) {
        // Use authorization middleware for non-admin users
        $auth = new AuthorizationMiddleware($db);
        $auth->requireAuth();
        $auth->requireCapability('view_own_inspection_reports');
        $currentUserId = $auth->getCurrentUser()['id'];
    } else {
        // Admin has full access - set up a mock user ID
        $currentUserId = 1;
    }
    
    // Get filter parameters
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $status = $_GET['status'] ?? 'all';
    $inspectionType = $_GET['inspection_type'] ?? 'all';
    $sortBy = $_GET['sortBy'] ?? 'inspection_date';
    $sortOrder = $_GET['sortOrder'] ?? 'desc';
    
    // Validate sort parameters
    $allowedSortFields = ['inspection_date', 'created_at', 'inspection_type', 'status', 'quality_rating'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'inspection_date';
    }
    
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    // Build query for inspection reports
    $query = "
        SELECT 
            sir.id,
            sir.project_id,
            sir.inspection_date,
            sir.inspection_type,
            sir.stage_name,
            sir.completion_status,
            sir.quality_rating,
            sir.safety_compliance,
            sir.observations,
            sir.recommendations,
            sir.issues_identified,
            sir.corrective_actions,
            sir.photos,
            sir.documents,
            sir.next_inspection_date,
            sir.status,
            sir.submitted_at,
            sir.reviewed_by,
            sir.reviewed_at,
            sir.review_notes,
            sir.created_at,
            sir.updated_at,
            cp.project_name,
            cp.project_location,
            cp.homeowner_name,
            CONCAT(reviewer.first_name, ' ', reviewer.last_name) as reviewed_by_name
        FROM site_inspection_reports sir
        JOIN construction_projects cp ON sir.project_id = cp.id
        LEFT JOIN users reviewer ON sir.reviewed_by = reviewer.id
        WHERE sir.inspector_id = :inspector_id
    ";
    
    $params = [':inspector_id' => $currentUserId];
    
    // Add project filter if specified
    if ($projectId) {
        // Verify project access (only for non-admin users)
        if (!$isAdmin && isset($auth)) {
            $auth->requireProjectAccess($projectId);
        }
        $query .= " AND sir.project_id = :project_id";
        $params[':project_id'] = $projectId;
    }
    
    // Add status filter
    if ($status !== 'all') {
        $query .= " AND sir.status = :status";
        $params[':status'] = $status;
    }
    
    // Add inspection type filter
    if ($inspectionType !== 'all') {
        $query .= " AND sir.inspection_type = :inspection_type";
        $params[':inspection_type'] = $inspectionType;
    }
    
    // Add sorting
    $query .= " ORDER BY sir." . $sortBy . " " . $sortOrder;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response data
    $formattedReports = [];
    foreach ($reports as $report) {
        $formattedReports[] = [
            'id' => (int)$report['id'],
            'project' => [
                'id' => (int)$report['project_id'],
                'name' => $report['project_name'],
                'location' => $report['project_location'],
                'homeowner_name' => $report['homeowner_name']
            ],
            'inspection_date' => $report['inspection_date'],
            'inspection_type' => $report['inspection_type'],
            'stage_name' => $report['stage_name'],
            'completion_status' => $report['completion_status'],
            'quality_rating' => $report['quality_rating'] ? (int)$report['quality_rating'] : null,
            'safety_compliance' => $report['safety_compliance'],
            'observations' => $report['observations'],
            'recommendations' => $report['recommendations'],
            'issues_identified' => $report['issues_identified'],
            'corrective_actions' => $report['corrective_actions'],
            'photos' => $report['photos'] ? json_decode($report['photos'], true) : [],
            'documents' => $report['documents'] ? json_decode($report['documents'], true) : [],
            'next_inspection_date' => $report['next_inspection_date'],
            'status' => $report['status'],
            'submitted_at' => $report['submitted_at'],
            'review' => [
                'reviewed_by' => $report['reviewed_by_name'],
                'reviewed_at' => $report['reviewed_at'],
                'review_notes' => $report['review_notes']
            ],
            'created_at' => $report['created_at'],
            'updated_at' => $report['updated_at']
        ];
    }
    
    // Get summary statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_reports,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_reports,
            COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_reports,
            COUNT(CASE WHEN status = 'reviewed' THEN 1 END) as reviewed_reports,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reports,
            AVG(quality_rating) as avg_quality_rating,
            COUNT(CASE WHEN safety_compliance = 'non_compliant' THEN 1 END) as safety_issues
        FROM site_inspection_reports
        WHERE inspector_id = :inspector_id
    ";
    
    if ($projectId) {
        $statsQuery .= " AND project_id = :project_id";
    }
    
    $statsStmt = $db->prepare($statsQuery);
    $statsParams = [':inspector_id' => $currentUserId];
    if ($projectId) {
        $statsParams[':project_id'] = $projectId;
    }
    $statsStmt->execute($statsParams);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the action (only for authenticated users)
    if (!$isAdmin && isset($auth)) {
        $auth->logAction('view_inspection_reports', $projectId, 'inspection_reports', null, [
            'project_filter' => $projectId,
            'status_filter' => $status,
            'report_count' => count($formattedReports)
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'reports' => $formattedReports,
        'statistics' => [
            'total_reports' => (int)$stats['total_reports'],
            'draft_reports' => (int)$stats['draft_reports'],
            'submitted_reports' => (int)$stats['submitted_reports'],
            'reviewed_reports' => (int)$stats['reviewed_reports'],
            'approved_reports' => (int)$stats['approved_reports'],
            'avg_quality_rating' => $stats['avg_quality_rating'] ? round((float)$stats['avg_quality_rating'], 2) : null,
            'safety_issues' => (int)$stats['safety_issues']
        ],
        'filters' => [
            'project_id' => $projectId,
            'status' => $status,
            'inspection_type' => $inspectionType,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching inspection reports: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch inspection reports'
    ]);
}
?>
<?php
/**
 * Get Site Notes for Inspector
 * Returns all site notes created by the current inspector
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
        $auth->requireCapability('view_site_notes');
        $currentUserId = $auth->getCurrentUser()['id'];
    } else {
        // Admin has full access - set up a mock user ID
        $currentUserId = 1;
    }
    
    // Get filter parameters
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $noteType = $_GET['note_type'] ?? 'all';
    $priority = $_GET['priority'] ?? 'all';
    $resolved = $_GET['resolved'] ?? 'all';
    $sortBy = $_GET['sortBy'] ?? 'created_at';
    $sortOrder = $_GET['sortOrder'] ?? 'desc';
    
    // Validate sort parameters
    $allowedSortFields = ['created_at', 'updated_at', 'priority', 'note_type', 'title'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }
    
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    // Build query for site notes
    $query = "
        SELECT 
            sn.id,
            sn.project_id,
            sn.note_type,
            sn.priority,
            sn.title,
            sn.content,
            sn.location,
            sn.photos,
            sn.tags,
            sn.is_resolved,
            sn.resolved_at,
            sn.resolved_by,
            sn.resolution_notes,
            sn.visibility,
            sn.created_at,
            sn.updated_at,
            cp.project_name,
            cp.project_location,
            cp.homeowner_name,
            CONCAT(resolver.first_name, ' ', resolver.last_name) as resolved_by_name
        FROM site_notes sn
        JOIN construction_projects cp ON sn.project_id = cp.id
        LEFT JOIN users resolver ON sn.resolved_by = resolver.id
        WHERE sn.inspector_id = :inspector_id
    ";
    
    $params = [':inspector_id' => $currentUserId];
    
    // Add project filter if specified
    if ($projectId) {
        // Verify project access (only for non-admin users)
        if (!$isAdmin && isset($auth)) {
            $auth->requireProjectAccess($projectId);
        }
        $query .= " AND sn.project_id = :project_id";
        $params[':project_id'] = $projectId;
    }
    
    // Add note type filter
    if ($noteType !== 'all') {
        $query .= " AND sn.note_type = :note_type";
        $params[':note_type'] = $noteType;
    }
    
    // Add priority filter
    if ($priority !== 'all') {
        $query .= " AND sn.priority = :priority";
        $params[':priority'] = $priority;
    }
    
    // Add resolved filter
    if ($resolved !== 'all') {
        $isResolved = $resolved === 'resolved' ? 1 : 0;
        $query .= " AND sn.is_resolved = :is_resolved";
        $params[':is_resolved'] = $isResolved;
    }
    
    // Add sorting
    $query .= " ORDER BY sn." . $sortBy . " " . $sortOrder;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response data
    $formattedNotes = [];
    foreach ($notes as $note) {
        $formattedNotes[] = [
            'id' => (int)$note['id'],
            'project' => [
                'id' => (int)$note['project_id'],
                'name' => $note['project_name'],
                'location' => $note['project_location'],
                'homeowner_name' => $note['homeowner_name']
            ],
            'note_type' => $note['note_type'],
            'priority' => $note['priority'],
            'title' => $note['title'],
            'content' => $note['content'],
            'location' => $note['location'],
            'photos' => $note['photos'] ? json_decode($note['photos'], true) : [],
            'tags' => $note['tags'] ? explode(',', $note['tags']) : [],
            'is_resolved' => (bool)$note['is_resolved'],
            'resolved_at' => $note['resolved_at'],
            'resolved_by' => $note['resolved_by_name'],
            'resolution_notes' => $note['resolution_notes'],
            'visibility' => $note['visibility'],
            'created_at' => $note['created_at'],
            'updated_at' => $note['updated_at']
        ];
    }
    
    // Get summary statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_notes,
            COUNT(CASE WHEN note_type = 'issue' THEN 1 END) as issue_notes,
            COUNT(CASE WHEN note_type = 'observation' THEN 1 END) as observation_notes,
            COUNT(CASE WHEN note_type = 'safety' THEN 1 END) as safety_notes,
            COUNT(CASE WHEN note_type = 'quality' THEN 1 END) as quality_notes,
            COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_notes,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_notes,
            COUNT(CASE WHEN is_resolved = 0 THEN 1 END) as unresolved_notes,
            COUNT(CASE WHEN is_resolved = 1 THEN 1 END) as resolved_notes
        FROM site_notes
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
        $auth->logAction('view_site_notes', $projectId, 'site_notes', null, [
            'project_filter' => $projectId,
            'note_type_filter' => $noteType,
            'priority_filter' => $priority,
            'note_count' => count($formattedNotes)
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'notes' => $formattedNotes,
        'statistics' => [
            'total_notes' => (int)$stats['total_notes'],
            'issue_notes' => (int)$stats['issue_notes'],
            'observation_notes' => (int)$stats['observation_notes'],
            'safety_notes' => (int)$stats['safety_notes'],
            'quality_notes' => (int)$stats['quality_notes'],
            'critical_notes' => (int)$stats['critical_notes'],
            'high_priority_notes' => (int)$stats['high_priority_notes'],
            'unresolved_notes' => (int)$stats['unresolved_notes'],
            'resolved_notes' => (int)$stats['resolved_notes']
        ],
        'filters' => [
            'project_id' => $projectId,
            'note_type' => $noteType,
            'priority' => $priority,
            'resolved' => $resolved,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching site notes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch site notes'
    ]);
}
?>
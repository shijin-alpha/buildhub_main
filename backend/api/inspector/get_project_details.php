<?php
/**
 * Get Project Details for Inspector
 * Returns detailed information about a specific project assigned to the inspector
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
        $auth->requireCapability('view_project_details');
        $currentUserId = $auth->getCurrentUser()['id'];
    } else {
        // Admin has full access - set up a mock user ID
        $currentUserId = 1;
    }
    
    // Get project ID from query parameter
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    
    if ($projectId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid project ID'
        ]);
        exit;
    }
    
    // Verify project access
    if (!$isAdmin && isset($auth)) {
        // For inspectors, verify they have access to this project
        $auth->requireProjectAccess($projectId);
    }
    // Admins have access to all projects
    
    // Get detailed project information
    $projectQuery = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.project_description,
            cp.status,
            cp.current_stage,
            cp.completion_percentage,
            cp.project_location,
            cp.homeowner_name,
            cp.homeowner_email,
            cp.homeowner_phone,
            cp.start_date,
            cp.expected_completion_date,
            cp.actual_completion_date,
            cp.total_cost,
            cp.timeline,
            cp.structured_data,
            cp.technical_details,
            cp.layout_images,
            cp.created_at,
            cp.updated_at,
            -- Contractor information
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email,
            c.phone as contractor_phone,
            c.company_name as contractor_company,
            -- Assignment information
            ipa.assigned_at,
            ipa.notes as assignment_notes,
            CONCAT(admin.first_name, ' ', admin.last_name) as assigned_by_name
        FROM construction_projects cp
        LEFT JOIN users c ON cp.contractor_id = c.id
        LEFT JOIN inspector_project_assignments ipa ON cp.id = ipa.project_id AND ipa.inspector_id = :inspector_id
        LEFT JOIN users admin ON ipa.assigned_by = admin.id
        WHERE cp.id = :project_id
    ";
    
    $projectStmt = $db->prepare($projectQuery);
    $projectStmt->execute([
        ':project_id' => $projectId,
        ':inspector_id' => $currentUserId
    ]);
    
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or access denied'
        ]);
        exit;
    }
    
    // Get inspection reports for this project
    if ($isAdmin) {
        // Admin can see all inspection reports for the project
        $reportsQuery = "
            SELECT 
                ir.id, ir.inspection_date, ir.inspection_type, ir.inspection_stage,
                ir.overall_status, ir.quality_score, ir.safety_compliance,
                ir.notes, ir.recommendations, ir.issues_identified,
                ir.corrective_actions_required, ir.next_inspection_date,
                ir.created_at, ir.updated_at,
                CONCAT(inspector.first_name, ' ', inspector.last_name) as inspector_name,
                inspector.email as inspector_email
            FROM inspection_reports ir
            LEFT JOIN users inspector ON ir.inspector_id = inspector.id
            WHERE ir.project_id = :project_id
            ORDER BY ir.inspection_date DESC, ir.created_at DESC
        ";
        
        $reportsStmt = $db->prepare($reportsQuery);
        $reportsStmt->execute([':project_id' => $projectId]);
    } else {
        // Inspector can only see their own inspection reports
        $reportsQuery = "
            SELECT 
                id, inspection_date, inspection_type, inspection_stage,
                overall_status, quality_score, safety_compliance,
                notes, recommendations, issues_identified,
                corrective_actions_required, next_inspection_date,
                created_at, updated_at
            FROM inspection_reports
            WHERE project_id = :project_id AND inspector_id = :inspector_id
            ORDER BY inspection_date DESC, created_at DESC
        ";
        
        $reportsStmt = $db->prepare($reportsQuery);
        $reportsStmt->execute([
            ':project_id' => $projectId,
            ':inspector_id' => $currentUserId
        ]);
    }
    
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get site notes for this project
    $notesQuery = "
        SELECT 
            id, note_type, priority, title, content, location,
            tags, is_resolved, resolved_at, resolution_notes,
            visibility, created_at, updated_at
        FROM site_notes
        WHERE project_id = :project_id AND inspector_id = :inspector_id
        ORDER BY created_at DESC
    ";
    
    $notesStmt = $db->prepare($notesQuery);
    $notesStmt->execute([
        ':project_id' => $projectId,
        ':inspector_id' => $currentUserId
    ]);
    
    $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment requests for this project
    $paymentsQuery = "
        SELECT 
            id, stage_name, requested_amount, approved_amount,
            status, verification_status, completion_percentage,
            work_description, created_at
        FROM stage_payment_requests
        WHERE project_id = :project_id
        ORDER BY created_at DESC
    ";
    
    $paymentsStmt = $db->prepare($paymentsQuery);
    $paymentsStmt->execute([':project_id' => $projectId]);
    $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse structured data for cost breakdown
    $costBreakdown = null;
    if ($project['structured_data']) {
        $structuredData = json_decode($project['structured_data'], true);
        if ($structuredData && isset($structuredData['totals'])) {
            $costBreakdown = [
                'materials' => $structuredData['totals']['materials'] ?? '0',
                'labor' => $structuredData['totals']['labor'] ?? '0',
                'utilities' => $structuredData['totals']['utilities'] ?? '0',
                'misc' => $structuredData['totals']['misc'] ?? '0',
                'grand_total' => $structuredData['totals']['grand'] ?? '0'
            ];
        }
    }
    
    // Parse technical details
    $technicalDetails = null;
    if ($project['technical_details']) {
        $technicalDetails = json_decode($project['technical_details'], true);
    }
    
    // Parse layout images
    $layoutImages = [];
    if ($project['layout_images']) {
        $layoutImages = json_decode($project['layout_images'], true) ?: [];
    }
    
    // Format the response
    $formattedProject = [
        'id' => (int)$project['id'],
        'project_name' => $project['project_name'],
        'project_description' => $project['project_description'],
        'status' => $project['status'],
        'current_stage' => $project['current_stage'],
        'completion_percentage' => (float)$project['completion_percentage'],
        'project_location' => $project['project_location'],
        'timeline' => $project['timeline'],
        'total_cost' => $project['total_cost'] ? (float)$project['total_cost'] : null,
        'cost_breakdown' => $costBreakdown,
        'dates' => [
            'start_date' => $project['start_date'],
            'expected_completion' => $project['expected_completion_date'],
            'actual_completion' => $project['actual_completion_date'],
            'created_at' => $project['created_at'],
            'updated_at' => $project['updated_at']
        ],
        'homeowner' => [
            'name' => $project['homeowner_name'],
            'email' => $project['homeowner_email'],
            'phone' => $project['homeowner_phone']
        ],
        'contractor' => [
            'name' => $project['contractor_name'],
            'email' => $project['contractor_email'],
            'phone' => $project['contractor_phone'],
            'company' => $project['contractor_company']
        ],
        'assignment' => [
            'assigned_at' => $project['assigned_at'],
            'assigned_by' => $project['assigned_by_name'],
            'notes' => $project['assignment_notes']
        ],
        'technical_details' => $technicalDetails,
        'layout_images' => $layoutImages,
        'statistics' => [
            'total_reports' => count($reports),
            'total_notes' => count($notes),
            'unresolved_notes' => count(array_filter($notes, function($note) {
                return !$note['is_resolved'];
            })),
            'payment_requests' => count($payments)
        ]
    ];
    
    // Format inspection reports
    $formattedReports = [];
    foreach ($reports as $report) {
        $formattedReports[] = [
            'id' => (int)$report['id'],
            'inspection_date' => $report['inspection_date'],
            'inspection_type' => $report['inspection_type'],
            'inspection_stage' => $report['inspection_stage'],
            'overall_status' => $report['overall_status'],
            'quality_score' => $report['quality_score'] ? (float)$report['quality_score'] : null,
            'safety_compliance' => $report['safety_compliance'],
            'notes' => $report['notes'],
            'recommendations' => $report['recommendations'],
            'issues_identified' => $report['issues_identified'],
            'corrective_actions_required' => $report['corrective_actions_required'],
            'next_inspection_date' => $report['next_inspection_date'],
            'created_at' => $report['created_at'],
            'updated_at' => $report['updated_at']
        ];
    }
    
    // Format site notes
    $formattedNotes = [];
    foreach ($notes as $note) {
        $formattedNotes[] = [
            'id' => (int)$note['id'],
            'note_type' => $note['note_type'],
            'priority' => $note['priority'],
            'title' => $note['title'],
            'content' => $note['content'],
            'location' => $note['location'],
            'tags' => $note['tags'] ? explode(',', $note['tags']) : [],
            'is_resolved' => (bool)$note['is_resolved'],
            'resolved_at' => $note['resolved_at'],
            'resolution_notes' => $note['resolution_notes'],
            'visibility' => $note['visibility'],
            'created_at' => $note['created_at'],
            'updated_at' => $note['updated_at']
        ];
    }
    
    // Format payment requests
    $formattedPayments = [];
    foreach ($payments as $payment) {
        $formattedPayments[] = [
            'id' => (int)$payment['id'],
            'stage_name' => $payment['stage_name'],
            'requested_amount' => (float)$payment['requested_amount'],
            'approved_amount' => $payment['approved_amount'] ? (float)$payment['approved_amount'] : null,
            'status' => $payment['status'],
            'verification_status' => $payment['verification_status'],
            'completion_percentage' => (float)$payment['completion_percentage'],
            'work_description' => $payment['work_description'],
            'created_at' => $payment['created_at']
        ];
    }
    
    // Log the action (only for authenticated users)
    if (!$isAdmin && isset($auth)) {
        $auth->logAction('view_project_details', $projectId, 'project', $projectId, [
            'project_name' => $project['project_name'],
            'reports_count' => count($reports),
            'notes_count' => count($notes)
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'project' => $formattedProject,
        'inspection_reports' => $formattedReports,
        'site_notes' => $formattedNotes,
        'payment_requests' => $formattedPayments
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching project details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch project details',
        'error' => $e->getMessage()
    ]);
}
?>
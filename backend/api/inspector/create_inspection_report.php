<?php
/**
 * Create new inspection report
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a site inspector OR admin
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isSiteInspector = isset($_SESSION['user_id']) && $_SESSION['role'] === 'site_inspector';

if (!$isAdmin && !$isSiteInspector) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['project_id', 'inspection_date', 'inspection_stage', 'inspection_type', 'overall_status'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // For admin, we need to get or assign an inspector ID
    if ($isAdmin) {
        // Admin can create reports, but we need an inspector ID
        // Check if there's an assigned inspector for this project
        $inspector_query = "SELECT inspector_id FROM site_inspector_assignments 
                           WHERE project_id = ? AND status = 'active' LIMIT 1";
        $inspector_stmt = $db->prepare($inspector_query);
        $inspector_stmt->execute([$input['project_id']]);
        $assigned_inspector = $inspector_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($assigned_inspector) {
            $inspector_id = $assigned_inspector['inspector_id'];
        } else {
            // If no inspector assigned, we can't create a report
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No site inspector assigned to this project']);
            exit;
        }
    } else {
        // Site inspector uses their own ID
        $inspector_id = $_SESSION['user_id'];
        
        // Verify inspector has access to this project
        $access_query = "SELECT COUNT(*) FROM site_inspector_assignments 
                         WHERE inspector_id = ? AND project_id = ? AND status = 'active'";
        $access_stmt = $db->prepare($access_query);
        $access_stmt->execute([$inspector_id, $input['project_id']]);
        
        if ($access_stmt->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this project']);
            exit;
        }
    }
    
    $db->beginTransaction();
    
    // Insert inspection report
    $report_query = "INSERT INTO inspection_reports 
                     (project_id, inspector_id, inspection_date, inspection_stage, inspection_type, 
                      overall_status, quality_score, safety_compliance, notes, recommendations, next_inspection_date,
                      inspection_time, weather_conditions, temperature, site_accessibility, work_progress_since_last,
                      materials_on_site, equipment_on_site, workforce_present, safety_equipment_available,
                      safety_violations_found, structural_integrity, workmanship_quality, code_compliance,
                      environmental_impact, waste_management, site_cleanliness, access_roads_condition,
                      utilities_status, security_measures, issues_identified, corrective_actions_required,
                      follow_up_required, inspector_signature, contractor_present, contractor_representative,
                      homeowner_notified)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $report_stmt = $db->prepare($report_query);
    $report_stmt->execute([
        $input['project_id'],
        $inspector_id,
        $input['inspection_date'],
        $input['inspection_stage'],
        $input['inspection_type'],
        $input['overall_status'],
        $input['quality_score'] ?? null,
        $input['safety_compliance'] ?? 'compliant',
        $input['notes'] ?? null,
        $input['recommendations'] ?? null,
        $input['next_inspection_date'] ?? null,
        $input['inspection_time'] ?? null,
        $input['weather_conditions'] ?? null,
        $input['temperature'] ?? null,
        $input['site_accessibility'] ?? 'good',
        $input['work_progress_since_last'] ?? null,
        $input['materials_on_site'] ?? null,
        $input['equipment_on_site'] ?? null,
        $input['workforce_present'] ?? null,
        $input['safety_equipment_available'] ?? 'yes',
        $input['safety_violations_found'] ?? 'no',
        $input['structural_integrity'] ?? 'satisfactory',
        $input['workmanship_quality'] ?? 'good',
        $input['code_compliance'] ?? 'compliant',
        $input['environmental_impact'] ?? 'minimal',
        $input['waste_management'] ?? 'proper',
        $input['site_cleanliness'] ?? 'good',
        $input['access_roads_condition'] ?? 'good',
        $input['utilities_status'] ?? 'operational',
        $input['security_measures'] ?? 'adequate',
        $input['issues_identified'] ?? null,
        $input['corrective_actions_required'] ?? null,
        $input['follow_up_required'] ?? 'no',
        $input['inspector_signature'] ?? null,
        $input['contractor_present'] ?? 'no',
        $input['contractor_representative'] ?? null,
        $input['homeowner_notified'] ?? 'no'
    ]);
    
    $inspection_report_id = $db->lastInsertId();
    
    // Insert checklist items if provided
    if (isset($input['checklist_items']) && is_array($input['checklist_items'])) {
        $checklist_query = "INSERT INTO inspection_checklist_items 
                           (inspection_report_id, category, item_description, status, notes, priority)
                           VALUES (?, ?, ?, ?, ?, ?)";
        $checklist_stmt = $db->prepare($checklist_query);
        
        foreach ($input['checklist_items'] as $item) {
            $checklist_stmt->execute([
                $inspection_report_id,
                $item['category'],
                $item['item_description'],
                $item['status'],
                $item['notes'] ?? null,
                $item['priority'] ?? 'medium'
            ]);
        }
    }
    
    // Get project details for notifications
    $project_query = "SELECT cp.project_name, cp.homeowner_id, cp.contractor_id 
                      FROM construction_projects cp 
                      WHERE cp.id = ?";
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([$input['project_id']]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notifications for homeowner and contractor
    $notification_query = "INSERT INTO inspection_notifications 
                          (inspection_report_id, recipient_id, recipient_type, notification_type, title, message)
                          VALUES (?, ?, ?, ?, ?, ?)";
    $notification_stmt = $db->prepare($notification_query);
    
    $title = "Inspection Report - " . $project['project_name'];
    $message = "A new inspection report has been submitted for your project. Status: " . $input['overall_status'];
    
    // Notify homeowner
    $notification_stmt->execute([
        $inspection_report_id,
        $project['homeowner_id'],
        'homeowner',
        'inspection_completed',
        $title,
        $message
    ]);
    
    // Notify contractor
    $notification_stmt->execute([
        $inspection_report_id,
        $project['contractor_id'],
        'contractor',
        'inspection_completed',
        $title,
        $message
    ]);
    
    // If status is rejected or needs attention, notify admin
    if (in_array($input['overall_status'], ['rejected', 'needs_attention'])) {
        // Get admin users (assuming role 'admin' exists or use a specific admin ID)
        $admin_query = "SELECT id FROM users WHERE role = 'admin' OR email LIKE '%admin%' LIMIT 1";
        $admin_stmt = $db->prepare($admin_query);
        $admin_stmt->execute();
        $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $notification_stmt->execute([
                $inspection_report_id,
                $admin['id'],
                'admin',
                'approval_required',
                "Attention Required - " . $project['project_name'],
                "Inspection report requires admin attention. Status: " . $input['overall_status']
            ]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Inspection report created successfully',
        'inspection_report_id' => $inspection_report_id
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Error creating inspection report: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create inspection report'
    ]);
}
?>
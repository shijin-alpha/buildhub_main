<?php
/**
 * Assign site inspector to project (Admin function)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is admin
$isAdmin = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
} elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isAdmin = true;
}

if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['inspector_id']) || !isset($input['project_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Inspector ID and Project ID are required']);
        exit;
    }
    
    $inspector_id = (int)$input['inspector_id'];
    $project_id = (int)$input['project_id'];
    $notes = $input['notes'] ?? null;
    $assigned_by = $_SESSION['user_id'] ?? 1; // Default to admin user ID 1
    
    // Verify inspector exists and has correct role
    $inspector_check = "SELECT id, first_name, last_name, email FROM users 
                       WHERE id = ? AND role = 'site_inspector' AND is_verified = 1";
    $inspector_stmt = $db->prepare($inspector_check);
    $inspector_stmt->execute([$inspector_id]);
    $inspector = $inspector_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspector) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid inspector or inspector not verified']);
        exit;
    }
    
    // Verify project exists
    $project_check = "SELECT id, project_name FROM construction_projects WHERE id = ?";
    $project_stmt = $db->prepare($project_check);
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    // Check if inspector is already assigned to this project
    $existing_check = "SELECT id, status FROM site_inspector_assignments 
                       WHERE inspector_id = ? AND project_id = ?";
    $existing_stmt = $db->prepare($existing_check);
    $existing_stmt->execute([$inspector_id, $project_id]);
    $existing = $existing_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'Inspector is already assigned to this project']);
            exit;
        } else {
            // Reactivate existing assignment
            $update_query = "UPDATE site_inspector_assignments 
                           SET status = 'active', assigned_date = CURRENT_TIMESTAMP, 
                               assigned_by = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$assigned_by, $notes, $existing['id']]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Inspector assignment reactivated successfully',
                'assignment_id' => $existing['id']
            ]);
            exit;
        }
    }
    
    // Create new assignment
    $insert_query = "INSERT INTO site_inspector_assignments 
                     (inspector_id, project_id, assigned_by, notes, status) 
                     VALUES (?, ?, ?, ?, 'active')";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->execute([$inspector_id, $project_id, $assigned_by, $notes]);
    
    $assignment_id = $db->lastInsertId();
    
    // Log the assignment action
    $log_query = "INSERT INTO admin_logs (admin_id, action, details, created_at) 
                  VALUES (?, 'assign_inspector', ?, CURRENT_TIMESTAMP)";
    $log_stmt = $db->prepare($log_query);
    $log_details = json_encode([
        'inspector_id' => $inspector_id,
        'inspector_name' => $inspector['first_name'] . ' ' . $inspector['last_name'],
        'project_id' => $project_id,
        'project_name' => $project['project_name'],
        'assignment_id' => $assignment_id
    ]);
    $log_stmt->execute([$assigned_by, $log_details]);
    
    // Send notification to inspector (optional)
    try {
        $notification_query = "INSERT INTO inspection_notifications 
                              (inspection_report_id, recipient_id, recipient_type, notification_type, 
                               title, message, created_at) 
                              VALUES (NULL, ?, 'inspector', 'assignment_created', ?, ?, CURRENT_TIMESTAMP)";
        $notification_stmt = $db->prepare($notification_query);
        $notification_title = "New Project Assignment";
        $notification_message = "You have been assigned to inspect project: " . $project['project_name'];
        $notification_stmt->execute([$inspector_id, $notification_title, $notification_message]);
    } catch (Exception $e) {
        // Notification failed but assignment succeeded - log but don't fail
        error_log("Failed to send inspector notification: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Inspector assigned successfully',
        'assignment_id' => $assignment_id,
        'inspector' => $inspector,
        'project' => $project
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in assign_site_inspector.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in assign_site_inspector.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>
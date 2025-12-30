<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get report details
        $report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : null;
        
        if (!$report_id) {
            echo json_encode(['success' => false, 'message' => 'Report ID is required']);
            exit;
        }
        
        // Get report
        $stmt = $pdo->prepare("
            SELECT pr.*, 
                   c.first_name as contractor_first_name, 
                   c.last_name as contractor_last_name,
                   c.email as contractor_email,
                   cr.requirements as project_requirements
            FROM progress_reports pr
            LEFT JOIN users c ON pr.contractor_id = c.id
            LEFT JOIN contractor_requests cr ON pr.project_id = cr.id
            WHERE pr.id = ? AND pr.homeowner_id = ?
        ");
        $stmt->execute([$report_id, $homeowner_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            exit;
        }
        
        // Mark as viewed if not already viewed
        if (!$report['homeowner_viewed_at']) {
            $updateStmt = $pdo->prepare("
                UPDATE progress_reports 
                SET homeowner_viewed_at = NOW(), status = 'viewed' 
                WHERE id = ?
            ");
            $updateStmt->execute([$report_id]);
        }
        
        // Parse report data
        $reportData = json_decode($report['report_data'], true);
        
        $response = [
            'id' => $report['id'],
            'project_id' => $report['project_id'],
            'contractor' => [
                'id' => $report['contractor_id'],
                'name' => trim($report['contractor_first_name'] . ' ' . $report['contractor_last_name']),
                'email' => $report['contractor_email']
            ],
            'report_type' => $report['report_type'],
            'period' => [
                'start' => $report['report_period_start'],
                'end' => $report['report_period_end']
            ],
            'created_at' => $report['created_at'],
            'status' => $report['status'],
            'viewed_at' => $report['homeowner_viewed_at'],
            'acknowledged_at' => $report['homeowner_acknowledged_at'],
            'report_data' => $reportData
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $response
        ]);
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Acknowledge report
        $input = json_decode(file_get_contents('php://input'), true);
        $report_id = isset($input['report_id']) ? intval($input['report_id']) : null;
        $acknowledgment_notes = isset($input['notes']) ? trim($input['notes']) : '';
        
        if (!$report_id) {
            echo json_encode(['success' => false, 'message' => 'Report ID is required']);
            exit;
        }
        
        // Verify report belongs to homeowner
        $stmt = $pdo->prepare("SELECT id FROM progress_reports WHERE id = ? AND homeowner_id = ?");
        $stmt->execute([$report_id, $homeowner_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            exit;
        }
        
        // Update acknowledgment
        $updateStmt = $pdo->prepare("
            UPDATE progress_reports 
            SET homeowner_acknowledged_at = NOW(), 
                status = 'acknowledged',
                acknowledgment_notes = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$acknowledgment_notes, $report_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Report acknowledged successfully'
        ]);
    }
    
} catch (Exception $e) {
    error_log("View progress report error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
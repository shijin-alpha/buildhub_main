<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? null;
    
    if (!$user_id || $user_role !== 'homeowner') {
        echo json_encode(['success' => false, 'message' => 'Homeowner authentication required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';
    
    // Ensure unified contractor engagement table exists
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_engagements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        homeowner_id INT NOT NULL,
        contractor_id INT NOT NULL,
        layout_request_id INT NULL,
        house_plan_id INT NULL,
        engagement_type ENUM('estimate_request', 'construction_contract') NOT NULL,
        status ENUM('sent', 'viewed', 'quoted', 'accepted', 'declined', 'completed') DEFAULT 'sent',
        message TEXT NULL,
        project_details JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_homeowner (homeowner_id),
        INDEX idx_contractor (contractor_id),
        INDEX idx_request (layout_request_id),
        INDEX idx_house_plan (house_plan_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    switch ($action) {
        case 'send_for_estimate':
            $contractor_ids = isset($input['contractor_ids']) ? $input['contractor_ids'] : [];
            $house_plan_id = isset($input['house_plan_id']) ? (int)$input['house_plan_id'] : null;
            $layout_request_id = isset($input['layout_request_id']) ? (int)$input['layout_request_id'] : null;
            $message = isset($input['message']) ? trim($input['message']) : '';
            $project_details = isset($input['project_details']) ? $input['project_details'] : [];
            
            if (empty($contractor_ids) || (!$house_plan_id && !$layout_request_id)) {
                echo json_encode(['success' => false, 'message' => 'Contractor IDs and either house plan or layout request required']);
                exit;
            }
            
            // Normalize contractor IDs
            if (!is_array($contractor_ids)) {
                $contractor_ids = [$contractor_ids];
            }
            $contractor_ids = array_map('intval', $contractor_ids);
            $contractor_ids = array_filter($contractor_ids, function($id) { return $id > 0; });
            
            $successCount = 0;
            foreach ($contractor_ids as $contractor_id) {
                // Verify contractor exists
                $contractorStmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE id = :id AND role = 'contractor' AND is_verified = 1");
                $contractorStmt->execute([':id' => $contractor_id]);
                $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$contractor) continue;
                
                // Insert engagement record
                $engagementStmt = $db->prepare("INSERT INTO contractor_engagements 
                                               (homeowner_id, contractor_id, layout_request_id, house_plan_id, engagement_type, message, project_details) 
                                               VALUES (:homeowner_id, :contractor_id, :layout_request_id, :house_plan_id, 'estimate_request', :message, :project_details)");
                
                $result = $engagementStmt->execute([
                    ':homeowner_id' => $user_id,
                    ':contractor_id' => $contractor_id,
                    ':layout_request_id' => $layout_request_id,
                    ':house_plan_id' => $house_plan_id,
                    ':message' => $message,
                    ':project_details' => json_encode($project_details)
                ]);
                
                if ($result) {
                    $successCount++;
                    
                    // Create notification for contractor
                    $notificationStmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) 
                                                     VALUES (:user_id, 'estimate_request', 'New Estimate Request', :message, CURRENT_TIMESTAMP)");
                    
                    $notificationMessage = "You have received a new estimate request from a homeowner.";
                    if ($message) {
                        $notificationMessage .= "\n\nMessage: " . $message;
                    }
                    
                    $notificationStmt->execute([
                        ':user_id' => $contractor_id,
                        ':message' => $notificationMessage
                    ]);
                }
            }
            
            echo json_encode([
                'success' => $successCount > 0,
                'message' => "Estimate request sent to $successCount contractor(s)",
                'sent_count' => $successCount
            ]);
            break;
            
        case 'get_engagements':
            $engagementsStmt = $db->prepare("SELECT 
                                            ce.*,
                                            u.first_name as contractor_first_name,
                                            u.last_name as contractor_last_name,
                                            u.email as contractor_email,
                                            u.phone as contractor_phone,
                                            hp.plan_name,
                                            lr.plot_size,
                                            lr.budget_range
                                           FROM contractor_engagements ce
                                           JOIN users u ON ce.contractor_id = u.id
                                           LEFT JOIN house_plans hp ON ce.house_plan_id = hp.id
                                           LEFT JOIN layout_requests lr ON ce.layout_request_id = lr.id
                                           WHERE ce.homeowner_id = :homeowner_id
                                           ORDER BY ce.created_at DESC");
            
            $engagementsStmt->execute([':homeowner_id' => $user_id]);
            $engagements = $engagementsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'engagements' => $engagements
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing contractor engagement: ' . $e->getMessage()
    ]);
}
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is an architect
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'architect') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $architectId = $_SESSION['user_id'];
    
    // First, let's check if the required tables exist
    $requiredTables = ['house_plans', 'contractor_engagements', 'contractor_layout_sends', 'users'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required tables: ' . implode(', ', $missingTables),
            'debug' => 'Database schema incomplete'
        ]);
        exit;
    }
    
    // Check if contractor_engagements table has required columns
    $engagementColumns = $db->query("SHOW COLUMNS FROM contractor_engagements")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('house_plan_id', $engagementColumns)) {
        // Try to add the column if it doesn't exist
        try {
            $db->exec("ALTER TABLE contractor_engagements ADD COLUMN house_plan_id INT NULL AFTER layout_request_id");
        } catch (Exception $e) {
            // Column might already exist or we don't have permission
        }
    }
    
    // Check if contractor_layout_sends table has required columns
    $sendsColumns = $db->query("SHOW COLUMNS FROM contractor_layout_sends")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('house_plan_id', $sendsColumns)) {
        // Try to add the column if it doesn't exist
        try {
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN house_plan_id INT NULL AFTER design_id");
        } catch (Exception $e) {
            // Column might already exist or we don't have permission
        }
    }
    
    // Start with a simple query to get house plans by this architect
    $housePlansQuery = "
        SELECT 
            hp.id as house_plan_id,
            hp.plan_name,
            hp.plot_width,
            hp.plot_height,
            hp.total_area,
            hp.status as plan_status,
            hp.created_at as plan_created_at,
            hp.updated_at as plan_updated_at,
            hp.layout_request_id
        FROM house_plans hp
        WHERE hp.architect_id = :architect_id
        AND hp.status != 'deleted'
        ORDER BY hp.updated_at DESC
    ";
    
    $stmt = $db->prepare($housePlansQuery);
    $stmt->bindValue(':architect_id', $architectId, PDO::PARAM_INT);
    $stmt->execute();
    
    $housePlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($housePlans)) {
        echo json_encode([
            'success' => true,
            'house_plans' => [],
            'summary' => [
                'total_plans' => 0,
                'total_contractors' => 0,
                'active_estimates' => 0,
                'completed_estimates' => 0
            ],
            'message' => 'No house plans found for this architect'
        ]);
        exit;
    }
    
    $contractorHousePlans = [];
    $totalContractors = 0;
    $activeEstimates = 0;
    $completedEstimates = 0;
    $contractorIds = [];
    
    foreach ($housePlans as $plan) {
        $planId = $plan['house_plan_id'];
        $layoutRequestId = $plan['layout_request_id'];
        
        // Get homeowner details from layout request
        $homeowner = null;
        if ($layoutRequestId) {
            $homeownerQuery = "
                SELECT u.id, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as name, u.email, u.phone
                FROM layout_requests lr
                JOIN users u ON lr.user_id = u.id
                WHERE lr.id = :layout_request_id
            ";
            $stmt = $db->prepare($homeownerQuery);
            $stmt->bindValue(':layout_request_id', $layoutRequestId, PDO::PARAM_INT);
            $stmt->execute();
            $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get layout request details
        $layoutRequest = null;
        if ($layoutRequestId) {
            $layoutQuery = "SELECT id, plot_size, budget_range, location, timeline FROM layout_requests WHERE id = :layout_request_id";
            $stmt = $db->prepare($layoutQuery);
            $stmt->bindValue(':layout_request_id', $layoutRequestId, PDO::PARAM_INT);
            $stmt->execute();
            $layoutRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get contractor engagements for this plan
        $contractorWork = [];
        
        // Check contractor_engagements
        $engagementQuery = "
            SELECT 
                ce.id as engagement_id,
                ce.engagement_type,
                ce.status as engagement_status,
                ce.created_at as engagement_created_at,
                ce.message as engagement_message,
                u.id as contractor_id,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as contractor_name,
                u.email as contractor_email,
                u.phone as contractor_phone
            FROM contractor_engagements ce
            JOIN users u ON ce.contractor_id = u.id
            WHERE (ce.house_plan_id = :plan_id OR ce.layout_request_id = :layout_request_id)
        ";
        
        $stmt = $db->prepare($engagementQuery);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->bindValue(':layout_request_id', $layoutRequestId, PDO::PARAM_INT);
        $stmt->execute();
        
        $engagements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($engagements as $engagement) {
            if (!in_array($engagement['contractor_id'], $contractorIds)) {
                $contractorIds[] = $engagement['contractor_id'];
            }
            
            $contractorWork[] = [
                'contractor' => [
                    'id' => $engagement['contractor_id'],
                    'name' => $engagement['contractor_name'] ?: 'Unknown Contractor',
                    'email' => $engagement['contractor_email'],
                    'phone' => $engagement['contractor_phone']
                ],
                'engagement' => [
                    'id' => $engagement['engagement_id'],
                    'type' => $engagement['engagement_type'],
                    'status' => $engagement['engagement_status'],
                    'created_at' => $engagement['engagement_created_at'],
                    'message' => $engagement['engagement_message']
                ],
                'send_details' => null,
                'estimate' => null
            ];
        }
        
        // Check contractor_layout_sends
        $sendsQuery = "
            SELECT 
                cls.id as send_id,
                cls.created_at as sent_at,
                cls.acknowledged_at,
                cls.due_date,
                cls.message as send_message,
                u.id as contractor_id,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as contractor_name,
                u.email as contractor_email,
                u.phone as contractor_phone
            FROM contractor_layout_sends cls
            JOIN users u ON cls.contractor_id = u.id
            WHERE cls.house_plan_id = :plan_id
        ";
        
        $stmt = $db->prepare($sendsQuery);
        $stmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->execute();
        
        $sends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sends as $send) {
            if (!in_array($send['contractor_id'], $contractorIds)) {
                $contractorIds[] = $send['contractor_id'];
            }
            
            // Check if this contractor is already in the work array
            $existingIndex = -1;
            foreach ($contractorWork as $index => $work) {
                if ($work['contractor']['id'] === $send['contractor_id']) {
                    $existingIndex = $index;
                    break;
                }
            }
            
            if ($existingIndex >= 0) {
                // Update existing contractor work with send details
                $contractorWork[$existingIndex]['send_details'] = [
                    'id' => $send['send_id'],
                    'sent_at' => $send['sent_at'],
                    'acknowledged_at' => $send['acknowledged_at'],
                    'due_date' => $send['due_date'],
                    'message' => $send['send_message']
                ];
            } else {
                // Add new contractor work entry
                $contractorWork[] = [
                    'contractor' => [
                        'id' => $send['contractor_id'],
                        'name' => $send['contractor_name'] ?: 'Unknown Contractor',
                        'email' => $send['contractor_email'],
                        'phone' => $send['contractor_phone']
                    ],
                    'engagement' => null,
                    'send_details' => [
                        'id' => $send['send_id'],
                        'sent_at' => $send['sent_at'],
                        'acknowledged_at' => $send['acknowledged_at'],
                        'due_date' => $send['due_date'],
                        'message' => $send['send_message']
                    ],
                    'estimate' => null
                ];
            }
        }
        
        // Get estimates for contractor work
        foreach ($contractorWork as &$work) {
            if ($work['send_details']) {
                $estimateQuery = "
                    SELECT 
                        cse.id as estimate_id,
                        cse.status as estimate_status,
                        cse.total_cost as estimate_amount,
                        cse.created_at as estimate_created_at,
                        cse.homeowner_action_at as estimate_accepted_at
                    FROM contractor_send_estimates cse
                    WHERE cse.send_id = :send_id
                ";
                
                $stmt = $db->prepare($estimateQuery);
                $stmt->bindValue(':send_id', $work['send_details']['id'], PDO::PARAM_INT);
                $stmt->execute();
                
                $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($estimate) {
                    $work['estimate'] = $estimate;
                    
                    if ($estimate['estimate_status'] === 'accepted' || $estimate['estimate_accepted_at']) {
                        $completedEstimates++;
                    } else {
                        $activeEstimates++;
                    }
                }
            }
        }
        
        // Only include plans that have contractor work
        if (!empty($contractorWork)) {
            $contractorHousePlans[] = [
                'house_plan_id' => $plan['house_plan_id'],
                'plan_name' => $plan['plan_name'],
                'plot_width' => $plan['plot_width'],
                'plot_height' => $plan['plot_height'],
                'total_area' => $plan['total_area'],
                'plan_status' => $plan['plan_status'],
                'plan_created_at' => $plan['plan_created_at'],
                'plan_updated_at' => $plan['plan_updated_at'],
                
                'homeowner' => $homeowner ? [
                    'id' => $homeowner['id'],
                    'name' => $homeowner['name'],
                    'email' => $homeowner['email'],
                    'phone' => $homeowner['phone']
                ] : null,
                
                'layout_request' => $layoutRequest ? [
                    'id' => $layoutRequest['id'],
                    'plot_size' => $layoutRequest['plot_size'],
                    'budget_range' => $layoutRequest['budget_range'],
                    'location' => $layoutRequest['location'],
                    'timeline' => $layoutRequest['timeline']
                ] : null,
                
                'contractor_work' => $contractorWork
            ];
        }
    }
    
    $totalContractors = count($contractorIds);
    
    echo json_encode([
        'success' => true,
        'house_plans' => $contractorHousePlans,
        'summary' => [
            'total_plans' => count($contractorHousePlans),
            'total_contractors' => $totalContractors,
            'active_estimates' => $activeEstimates,
            'completed_estimates' => $completedEstimates
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_contractor_house_plans.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'debug' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
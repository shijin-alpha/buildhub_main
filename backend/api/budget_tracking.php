<?php
/**
 * Budget Tracking API
 * 
 * Purpose: Real-time cost monitoring and budget overrun calculation
 * Returns: Budget breakdown, payments, overrun status
 * 
 * Method: GET
 * Authentication: Optional (project-based access control recommended)
 * 
 * Query Parameters:
 * - project_id: Required
 * - action: 'summary' (default), 'breakdown', 'payments'
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$project_id = intval($_GET['project_id'] ?? 0);
$action = $_GET['action'] ?? 'summary';

if (!$project_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Project ID required'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    switch ($action) {
        case 'summary':
            // Get comprehensive budget summary
            $query = "SELECT 
                cp.id,
                cp.project_name,
                cp.estimated_cost,
                cp.actual_cost_overrun_percentage,
                cp.status,
                (SELECT COALESCE(SUM(amount), 0) 
                 FROM stage_payment_requests 
                 WHERE project_id = cp.id 
                 AND status IN ('paid', 'pending', 'approved')) as stage_payments,
                (SELECT COALESCE(SUM(amount), 0) 
                 FROM custom_payment_requests 
                 WHERE project_id = cp.id 
                 AND status IN ('approved', 'paid', 'pending')) as custom_payments,
                (SELECT COUNT(*) 
                 FROM stage_payment_requests 
                 WHERE project_id = cp.id) as total_stage_requests,
                (SELECT COUNT(*) 
                 FROM stage_payment_requests 
                 WHERE project_id = cp.id 
                 AND status = 'paid') as paid_stage_requests,
                (SELECT COUNT(*) 
                 FROM custom_payment_requests 
                 WHERE project_id = cp.id) as total_custom_requests,
                (SELECT COUNT(*) 
                 FROM custom_payment_requests 
                 WHERE project_id = cp.id 
                 AND status = 'paid') as paid_custom_requests
            FROM construction_projects cp
            WHERE cp.id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $project = $result->fetch_assoc();
            
            if (!$project) {
                throw new Exception('Project not found');
            }
            
            $total_cost = $project['stage_payments'] + $project['custom_payments'];
            $remaining_budget = $project['estimated_cost'] - $total_cost;
            $overrun_amount = max(0, $total_cost - $project['estimated_cost']);
            $budget_utilization = $project['estimated_cost'] > 0 
                ? ($total_cost / $project['estimated_cost']) * 100 
                : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'project_id' => intval($project['id']),
                    'project_name' => $project['project_name'],
                    'status' => $project['status'],
                    'budget' => [
                        'estimated_cost' => floatval($project['estimated_cost']),
                        'stage_payments' => floatval($project['stage_payments']),
                        'custom_payments' => floatval($project['custom_payments']),
                        'total_cost' => $total_cost,
                        'remaining_budget' => $remaining_budget,
                        'budget_utilization_pct' => round($budget_utilization, 2)
                    ],
                    'overrun' => [
                        'is_over_budget' => $total_cost > $project['estimated_cost'],
                        'overrun_amount' => $overrun_amount,
                        'overrun_percentage' => floatval($project['actual_cost_overrun_percentage']),
                        'status' => $total_cost > $project['estimated_cost'] ? 'OVER BUDGET' : 'WITHIN BUDGET'
                    ],
                    'payment_requests' => [
                        'stage_requests' => [
                            'total' => intval($project['total_stage_requests']),
                            'paid' => intval($project['paid_stage_requests']),
                            'pending' => intval($project['total_stage_requests']) - intval($project['paid_stage_requests'])
                        ],
                        'custom_requests' => [
                            'total' => intval($project['total_custom_requests']),
                            'paid' => intval($project['paid_custom_requests']),
                            'pending' => intval($project['total_custom_requests']) - intval($project['paid_custom_requests'])
                        ]
                    ]
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'breakdown':
            // Get detailed payment breakdown
            $query = "SELECT 
                'stage' as payment_type,
                stage_name as description,
                amount,
                status,
                request_date as date,
                approval_date
            FROM stage_payment_requests
            WHERE project_id = ?
            UNION ALL
            SELECT 
                'custom' as payment_type,
                request_title as description,
                requested_amount as amount,
                status,
                request_date as date,
                approval_date
            FROM custom_payment_requests
            WHERE project_id = ?
            ORDER BY date DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $project_id, $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = [
                    'type' => $row['payment_type'],
                    'description' => $row['description'],
                    'amount' => floatval($row['amount']),
                    'status' => $row['status'],
                    'request_date' => $row['date'],
                    'approval_date' => $row['approval_date']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'project_id' => $project_id,
                    'payments' => $payments,
                    'total_payments' => count($payments)
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'payments':
            // Get payment summary by status
            $query = "SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM (
                SELECT status, amount FROM stage_payment_requests WHERE project_id = ?
                UNION ALL
                SELECT status, requested_amount as amount FROM custom_payment_requests WHERE project_id = ?
            ) as all_payments
            GROUP BY status";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $project_id, $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $by_status = [];
            while ($row = $result->fetch_assoc()) {
                $by_status[$row['status']] = [
                    'count' => intval($row['count']),
                    'total_amount' => floatval($row['total_amount'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'project_id' => $project_id,
                    'payments_by_status' => $by_status
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            break;
            
        default:
            throw new Exception('Invalid action. Use: summary, breakdown, or payments');
    }
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

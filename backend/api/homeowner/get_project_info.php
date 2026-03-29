<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    $project_id = $_GET['project_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Homeowner not authenticated'
        ]);
        exit;
    }

    if (!$project_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID required'
        ]);
        exit;
    }

    // Get comprehensive project information
    $projectQuery = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.total_cost,
            cp.status,
            cp.current_stage,
            cp.completion_percentage,
            cp.start_date,
            cp.expected_completion_date,
            cp.project_location,
            cp.timeline,
            cp.structured_data,
            cp.homeowner_name,
            cp.homeowner_email,
            cp.homeowner_phone,
            cp.plot_size,
            cp.budget_range,
            cp.preferred_style,
            cp.requirements,
            cp.created_at,
            cp.updated_at,
            
            -- Contractor information
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            u_contractor.company_name as contractor_company,
            
            -- Get latest progress from daily_progress_updates
            COALESCE(
                (SELECT MAX(dpu.cumulative_completion_percentage) 
                 FROM daily_progress_updates dpu 
                 WHERE dpu.project_id = cp.id), 
                cp.completion_percentage, 0
            ) as latest_progress,
            
            -- Get current stage from latest daily update
            COALESCE(
                (SELECT dpu.construction_stage 
                 FROM daily_progress_updates dpu 
                 WHERE dpu.project_id = cp.id 
                 ORDER BY dpu.update_date DESC, dpu.created_at DESC 
                 LIMIT 1), 
                cp.current_stage
            ) as current_construction_stage,
            
            -- Count daily updates
            (SELECT COUNT(*) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as daily_updates_count,
            
            -- Count weekly summaries (for now, we'll calculate based on daily updates)
            (SELECT COUNT(DISTINCT YEARWEEK(dpu.update_date)) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as weekly_summaries_count,
            
            -- Count monthly reports (calculate based on daily updates)
            (SELECT COUNT(DISTINCT DATE_FORMAT(dpu.update_date, '%Y-%m')) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as monthly_reports_count,
            
            -- Get last update date
            (SELECT MAX(dpu.update_date) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as last_update_date,
            
            -- Get last update timestamp
            (SELECT MAX(dpu.created_at) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as last_update_timestamp,
            
            -- Count completed stages
            (SELECT COUNT(DISTINCT dpu.construction_stage) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id 
             AND dpu.cumulative_completion_percentage >= 100) as completed_stages,
            
            -- Get total stages worked on
            (SELECT COUNT(DISTINCT dpu.construction_stage) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as total_stages_worked
            
        FROM construction_projects cp
        LEFT JOIN users u_contractor ON cp.contractor_id = u_contractor.id
        WHERE cp.id = :project_id 
        AND cp.homeowner_id = :homeowner_id
    ";
    
    $stmt = $db->prepare($projectQuery);
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindParam(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or access denied'
        ]);
        exit;
    }

    // Parse structured data
    $structuredData = null;
    if ($project['structured_data']) {
        try {
            $structuredData = json_decode($project['structured_data'], true);
        } catch (Exception $e) {
            // Ignore JSON parsing errors
        }
    }

    // Get recent daily progress updates
    $recentUpdatesQuery = "
        SELECT 
            dpu.*,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM daily_progress_updates dpu
        LEFT JOIN users u ON dpu.contractor_id = u.id
        WHERE dpu.project_id = :project_id
        ORDER BY dpu.update_date DESC, dpu.created_at DESC
        LIMIT 10
    ";
    
    $updatesStmt = $db->prepare($recentUpdatesQuery);
    $updatesStmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $updatesStmt->execute();
    $recentUpdates = $updatesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payment information
    $paymentQuery = "
        SELECT 
            COUNT(*) as total_payments,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_payments,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN requested_amount END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN requested_amount END), 0) as total_pending,
            MAX(total_project_cost) as project_budget
        FROM stage_payment_requests
        WHERE project_id = :project_id
        AND homeowner_id = :homeowner_id
    ";
    
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $paymentStmt->bindParam(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $paymentStmt->execute();
    $paymentInfo = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate days remaining
    $daysRemaining = null;
    if ($project['expected_completion_date']) {
        $daysRemaining = floor((strtotime($project['expected_completion_date']) - time()) / (60 * 60 * 24));
    }

    // Format the response
    $response = [
        'success' => true,
        'data' => [
            'project_info' => [
                'id' => (int)$project['id'],
                'project_name' => $project['project_name'],
                'total_cost' => (float)$project['total_cost'],
                'status' => $project['status'],
                'current_stage' => $project['current_construction_stage'],
                'completion_percentage' => (float)$project['latest_progress'],
                'start_date' => $project['start_date'],
                'expected_completion_date' => $project['expected_completion_date'],
                'days_remaining' => $daysRemaining,
                'project_location' => $project['project_location'],
                'timeline' => $project['timeline'],
                'plot_size' => $project['plot_size'],
                'budget_range' => $project['budget_range'],
                'preferred_style' => $project['preferred_style'],
                'requirements' => $project['requirements'],
                'created_at' => $project['created_at'],
                'updated_at' => $project['updated_at'],
                
                // Homeowner information
                'homeowner_name' => $project['homeowner_name'],
                'homeowner_email' => $project['homeowner_email'],
                'homeowner_phone' => $project['homeowner_phone'],
                
                // Contractor information
                'contractor_name' => $project['contractor_name'],
                'contractor_email' => $project['contractor_email'],
                'contractor_phone' => $project['contractor_phone'],
                'contractor_company' => $project['contractor_company'],
                
                // Progress statistics
                'latest_progress' => (float)$project['latest_progress'],
                'completed_stages' => (int)$project['completed_stages'],
                'total_stages_worked' => (int)$project['total_stages_worked'],
                
                // Update history counts
                'daily_updates_count' => (int)$project['daily_updates_count'],
                'weekly_summaries_count' => (int)$project['weekly_summaries_count'],
                'monthly_reports_count' => (int)$project['monthly_reports_count'],
                'last_update_date' => $project['last_update_date'],
                'last_update_timestamp' => $project['last_update_timestamp'],
                
                // Payment information
                'payment_info' => [
                    'total_payments' => (int)$paymentInfo['total_payments'],
                    'paid_payments' => (int)$paymentInfo['paid_payments'],
                    'pending_payments' => (int)$paymentInfo['pending_payments'],
                    'total_paid' => (float)$paymentInfo['total_paid'],
                    'total_pending' => (float)$paymentInfo['total_pending'],
                    'project_budget' => (float)$paymentInfo['project_budget']
                ],
                
                // Structured data from estimate
                'structured_data' => $structuredData,
                
                // Built-up area from structured data
                'built_up_area' => $structuredData['built_up_area'] ?? 'N/A',
                'floors' => $structuredData['floors'] ?? 'N/A',
                'project_address' => $structuredData['project_address'] ?? $project['project_location'],
                'estimate_cost' => $structuredData['totals']['grand'] ?? $project['total_cost']
            ],
            'recent_updates' => $recentUpdates,
            'summary' => [
                'total_updates' => (int)$project['daily_updates_count'],
                'latest_progress' => (float)$project['latest_progress'],
                'current_stage' => $project['current_construction_stage'],
                'days_remaining' => $daysRemaining,
                'project_health' => $daysRemaining < 0 ? 'overdue' : ($project['latest_progress'] < 25 && $daysRemaining < 30 ? 'at_risk' : 'on_track')
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Get project info error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving project information: ' . $e->getMessage()
    ]);
}
?>
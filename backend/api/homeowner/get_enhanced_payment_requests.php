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
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $homeowner_id = isset($_GET['homeowner_id']) ? (int)$_GET['homeowner_id'] : 0;
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $status = $_GET['status'] ?? 'all';
    
    if ($homeowner_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid homeowner ID']);
        exit;
    }
    
    // Build query based on filters
    $whereClause = "WHERE espr.homeowner_id = :homeowner_id";
    $params = [':homeowner_id' => $homeowner_id];
    
    if ($project_id) {
        $whereClause .= " AND espr.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    
    if ($status !== 'all') {
        $whereClause .= " AND espr.status = :status";
        $params[':status'] = $status;
    }
    
    // Get enhanced payment requests with project and contractor details
    $query = "
        SELECT 
            espr.*,
            -- Project details
            cse.total_cost as project_total_cost,
            cse.timeline as project_timeline,
            cls.layout_id,
            cls.design_id,
            
            -- Homeowner details
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            
            -- Contractor details
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            
            -- Layout request details
            lr.plot_size,
            lr.budget_range,
            lr.location,
            lr.requirements,
            
            -- Time calculations
            DATEDIFF(NOW(), espr.request_date) as days_since_request,
            DATEDIFF(espr.work_end_date, espr.work_start_date) as work_duration_days,
            
            CASE 
                WHEN espr.status = 'pending' AND DATEDIFF(NOW(), espr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue,
            
            -- Progress information
            COALESCE(
                (SELECT AVG(completion_percentage) 
                 FROM construction_progress_updates cpu 
                 WHERE cpu.project_id = espr.project_id), 
                0
            ) as overall_project_progress
            
        FROM enhanced_stage_payment_requests espr
        LEFT JOIN contractor_send_estimates cse ON espr.project_id = cse.id
        LEFT JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u_homeowner ON espr.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON espr.contractor_id = u_contractor.id
        LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
        $whereClause
        ORDER BY espr.request_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_requests,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN requested_amount END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN COALESCE(approved_amount, requested_amount) END), 0) as approved_amount,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN COALESCE(approved_amount, requested_amount) END), 0) as paid_amount,
            COUNT(CASE WHEN status = 'pending' AND DATEDIFF(NOW(), request_date) > 7 THEN 1 END) as overdue_requests,
            
            -- Cost breakdown totals
            COALESCE(SUM(CASE WHEN status IN ('approved', 'paid') THEN labor_cost END), 0) as total_labor_cost,
            COALESCE(SUM(CASE WHEN status IN ('approved', 'paid') THEN material_cost END), 0) as total_material_cost,
            COALESCE(SUM(CASE WHEN status IN ('approved', 'paid') THEN equipment_cost END), 0) as total_equipment_cost,
            COALESCE(SUM(CASE WHEN status IN ('approved', 'paid') THEN other_expenses END), 0) as total_other_expenses
        FROM enhanced_stage_payment_requests 
        $whereClause
    ";
    
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get project-wise summary if no specific project filter
    $projectSummary = [];
    if (!$project_id) {
        $projectQuery = "
            SELECT 
                cse.id as project_id,
                u_homeowner.first_name as homeowner_first_name,
                u_homeowner.last_name as homeowner_last_name,
                u_contractor.first_name as contractor_first_name,
                u_contractor.last_name as contractor_last_name,
                cse.total_cost,
                lr.plot_size,
                lr.budget_range,
                lr.location,
                COUNT(espr.id) as total_requests,
                COUNT(CASE WHEN espr.status = 'pending' THEN 1 END) as pending_requests,
                COUNT(CASE WHEN espr.status = 'approved' THEN 1 END) as approved_requests,
                COUNT(CASE WHEN espr.status = 'paid' THEN 1 END) as paid_requests,
                COALESCE(SUM(CASE WHEN espr.status = 'paid' THEN COALESCE(espr.approved_amount, espr.requested_amount) END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN espr.status = 'pending' THEN espr.requested_amount END), 0) as total_pending,
                COALESCE(SUM(CASE WHEN espr.status = 'approved' THEN COALESCE(espr.approved_amount, espr.requested_amount) END), 0) as total_approved,
                
                -- Progress information
                COALESCE(
                    (SELECT AVG(completion_percentage) 
                     FROM construction_progress_updates cpu 
                     WHERE cpu.project_id = cse.id), 
                    0
                ) as overall_progress
            FROM contractor_send_estimates cse
            LEFT JOIN contractor_layout_sends cls ON cse.send_id = cls.id
            LEFT JOIN users u_homeowner ON cls.homeowner_id = u_homeowner.id
            LEFT JOIN users u_contractor ON cse.contractor_id = u_contractor.id
            LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
            LEFT JOIN enhanced_stage_payment_requests espr ON cse.id = espr.project_id
            WHERE cls.homeowner_id = :homeowner_id
            GROUP BY cse.id
            ORDER BY cse.id DESC
        ";
        
        $projectStmt = $db->prepare($projectQuery);
        $projectStmt->execute([':homeowner_id' => $homeowner_id]);
        $projectSummary = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get stage-wise breakdown
    $stageBreakdownQuery = "
        SELECT 
            stage_name,
            COUNT(*) as request_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
            COALESCE(SUM(requested_amount), 0) as total_requested,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN COALESCE(approved_amount, requested_amount) END), 0) as total_paid,
            COALESCE(AVG(completion_percentage), 0) as avg_completion,
            COALESCE(SUM(labor_cost), 0) as total_labor_cost,
            COALESCE(SUM(material_cost), 0) as total_material_cost,
            COALESCE(SUM(equipment_cost), 0) as total_equipment_cost,
            COALESCE(SUM(other_expenses), 0) as total_other_expenses
        FROM enhanced_stage_payment_requests 
        $whereClause
        GROUP BY stage_name
        ORDER BY 
            CASE stage_name
                WHEN 'Foundation' THEN 1
                WHEN 'Structure' THEN 2
                WHEN 'Brickwork' THEN 3
                WHEN 'Roofing' THEN 4
                WHEN 'Electrical' THEN 5
                WHEN 'Plumbing' THEN 6
                WHEN 'Finishing' THEN 7
                ELSE 8
            END
    ";
    
    $stageStmt = $db->prepare($stageBreakdownQuery);
    $stageStmt->execute($params);
    $stageBreakdown = $stageStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response data
    foreach ($requests as &$request) {
        // Convert numeric fields
        $request['requested_amount'] = (float)$request['requested_amount'];
        $request['approved_amount'] = $request['approved_amount'] ? (float)$request['approved_amount'] : null;
        $request['percentage_of_total'] = (float)$request['percentage_of_total'];
        $request['completion_percentage'] = (float)$request['completion_percentage'];
        $request['project_total_cost'] = (float)$request['project_total_cost'];
        $request['labor_cost'] = (float)$request['labor_cost'];
        $request['material_cost'] = (float)$request['material_cost'];
        $request['equipment_cost'] = (float)$request['equipment_cost'];
        $request['other_expenses'] = (float)$request['other_expenses'];
        $request['weather_delays'] = (int)$request['weather_delays'];
        $request['workers_count'] = (int)$request['workers_count'];
        $request['overall_project_progress'] = (float)$request['overall_project_progress'];
        $request['is_overdue'] = (bool)$request['is_overdue'];
        $request['safety_compliance'] = (bool)$request['safety_compliance'];
        
        // Calculate cost breakdown total
        $request['cost_breakdown_total'] = $request['labor_cost'] + $request['material_cost'] + 
                                         $request['equipment_cost'] + $request['other_expenses'];
        
        // Format dates
        $request['request_date_formatted'] = date('M j, Y g:i A', strtotime($request['request_date']));
        if ($request['homeowner_response_date']) {
            $request['homeowner_response_date_formatted'] = date('M j, Y g:i A', strtotime($request['homeowner_response_date']));
        }
        if ($request['payment_date']) {
            $request['payment_date_formatted'] = date('M j, Y g:i A', strtotime($request['payment_date']));
        }
        if ($request['work_start_date']) {
            $request['work_start_date_formatted'] = date('M j, Y', strtotime($request['work_start_date']));
        }
        if ($request['work_end_date']) {
            $request['work_end_date_formatted'] = date('M j, Y', strtotime($request['work_end_date']));
        }
        
        // Add contractor and project display names
        $request['contractor_name'] = trim($request['contractor_first_name'] . ' ' . $request['contractor_last_name']);
        $request['homeowner_name'] = trim($request['homeowner_first_name'] . ' ' . $request['homeowner_last_name']);
        $request['project_display_name'] = $request['contractor_name'] . ' - ' . ($request['plot_size'] ?: 'Unknown size');
        
        // Add urgency level
        if ($request['status'] === 'pending') {
            if ($request['days_since_request'] > 14) {
                $request['urgency'] = 'high';
            } elseif ($request['days_since_request'] > 7) {
                $request['urgency'] = 'medium';
            } else {
                $request['urgency'] = 'low';
            }
        } else {
            $request['urgency'] = 'none';
        }
        
        // Add stage progress indicator
        $request['stage_progress_status'] = getStageProgressStatus($request['completion_percentage'], $request['quality_check_status']);
    }
    
    // Format summary data
    foreach (['pending_amount', 'approved_amount', 'paid_amount', 'total_labor_cost', 'total_material_cost', 'total_equipment_cost', 'total_other_expenses'] as $field) {
        $summary[$field] = (float)$summary[$field];
    }
    
    // Format project summary data
    foreach ($projectSummary as &$project) {
        foreach (['total_cost', 'total_paid', 'total_pending', 'total_approved', 'overall_progress'] as $field) {
            $project[$field] = (float)$project[$field];
        }
        $project['project_display_name'] = trim($project['contractor_first_name'] . ' ' . $project['contractor_last_name']) . 
                                         ' - ' . ($project['plot_size'] ?: 'Unknown size');
        $project['payment_completion_percentage'] = $project['total_cost'] > 0 ? 
                                                   ($project['total_paid'] / $project['total_cost']) * 100 : 0;
    }
    
    // Format stage breakdown data
    foreach ($stageBreakdown as &$stage) {
        foreach (['total_requested', 'total_paid', 'avg_completion', 'total_labor_cost', 'total_material_cost', 'total_equipment_cost', 'total_other_expenses'] as $field) {
            $stage[$field] = (float)$stage[$field];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'requests' => $requests,
            'summary' => $summary,
            'project_summary' => $projectSummary,
            'stage_breakdown' => $stageBreakdown,
            'filters' => [
                'homeowner_id' => $homeowner_id,
                'project_id' => $project_id,
                'status' => $status
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get enhanced payment requests error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment requests: ' . $e->getMessage()
    ]);
}

/**
 * Get stage progress status based on completion and quality check
 */
function getStageProgressStatus($completion_percentage, $quality_check_status) {
    if ($completion_percentage >= 100 && $quality_check_status === 'passed') {
        return 'completed';
    } elseif ($completion_percentage >= 100 && $quality_check_status === 'pending') {
        return 'awaiting_quality_check';
    } elseif ($completion_percentage >= 75) {
        return 'near_completion';
    } elseif ($completion_percentage >= 50) {
        return 'in_progress';
    } elseif ($completion_percentage >= 25) {
        return 'started';
    } else {
        return 'not_started';
    }
}
?>
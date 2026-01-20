<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


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

    // Get contractor_id from query parameter
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    
    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Check if construction_projects table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'construction_projects'");
    $hasConstructionProjects = $tableCheck->rowCount() > 0;
    
    $projects = [];
    
    if ($hasConstructionProjects) {
        // Get projects from construction_projects table (new system)
        $constructionStmt = $db->prepare("
            SELECT 
                cp.id as construction_project_id,
                cp.estimate_id as project_id,
                cp.project_name,
                cp.project_description,
                cp.total_cost,
                cp.timeline,
                cp.status as project_status,
                cp.homeowner_name,
                cp.homeowner_email,
                cp.homeowner_phone,
                cp.project_location as location,
                cp.plot_size,
                cp.budget_range,
                cp.requirements,
                cp.preferred_style,
                cp.current_stage,
                cp.completion_percentage as latest_progress,
                cp.created_at as project_created_at,
                cp.last_update_date,
                'construction_project' as source_type,
                -- Get progress statistics
                COALESCE(
                    (SELECT COUNT(DISTINCT stage_name) 
                     FROM construction_progress_updates cpu 
                     WHERE cpu.project_id = cp.estimate_id AND cpu.stage_status = 'Completed'), 
                    0
                ) as completed_stages,
                COALESCE(
                    (SELECT COUNT(DISTINCT stage_name) 
                     FROM construction_progress_updates cpu 
                     WHERE cpu.project_id = cp.estimate_id AND cpu.stage_status = 'In Progress'), 
                    0
                ) as in_progress_stages,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM construction_progress_updates cpu 
                     WHERE cpu.project_id = cp.estimate_id), 
                    0
                ) as total_updates
            FROM construction_projects cp
            WHERE cp.contractor_id = :contractor_id 
            AND cp.status IN ('created', 'in_progress', 'on_hold')
            ORDER BY cp.created_at DESC
        ");
        
        $constructionStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $constructionStmt->execute();
        $constructionProjects = $constructionStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process construction projects
        foreach ($constructionProjects as $project) {
            $project['homeowner_first_name'] = explode(' ', $project['homeowner_name'])[0] ?? '';
            $project['homeowner_last_name'] = implode(' ', array_slice(explode(' ', $project['homeowner_name']), 1)) ?: '';
            $project['effective_status'] = $project['project_status'];
            $project['estimate_date'] = $project['project_created_at'];
            $project['acknowledged_at'] = $project['project_created_at'];
            
            $projects[] = $project;
        }
    }
    
    // Get assigned projects from estimates (legacy system)
    $stmt = $db->prepare("
        SELECT 
            cse.id as project_id,
            cse.send_id,
            cse.total_cost,
            cse.timeline,
            cse.materials,
            cse.cost_breakdown,
            cse.notes,
            cse.status as project_status,
            cse.created_at as estimate_date,
            cls.acknowledged_at,
            cls.homeowner_id,
            cls.layout_id,
            cls.design_id,
            cls.message as project_message,
            u_homeowner.id as homeowner_id,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            lr.id as layout_request_id,
            lr.plot_size,
            lr.budget_range,
            lr.requirements,
            lr.preferred_style,
            lr.location,
            lr.timeline as requested_timeline,
            lr.created_at as request_date,
            'estimate' as source_type,
            -- Check if estimate is acknowledged/accepted
            CASE 
                WHEN cls.acknowledged_at IS NOT NULL THEN 'acknowledged'
                WHEN cse.status = 'accepted' THEN 'accepted'
                WHEN cse.status = 'construction_started' THEN 'construction_started'
                WHEN cse.status = 'in_progress' THEN 'in_progress'
                ELSE cse.status
            END as effective_status
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u_homeowner ON cls.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON cse.contractor_id = u_contractor.id
        LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
        WHERE cse.contractor_id = :contractor_id 
        AND (
            cls.acknowledged_at IS NOT NULL 
            OR cse.status IN ('accepted', 'construction_started', 'in_progress')
        )
        AND cse.total_cost IS NOT NULL 
        AND cse.total_cost > 0
        AND cse.timeline IS NOT NULL
        AND cse.timeline != ''
        " . ($hasConstructionProjects ? "AND cse.id NOT IN (SELECT estimate_id FROM construction_projects WHERE contractor_id = :contractor_id2)" : "") . "
        ORDER BY cls.acknowledged_at DESC, cse.created_at DESC
    ");

    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    if ($hasConstructionProjects) {
        $stmt->bindValue(':contractor_id2', $contractor_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $estimateProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add estimate projects to the main projects array
    $projects = array_merge($projects, $estimateProjects);

    // Process each project
    foreach ($projects as &$project) {
        // Set default values for missing fields
        if (!isset($project['latest_progress'])) $project['latest_progress'] = 0;
        if (!isset($project['completed_stages'])) $project['completed_stages'] = 0;
        if (!isset($project['in_progress_stages'])) $project['in_progress_stages'] = 0;
        if (!isset($project['total_updates'])) $project['total_updates'] = 0;
        $project['daily_updates_count'] = 0;
        $project['weekly_summaries_count'] = 0;
        $project['monthly_reports_count'] = 0;
        $project['enhanced_progress'] = $project['latest_progress'];
        $project['last_update'] = $project['last_update_date'] ?? null;

        // Handle different project sources
        if ($project['source_type'] === 'construction_project') {
            // Construction project - data is already formatted
            $project['homeowner_name'] = $project['homeowner_name'];
            $project['project_display_name'] = $project['project_name'];
            $project['project_description'] = $project['project_description'] ?: sprintf(
                "Cost: ₹%s | Timeline: %s | Location: %s",
                number_format($project['total_cost'], 2),
                $project['timeline'],
                $project['location'] ?: 'Not specified'
            );
        } else {
            // Estimate project - format data
            $project['homeowner_name'] = trim($project['homeowner_first_name'] . ' ' . $project['homeowner_last_name']);
            $project['project_display_name'] = sprintf(
                "%s %s - %s (%s) - %s%% Complete - %s",
                $project['homeowner_first_name'],
                $project['homeowner_last_name'],
                $project['plot_size'] ?: 'Unknown size',
                $project['budget_range'] ?: 'Unknown budget',
                number_format($project['latest_progress'], 1),
                ucfirst(str_replace('_', ' ', $project['effective_status']))
            );
            
            $project['project_description'] = sprintf(
                "Estimate: ₹%s | Timeline: %s | Location: %s | Status: %s",
                number_format($project['total_cost'], 2),
                $project['timeline'],
                $project['location'] ?: 'Not specified',
                ucfirst(str_replace('_', ' ', $project['effective_status']))
            );
        }

        // Format dates
        $project['estimate_date_formatted'] = date('M j, Y', strtotime($project['estimate_date']));
        $project['request_date_formatted'] = isset($project['request_date']) && $project['request_date'] ? date('M j, Y', strtotime($project['request_date'])) : 'N/A';
        $project['acknowledged_date_formatted'] = $project['acknowledged_at'] ? date('M j, Y', strtotime($project['acknowledged_at'])) : 'Not acknowledged';
        $project['last_update_formatted'] = $project['last_update'] ? date('M j, Y g:i A', strtotime($project['last_update'])) : 'No updates yet';

        // Use effective status for display
        if (!isset($project['effective_status'])) {
            $project['effective_status'] = $project['project_status'];
        }

        // Add project summary for quick reference
        $project['project_summary'] = [
            'homeowner_name' => $project['homeowner_name'],
            'contractor_name' => isset($project['contractor_first_name']) ? $project['contractor_first_name'] . ' ' . $project['contractor_last_name'] : 'N/A',
            'plot_details' => ($project['plot_size'] ?: 'Unknown') . ' - ' . ($project['budget_range'] ?: 'Unknown'),
            'location' => $project['location'] ?: 'Location not specified',
            'progress_summary' => $project['latest_progress'] . '% complete',
            'status_display' => ucfirst(str_replace('_', ' ', $project['effective_status'])),
            'last_activity' => $project['last_update_formatted'],
            'total_cost_formatted' => $project['total_cost'] ? '₹' . number_format($project['total_cost'], 2) : 'Not specified',
            'timeline' => $project['timeline'] ?: 'Not specified',
            'estimate_ready' => !empty($project['total_cost']) && !empty($project['timeline']),
            'is_acknowledged' => !empty($project['acknowledged_at']),
            'ready_for_construction' => !empty($project['acknowledged_at']) && !empty($project['total_cost']) && !empty($project['timeline']),
            'source_type' => $project['source_type']
        ];

        // Calculate project status
        if ($project['latest_progress'] >= 100) {
            $project['progress_status'] = 'completed';
            $project['progress_status_class'] = 'badge-success';
        } elseif ($project['latest_progress'] > 0) {
            $project['progress_status'] = 'in_progress';
            $project['progress_status_class'] = 'badge-primary';
        } else {
            $project['progress_status'] = 'not_started';
            $project['progress_status_class'] = 'badge-secondary';
        }

        // Set empty arrays for missing data
        $project['recent_updates'] = [];
        $project['stages_summary'] = [];
    }

    // Get overall statistics
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_projects,
            0 as completed_projects,
            COUNT(*) as active_projects,
            0 as not_started_projects,
            0 as avg_progress
        FROM contractor_send_estimates cse
        WHERE cse.contractor_id = :contractor_id 
        AND cse.status IN ('accepted', 'construction_started', 'in_progress')
    ");
    
    $statsStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $projects,
            'statistics' => [
                'total_projects' => (int)$stats['total_projects'],
                'completed_projects' => (int)$stats['completed_projects'],
                'active_projects' => (int)$stats['active_projects'],
                'not_started_projects' => (int)$stats['not_started_projects'],
                'average_progress' => round((float)$stats['avg_progress'], 1)
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Get assigned projects error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $contractor_id = $_SESSION['user_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    $phase_name = $_GET['phase'] ?? '';
    
    if (empty($phase_name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Construction phase is required'
        ]);
        exit;
    }
    
    // Get phase information
    $phase_query = "SELECT id, phase_name, description FROM construction_phases WHERE phase_name = :phase_name";
    $phase_stmt = $db->prepare($phase_query);
    $phase_stmt->execute([':phase_name' => $phase_name]);
    $phase_info = $phase_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$phase_info) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid construction phase'
        ]);
        exit;
    }
    
    // Get required worker types for this phase
    $requirements_query = "
        SELECT 
            pwr.*,
            wt.type_name,
            wt.category,
            wt.description as worker_description,
            wt.base_wage_per_day
        FROM phase_worker_requirements pwr
        JOIN worker_types wt ON pwr.worker_type_id = wt.id
        WHERE pwr.phase_id = :phase_id
        ORDER BY 
            CASE pwr.priority_level 
                WHEN 'essential' THEN 1 
                WHEN 'important' THEN 2 
                WHEN 'optional' THEN 3 
            END,
            wt.category DESC,
            wt.type_name
    ";
    
    $requirements_stmt = $db->prepare($requirements_query);
    $requirements_stmt->execute([':phase_id' => $phase_info['id']]);
    $requirements = $requirements_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get contractor's available workers for each required type
    $available_workers = [];
    
    foreach ($requirements as $requirement) {
        $workers_query = "
            SELECT 
                cw.*,
                wt.type_name,
                wt.category,
                CASE 
                    WHEN cw.is_main_worker THEN 'Main Worker'
                    WHEN cw.skill_level = 'master' THEN 'Master'
                    WHEN cw.skill_level = 'senior' THEN 'Senior'
                    WHEN cw.skill_level = 'junior' THEN 'Junior'
                    ELSE 'Apprentice'
                END as worker_role,
                CASE 
                    WHEN cw.daily_wage > wt.base_wage_per_day * 1.5 THEN 'Premium'
                    WHEN cw.daily_wage > wt.base_wage_per_day * 1.2 THEN 'Above Average'
                    WHEN cw.daily_wage >= wt.base_wage_per_day * 0.8 THEN 'Standard'
                    ELSE 'Below Average'
                END as wage_category
            FROM contractor_workers cw
            JOIN worker_types wt ON cw.worker_type_id = wt.id
            WHERE cw.contractor_id = :contractor_id
            AND cw.worker_type_id = :worker_type_id
            AND cw.is_available = 1
            ORDER BY 
                cw.is_main_worker DESC,
                cw.skill_level DESC,
                cw.experience_years DESC,
                cw.daily_wage DESC
        ";
        
        $workers_stmt = $db->prepare($workers_query);
        $workers_stmt->execute([
            ':contractor_id' => $contractor_id,
            ':worker_type_id' => $requirement['worker_type_id']
        ]);
        
        $workers = $workers_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $available_workers[$requirement['worker_type_id']] = [
            'requirement' => $requirement,
            'workers' => $workers
        ];
    }
    
    // Get contractor's worker statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_workers,
            COUNT(CASE WHEN is_main_worker = 1 THEN 1 END) as main_workers,
            COUNT(CASE WHEN skill_level = 'master' THEN 1 END) as master_workers,
            COUNT(CASE WHEN skill_level = 'senior' THEN 1 END) as senior_workers,
            COUNT(CASE WHEN skill_level = 'junior' THEN 1 END) as junior_workers,
            COUNT(CASE WHEN skill_level = 'apprentice' THEN 1 END) as apprentice_workers,
            AVG(daily_wage) as average_wage,
            MIN(daily_wage) as min_wage,
            MAX(daily_wage) as max_wage
        FROM contractor_workers 
        WHERE contractor_id = :contractor_id AND is_available = 1
    ";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([':contractor_id' => $contractor_id]);
    $worker_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate phase readiness
    $phase_readiness = [
        'total_requirements' => count($requirements),
        'essential_met' => 0,
        'important_met' => 0,
        'optional_met' => 0,
        'missing_essential' => [],
        'recommendations' => []
    ];
    
    foreach ($requirements as $requirement) {
        $available_count = count($available_workers[$requirement['worker_type_id']]['workers']);
        $min_required = $requirement['min_workers'];
        
        if ($available_count >= $min_required) {
            switch ($requirement['priority_level']) {
                case 'essential':
                    $phase_readiness['essential_met']++;
                    break;
                case 'important':
                    $phase_readiness['important_met']++;
                    break;
                case 'optional':
                    $phase_readiness['optional_met']++;
                    break;
            }
        } else {
            if ($requirement['priority_level'] === 'essential') {
                $phase_readiness['missing_essential'][] = [
                    'worker_type' => $requirement['type_name'],
                    'required' => $min_required,
                    'available' => $available_count,
                    'shortage' => $min_required - $available_count
                ];
            }
            
            $phase_readiness['recommendations'][] = [
                'type' => 'shortage',
                'worker_type' => $requirement['type_name'],
                'priority' => $requirement['priority_level'],
                'message' => "Need " . ($min_required - $available_count) . " more " . $requirement['type_name'] . "(s)"
            ];
        }
    }
    
    // Add recommendations for optimal team composition
    foreach ($available_workers as $worker_type_id => $data) {
        $requirement = $data['requirement'];
        $workers = $data['workers'];
        
        $main_workers = array_filter($workers, function($w) { return $w['is_main_worker']; });
        $apprentices = array_filter($workers, function($w) { return $w['skill_level'] === 'apprentice'; });
        
        if (count($main_workers) === 0 && count($workers) > 0) {
            $phase_readiness['recommendations'][] = [
                'type' => 'leadership',
                'worker_type' => $requirement['type_name'],
                'priority' => 'important',
                'message' => "Consider designating a main " . $requirement['type_name'] . " for better coordination"
            ];
        }
        
        if (count($workers) > 2 && count($apprentices) === 0) {
            $phase_readiness['recommendations'][] = [
                'type' => 'cost_optimization',
                'worker_type' => $requirement['type_name'],
                'priority' => 'optional',
                'message' => "Consider adding apprentice " . $requirement['type_name'] . "(s) to reduce costs"
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'phase_info' => $phase_info,
            'requirements' => $requirements,
            'available_workers' => $available_workers,
            'worker_stats' => $worker_stats,
            'phase_readiness' => $phase_readiness
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get phase workers error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving phase workers: ' . $e->getMessage()
    ]);
}
?>
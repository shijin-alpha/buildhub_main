<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
    
    if (!$project_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit;
    }
    
    // Get AI prediction for the project — stored on construction_projects directly
    $prediction_query = "
        SELECT 
            predicted_cost_risk_level   AS cost_risk_level,
            predicted_cost_probability  AS cost_risk_probability,
            predicted_time_risk_level   AS time_risk_level,
            predicted_time_probability  AS time_risk_probability,
            ai_prediction_date          AS prediction_locked_at,
            ai_prediction_date          AS created_at
        FROM construction_projects
        WHERE id = :project_id
          AND predicted_cost_risk_level IS NOT NULL
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($prediction_query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $prediction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get project details
    $project_query = "
        SELECT 
            cp.id as project_id,
            cp.project_name,
            cp.estimated_cost as budget_amount,
            cp.status,
            cp.created_at,
            cp.actual_completion_date as completion_date,
            cp.plot_size,
            cp.project_location,
            cp.completion_percentage
        FROM construction_projects cp
        WHERE cp.id = :project_id
    ";
    
    $stmt = $conn->prepare($project_query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit;
    }
    
    // Get actual cost spent (from stage payments and custom payments)
    $cost_query = "
        SELECT 
            (SELECT COALESCE(SUM(requested_amount), 0) 
             FROM stage_payment_requests 
             WHERE project_id = ? AND status IN ('paid', 'approved')) +
            (SELECT COALESCE(SUM(requested_amount), 0) 
             FROM custom_payment_requests 
             WHERE project_id = ? AND status IN ('paid', 'approved'))
        as total_spent
    ";
    
    $stmt = $conn->prepare($cost_query);
    $stmt->execute([$project_id, $project_id]);
    $cost_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get progress timeline
    $timeline_query = "
        SELECT 
            DATE(created_at) as date,
            completion_percentage as actual_progress
        FROM construction_progress_updates
        WHERE project_id = :project_id
        ORDER BY created_at ASC
    ";
    
    $stmt = $conn->prepare($timeline_query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $timeline_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no progress updates, create a timeline point with current project status
    if (empty($timeline_data) && $project['completion_percentage'] > 0) {
        $timeline_data[] = [
            'date' => date('Y-m-d'),
            'actual_progress' => floatval($project['completion_percentage'])
        ];
    }
    
    // Calculate predicted progress based on linear timeline
    $start_date = new DateTime($project['created_at']);
    $current_date = new DateTime();
    $days_elapsed = $start_date->diff($current_date)->days;
    
    // Estimate total duration (you can adjust this based on your model)
    $estimated_duration_days = 180; // Default 6 months
    $predicted_progress = min(100, ($days_elapsed / $estimated_duration_days) * 100);
    
    // Add predicted progress to timeline
    foreach ($timeline_data as &$point) {
        $point_date = new DateTime($point['date']);
        $days_from_start = $start_date->diff($point_date)->days;
        $point['predicted_progress'] = min(100, ($days_from_start / $estimated_duration_days) * 100);
    }
    
    // Get current progress - use project's completion_percentage if no progress updates
    $current_progress = floatval($project['completion_percentage'] ?? 0);
    if (!empty($timeline_data)) {
        // If there are progress updates, use the latest one
        $current_progress = floatval($timeline_data[count($timeline_data) - 1]['actual_progress']);
    }
    
    // Get model performance metrics
    $metrics_query = "
        SELECT 
            metric_type,
            accuracy,
            precision_score,
            recall_score,
            f1_score
        FROM ai_evaluation_metrics
        ORDER BY calculated_at DESC
        LIMIT 2
    ";
    
    $stmt = $conn->prepare($metrics_query);
    $stmt->execute();
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cost_model_metrics = null;
    $time_model_metrics = null;
    
    foreach ($metrics as $metric) {
        if ($metric['metric_type'] === 'cost') {
            $cost_model_metrics = [
                'accuracy'  => round($metric['accuracy'] * 100, 1),
                'precision' => round($metric['precision_score'] * 100, 1),
                'recall'    => round($metric['recall_score'] * 100, 1),
                'f1_score'  => round($metric['f1_score'] * 100, 1)
            ];
        } elseif ($metric['metric_type'] === 'time') {
            $time_model_metrics = [
                'accuracy'  => round($metric['accuracy'] * 100, 1),
                'precision' => round($metric['precision_score'] * 100, 1),
                'recall'    => round($metric['recall_score'] * 100, 1),
                'f1_score'  => round($metric['f1_score'] * 100, 1)
            ];
        }
    }
    
    // Calculate overall accuracy
    $overall_accuracy = 0;
    if ($cost_model_metrics && $time_model_metrics) {
        $overall_accuracy = ($cost_model_metrics['accuracy'] + $time_model_metrics['accuracy']) / 2;
    }
    
    // Generate insights
    $insights = [];
    
    // Cost insights
    if ($prediction) {
        $budget = floatval($project['budget_amount']);
        $spent = floatval($cost_data['total_spent']);
        $remaining = $budget - $spent;
        $spend_percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;
        
        if ($prediction['cost_risk_level'] === 'High' && $spend_percentage > 70) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High Cost Risk Alert',
                'message' => 'Project was predicted as high cost risk and has already spent ' . 
                            round($spend_percentage, 1) . '% of budget. Monitor expenses closely.'
            ];
        }
        
        if ($spend_percentage < 50 && $current_progress > 60) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Budget Management',
                'message' => 'Project is ' . round($current_progress, 1) . '% complete with only ' . 
                            round($spend_percentage, 1) . '% of budget spent. Great cost control!'
            ];
        }
        
        // Time insights
        if ($prediction['time_risk_level'] === 'High' && $current_progress < $predicted_progress) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Schedule Delay Detected',
                'message' => 'Project is behind predicted schedule. Current progress: ' . 
                            round($current_progress, 1) . '%, Expected: ' . 
                            round($predicted_progress, 1) . '%'
            ];
        }
        
        if ($current_progress > $predicted_progress + 10) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Ahead of Schedule',
                'message' => 'Project is progressing faster than predicted! Current progress: ' . 
                            round($current_progress, 1) . '%, Expected: ' . 
                            round($predicted_progress, 1) . '%'
            ];
        }
        
        // Model confidence insights
        if ($prediction['cost_risk_probability'] > 0.9 || $prediction['time_risk_probability'] > 0.9) {
            $insights[] = [
                'type' => 'info',
                'title' => 'High Confidence Prediction',
                'message' => 'AI models show high confidence (>90%) in risk predictions. ' .
                            'Recommendations are highly reliable.'
            ];
        }
    }
    
    // Prepare risk probabilities
    $cost_risk_probs = [
        'Low' => 0,
        'Medium' => 0,
        'High' => 0
    ];
    
    if ($prediction) {
        $cost_risk_probs[$prediction['cost_risk_level']] = floatval($prediction['cost_risk_probability']);
        // Distribute remaining probability
        $remaining_prob = 1 - $cost_risk_probs[$prediction['cost_risk_level']];
        foreach ($cost_risk_probs as $level => $prob) {
            if ($prob == 0) {
                $cost_risk_probs[$level] = $remaining_prob / 2;
            }
        }
    }
    
    // Build response
    $response = [
        'success' => true,
        'data' => [
            'project' => [
                'id' => $project['project_id'],
                'name' => $project['project_name'],
                'status' => $project['status'],
                'budget' => floatval($project['budget_amount'])
            ],
            'prediction' => $prediction ? [
                'cost_risk_level' => $prediction['cost_risk_level'],
                'cost_risk_probability' => floatval($prediction['cost_risk_probability']),
                'cost_risk_probabilities' => $cost_risk_probs,
                'time_risk_level' => $prediction['time_risk_level'],
                'time_risk_probability' => floatval($prediction['time_risk_probability']),
                'locked' => !is_null($prediction['prediction_locked_at'])
            ] : null,
            'cost_analysis' => [
                'predicted_budget' => floatval($project['budget_amount']),
                'actual_spent' => floatval($cost_data['total_spent']),
                'remaining' => floatval($project['budget_amount']) - floatval($cost_data['total_spent']),
                'spend_percentage' => $project['budget_amount'] > 0 ? 
                    (floatval($cost_data['total_spent']) / floatval($project['budget_amount'])) * 100 : 0
            ],
            'time_analysis' => [
                'timeline' => $timeline_data,
                'current_progress' => $current_progress,
                'predicted_progress' => $predicted_progress,
                'days_elapsed' => $days_elapsed,
                'estimated_duration' => $estimated_duration_days
            ],
            'model_performance' => [
                'cost_model' => $cost_model_metrics,
                'time_model' => $time_model_metrics,
                'overall_accuracy' => $overall_accuracy
            ],
            'insights' => $insights
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

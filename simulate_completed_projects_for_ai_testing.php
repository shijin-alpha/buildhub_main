<?php
/**
 * Simulate Completed Projects for AI Evaluation Testing
 * 
 * This script creates realistic test projects with:
 * - AI predictions saved
 * - Projects completed with actual cost/time data
 * - Automatic evaluation triggered
 * 
 * Purpose: Generate data to test AI self-evaluation system
 */

require_once 'backend/config/database.php';

echo "=================================================================\n";
echo "Simulate Completed Projects for AI Evaluation Testing\n";
echo "=================================================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Test scenarios with different outcomes
    $test_scenarios = [
        [
            'name' => 'Accurate High Risk Prediction (TP)',
            'project_name' => 'Luxury Villa - High Risk',
            'estimated_cost' => 5000000,
            'estimated_duration' => 180,
            'predicted_cost_risk' => 'High',
            'predicted_cost_prob' => 0.85,
            'predicted_time_risk' => 'High',
            'predicted_time_prob' => 0.78,
            'actual_cost_overrun_pct' => 12.5,  // Over 5% threshold = High
            'actual_time_overrun_pct' => 15.0,  // Over 5% threshold = High
            'expected_result' => 'TP/TP - Correctly predicted both risks'
        ],
        [
            'name' => 'False Alarm (FP)',
            'project_name' => 'Simple House - False Alarm',
            'estimated_cost' => 2000000,
            'estimated_duration' => 120,
            'predicted_cost_risk' => 'High',
            'predicted_cost_prob' => 0.72,
            'predicted_time_risk' => 'Medium',
            'predicted_time_prob' => 0.55,
            'actual_cost_overrun_pct' => 2.0,   // Under 5% threshold = Low
            'actual_time_overrun_pct' => 3.0,   // Under 5% threshold = Low
            'expected_result' => 'FP/FP - Predicted high but was low'
        ],
        [
            'name' => 'Accurate Low Risk Prediction (TN)',
            'project_name' => 'Standard Home - Low Risk',
            'estimated_cost' => 3000000,
            'estimated_duration' => 150,
            'predicted_cost_risk' => 'Low',
            'predicted_cost_prob' => 0.15,
            'predicted_time_risk' => 'Low',
            'predicted_time_prob' => 0.20,
            'actual_cost_overrun_pct' => 1.5,   // Under 5% threshold = Low
            'actual_time_overrun_pct' => 2.5,   // Under 5% threshold = Low
            'expected_result' => 'TN/TN - Correctly predicted low risk'
        ],
        [
            'name' => 'Missed Risk (FN)',
            'project_name' => 'Complex Design - Missed Risk',
            'estimated_cost' => 4000000,
            'estimated_duration' => 200,
            'predicted_cost_risk' => 'Low',
            'predicted_cost_prob' => 0.25,
            'predicted_time_risk' => 'Low',
            'predicted_time_prob' => 0.18,
            'actual_cost_overrun_pct' => 18.0,  // Over 5% threshold = High
            'actual_time_overrun_pct' => 22.0,  // Over 5% threshold = High
            'expected_result' => 'FN/FN - Predicted low but was high'
        ],
        [
            'name' => 'Mixed Results',
            'project_name' => 'Modern Apartment - Mixed',
            'estimated_cost' => 3500000,
            'estimated_duration' => 160,
            'predicted_cost_risk' => 'High',
            'predicted_cost_prob' => 0.80,
            'predicted_time_risk' => 'Low',
            'predicted_time_prob' => 0.22,
            'actual_cost_overrun_pct' => 8.5,   // Over 5% = High (TP)
            'actual_time_overrun_pct' => 2.0,   // Under 5% = Low (TN)
            'expected_result' => 'TP/TN - Cost correct, Time correct'
        ]
    ];
    
    $created_projects = [];
    
    foreach ($test_scenarios as $index => $scenario) {
        echo "\n" . ($index + 1) . ". Creating: {$scenario['name']}\n";
        echo str_repeat("-", 60) . "\n";
        
        // Step 1: Create estimate first (required for foreign key)
        $estimate_query = "INSERT INTO contractor_send_estimates (
            contractor_id,
            homeowner_id,
            project_name,
            total_cost,
            timeline,
            status,
            created_at
        ) VALUES (29, :homeowner_id, :project_name, :total_cost, :timeline, 'accepted', NOW())";
        
        $stmt = $conn->prepare($estimate_query);
        $homeowner_id = 28; // Use existing homeowner
        $timeline = $scenario['estimated_duration'] . ' days';
        $stmt->execute([
            ':homeowner_id' => $homeowner_id,
            ':project_name' => $scenario['project_name'],
            ':total_cost' => $scenario['estimated_cost'],
            ':timeline' => $timeline
        ]);
        $estimate_id = $conn->lastInsertId();
        
        // Step 2: Create project
        $insert_query = "INSERT INTO construction_projects (
            estimate_id,
            contractor_id,
            homeowner_id,
            project_name,
            project_description,
            total_cost,
            timeline,
            status,
            created_at
        ) VALUES (:estimate_id, 29, :homeowner_id, :project_name, 'AI Evaluation Test Project', :total_cost, :timeline, 'created', NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->execute([
            ':estimate_id' => $estimate_id,
            ':homeowner_id' => $homeowner_id,
            ':project_name' => $scenario['project_name'],
            ':total_cost' => $scenario['estimated_cost'],
            ':timeline' => $timeline
        ]);
        $project_id = $conn->lastInsertId();
        
        echo "   ✓ Estimate and project created (ID: $project_id)\n";
        
        // Step 3: Save AI predictions
        $pred_query = "UPDATE construction_projects SET
            predicted_cost_risk_level = :cost_risk,
            predicted_cost_probability = :cost_prob,
            predicted_time_risk_level = :time_risk,
            predicted_time_probability = :time_prob,
            prediction_generated_at = NOW(),
            model_version = 'v1.0.0'
        WHERE id = :project_id";
        
        $stmt = $conn->prepare($pred_query);
        $stmt->execute([
            ':cost_risk' => $scenario['predicted_cost_risk'],
            ':cost_prob' => $scenario['predicted_cost_prob'],
            ':time_risk' => $scenario['predicted_time_risk'],
            ':time_prob' => $scenario['predicted_time_prob'],
            ':project_id' => $project_id
        ]);
        
        echo "   ✓ AI predictions saved\n";
        echo "     - Cost Risk: {$scenario['predicted_cost_risk']} ({$scenario['predicted_cost_prob']})\n";
        echo "     - Time Risk: {$scenario['predicted_time_risk']} ({$scenario['predicted_time_prob']})\n";
        
        // Step 4: Start project (locks predictions)
        $start_query = "UPDATE construction_projects SET
            start_date = DATE_SUB(NOW(), INTERVAL 90 DAY),
            status = 'in_progress',
            predictions_locked = 1
        WHERE id = :project_id";
        
        $stmt = $conn->prepare($start_query);
        $stmt->execute([':project_id' => $project_id]);
        
        echo "   ✓ Project started (predictions locked)\n";
        
        // Step 5: Simulate payments to create actual cost
        $actual_total_cost = $scenario['estimated_cost'] * (1 + $scenario['actual_cost_overrun_pct'] / 100);
        
        // Create stage payment
        $payment_query = "INSERT INTO stage_payment_requests (
            project_id,
            stage_name,
            amount,
            status,
            created_at
        ) VALUES (:project_id, 'Final Payment', :amount, 'paid', NOW())";
        
        $stmt = $conn->prepare($payment_query);
        $stmt->execute([
            ':project_id' => $project_id,
            ':amount' => $actual_total_cost
        ]);
        
        echo "   ✓ Payment recorded (₹" . number_format($actual_total_cost) . ")\n";
        
        // Step 6: Calculate actual time overrun
        $planned_end = "DATE_ADD(DATE_SUB(NOW(), INTERVAL 90 DAY), INTERVAL {$scenario['estimated_duration']} DAY)";
        $actual_duration = $scenario['estimated_duration'] * (1 + $scenario['actual_time_overrun_pct'] / 100);
        $actual_end = "DATE_ADD(DATE_SUB(NOW(), INTERVAL 90 DAY), INTERVAL $actual_duration DAY)";
        
        $time_query = "UPDATE construction_projects SET
            expected_completion_date = $planned_end,
            actual_completion_date = $actual_end,
            actual_time_overrun_percentage = :time_overrun
        WHERE id = :project_id";
        
        $stmt = $conn->prepare($time_query);
        $stmt->execute([
            ':time_overrun' => $scenario['actual_time_overrun_pct'],
            ':project_id' => $project_id
        ]);
        
        echo "   ✓ Schedule data recorded\n";
        
        // Step 7: Complete project (triggers evaluation)
        $complete_query = "UPDATE construction_projects SET
            status = 'completed',
            actual_cost_overrun_percentage = :cost_overrun
        WHERE id = :project_id";
        
        $stmt = $conn->prepare($complete_query);
        $stmt->execute([
            ':cost_overrun' => $scenario['actual_cost_overrun_pct'],
            ':project_id' => $project_id
        ]);
        
        echo "   ✓ Project completed\n";
        
        // Step 8: Manually trigger evaluation
        $eval_query = "CALL evaluate_project_predictions(:project_id)";
        $stmt = $conn->prepare($eval_query);
        $stmt->execute([':project_id' => $project_id]);
        
        echo "   ✓ Evaluation triggered\n";
        
        // Step 9: Get evaluation results
        $result_query = "SELECT 
            cost_prediction_classification,
            time_prediction_classification,
            cost_prediction_correct,
            time_prediction_correct,
            actual_cost_overrun_percentage,
            actual_time_overrun_percentage
        FROM construction_projects WHERE id = :project_id";
        
        $stmt = $conn->prepare($result_query);
        $stmt->execute([':project_id' => $project_id]);
        $result = $stmt->fetch();
        
        echo "\n   📊 EVALUATION RESULTS:\n";
        echo "   - Cost: {$result['cost_prediction_classification']} ";
        echo ($result['cost_prediction_correct'] ? "✓ Correct" : "✗ Wrong") . "\n";
        echo "   - Time: {$result['time_prediction_classification']} ";
        echo ($result['time_prediction_correct'] ? "✓ Correct" : "✗ Wrong") . "\n";
        echo "   - Actual Cost Overrun: {$result['actual_cost_overrun_percentage']}%\n";
        echo "   - Actual Time Overrun: {$result['actual_time_overrun_percentage']}%\n";
        echo "   - Expected: {$scenario['expected_result']}\n";
        
        $created_projects[] = [
            'id' => $project_id,
            'name' => $scenario['project_name'],
            'scenario' => $scenario['name']
        ];
    }
    
    // Calculate aggregate metrics
    echo "\n\n" . str_repeat("=", 60) . "\n";
    echo "CALCULATING AGGREGATE METRICS\n";
    echo str_repeat("=", 60) . "\n";
    
    $conn->query("CALL update_aggregated_metrics()");
    
    // Display metrics
    $metrics_query = "SELECT * FROM v_latest_ai_metrics ORDER BY metric_type";
    $metrics_result = $conn->query($metrics_query);
    
    while ($metric = $metrics_result->fetch()) {
        echo "\n" . strtoupper($metric['metric_type']) . " PREDICTIONS:\n";
        echo "  Confusion Matrix:\n";
        echo "    TP: {$metric['true_positives']}, FP: {$metric['false_positives']}\n";
        echo "    TN: {$metric['true_negatives']}, FN: {$metric['false_negatives']}\n";
        echo "  Performance:\n";
        echo "    Accuracy:  {$metric['accuracy']}%\n";
        echo "    Precision: {$metric['precision_score']}%\n";
        echo "    Recall:    {$metric['recall_score']}%\n";
        echo "    F1 Score:  {$metric['f1_score']}%\n";
    }
    
    echo "\n\n" . str_repeat("=", 60) . "\n";
    echo "✅ SIMULATION COMPLETE!\n";
    echo str_repeat("=", 60) . "\n";
    echo "\nCreated " . count($created_projects) . " test projects:\n";
    foreach ($created_projects as $proj) {
        echo "  • Project {$proj['id']}: {$proj['name']}\n";
        echo "    ({$proj['scenario']})\n";
    }
    
    echo "\n📊 Next Steps:\n";
    echo "  1. View metrics: GET /backend/api/ml/get_evaluation_metrics.php?action=latest\n";
    echo "  2. View confusion matrix: GET /backend/api/ml/get_evaluation_metrics.php?action=confusion_matrix\n";
    echo "  3. View project details: GET /backend/api/ml/get_evaluation_metrics.php?action=project_performance\n";
    echo "  4. Check homeowner dashboard to see completed projects\n";
    
    $conn = null; // Close PDO connection
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

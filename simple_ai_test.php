<?php
/**
 * Simple AI Evaluation Test
 * Uses existing projects and adds AI predictions + evaluations
 */

require_once 'backend/config/database.php';

echo "=================================================================\n";
echo "Simple AI Evaluation Test\n";
echo "=================================================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get existing projects
    $query = "SELECT id, project_name, total_cost, status FROM construction_projects WHERE id IN (1, 2, 3) ORDER BY id";
    $result = $conn->query($query);
    $projects = $result->fetchAll();
    
    if (count($projects) == 0) {
        echo "❌ No projects found. Please ensure projects with IDs 1, 2, 3 exist.\n";
        exit(1);
    }
    
    echo "Found " . count($projects) . " projects to test with.\n\n";
    
    // Test scenarios
    $scenarios = [
        ['cost_risk' => 'High', 'cost_prob' => 0.85, 'time_risk' => 'High', 'time_prob' => 0.78, 'cost_overrun' => 12.5, 'time_overrun' => 15.0],
        ['cost_risk' => 'Low', 'cost_prob' => 0.15, 'time_risk' => 'Low', 'time_prob' => 0.20, 'cost_overrun' => 2.0, 'time_overrun' => 3.0],
        ['cost_risk' => 'High', 'cost_prob' => 0.80, 'time_risk' => 'Low', 'time_prob' => 0.22, 'cost_overrun' => 8.5, 'time_overrun' => 2.0],
    ];
    
    foreach ($projects as $index => $project) {
        $scenario = $scenarios[$index];
        $project_id = $project['id'];
        
        echo ($index + 1) . ". Testing Project ID {$project_id}: {$project['project_name']}\n";
        echo str_repeat("-", 60) . "\n";
        
        // Add AI predictions
        $pred_query = "UPDATE construction_projects SET
            predicted_cost_risk_level = :cost_risk,
            predicted_cost_probability = :cost_prob,
            predicted_time_risk_level = :time_risk,
            predicted_time_probability = :time_prob,
            prediction_generated_at = NOW(),
            model_version = 'v1.0.0',
            predictions_locked = 1
        WHERE id = :project_id";
        
        $stmt = $conn->prepare($pred_query);
        $stmt->execute([
            ':cost_risk' => $scenario['cost_risk'],
            ':cost_prob' => $scenario['cost_prob'],
            ':time_risk' => $scenario['time_risk'],
            ':time_prob' => $scenario['time_prob'],
            ':project_id' => $project_id
        ]);
        
        echo "   ✓ AI predictions saved\n";
        echo "     - Cost: {$scenario['cost_risk']} ({$scenario['cost_prob']})\n";
        echo "     - Time: {$scenario['time_risk']} ({$scenario['time_prob']})\n";
        
        // Set to completed with overruns
        $complete_query = "UPDATE construction_projects SET
            status = 'completed',
            actual_cost_overrun_percentage = :cost_overrun,
            actual_time_overrun_percentage = :time_overrun
        WHERE id = :project_id";
        
        $stmt = $conn->prepare($complete_query);
        $stmt->execute([
            ':cost_overrun' => $scenario['cost_overrun'],
            ':time_overrun' => $scenario['time_overrun'],
            ':project_id' => $project_id
        ]);
        
        echo "   ✓ Project marked completed with overruns\n";
        echo "     - Cost overrun: {$scenario['cost_overrun']}%\n";
        echo "     - Time overrun: {$scenario['time_overrun']}%\n";
        
        // Trigger evaluation
        try {
            $eval_query = "CALL evaluate_project_predictions(:project_id)";
            $stmt = $conn->prepare($eval_query);
            $stmt->execute([':project_id' => $project_id]);
            echo "   ✓ Evaluation completed\n\n";
        } catch (Exception $e) {
            echo "   ⚠ Evaluation procedure not found (run migration first)\n\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ TEST COMPLETE!\n";
    echo str_repeat("=", 60) . "\n";
    
    // Show results
    echo "\nResults:\n";
    $results_query = "SELECT 
        id,
        project_name,
        predicted_cost_risk_level,
        actual_cost_overrun_percentage,
        cost_prediction_classification,
        cost_prediction_correct
    FROM construction_projects 
    WHERE id IN (1, 2, 3)
    ORDER BY id";
    
    $results = $conn->query($results_query);
    while ($row = $results->fetch()) {
        echo "\nProject {$row['id']}: {$row['project_name']}\n";
        echo "  Predicted: {$row['predicted_cost_risk_level']}\n";
        echo "  Actual Overrun: {$row['actual_cost_overrun_percentage']}%\n";
        echo "  Classification: {$row['cost_prediction_classification']}\n";
        echo "  Correct: " . ($row['cost_prediction_correct'] ? "Yes ✓" : "No ✗") . "\n";
    }
    
    echo "\n📊 View full metrics at:\n";
    echo "   http://localhost/backend/api/ml/get_evaluation_metrics.php?action=latest\n";
    
    $conn = null;
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

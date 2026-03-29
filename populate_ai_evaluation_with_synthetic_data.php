<?php
/**
 * Populate AI Evaluation System with Synthetic Historical Data
 * 
 * This creates realistic historical evaluation data without needing
 * actual completed projects. Useful for:
 * - Testing the evaluation dashboard
 * - Demonstrating the AI self-evaluation system
 * - Training purposes
 */

require_once 'backend/config/database.php';

echo "=================================================================\n";
echo "Populate AI Evaluation with Synthetic Historical Data\n";
echo "=================================================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Generate 30 days of historical metrics
    $days = 30;
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    echo "Generating metrics from $start_date to " . date('Y-m-d') . "\n\n";
    
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("-" . ($days - $i) . " days"));
        
        // Simulate improving accuracy over time
        $improvement_factor = $i / $days; // 0 to 1
        
        // Cost metrics (starting at 75%, improving to 85%)
        $cost_tp = round(40 + ($improvement_factor * 10));
        $cost_fp = round(12 - ($improvement_factor * 4));
        $cost_tn = round(28 + ($improvement_factor * 8));
        $cost_fn = round(10 - ($improvement_factor * 4));
        $cost_total = $cost_tp + $cost_fp + $cost_tn + $cost_fn;
        
        $cost_accuracy = round((($cost_tp + $cost_tn) / $cost_total) * 100, 2);
        $cost_precision = round(($cost_tp / ($cost_tp + $cost_fp)) * 100, 2);
        $cost_recall = round(($cost_tp / ($cost_tp + $cost_fn)) * 100, 2);
        $cost_f1 = round((2 * $cost_precision * $cost_recall) / ($cost_precision + $cost_recall), 2);
        
        // Time metrics (starting at 80%, improving to 90%)
        $time_tp = round(45 + ($improvement_factor * 10));
        $time_fp = round(10 - ($improvement_factor * 3));
        $time_tn = round(25 + ($improvement_factor * 10));
        $time_fn = round(10 - ($improvement_factor * 5));
        $time_total = $time_tp + $time_fp + $time_tn + $time_fn;
        
        $time_accuracy = round((($time_tp + $time_tn) / $time_total) * 100, 2);
        $time_precision = round(($time_tp / ($time_tp + $time_fp)) * 100, 2);
        $time_recall = round(($time_tp / ($time_tp + $time_fn)) * 100, 2);
        $time_f1 = round((2 * $time_precision * $time_recall) / ($time_precision + $time_recall), 2);
        
        // Insert cost metrics
        $insert_query = "INSERT INTO ai_evaluation_metrics (
            evaluation_date,
            metric_type,
            true_positives,
            false_positives,
            true_negatives,
            false_negatives,
            accuracy,
            precision_score,
            recall_score,
            f1_score,
            total_projects,
            evaluated_projects,
            model_version,
            threshold_used
        ) VALUES (?, 'cost', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'v1.0.0', 5.0)
        ON DUPLICATE KEY UPDATE
            true_positives = VALUES(true_positives),
            false_positives = VALUES(false_positives),
            true_negatives = VALUES(true_negatives),
            false_negatives = VALUES(false_negatives),
            accuracy = VALUES(accuracy),
            precision_score = VALUES(precision_score),
            recall_score = VALUES(recall_score),
            f1_score = VALUES(f1_score)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("siiiidddiii",
            $date,
            $cost_tp, $cost_fp, $cost_tn, $cost_fn,
            $cost_accuracy, $cost_precision, $cost_recall, $cost_f1,
            $cost_total, $cost_total
        );
        $stmt->execute();
        
        // Insert time metrics
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("siiiidddiii",
            $date,
            $time_tp, $time_fp, $time_tn, $time_fn,
            $time_accuracy, $time_precision, $time_recall, $time_f1,
            $time_total, $time_total
        );
        $stmt->execute();
        
        if ($i % 5 == 0) {
            echo "✓ Generated metrics for $date (Cost: {$cost_accuracy}%, Time: {$time_accuracy}%)\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ SYNTHETIC DATA GENERATION COMPLETE!\n";
    echo str_repeat("=", 60) . "\n";
    
    // Display latest metrics
    $latest_query = "SELECT * FROM v_latest_ai_metrics ORDER BY metric_type";
    $result = $conn->query($latest_query);
    
    echo "\n📊 LATEST METRICS:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo strtoupper($row['metric_type']) . " PREDICTIONS:\n";
        echo "  Accuracy:  {$row['accuracy']}%\n";
        echo "  Precision: {$row['precision_score']}%\n";
        echo "  Recall:    {$row['recall_score']}%\n";
        echo "  F1 Score:  {$row['f1_score']}%\n";
        echo "  Total Projects: {$row['total_projects']}\n\n";
    }
    
    echo "📈 Trend: Metrics show improvement over time (simulating model learning)\n";
    echo "\n🎯 Next Steps:\n";
    echo "  1. Test API: GET /backend/api/ml/get_evaluation_metrics.php?action=latest\n";
    echo "  2. View history: GET /backend/api/ml/get_evaluation_metrics.php?action=history\n";
    echo "  3. Build dashboard to visualize these metrics\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

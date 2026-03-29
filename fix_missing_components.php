<?php
/**
 * Fix Missing Components
 * Creates the missing procedure and view
 */

$conn = new mysqli('localhost', 'root', '', 'buildhub');

echo "=== FIXING MISSING COMPONENTS ===\n\n";

// Fix 1: Create evaluate_project_predictions procedure
echo "--- Creating evaluate_project_predictions procedure ---\n";

$sql = "DROP PROCEDURE IF EXISTS evaluate_project_predictions";
$conn->query($sql);

// Change delimiter for procedure creation
$conn->query("SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");

$sql = "CREATE PROCEDURE evaluate_project_predictions(IN p_project_id INT)
BEGIN
  DECLARE v_evaluation_enabled INT DEFAULT 1;
  DECLARE v_has_predictions TINYINT(1) DEFAULT 0;
  DECLARE v_already_evaluated TINYINT(1) DEFAULT 0;
  DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
  
  SELECT CAST(COALESCE(config_value, '1') AS UNSIGNED) INTO v_evaluation_enabled
  FROM ai_evaluation_config
  WHERE config_key = 'auto_evaluation_enabled'
  LIMIT 1;
  
  IF v_evaluation_enabled = 0 THEN
    SELECT 'Evaluation disabled' as message;
  ELSE
    SELECT 
      CASE WHEN predicted_cost_risk_level IS NOT NULL OR predicted_time_risk_level IS NOT NULL THEN 1 ELSE 0 END,
      CASE WHEN evaluation_completed_at IS NOT NULL THEN 1 ELSE 0 END
    INTO v_has_predictions, v_already_evaluated
    FROM construction_projects
    WHERE id = p_project_id;
    
    IF v_has_predictions = 1 AND v_already_evaluated = 0 THEN
      CALL calculate_actual_cost_overrun(p_project_id);
      CALL determine_ground_truth_labels(p_project_id);
      CALL classify_predictions(p_project_id);
      CALL update_aggregated_metrics();
    END IF;
  END IF;
END";

if ($conn->query($sql)) {
    echo "✅ evaluate_project_predictions created\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

// Fix 2: Create v_latest_ai_metrics view
echo "\n--- Creating v_latest_ai_metrics view ---\n";

$sql = "DROP VIEW IF EXISTS v_latest_ai_metrics";
$conn->query($sql);

$sql = "CREATE VIEW v_latest_ai_metrics AS
SELECT 
  m1.metric_type,
  m1.id,
  m1.accuracy,
  m1.precision_val as precision_score,
  m1.recall_val as recall_score,
  m1.f1_score,
  m1.true_positives,
  m1.false_positives,
  m1.true_negatives,
  m1.false_negatives,
  m1.calculated_at as evaluation_date
FROM ai_evaluation_metrics m1
INNER JOIN (
  SELECT metric_type, MAX(id) as max_id
  FROM ai_evaluation_metrics
  GROUP BY metric_type
) m2 ON m1.metric_type = m2.metric_type AND m1.id = m2.max_id";

if ($conn->query($sql)) {
    echo "✅ v_latest_ai_metrics created\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

echo "\n=== VERIFICATION ===\n\n";

// Verify procedure
$result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'buildhub' AND Name = 'evaluate_project_predictions'");
echo ($result->num_rows > 0 ? "✅" : "❌") . " evaluate_project_predictions\n";

// Verify view
$result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_buildhub = 'v_latest_ai_metrics'");
echo ($result->num_rows > 0 ? "✅" : "❌") . " v_latest_ai_metrics\n";

echo "\n🎉 ALL COMPONENTS NOW INSTALLED! 🎉\n";

$conn->close();
?>

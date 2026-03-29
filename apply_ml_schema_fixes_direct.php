<?php
/**
 * Apply ML Schema Fixes Directly
 * Executes each ALTER TABLE statement individually with error reporting
 */

require_once 'backend/config/database.php';

echo "=" . str_repeat("=", 79) . "\n";
echo "ML SCHEMA FIXES - DIRECT APPLICATION\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$database = new Database();
$conn = $database->getConnection();

$success_count = 0;
$error_count = 0;

// ============================================================================
// Fix 1: layout_requests prediction columns
// ============================================================================
echo "Fix 1: Adding prediction columns to layout_requests\n";
echo str_repeat("-", 79) . "\n";

$columns = [
    "predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level'",
    "predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)'",
    "predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level'",
    "predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)'",
    "prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made'",
    "model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction'",
    "prediction_features JSON NULL COMMENT 'Features used for prediction (for retraining)'",
    "prediction_explanation JSON NULL COMMENT 'Top risk factors and explanations'"
];

foreach ($columns as $column) {
    $col_name = explode(' ', $column)[0];
    
    try {
        $sql = "ALTER TABLE layout_requests ADD COLUMN $column";
        $conn->exec($sql);
        echo "  ✓ Added column: $col_name\n";
        $success_count++;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ Column already exists: $col_name\n";
        } else {
            echo "  ✗ Error adding $col_name: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}

// Add index
try {
    $conn->exec("CREATE INDEX idx_layout_predictions ON layout_requests(predicted_cost_risk_level, predicted_time_risk_level)");
    echo "  ✓ Added index: idx_layout_predictions\n";
    $success_count++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate key') !== false) {
        echo "  ℹ Index already exists: idx_layout_predictions\n";
    } else {
        echo "  ⚠ Index warning: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ============================================================================
// Fix 2: contractor_send_estimates prediction columns
// ============================================================================
echo "Fix 2: Adding prediction columns to contractor_send_estimates\n";
echo str_repeat("-", 79) . "\n";

$columns = [
    "predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'Copied from layout_requests'",
    "predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Copied from layout_requests'",
    "predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'Copied from layout_requests'",
    "predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Copied from layout_requests'",
    "model_version VARCHAR(50) NULL COMMENT 'Copied from layout_requests'",
    "prediction_explanation JSON NULL COMMENT 'Copied from layout_requests'"
];

foreach ($columns as $column) {
    $col_name = explode(' ', $column)[0];
    
    try {
        $sql = "ALTER TABLE contractor_send_estimates ADD COLUMN $column";
        $conn->exec($sql);
        echo "  ✓ Added column: $col_name\n";
        $success_count++;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ Column already exists: $col_name\n";
        } else {
            echo "  ✗ Error adding $col_name: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}

echo "\n";

// ============================================================================
// Fix 3: construction_projects evaluation columns
// ============================================================================
echo "Fix 3: Adding evaluation columns to construction_projects\n";
echo str_repeat("-", 79) . "\n";

$columns = [
    "cost_medium_threshold DECIMAL(5,2) DEFAULT 5.0 COMMENT '% threshold for medium cost risk'",
    "cost_high_threshold DECIMAL(5,2) DEFAULT 15.0 COMMENT '% threshold for high cost risk'",
    "time_medium_threshold DECIMAL(5,2) DEFAULT 5.0 COMMENT '% threshold for medium time risk'",
    "time_high_threshold DECIMAL(5,2) DEFAULT 15.0 COMMENT '% threshold for high time risk'",
    "cost_ground_truth_label ENUM('Low', 'Medium', 'High') NULL COMMENT 'Actual cost risk category'",
    "time_ground_truth_label ENUM('Low', 'Medium', 'High') NULL COMMENT 'Actual time risk category'",
    "cost_prediction_correct TINYINT(1) NULL COMMENT 'Was cost prediction correct?'",
    "time_prediction_correct TINYINT(1) NULL COMMENT 'Was time prediction correct?'",
    "evaluation_completed_at TIMESTAMP NULL COMMENT 'When evaluation was completed'"
];

foreach ($columns as $column) {
    $col_name = explode(' ', $column)[0];
    
    try {
        $sql = "ALTER TABLE construction_projects ADD COLUMN $column";
        $conn->exec($sql);
        echo "  ✓ Added column: $col_name\n";
        $success_count++;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ℹ Column already exists: $col_name\n";
        } else {
            echo "  ✗ Error adding $col_name: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}

echo "\n";

// ============================================================================
// Verification
// ============================================================================
echo str_repeat("=", 79) . "\n";
echo "VERIFICATION\n";
echo str_repeat("=", 79) . "\n\n";

// Check layout_requests
$stmt = $conn->query("SHOW COLUMNS FROM layout_requests LIKE 'predicted%'");
$count = $stmt->rowCount();
echo "layout_requests prediction columns: $count\n";

// Check contractor_send_estimates
$stmt = $conn->query("SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'");
$count = $stmt->rowCount();
echo "contractor_send_estimates prediction columns: $count\n";

// Check construction_projects
$stmt = $conn->query("SHOW COLUMNS FROM construction_projects WHERE Field LIKE '%threshold%' OR Field LIKE '%ground_truth%'");
$count = $stmt->rowCount();
echo "construction_projects evaluation columns: $count\n";

echo "\n" . str_repeat("=", 79) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 79) . "\n\n";

echo "Successful operations: $success_count\n";
echo "Errors: $error_count\n\n";

if ($error_count == 0) {
    echo "✅ ALL SCHEMA FIXES APPLIED SUCCESSFULLY!\n";
} else {
    echo "⚠️  SOME ERRORS OCCURRED - Please review above\n";
}

echo "\n" . str_repeat("=", 79) . "\n";

$conn = null;
?>

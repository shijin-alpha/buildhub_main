<?php
/**
 * System Verification Script - Database Check
 * Verifies all required tables, triggers, and procedures exist
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CONSTRUCTION AI SYSTEM VERIFICATION ===\n\n";

try {
    $conn = new mysqli('localhost', 'root', '', 'buildhub');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Database connection successful\n\n";
    
    // Check required tables
    echo "--- CHECKING REQUIRED TABLES ---\n";
    $required_tables = [
        'construction_projects',
        'contractor_send_estimates',
        'stage_payment_requests',
        'custom_payment_requests',
        'daily_progress_updates',
        'ai_evaluation_config',
        'ai_evaluation_metrics',
        'ai_prediction_audit'
    ];
    
    $result = $conn->query("SHOW TABLES");
    $existing_tables = [];
    while ($row = $result->fetch_array()) {
        $existing_tables[] = $row[0];
    }
    
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            echo "✅ $table exists\n";
        } else {
            echo "❌ $table MISSING\n";
        }
    }
    
    // Check construction_projects columns
    echo "\n--- CHECKING construction_projects PREDICTION COLUMNS ---\n";
    $required_columns = [
        'predicted_cost_risk_level',
        'predicted_cost_probability',
        'predicted_time_risk_level',
        'predicted_time_probability',
        'prediction_generated_at',
        'model_version',
        'predictions_locked',
        'actual_cost_overrun_percentage',
        'actual_time_overrun_percentage',
        'cost_ground_truth_label',
        'time_ground_truth_label',
        'cost_prediction_classification',
        'time_prediction_classification',
        'cost_prediction_correct',
        'time_prediction_correct',
        'evaluation_completed_at'
    ];
    
    $result = $conn->query("DESCRIBE construction_projects");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($required_columns as $column) {
        if (in_array($column, $existing_columns)) {
            echo "✅ $column exists\n";
        } else {
            echo "❌ $column MISSING\n";
        }
    }
    
    // Check contractor_send_estimates columns
    echo "\n--- CHECKING contractor_send_estimates PREDICTION COLUMNS ---\n";
    $estimate_columns = [
        'predicted_cost_risk_level',
        'predicted_cost_probability',
        'predicted_time_risk_level',
        'predicted_time_probability',
        'prediction_generated_at',
        'model_version'
    ];
    
    $result = $conn->query("DESCRIBE contractor_send_estimates");
    $existing_estimate_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_estimate_columns[] = $row['Field'];
    }
    
    foreach ($estimate_columns as $column) {
        if (in_array($column, $existing_estimate_columns)) {
            echo "✅ $column exists\n";
        } else {
            echo "❌ $column MISSING\n";
        }
    }
    
    // Check triggers
    echo "\n--- CHECKING DATABASE TRIGGERS ---\n";
    $required_triggers = [
        'copy_predictions_to_project',
        'lock_predictions_on_start',
        'auto_evaluate_on_completion'
    ];
    
    $result = $conn->query("SHOW TRIGGERS");
    $existing_triggers = [];
    while ($row = $result->fetch_assoc()) {
        $existing_triggers[] = $row['Trigger'];
    }
    
    foreach ($required_triggers as $trigger) {
        if (in_array($trigger, $existing_triggers)) {
            echo "✅ $trigger exists\n";
        } else {
            echo "❌ $trigger MISSING\n";
        }
    }
    
    // Check stored procedures
    echo "\n--- CHECKING STORED PROCEDURES ---\n";
    $required_procedures = [
        'evaluate_project_predictions',
        'calculate_actual_cost_overrun',
        'determine_ground_truth_labels',
        'classify_predictions',
        'update_aggregated_metrics'
    ];
    
    $result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'buildhub'");
    $existing_procedures = [];
    while ($row = $result->fetch_assoc()) {
        $existing_procedures[] = $row['Name'];
    }
    
    foreach ($required_procedures as $procedure) {
        if (in_array($procedure, $existing_procedures)) {
            echo "✅ $procedure exists\n";
        } else {
            echo "❌ $procedure MISSING\n";
        }
    }
    
    // Check views
    echo "\n--- CHECKING DATABASE VIEWS ---\n";
    $required_views = [
        'v_latest_ai_metrics',
        'v_project_evaluation_summary',
        'v_confusion_matrix_breakdown'
    ];
    
    $result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $existing_views = [];
    while ($row = $result->fetch_array()) {
        $existing_views[] = $row[0];
    }
    
    foreach ($required_views as $view) {
        if (in_array($view, $existing_views)) {
            echo "✅ $view exists\n";
        } else {
            echo "❌ $view MISSING\n";
        }
    }
    
    // Check data
    echo "\n--- CHECKING DATA PRESENCE ---\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM construction_projects");
    $row = $result->fetch_assoc();
    echo "Projects: " . $row['count'] . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM construction_projects WHERE predicted_cost_risk_level IS NOT NULL");
    $row = $result->fetch_assoc();
    echo "Projects with predictions: " . $row['count'] . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM construction_projects WHERE predictions_locked = 1");
    $row = $result->fetch_assoc();
    echo "Projects with locked predictions: " . $row['count'] . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM construction_projects WHERE status = 'completed'");
    $row = $result->fetch_assoc();
    echo "Completed projects: " . $row['count'] . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM construction_projects WHERE evaluation_completed_at IS NOT NULL");
    $row = $result->fetch_assoc();
    echo "Evaluated projects: " . $row['count'] . "\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM ai_evaluation_metrics");
    $row = $result->fetch_assoc();
    echo "Evaluation metrics records: " . $row['count'] . "\n";
    
    echo "\n=== VERIFICATION COMPLETE ===\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

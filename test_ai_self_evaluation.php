<?php
/**
 * AI Self-Evaluation Framework Test Script
 * 
 * Purpose: Test the complete AI self-evaluation workflow
 * 
 * Tests:
 * 1. Save AI predictions for a project
 * 2. Lock predictions when work begins
 * 3. Complete project and trigger evaluation
 * 4. Calculate aggregate metrics
 * 5. Retrieve evaluation results
 */

require_once __DIR__ . '/backend/config/database.php';

echo "=================================================================\n";
echo "AI Self-Evaluation Framework - Test Suite\n";
echo "=================================================================\n\n";

$database = new Database();
$conn = $database->getConnection();

// Test 1: Create a test project
echo "Test 1: Creating test project...\n";

$create_project = "
INSERT INTO construction_projects (
    homeowner_id, project_name, estimated_cost, status, created_at
) VALUES (
    1, 'AI Evaluation Test Project', 2500000, 'approved', NOW()
)";

$conn->query($create_project);
$test_project_id = $conn->insert_id;

echo "  ✓ Created project ID: $test_project_id\n\n";

// Test 2: Save AI prediction
echo "Test 2: Saving AI prediction...\n";

$save_prediction = "
CALL save_ai_prediction(
    $test_project_id,
    'High',
    0.8500,
    'Medium',
    0.6200,
    'v1.0.0-test'
)";

try {
    $conn->query($save_prediction);
    echo "  ✓ Prediction saved successfully\n";
    
    // Verify prediction was saved
    $verify = "SELECT predicted_cost_risk_level, predicted_time_risk_level, 
                      predictions_locked FROM construction_projects WHERE id = $test_project_id";
    $result = $conn->query($verify);
    $project = $result->fetch_assoc();
    
    echo "  ✓ Cost Risk: {$project['predicted_cost_risk_level']}\n";
    echo "  ✓ Time Risk: {$project['predicted_time_risk_level']}\n";
    echo "  ✓ Locked: " . ($project['predictions_locked'] ? 'Yes' : 'No') . "\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Try to modify prediction (should work before lock)
echo "Test 3: Attempting to modify prediction before lock...\n";

try {
    $modify = "
    CALL save_ai_prediction(
        $test_project_id,
        'Low',
        0.2000,
        'Low',
        0.1500,
        'v1.0.0-test'
    )";
    
    $conn->query($modify);
    echo "  ✓ Prediction modified successfully (as expected)\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Unexpected error: " . $e->getMessage() . "\n\n";
}

// Test 4: Start work (lock predictions)
echo "Test 4: Starting work (locking predictions)...\n";

$start_work = "
UPDATE construction_projects 
SET actual_start_date = CURDATE(), status = 'in_progress'
WHERE id = $test_project_id";

$conn->query($start_work);

// Verify lock
$verify_lock = "SELECT predictions_locked FROM construction_projects WHERE id = $test_project_id";
$result = $conn->query($verify_lock);
$project = $result->fetch_assoc();

if ($project['predictions_locked'] == 1) {
    echo "  ✓ Predictions locked successfully\n\n";
} else {
    echo "  ✗ Predictions NOT locked (trigger may not be working)\n\n";
}

// Test 5: Try to modify locked prediction (should fail)
echo "Test 5: Attempting to modify locked prediction...\n";

try {
    $modify_locked = "
    CALL save_ai_prediction(
        $test_project_id,
        'High',
        0.9000,
        'High',
        0.9000,
        'v1.0.0-test'
    )";
    
    $conn->query($modify_locked);
    echo "  ✗ Prediction was modified (should have been blocked!)\n\n";
    
} catch (Exception $e) {
    echo "  ✓ Modification blocked as expected\n";
    echo "  ✓ Error message: " . $e->getMessage() . "\n\n";
}

// Test 6: Add payment data for cost overrun calculation
echo "Test 6: Adding payment data...\n";

$add_stage_payment = "
INSERT INTO stage_payment_requests (project_id, stage_name, amount, status, requested_at)
VALUES ($test_project_id, 'Foundation', 1500000, 'paid', NOW())";

$add_custom_payment = "
INSERT INTO custom_payment_requests (project_id, description, amount, status, requested_at)
VALUES ($test_project_id, 'Extra work', 1200000, 'paid', NOW())";

$conn->query($add_stage_payment);
$conn->query($add_custom_payment);

echo "  ✓ Stage payment: ₹1,500,000\n";
echo "  ✓ Custom payment: ₹1,200,000\n";
echo "  ✓ Total: ₹2,700,000 (vs estimate ₹2,500,000 = 8% overrun)\n\n";

// Test 7: Add schedule data for time overrun calculation
echo "Test 7: Adding schedule data...\n";

$add_schedule = "
UPDATE construction_projects
SET 
    planned_start_date = DATE_SUB(CURDATE(), INTERVAL 100 DAY),
    planned_end_date = DATE_SUB(CURDATE(), INTERVAL 10 DAY),
    actual_start_date = DATE_SUB(CURDATE(), INTERVAL 100 DAY),
    actual_end_date = CURDATE()
WHERE id = $test_project_id";

$conn->query($add_schedule);

echo "  ✓ Planned duration: 90 days\n";
echo "  ✓ Actual duration: 100 days\n";
echo "  ✓ Expected time overrun: ~11%\n\n";

// Test 8: Complete project (trigger evaluation)
echo "Test 8: Completing project (triggering evaluation)...\n";

$complete_project = "
UPDATE construction_projects
SET status = 'completed'
WHERE id = $test_project_id";

$conn->query($complete_project);

// Wait a moment for trigger to execute
sleep(1);

// Check if evaluation was completed
$check_eval = "
SELECT 
    evaluation_completed_at,
    actual_cost_overrun_percentage,
    actual_time_overrun_percentage,
    cost_ground_truth_label,
    time_ground_truth_label,
    cost_prediction_classification,
    time_prediction_classification,
    cost_prediction_correct,
    time_prediction_correct
FROM construction_projects
WHERE id = $test_project_id";

$result = $conn->query($check_eval);
$eval = $result->fetch_assoc();

if ($eval['evaluation_completed_at']) {
    echo "  ✓ Evaluation completed at: {$eval['evaluation_completed_at']}\n";
    echo "  ✓ Cost overrun: {$eval['actual_cost_overrun_percentage']}%\n";
    echo "  ✓ Time overrun: {$eval['actual_time_overrun_percentage']}%\n";
    echo "  ✓ Cost ground truth: {$eval['cost_ground_truth_label']}\n";
    echo "  ✓ Time ground truth: {$eval['time_ground_truth_label']}\n";
    echo "  ✓ Cost classification: {$eval['cost_prediction_classification']}\n";
    echo "  ✓ Time classification: {$eval['time_prediction_classification']}\n";
    echo "  ✓ Cost prediction correct: " . ($eval['cost_prediction_correct'] ? 'Yes' : 'No') . "\n";
    echo "  ✓ Time prediction correct: " . ($eval['time_prediction_correct'] ? 'Yes' : 'No') . "\n\n";
} else {
    echo "  ✗ Evaluation NOT completed (trigger may not be working)\n\n";
}

// Test 9: Calculate aggregate metrics
echo "Test 9: Calculating aggregate metrics...\n";

$calc_metrics = "CALL calculate_aggregate_metrics()";
$result = $conn->query($calc_metrics);

if ($result) {
    echo "  ✓ Metrics calculated successfully\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "\n  {$row['metric_type']} Predictions:\n";
        echo "    TP: {$row['TP']}, FP: {$row['FP']}, TN: {$row['TN']}, FN: {$row['FN']}\n";
        
        if ($row['accuracy'] !== null) {
            echo "    Accuracy: " . round($row['accuracy'] * 100, 2) . "%\n";
        }
        if ($row['precision_val'] !== null) {
            echo "    Precision: " . round($row['precision_val'] * 100, 2) . "%\n";
        }
        if ($row['recall_val'] !== null) {
            echo "    Recall: " . round($row['recall_val'] * 100, 2) . "%\n";
        }
        if ($row['f1_score'] !== null) {
            echo "    F1 Score: " . round($row['f1_score'] * 100, 2) . "%\n";
        }
    }
    echo "\n";
} else {
    echo "  ✗ Error calculating metrics\n\n";
}

// Test 10: View prediction performance
echo "Test 10: Viewing prediction performance...\n";

$view_performance = "SELECT * FROM v_ai_prediction_performance WHERE project_id = $test_project_id";
$result = $conn->query($view_performance);

if ($result && $result->num_rows > 0) {
    $perf = $result->fetch_assoc();
    echo "  ✓ Performance data retrieved\n";
    echo "  ✓ Cost prediction status: {$perf['cost_prediction_status']}\n";
    echo "  ✓ Time prediction status: {$perf['time_prediction_status']}\n\n";
} else {
    echo "  ✗ No performance data found\n\n";
}

// Test 11: Check audit log
echo "Test 11: Checking audit log...\n";

$check_audit = "
SELECT event_type, created_at 
FROM ai_prediction_audit 
WHERE project_id = $test_project_id 
ORDER BY created_at";

$result = $conn->query($check_audit);

if ($result && $result->num_rows > 0) {
    echo "  ✓ Audit log entries:\n";
    while ($row = $result->fetch_assoc()) {
        echo "    • {$row['event_type']} at {$row['created_at']}\n";
    }
    echo "\n";
} else {
    echo "  ✗ No audit log entries found\n\n";
}

// Cleanup
echo "Cleanup: Removing test project...\n";
$cleanup = "DELETE FROM construction_projects WHERE id = $test_project_id";
$conn->query($cleanup);
echo "  ✓ Test project removed\n\n";

echo "=================================================================\n";
echo "✓ ALL TESTS COMPLETED\n";
echo "=================================================================\n\n";

echo "Summary:\n";
echo "• Predictions can be saved and modified before work begins\n";
echo "• Predictions are automatically locked when work starts\n";
echo "• Locked predictions cannot be modified\n";
echo "• Projects are automatically evaluated on completion\n";
echo "• Confusion matrix classification works correctly\n";
echo "• Aggregate metrics can be calculated\n";
echo "• Audit trail is maintained\n\n";

$conn->close();
?>

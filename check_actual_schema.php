<?php
/**
 * Check Actual Database Schema
 */

$conn = new mysqli('localhost', 'root', '', 'buildhub');

echo "=== ACTUAL DATABASE SCHEMA CHECK ===\n\n";

// Check contractor_send_estimates
echo "--- contractor_send_estimates columns ---\n";
$result = $conn->query("DESCRIBE contractor_send_estimates");
while ($row = $result->fetch_assoc()) {
    if (strpos($row['Field'], 'predict') !== false || strpos($row['Field'], 'model') !== false) {
        echo "✅ " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

// Check construction_projects
echo "\n--- construction_projects columns ---\n";
$result = $conn->query("DESCRIBE construction_projects");
while ($row = $result->fetch_assoc()) {
    if (strpos($row['Field'], 'predict') !== false || 
        strpos($row['Field'], 'ground_truth') !== false ||
        strpos($row['Field'], 'classification') !== false ||
        strpos($row['Field'], 'evaluation') !== false ||
        strpos($row['Field'], 'overrun') !== false ||
        strpos($row['Field'], 'model') !== false ||
        strpos($row['Field'], 'locked') !== false) {
        echo "✅ " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

// Check tables
echo "\n--- AI-related tables ---\n";
$tables = ['ai_evaluation_config', 'ai_evaluation_metrics', 'ai_prediction_audit'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ $table exists\n";
    } else {
        echo "❌ $table MISSING\n";
    }
}

// Check triggers
echo "\n--- Triggers ---\n";
$result = $conn->query("SHOW TRIGGERS");
while ($row = $result->fetch_assoc()) {
    echo "✅ " . $row['Trigger'] . " - " . $row['Event'] . " " . $row['Timing'] . " " . $row['Table'] . "\n";
}

// Check procedures
echo "\n--- Stored Procedures ---\n";
$result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'buildhub'");
while ($row = $result->fetch_assoc()) {
    echo "✅ " . $row['Name'] . "\n";
}

$conn->close();
?>

<?php
/**
 * Check AI Predictions in Database
 * 
 * This script shows all stored AI predictions to verify they're being saved correctly.
 * 
 * Usage: Open in browser: http://localhost/buildhub/check_predictions_in_database.php
 */

require_once __DIR__ . '/backend/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check AI Predictions in Database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #3498db; color: white; padding: 12px; text-align: left; font-weight: bold; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .risk-high { color: #dc3545; font-weight: bold; }
        .risk-medium { color: #ffc107; font-weight: bold; }
        .risk-low { color: #28a745; font-weight: bold; }
        .no-data { text-align: center; padding: 40px; color: #6c757d; font-style: italic; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db; }
        .stat-number { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #6c757d; margin-top: 5px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .refresh-btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .refresh-btn:hover { background: #2980b9; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 AI Predictions Database Check</h1>";
echo "<p>This page shows all AI predictions stored in the database.</p>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "<div class='success'>✅ Database connection established</div>";
    
    // Check if columns exist
    echo "<h2>1. Schema Verification</h2>";
    $check_columns = "SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'";
    $stmt = $conn->query($check_columns);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($columns) == 0) {
        echo "<div class='error'>";
        echo "<h3>❌ Prediction Columns NOT Found!</h3>";
        echo "<p>The prediction columns have not been added to the database yet.</p>";
        echo "<p><strong>Action Required:</strong> Run the fix script first:</p>";
        echo "<pre>http://localhost/buildhub/apply_prediction_columns_fix.php</pre>";
        echo "</div>";
        exit();
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Prediction Columns Found (" . count($columns) . " columns)</h3>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li><strong>" . $col['Field'] . "</strong> (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Get statistics
    echo "<h2>2. Prediction Statistics</h2>";
    
    $stats_query = "
        SELECT 
            COUNT(*) as total_estimates,
            COUNT(predicted_cost_risk_level) as estimates_with_predictions,
            SUM(CASE WHEN predicted_cost_risk_level = 'High' THEN 1 ELSE 0 END) as high_cost_risk,
            SUM(CASE WHEN predicted_cost_risk_level = 'Medium' THEN 1 ELSE 0 END) as medium_cost_risk,
            SUM(CASE WHEN predicted_cost_risk_level = 'Low' THEN 1 ELSE 0 END) as low_cost_risk,
            SUM(CASE WHEN predicted_time_risk_level = 'High' THEN 1 ELSE 0 END) as high_time_risk,
            SUM(CASE WHEN predicted_time_risk_level = 'Medium' THEN 1 ELSE 0 END) as medium_time_risk,
            SUM(CASE WHEN predicted_time_risk_level = 'Low' THEN 1 ELSE 0 END) as low_time_risk
        FROM contractor_send_estimates
    ";
    
    $stmt = $conn->query($stats_query);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='stats'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>" . $stats['total_estimates'] . "</div>";
    echo "<div class='stat-label'>Total Estimates</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>" . $stats['estimates_with_predictions'] . "</div>";
    echo "<div class='stat-label'>With AI Predictions</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>";
    echo "<span class='risk-high'>" . $stats['high_cost_risk'] . "</span> / ";
    echo "<span class='risk-medium'>" . $stats['medium_cost_risk'] . "</span> / ";
    echo "<span class='risk-low'>" . $stats['low_cost_risk'] . "</span>";
    echo "</div>";
    echo "<div class='stat-label'>Cost Risk (H/M/L)</div>";
    echo "</div>";
    
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>";
    echo "<span class='risk-high'>" . $stats['high_time_risk'] . "</span> / ";
    echo "<span class='risk-medium'>" . $stats['medium_time_risk'] . "</span> / ";
    echo "<span class='risk-low'>" . $stats['low_time_risk'] . "</span>";
    echo "</div>";
    echo "<div class='stat-label'>Time Risk (H/M/L)</div>";
    echo "</div>";
    echo "</div>";
    
    // Get recent predictions
    echo "<h2>3. Recent Predictions (Last 10)</h2>";
    
    $predictions_query = "
        SELECT 
            cse.id,
            cse.send_id,
            cse.contractor_id,
            cse.total_cost,
            cse.timeline,
            cse.status,
            cse.predicted_cost_risk_level,
            cse.predicted_cost_probability,
            cse.predicted_time_risk_level,
            cse.predicted_time_probability,
            cse.prediction_generated_at,
            cse.model_version,
            cse.created_at,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name
        FROM contractor_send_estimates cse
        LEFT JOIN users u ON cse.contractor_id = u.id
        ORDER BY cse.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $conn->query($predictions_query);
    $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($predictions) == 0) {
        echo "<div class='no-data'>No estimates found in the database.</div>";
    } else {
        echo "<div style='overflow-x: auto;'>";
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Contractor</th>";
        echo "<th>Total Cost</th>";
        echo "<th>Timeline</th>";
        echo "<th>Cost Risk</th>";
        echo "<th>Cost Prob</th>";
        echo "<th>Time Risk</th>";
        echo "<th>Time Prob</th>";
        echo "<th>Model Ver</th>";
        echo "<th>Predicted At</th>";
        echo "<th>Status</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($predictions as $pred) {
            echo "<tr>";
            echo "<td>" . $pred['id'] . "</td>";
            echo "<td>" . htmlspecialchars($pred['contractor_first_name'] . ' ' . $pred['contractor_last_name']) . "</td>";
            echo "<td>₹" . number_format($pred['total_cost'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($pred['timeline']) . "</td>";
            
            // Cost Risk
            if ($pred['predicted_cost_risk_level']) {
                $risk_class = 'risk-' . strtolower($pred['predicted_cost_risk_level']);
                echo "<td class='$risk_class'>" . $pred['predicted_cost_risk_level'] . "</td>";
            } else {
                echo "<td style='color: #999;'>Not predicted</td>";
            }
            
            // Cost Probability
            if ($pred['predicted_cost_probability']) {
                $prob_percent = round($pred['predicted_cost_probability'] * 100, 1);
                echo "<td>" . $prob_percent . "%</td>";
            } else {
                echo "<td style='color: #999;'>-</td>";
            }
            
            // Time Risk
            if ($pred['predicted_time_risk_level']) {
                $risk_class = 'risk-' . strtolower($pred['predicted_time_risk_level']);
                echo "<td class='$risk_class'>" . $pred['predicted_time_risk_level'] . "</td>";
            } else {
                echo "<td style='color: #999;'>Not predicted</td>";
            }
            
            // Time Probability
            if ($pred['predicted_time_probability']) {
                $prob_percent = round($pred['predicted_time_probability'] * 100, 1);
                echo "<td>" . $prob_percent . "%</td>";
            } else {
                echo "<td style='color: #999;'>-</td>";
            }
            
            echo "<td>" . ($pred['model_version'] ?: '-') . "</td>";
            echo "<td>" . ($pred['prediction_generated_at'] ?: '-') . "</td>";
            echo "<td>" . htmlspecialchars($pred['status']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
    
    // Show most recent prediction details
    echo "<h2>4. Most Recent Prediction Details</h2>";
    
    $latest_query = "
        SELECT *
        FROM contractor_send_estimates
        WHERE predicted_cost_risk_level IS NOT NULL
        ORDER BY prediction_generated_at DESC
        LIMIT 1
    ";
    
    $stmt = $conn->query($latest_query);
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($latest) {
        echo "<div class='success'>";
        echo "<h3>✅ Latest Prediction Found!</h3>";
        echo "<table>";
        echo "<tr><th style='width: 250px;'>Field</th><th>Value</th></tr>";
        echo "<tr><td><strong>Estimate ID</strong></td><td>" . $latest['id'] . "</td></tr>";
        echo "<tr><td><strong>Send ID</strong></td><td>" . $latest['send_id'] . "</td></tr>";
        echo "<tr><td><strong>Total Cost</strong></td><td>₹" . number_format($latest['total_cost'], 2) . "</td></tr>";
        echo "<tr><td><strong>Timeline</strong></td><td>" . htmlspecialchars($latest['timeline']) . "</td></tr>";
        
        $cost_risk_class = 'risk-' . strtolower($latest['predicted_cost_risk_level']);
        echo "<tr><td><strong>Cost Risk Level</strong></td><td class='$cost_risk_class'>" . $latest['predicted_cost_risk_level'] . "</td></tr>";
        
        $cost_prob_percent = round($latest['predicted_cost_probability'] * 100, 1);
        echo "<tr><td><strong>Cost Risk Probability</strong></td><td>" . $cost_prob_percent . "%</td></tr>";
        
        $time_risk_class = 'risk-' . strtolower($latest['predicted_time_risk_level']);
        echo "<tr><td><strong>Time Risk Level</strong></td><td class='$time_risk_class'>" . $latest['predicted_time_risk_level'] . "</td></tr>";
        
        $time_prob_percent = round($latest['predicted_time_probability'] * 100, 1);
        echo "<tr><td><strong>Time Risk Probability</strong></td><td>" . $time_prob_percent . "%</td></tr>";
        
        echo "<tr><td><strong>Model Version</strong></td><td>" . $latest['model_version'] . "</td></tr>";
        echo "<tr><td><strong>Prediction Generated At</strong></td><td>" . $latest['prediction_generated_at'] . "</td></tr>";
        echo "<tr><td><strong>Estimate Created At</strong></td><td>" . $latest['created_at'] . "</td></tr>";
        echo "<tr><td><strong>Status</strong></td><td>" . htmlspecialchars($latest['status']) . "</td></tr>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ No Predictions Found Yet</h3>";
        echo "<p>No AI predictions have been stored in the database yet.</p>";
        echo "<p><strong>This could mean:</strong></p>";
        echo "<ul>";
        echo "<li>You haven't submitted a custom request yet</li>";
        echo "<li>The risk assessment modal wasn't shown</li>";
        echo "<li>The FastAPI ML service isn't running</li>";
        echo "<li>There was an error during prediction generation</li>";
        echo "</ul>";
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ol>";
        echo "<li>Make sure FastAPI service is running: <code>http://localhost:8000/health</code></li>";
        echo "<li>Submit a new custom request from homeowner dashboard</li>";
        echo "<li>View the risk assessment modal</li>";
        echo "<li>Check browser console for any errors</li>";
        echo "<li>Refresh this page to see if predictions were saved</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    // Check construction_projects table
    echo "<h2>5. Predictions in Construction Projects</h2>";
    
    $projects_query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.predicted_cost_risk_level,
            cp.predicted_cost_probability,
            cp.predicted_time_risk_level,
            cp.predicted_time_probability,
            cp.prediction_generated_at,
            cp.model_version,
            cp.status
        FROM construction_projects cp
        WHERE cp.predicted_cost_risk_level IS NOT NULL
        ORDER BY cp.created_at DESC
        LIMIT 5
    ";
    
    try {
        $stmt = $conn->query($projects_query);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($projects) > 0) {
            echo "<div class='success'>";
            echo "<p>✅ Found " . count($projects) . " projects with predictions</p>";
            echo "</div>";
            
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Project ID</th>";
            echo "<th>Project Name</th>";
            echo "<th>Cost Risk</th>";
            echo "<th>Time Risk</th>";
            echo "<th>Model Version</th>";
            echo "<th>Status</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($projects as $proj) {
                echo "<tr>";
                echo "<td>" . $proj['id'] . "</td>";
                echo "<td>" . htmlspecialchars($proj['project_name']) . "</td>";
                
                $cost_risk_class = 'risk-' . strtolower($proj['predicted_cost_risk_level']);
                echo "<td class='$cost_risk_class'>" . $proj['predicted_cost_risk_level'] . "</td>";
                
                $time_risk_class = 'risk-' . strtolower($proj['predicted_time_risk_level']);
                echo "<td class='$time_risk_class'>" . $proj['predicted_time_risk_level'] . "</td>";
                
                echo "<td>" . $proj['model_version'] . "</td>";
                echo "<td>" . htmlspecialchars($proj['status']) . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<div class='info'>";
            echo "<p>ℹ️ No projects with predictions yet. Predictions will be copied when estimates are accepted and projects are created.</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='warning'>";
        echo "<p>⚠️ Could not check construction_projects table. Columns may not exist yet.</p>";
        echo "</div>";
    }
    
    // Refresh button
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<button class='refresh-btn' onclick='location.reload()'>🔄 Refresh Page</button>";
    echo "</div>";
    
    // SQL Query for manual checking
    echo "<h2>6. Manual SQL Query</h2>";
    echo "<div class='info'>";
    echo "<p>You can also check predictions manually using this SQL query in phpMyAdmin:</p>";
    echo "<pre>SELECT id, send_id, 
       predicted_cost_risk_level, 
       predicted_cost_probability,
       predicted_time_risk_level, 
       predicted_time_probability,
       prediction_generated_at,
       model_version,
       created_at
FROM contractor_send_estimates
WHERE predicted_cost_risk_level IS NOT NULL
ORDER BY created_at DESC
LIMIT 10;</pre>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error Occurred</h2>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>

<?php
/**
 * Simple Cost & Time Overrun System Test
 * Works with current database schema
 */

require_once 'backend/config/database.php';

$db = (new Database())->getConnection();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost & Time Overrun System - Schema Check</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #e5e7eb; }
        .code { background: #1f2937; color: #10b981; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .code pre { margin: 0; color: #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Cost & Time Overrun System - Database Check</h1>
        
        <?php
        // Check construction_projects table structure
        echo "<div class='section'>";
        echo "<h2>1. Checking construction_projects Table</h2>";
        
        try {
            $stmt = $db->query("DESCRIBE construction_projects");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $required_columns = [
                'estimated_cost' => 'Original project budget',
                'planned_start_date' => 'Planned start date for schedule tracking',
                'planned_end_date' => 'Planned end date for schedule tracking',
                'actual_start_date' => 'Actual start date',
                'actual_end_date' => 'Actual completion date',
                'actual_time_overrun_percentage' => 'Calculated time overrun %',
                'schedule_locked' => 'Prevents changing planned dates after work starts'
            ];
            
            $existing_columns = array_column($columns, 'Field');
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Purpose</th><th>Status</th></tr>";
            
            $missing = [];
            foreach ($required_columns as $col => $purpose) {
                $exists = in_array($col, $existing_columns);
                $status = $exists ? "<span class='success'>✅ EXISTS</span>" : "<span class='error'>❌ MISSING</span>";
                echo "<tr><td><code>$col</code></td><td>$purpose</td><td>$status</td></tr>";
                if (!$exists) {
                    $missing[] = $col;
                }
            }
            echo "</table>";
            
            if (count($missing) > 0) {
                echo "<div class='warning'>";
                echo "<h3>⚠️ Missing Columns Detected</h3>";
                echo "<p>The following columns need to be added for the cost & time overrun system to work:</p>";
                echo "<ul>";
                foreach ($missing as $col) {
                    echo "<li><code>$col</code> - {$required_columns[$col]}</li>";
                }
                echo "</ul>";
                echo "</div>";
                
                // Generate SQL to add missing columns
                echo "<h3>📝 SQL to Add Missing Columns:</h3>";
                echo "<div class='code'><pre>";
                echo "ALTER TABLE construction_projects\n";
                
                $alterStatements = [];
                if (in_array('estimated_cost', $missing)) {
                    $alterStatements[] = "ADD COLUMN estimated_cost DECIMAL(15,2) DEFAULT NULL COMMENT 'Original project budget'";
                }
                if (in_array('planned_start_date', $missing)) {
                    $alterStatements[] = "ADD COLUMN planned_start_date DATE DEFAULT NULL COMMENT 'Planned start date'";
                }
                if (in_array('planned_end_date', $missing)) {
                    $alterStatements[] = "ADD COLUMN planned_end_date DATE DEFAULT NULL COMMENT 'Planned end date'";
                }
                if (in_array('actual_start_date', $missing)) {
                    $alterStatements[] = "ADD COLUMN actual_start_date DATE DEFAULT NULL COMMENT 'Actual start date'";
                }
                if (in_array('actual_end_date', $missing)) {
                    $alterStatements[] = "ADD COLUMN actual_end_date DATE DEFAULT NULL COMMENT 'Actual completion date'";
                }
                if (in_array('actual_time_overrun_percentage', $missing)) {
                    $alterStatements[] = "ADD COLUMN actual_time_overrun_percentage DECIMAL(10,2) DEFAULT NULL COMMENT 'Time overrun percentage'";
                }
                if (in_array('schedule_locked', $missing)) {
                    $alterStatements[] = "ADD COLUMN schedule_locked TINYINT(1) DEFAULT 0 COMMENT 'Lock planned dates after work starts'";
                }
                
                echo implode(",\n", $alterStatements) . ";";
                echo "</pre></div>";
                
            } else {
                echo "<div class='success'>";
                echo "<h3>✅ All Required Columns Present!</h3>";
                echo "<p>The construction_projects table has all necessary columns for cost & time overrun tracking.</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Check payment tables
        echo "<div class='section'>";
        echo "<h2>2. Checking Payment Tables</h2>";
        
        $payment_tables = [
            'stage_payment_requests' => 'Tracks stage-based payments',
            'custom_payment_requests' => 'Tracks custom/additional payment requests'
        ];
        
        echo "<table>";
        echo "<tr><th>Table</th><th>Purpose</th><th>Status</th></tr>";
        
        foreach ($payment_tables as $table => $purpose) {
            try {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                $status = $exists ? "<span class='success'>✅ EXISTS</span>" : "<span class='error'>❌ MISSING</span>";
                echo "<tr><td><code>$table</code></td><td>$purpose</td><td>$status</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td><code>$table</code></td><td>$purpose</td><td><span class='error'>❌ ERROR</span></td></tr>";
            }
        }
        echo "</table>";
        echo "</div>";
        
        // Show current projects
        echo "<div class='section'>";
        echo "<h2>3. Current Projects</h2>";
        
        try {
            $stmt = $db->query("SELECT id, project_name, status, total_cost, start_date, expected_completion_date FROM construction_projects ORDER BY id DESC LIMIT 5");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($projects) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Project Name</th><th>Status</th><th>Total Cost</th><th>Start Date</th><th>Expected End</th></tr>";
                foreach ($projects as $p) {
                    echo "<tr>";
                    echo "<td>{$p['id']}</td>";
                    echo "<td>" . htmlspecialchars($p['project_name']) . "</td>";
                    echo "<td>{$p['status']}</td>";
                    echo "<td>₹" . number_format($p['total_cost'] ?? 0) . "</td>";
                    echo "<td>{$p['start_date']}</td>";
                    echo "<td>{$p['expected_completion_date']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No projects found.</p>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Next steps
        echo "<div class='section'>";
        echo "<h2>4. Next Steps</h2>";
        
        if (count($missing ?? []) > 0) {
            echo "<div class='warning'>";
            echo "<h3>⚠️ Action Required</h3>";
            echo "<p>To enable the cost & time overrun system:</p>";
            echo "<ol>";
            echo "<li>Copy the SQL statement above</li>";
            echo "<li>Run it in phpMyAdmin or your MySQL client</li>";
            echo "<li>Refresh this page to verify</li>";
            echo "<li>Then run the full test: <code>run_overrun_test.php</code></li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='success'>";
            echo "<h3>✅ Ready to Test!</h3>";
            echo "<p>Your database schema is ready. You can now:</p>";
            echo "<ol>";
            echo "<li>Run the full test: <a href='run_overrun_test.php'>run_overrun_test.php</a></li>";
            echo "<li>Or use the quick test: <code>quick-test.bat</code></li>";
            echo "</ol>";
            echo "</div>";
        }
        
        echo "</div>";
        ?>
        
    </div>
</body>
</html>

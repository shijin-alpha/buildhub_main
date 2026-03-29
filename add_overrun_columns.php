<?php
/**
 * Add Missing Columns for Cost & Time Overrun System
 * This script automatically adds the required columns to your database
 */

require_once 'backend/config/database.php';

$db = (new Database())->getConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Adding Overrun System Columns</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .success { color: #10b981; padding: 15px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; padding: 15px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; padding: 15px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .code { background: #1f2937; color: #10b981; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #059669; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Adding Cost & Time Overrun System Columns</h1>";

try {
    // Check if columns already exist
    $stmt = $db->query("DESCRIBE construction_projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existing_columns = array_column($columns, 'Field');
    
    $columns_to_add = [];
    
    if (!in_array('estimated_cost', $existing_columns)) {
        $columns_to_add[] = [
            'name' => 'estimated_cost',
            'sql' => "ADD COLUMN estimated_cost DECIMAL(15,2) DEFAULT NULL COMMENT 'Original project budget'"
        ];
    }
    
    if (!in_array('schedule_locked', $existing_columns)) {
        $columns_to_add[] = [
            'name' => 'schedule_locked',
            'sql' => "ADD COLUMN schedule_locked TINYINT(1) DEFAULT 0 COMMENT 'Lock planned dates after work starts'"
        ];
    }
    
    if (count($columns_to_add) == 0) {
        echo "<div class='success'>";
        echo "<h2>✅ All Columns Already Exist!</h2>";
        echo "<p>Your database already has all the required columns for the cost & time overrun system.</p>";
        echo "<a href='test_overrun_system_simple.php' class='btn'>← Back to Check</a>";
        echo "<a href='run_overrun_test.php' class='btn'>Run Full Test →</a>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<h2>📝 Adding Missing Columns...</h2>";
        echo "<p>The following columns will be added:</p>";
        echo "<ul>";
        foreach ($columns_to_add as $col) {
            echo "<li><code>{$col['name']}</code></li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // Build and execute ALTER TABLE statement
        $alter_parts = array_column($columns_to_add, 'sql');
        $sql = "ALTER TABLE construction_projects " . implode(", ", $alter_parts);
        
        echo "<div class='code'>";
        echo "<strong>Executing SQL:</strong><br>";
        echo htmlspecialchars($sql);
        echo "</div>";
        
        // Execute the ALTER TABLE
        $db->exec($sql);
        
        echo "<div class='success'>";
        echo "<h2>✅ Success!</h2>";
        echo "<p>All required columns have been added successfully.</p>";
        echo "<p><strong>Added columns:</strong></p>";
        echo "<ul>";
        foreach ($columns_to_add as $col) {
            echo "<li>✅ <code>{$col['name']}</code></li>";
        }
        echo "</ul>";
        echo "</div>";
        
        // Verify the columns were added
        $stmt = $db->query("DESCRIBE construction_projects");
        $new_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $new_column_names = array_column($new_columns, 'Field');
        
        $all_present = true;
        foreach ($columns_to_add as $col) {
            if (!in_array($col['name'], $new_column_names)) {
                $all_present = false;
                break;
            }
        }
        
        if ($all_present) {
            echo "<div class='success'>";
            echo "<h2>✅ Verification Passed!</h2>";
            echo "<p>All columns have been verified and are present in the database.</p>";
            echo "</div>";
        }
        
        echo "<div class='info'>";
        echo "<h2>🎯 Next Steps</h2>";
        echo "<p>Your database is now ready for the cost & time overrun system!</p>";
        echo "<ol>";
        echo "<li>Verify the changes: <a href='test_overrun_system_simple.php' class='btn'>Check Schema</a></li>";
        echo "<li>Run the full test: <a href='run_overrun_test.php' class='btn'>Run Test</a></li>";
        echo "</ol>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>What to do:</strong></p>";
    echo "<ol>";
    echo "<li>Copy the SQL statement above</li>";
    echo "<li>Open phpMyAdmin</li>";
    echo "<li>Select the 'buildhub' database</li>";
    echo "<li>Go to the SQL tab</li>";
    echo "<li>Paste and execute the SQL</li>";
    echo "</ol>";
    echo "<a href='test_overrun_system_simple.php' class='btn'>← Back to Check</a>";
    echo "</div>";
}

echo "    </div>
</body>
</html>";
?>

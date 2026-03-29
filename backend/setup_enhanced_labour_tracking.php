<?php
/**
 * Enhanced Labour Tracking Setup Script
 * This script migrates the database to support enhanced labour tracking features
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>Enhanced Labour Tracking Setup</h1>";

try {
    // Database connection
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>🔧 Setting up Enhanced Labour Tracking...</h2>";
    
    // Read and execute migration script
    $migrationSQL = file_get_contents(__DIR__ . '/database/migrate_labour_tracking_enhancements.sql');
    
    if (!$migrationSQL) {
        throw new Exception("Could not read migration file");
    }
    
    // Split SQL statements and execute them
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            $errorCount++;
            echo "<p>⚠️ Warning: " . $e->getMessage() . "</p>";
            echo "<p>Statement: " . substr($statement, 0, 100) . "...</p>";
        }
    }
    
    echo "<h3>📊 Migration Summary</h3>";
    echo "<p><strong>Successful operations:</strong> $successCount</p>";
    echo "<p><strong>Warnings/Errors:</strong> $errorCount</p>";
    
    // Verify the setup
    echo "<h3>🔍 Verification</h3>";
    
    // Check if new columns exist
    $columnsCheck = $pdo->query("DESCRIBE daily_labour_tracking");
    $columns = $columnsCheck->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['hourly_rate', 'total_wages', 'productivity_rating', 'safety_compliance'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "<p>✅ All required columns are present</p>";
    } else {
        echo "<p>❌ Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }
    
    // Check data integrity
    $dataCheck = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            COUNT(CASE WHEN hourly_rate > 0 THEN 1 END) as records_with_hourly_rate,
            COUNT(CASE WHEN total_wages > 0 THEN 1 END) as records_with_total_wages,
            COUNT(CASE WHEN productivity_rating IS NOT NULL THEN 1 END) as records_with_productivity,
            COUNT(CASE WHEN safety_compliance IS NOT NULL THEN 1 END) as records_with_safety
        FROM daily_labour_tracking
    ");
    
    $stats = $dataCheck->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>📈 Data Statistics</h4>";
    echo "<ul>";
    echo "<li>Total labour records: " . $stats['total_records'] . "</li>";
    echo "<li>Records with hourly rate: " . $stats['records_with_hourly_rate'] . "</li>";
    echo "<li>Records with total wages: " . $stats['records_with_total_wages'] . "</li>";
    echo "<li>Records with productivity rating: " . $stats['records_with_productivity'] . "</li>";
    echo "<li>Records with safety compliance: " . $stats['records_with_safety'] . "</li>";
    echo "</ul>";
    
    // Test the enhanced functionality
    echo "<h3>🧪 Testing Enhanced Features</h3>";
    
    // Test worker types
    $workerTypesCheck = $pdo->query("SHOW COLUMNS FROM daily_labour_tracking LIKE 'worker_type'");
    $workerTypeInfo = $workerTypesCheck->fetch(PDO::FETCH_ASSOC);
    
    if (strpos($workerTypeInfo['Type'], 'Site Engineer') !== false) {
        echo "<p>✅ Enhanced worker types are available</p>";
    } else {
        echo "<p>❌ Enhanced worker types not found</p>";
    }
    
    // Test safety compliance enum
    $safetyCheck = $pdo->query("SHOW COLUMNS FROM daily_labour_tracking LIKE 'safety_compliance'");
    $safetyInfo = $safetyCheck->fetch(PDO::FETCH_ASSOC);
    
    if (strpos($safetyInfo['Type'], 'excellent') !== false) {
        echo "<p>✅ Safety compliance options are available</p>";
    } else {
        echo "<p>❌ Safety compliance options not found</p>";
    }
    
    echo "<h2>🎉 Enhanced Labour Tracking Setup Complete!</h2>";
    echo "<p><strong>New Features Available:</strong></p>";
    echo "<ul>";
    echo "<li>✨ Hourly rate tracking with auto-calculation of wages</li>";
    echo "<li>📊 Productivity rating system (1-5 stars)</li>";
    echo "<li>🦺 Safety compliance monitoring</li>";
    echo "<li>👷 Extended worker types (19 categories)</li>";
    echo "<li>💰 Automatic wage calculation (regular + overtime)</li>";
    echo "<li>📈 Enhanced reporting and analytics</li>";
    echo "</ul>";
    
    echo "<h3>📝 Usage Instructions</h3>";
    echo "<ol>";
    echo "<li>Navigate to the contractor dashboard</li>";
    echo "<li>Go to 'Progress Updates' section</li>";
    echo "<li>Click 'Daily Progress Update'</li>";
    echo "<li>Add worker entries with the new enhanced fields</li>";
    echo "<li>View automatic wage calculations and summaries</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2>❌ Setup Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "<hr>";
echo "<p><small>Enhanced Labour Tracking Setup - " . date('Y-m-d H:i:s') . "</small></p>";
?>
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Setting up progress reports system...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/create_progress_reports_table.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Progress reports system setup completed successfully!\n";
    echo "\nFeatures enabled:\n";
    echo "- Progress report generation (daily/weekly/monthly)\n";
    echo "- Report storage and history\n";
    echo "- Automatic homeowner notifications\n";
    echo "- Report viewing and acknowledgment tracking\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up progress reports system: " . $e->getMessage() . "\n";
    exit(1);
}
?>
<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Weekly Progress Summaries Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE weekly_progress_summaries');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== Monthly Progress Reports Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE monthly_progress_reports');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
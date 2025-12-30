<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Adding acknowledgment_notes column to progress_reports table...\n";
    
    $sql = "ALTER TABLE progress_reports ADD COLUMN acknowledgment_notes TEXT NULL AFTER homeowner_acknowledged_at";
    
    try {
        $pdo->exec($sql);
        echo "✓ Successfully added acknowledgment_notes column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠ Column already exists, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Database update completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Checking and fixing metadata column issues...\n";
    
    // Check if inbox_messages table has metadata column
    $stmt = $db->query("SHOW COLUMNS FROM inbox_messages LIKE 'metadata'");
    if ($stmt->rowCount() > 0) {
        echo "✓ inbox_messages.metadata column exists\n";
    } else {
        echo "✗ inbox_messages.metadata column missing - adding it...\n";
        $db->exec("ALTER TABLE inbox_messages ADD COLUMN metadata LONGTEXT NULL");
        echo "✓ Added metadata column to inbox_messages\n";
    }
    
    // Check if notifications table exists and has metadata column
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM notifications LIKE 'metadata'");
        if ($stmt->rowCount() > 0) {
            echo "✓ notifications.metadata column exists\n";
        } else {
            echo "✗ notifications.metadata column missing - adding it...\n";
            $db->exec("ALTER TABLE notifications ADD COLUMN metadata LONGTEXT NULL");
            echo "✓ Added metadata column to notifications\n";
        }
    } else {
        echo "ℹ notifications table doesn't exist\n";
    }
    
    // Check other tables that might need metadata column
    $tables_to_check = ['layout_payments', 'layout_technical_details'];
    
    foreach ($tables_to_check as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SHOW COLUMNS FROM $table LIKE 'metadata'");
            if ($stmt->rowCount() == 0) {
                echo "ℹ $table doesn't have metadata column (this is normal)\n";
            } else {
                echo "✓ $table.metadata column exists\n";
            }
        }
    }
    
    echo "\nMetadata column check completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
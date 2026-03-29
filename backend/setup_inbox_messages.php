<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up inbox messages system...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/create_inbox_messages_table.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n✅ Inbox messages system setup completed!\n";
    echo "\nTable created:\n";
    echo "- inbox_messages: For storing internal messages between users\n";
    echo "\nFeatures:\n";
    echo "- Message types: plan_saved, plan_submitted, plan_updated, etc.\n";
    echo "- Priority levels: low, normal, high, urgent\n";
    echo "- Read/unread status tracking\n";
    echo "- JSON metadata support\n";
    echo "- Foreign key relationships with users table\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
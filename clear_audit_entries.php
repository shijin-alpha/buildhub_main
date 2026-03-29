<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $db->exec('DELETE FROM immutable_payment_audit_ledger');
    $db->exec('DELETE FROM audit_ledger_statistics');
    $db->exec('INSERT INTO audit_ledger_statistics (id, total_entries, last_block_number) VALUES (1, 0, 0)');
    
    echo "✅ Cleared existing audit entries\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
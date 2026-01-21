<?php
require_once 'backend/config/database.php';

try {
    // Check if daily_progress_updates table exists
    $stmt = $db->query("SHOW TABLES LIKE 'daily_progress_updates'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ daily_progress_updates table exists\n\n";
        
        // Show table structure
        echo "Table structure:\n";
        $stmt = $db->query('DESCRIBE daily_progress_updates');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Show sample data
        echo "\nSample data:\n";
        $stmt = $db->query('SELECT * FROM daily_progress_updates ORDER BY update_date DESC LIMIT 5');
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($data)) {
            echo "❌ No data found in daily_progress_updates table\n";
        } else {
            foreach ($data as $row) {
                echo "ID: {$row['id']}, Project: {$row['project_id']}, Contractor: {$row['contractor_id']}, Date: {$row['update_date']}, Stage: {$row['construction_stage']}\n";
                echo "  Work Done: " . substr($row['work_done_today'], 0, 50) . "...\n";
            }
        }
        
        // Count total updates
        $stmt = $db->query('SELECT COUNT(*) as total FROM daily_progress_updates');
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "\nTotal daily progress updates: {$total}\n";
        
        // Count by contractor
        echo "\nUpdates by contractor:\n";
        $stmt = $db->query('
            SELECT contractor_id, COUNT(*) as count 
            FROM daily_progress_updates 
            GROUP BY contractor_id 
            ORDER BY count DESC
        ');
        $contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($contractors as $contractor) {
            echo "- Contractor {$contractor['contractor_id']}: {$contractor['count']} updates\n";
        }
        
    } else {
        echo "❌ daily_progress_updates table does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
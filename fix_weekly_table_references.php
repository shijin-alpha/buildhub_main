<?php
/**
 * Fix Weekly Table References
 * Migrates data from weekly_progress_summaries to weekly_progress_summary
 * and drops the old table
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Fixing weekly progress table references...\n\n";
    
    // Check if old table has any data
    $oldCount = $db->query("SELECT COUNT(*) FROM weekly_progress_summaries")->fetchColumn();
    echo "Old table (weekly_progress_summaries) has $oldCount records\n";
    
    $newCount = $db->query("SELECT COUNT(*) FROM weekly_progress_summary")->fetchColumn();
    echo "New table (weekly_progress_summary) has $newCount records\n\n";
    
    if ($oldCount > 0) {
        echo "Migrating data from old table to new table...\n";
        
        // Migrate data
        $db->exec("
            INSERT INTO weekly_progress_summary 
            (project_id, contractor_id, homeowner_id, week_start_date, week_end_date, 
             stages_worked, delays_and_reasons, weekly_remarks, created_at)
            SELECT 
                project_id, contractor_id, homeowner_id, week_start_date, week_end_date,
                stages_worked, delays_and_reasons, weekly_remarks, created_at
            FROM weekly_progress_summaries
            WHERE NOT EXISTS (
                SELECT 1 FROM weekly_progress_summary wps
                WHERE wps.project_id = weekly_progress_summaries.project_id
                AND wps.contractor_id = weekly_progress_summaries.contractor_id
                AND wps.week_start_date = weekly_progress_summaries.week_start_date
            )
        ");
        
        $migrated = $db->query("SELECT ROW_COUNT()")->fetchColumn();
        echo "✓ Migrated $migrated records\n\n";
    }
    
    // Drop the old table
    echo "Dropping old table (weekly_progress_summaries)...\n";
    $db->exec("DROP TABLE IF EXISTS weekly_progress_summaries");
    echo "✓ Old table dropped\n\n";
    
    // Verify
    $finalCount = $db->query("SELECT COUNT(*) FROM weekly_progress_summary")->fetchColumn();
    echo "✓ Final count in weekly_progress_summary: $finalCount records\n";
    
    echo "\n✓ Weekly table references fixed successfully!\n";
    echo "\nNow all code will use the correct table: weekly_progress_summary\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

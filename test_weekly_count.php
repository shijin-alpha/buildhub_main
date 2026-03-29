<?php
/**
 * Test Weekly Count Display
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Testing weekly count retrieval...\n\n";
    
    // Get all weekly summaries
    $summaries = $db->query("
        SELECT 
            id, project_id, contractor_id, 
            week_start_date, week_end_date,
            start_progress_percentage, end_progress_percentage
        FROM weekly_progress_summary
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total weekly summaries in database: " . count($summaries) . "\n\n";
    
    foreach ($summaries as $summary) {
        echo "Weekly Summary ID: {$summary['id']}\n";
        echo "  Project ID: {$summary['project_id']}\n";
        echo "  Contractor ID: {$summary['contractor_id']}\n";
        echo "  Week: {$summary['week_start_date']} to {$summary['week_end_date']}\n";
        echo "  Progress: {$summary['start_progress_percentage']}% → {$summary['end_progress_percentage']}%\n";
        echo "\n";
        
        // Test the count query that the API uses
        $projectId = $summary['project_id'];
        $count = $db->query("
            SELECT COUNT(*) as count 
            FROM weekly_progress_summary 
            WHERE project_id = $projectId
        ")->fetchColumn();
        
        echo "  Count for project $projectId: $count\n";
        echo str_repeat("-", 60) . "\n\n";
    }
    
    echo "✓ Test complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>

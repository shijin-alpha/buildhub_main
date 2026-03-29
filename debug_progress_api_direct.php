<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== TESTING HOMEOWNER PROGRESS API DIRECTLY ===\n";
    
    // Test the homeowner API query directly
    $stmt = $db->prepare('
        SELECT 
            dpu.*,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            u_contractor.company_name as contractor_company,
            COUNT(dlt.id) as labour_entries_count,
            GROUP_CONCAT(DISTINCT dlt.worker_type) as worker_types,
            SUM(dlt.worker_count) as total_workers,
            AVG(dlt.productivity_rating) as avg_productivity
        FROM daily_progress_updates dpu
        LEFT JOIN users u_contractor ON dpu.contractor_id = u_contractor.id
        LEFT JOIN daily_labour_tracking dlt ON dpu.id = dlt.daily_progress_id
        WHERE dpu.homeowner_id = 28
        GROUP BY dpu.id
        ORDER BY dpu.update_date DESC, dpu.created_at DESC
        LIMIT 10
    ');
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($updates) . " updates for homeowner 28:\n";
    foreach($updates as $update) {
        echo "- ID: {$update['id']}, Project: {$update['project_id']}, Stage: {$update['construction_stage']}\n";
        echo "  Progress: {$update['cumulative_completion_percentage']}%, Date: {$update['update_date']}\n";
        echo "  Work: " . substr($update['work_done_today'], 0, 50) . "...\n";
        echo "  Photos: {$update['progress_photos']}\n";
        echo "  Contractor: {$update['contractor_first_name']} {$update['contractor_last_name']}\n";
        echo "---\n";
    }

    echo "\n=== TESTING CONTRACTOR PROGRESS API DIRECTLY ===\n";
    
    // Test the contractor API query directly
    $stmt2 = $db->prepare('
        SELECT 
            dpu.*,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email
        FROM daily_progress_updates dpu
        LEFT JOIN users u_homeowner ON dpu.homeowner_id = u_homeowner.id
        WHERE dpu.contractor_id = 29
        ORDER BY dpu.created_at DESC
        LIMIT 10
    ');
    $stmt2->execute();
    $updates2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($updates2) . " updates for contractor 29:\n";
    foreach($updates2 as $update) {
        echo "- ID: {$update['id']}, Project: {$update['project_id']}, Stage: {$update['construction_stage']}\n";
        echo "  Progress: {$update['cumulative_completion_percentage']}%, Date: {$update['update_date']}\n";
        echo "  Work: " . substr($update['work_done_today'], 0, 50) . "...\n";
        echo "  Homeowner: {$update['homeowner_first_name']} {$update['homeowner_last_name']}\n";
        echo "---\n";
    }

    echo "\n=== TESTING API RESPONSE FORMAT ===\n";
    
    // Test the exact format that should be returned by the API
    if (count($updates) > 0) {
        $update = $updates[0];
        
        // Process like the API does
        $update['photos'] = json_decode($update['progress_photos'], true) ?: [];
        $update['contractor_name'] = trim($update['contractor_first_name'] . ' ' . $update['contractor_last_name']);
        
        // Transform to report format like the frontend expects
        $transformedReport = [
            'id' => $update['id'],
            'project_id' => $update['project_id'],
            'contractor_id' => $update['contractor_id'],
            'contractor_name' => $update['contractor_name'],
            'project_name' => "Project {$update['project_id']} - {$update['construction_stage']}",
            'report_type' => 'daily',
            'period_start' => $update['update_date'],
            'period_end' => $update['update_date'],
            'created_at' => $update['created_at'],
            'status' => 'sent',
            'viewed_at' => null,
            'acknowledged_at' => null,
            'summary' => [
                'total_days' => 1,
                'total_workers' => $update['total_workers'] ?: 0,
                'total_hours' => $update['working_hours'] ?: 0,
                'progress_percentage' => $update['cumulative_completion_percentage'] ?: 0,
                'photos_count' => count($update['photos']),
                'stage' => $update['construction_stage'],
                'work_description' => $update['work_done_today'],
                'weather' => $update['weather_condition'],
                'incremental_progress' => $update['incremental_completion_percentage']
            ],
            'has_photos' => count($update['photos']) > 0,
            'full_update_data' => $update
        ];
        
        echo "Sample transformed report:\n";
        echo json_encode($transformedReport, JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
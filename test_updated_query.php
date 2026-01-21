<?php
require_once 'backend/config/database.php';

try {
    echo "=== Testing Updated Query ===\n\n";
    
    $contractor_id = 27;
    
    $query = "
        SELECT 
            pr.*,
            COALESCE(
                CONCAT(lr.plot_size, ' - ', lr.preferred_style, ' Style'),
                CONCAT('Project ', pr.project_id)
            ) as project_name,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
            u.email as homeowner_email
        FROM progress_reports pr
        LEFT JOIN layout_requests lr ON pr.project_id = lr.id AND lr.status != 'deleted'
        LEFT JOIN users u ON pr.homeowner_id = u.id
        WHERE pr.contractor_id = :contractor_id
        ORDER BY pr.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':contractor_id' => $contractor_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($reports) . " reports for contractor $contractor_id\n";
    
    foreach ($reports as $report) {
        echo "\n--- Report ---\n";
        echo "ID: {$report['id']}\n";
        echo "Type: {$report['report_type']}\n";
        echo "Status: {$report['status']}\n";
        echo "Project Name: " . ($report['project_name'] ?? 'NULL') . "\n";
        echo "Homeowner: " . ($report['homeowner_name'] ?? 'NULL') . "\n";
        echo "Period: {$report['report_period_start']} to {$report['report_period_end']}\n";
        echo "Created: {$report['created_at']}\n";
        
        // Process report data
        $reportData = json_decode($report['report_data'], true);
        if ($reportData && isset($reportData['summary'])) {
            echo "Summary:\n";
            echo "  - Days: " . ($reportData['summary']['total_days'] ?? 0) . "\n";
            echo "  - Progress: " . ($reportData['summary']['progress_percentage'] ?? 0) . "%\n";
            echo "  - Photos: " . ($reportData['summary']['photos_count'] ?? 0) . "\n";
            echo "  - Cost: ₹" . number_format($reportData['costs']['total_cost'] ?? 0) . "\n";
        }
    }
    
    // Test grouping
    echo "\n=== Testing Grouping ===\n";
    $groupedReports = [
        'daily' => [],
        'weekly' => [],
        'monthly' => []
    ];
    
    foreach ($reports as $report) {
        $groupedReports[$report['report_type']][] = $report;
    }
    
    echo "Daily reports: " . count($groupedReports['daily']) . "\n";
    echo "Weekly reports: " . count($groupedReports['weekly']) . "\n";
    echo "Monthly reports: " . count($groupedReports['monthly']) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
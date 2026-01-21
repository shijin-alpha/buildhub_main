<?php
require_once 'backend/config/database.php';

echo "=== Testing Sent Reports Query Directly ===\n";

$contractor_id = 27;

// Test the exact query from the API
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
$stmt->bindValue(':contractor_id', $contractor_id);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($reports) . " reports for contractor $contractor_id\n\n";

foreach ($reports as $report) {
    echo "--- Report ID: " . $report['id'] . " ---\n";
    echo "Project ID: " . $report['project_id'] . "\n";
    echo "Project Name: " . $report['project_name'] . "\n";
    echo "Homeowner: " . $report['homeowner_name'] . "\n";
    echo "Type: " . $report['report_type'] . "\n";
    echo "Status: " . $report['status'] . "\n";
    echo "Period: " . $report['report_period_start'] . " to " . $report['report_period_end'] . "\n";
    
    $reportData = json_decode($report['report_data'], true);
    if ($reportData && isset($reportData['summary'])) {
        echo "Summary - Days: " . ($reportData['summary']['total_days'] ?? 0) . 
             ", Progress: " . ($reportData['summary']['progress_percentage'] ?? 0) . "%\n";
    }
    echo "\n";
}

// Test grouping
$groupedReports = ['daily' => [], 'weekly' => [], 'monthly' => []];
foreach ($reports as $report) {
    $groupedReports[$report['report_type']][] = $report;
}

echo "=== Grouped Results ===\n";
echo "Daily: " . count($groupedReports['daily']) . " reports\n";
echo "Weekly: " . count($groupedReports['weekly']) . " reports\n";
echo "Monthly: " . count($groupedReports['monthly']) . " reports\n";
?>
<?php
session_start();

// Simulate contractor login
$_SESSION['user_id'] = 29;
$_SESSION['role'] = 'contractor';
$_SESSION['email'] = 'shijinthomas248@gmail.com';

echo "=== Simulated Session ===\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Role: " . $_SESSION['role'] . "\n";

// Now test the API logic
require_once 'backend/config/database.php';

try {
    $contractor_id = $_SESSION['user_id'];
    
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
    
    echo "\n=== API Results ===\n";
    echo "Found " . count($reports) . " reports for contractor {$contractor_id}\n";
    
    if (!empty($reports)) {
        foreach ($reports as $report) {
            echo "\n✅ Report Found:\n";
            echo "- ID: {$report['id']}\n";
            echo "- Type: {$report['report_type']}\n";
            echo "- Status: {$report['status']}\n";
            echo "- Project: {$report['project_name']}\n";
            echo "- Homeowner: {$report['homeowner_name']}\n";
            echo "- Period: {$report['report_period_start']} to {$report['report_period_end']}\n";
            echo "- Created: {$report['created_at']}\n";
            
            // Check if report_data is valid JSON
            $reportData = json_decode($report['report_data'], true);
            if ($reportData) {
                echo "- Report data: Valid JSON with " . count($reportData) . " keys\n";
                if (isset($reportData['summary'])) {
                    echo "- Summary available: Yes\n";
                } else {
                    echo "- Summary available: No\n";
                }
            } else {
                echo "- Report data: Invalid JSON or empty\n";
            }
        }
        
        // Test the grouping logic
        $groupedReports = [
            'daily' => [],
            'weekly' => [],
            'monthly' => []
        ];
        
        foreach ($reports as $report) {
            $groupedReports[$report['report_type']][] = $report;
        }
        
        echo "\n=== Grouped Results ===\n";
        echo "Daily reports: " . count($groupedReports['daily']) . "\n";
        echo "Weekly reports: " . count($groupedReports['weekly']) . "\n";
        echo "Monthly reports: " . count($groupedReports['monthly']) . "\n";
        
    } else {
        echo "❌ No reports found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
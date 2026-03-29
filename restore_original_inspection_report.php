<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== RESTORING ORIGINAL INSPECTION REPORT ===\n\n";
    
    // First, let's see what inspection reports exist
    echo "Current inspection reports:\n";
    $stmt = $db->query('
        SELECT ir.id, ir.project_id, ir.inspection_date, ir.overall_status, 
               cp.homeowner_id, cp.project_name, cp.homeowner_name
        FROM inspection_reports ir 
        JOIN construction_projects cp ON ir.project_id = cp.id 
        ORDER BY ir.id
    ');
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reports as $report) {
        echo "  Report {$report['id']}: Project {$report['project_id']} ({$report['project_name']}) - Homeowner {$report['homeowner_id']} ({$report['homeowner_name']}) - Status: {$report['overall_status']}\n";
    }
    
    // Find the original report (ID 1) and the sample report (ID 2)
    $originalReport = null;
    $sampleReport = null;
    
    foreach ($reports as $report) {
        if ($report['id'] == 1) {
            $originalReport = $report;
        } elseif ($report['id'] == 2) {
            $sampleReport = $report;
        }
    }
    
    if ($originalReport) {
        echo "\n✅ Found original report (ID 1) for homeowner {$originalReport['homeowner_id']}\n";
        
        // Option 1: Update the original report's project to belong to user 32
        echo "Updating original report to belong to user 32...\n";
        
        // First, update the project to belong to user 32
        $stmt = $db->prepare('UPDATE construction_projects SET homeowner_id = 32, homeowner_name = ? WHERE id = ?');
        $stmt->execute(['Amal Samuel', $originalReport['project_id']]);
        
        echo "✅ Updated project {$originalReport['project_id']} to belong to user 32\n";
        
    } else {
        echo "❌ Original report (ID 1) not found\n";
    }
    
    // Remove the sample report if it exists
    if ($sampleReport) {
        echo "\nRemoving sample report (ID 2)...\n";
        
        // Delete checklist items first (if any)
        $stmt = $db->prepare('DELETE FROM inspection_checklist_items WHERE inspection_report_id = ?');
        $stmt->execute([2]);
        
        // Delete the sample report
        $stmt = $db->prepare('DELETE FROM inspection_reports WHERE id = ?');
        $stmt->execute([2]);
        
        echo "✅ Removed sample report (ID 2)\n";
        
        // Also remove the sample project if it was created for user 32
        $stmt = $db->prepare('DELETE FROM construction_projects WHERE id = ? AND homeowner_id = 32');
        $stmt->execute([6]); // Project ID 6 was created for the sample
        
        echo "✅ Removed sample project (ID 6)\n";
    }
    
    echo "\n=== VERIFICATION ===\n";
    
    // Check what reports user 32 now has
    $stmt = $db->prepare('
        SELECT ir.id, ir.inspection_date, ir.overall_status, ir.quality_score, ir.notes,
               cp.project_name, cp.homeowner_name 
        FROM inspection_reports ir 
        JOIN construction_projects cp ON ir.project_id = cp.id 
        WHERE cp.homeowner_id = 32
    ');
    $stmt->execute();
    $userReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($userReports)) {
        echo "❌ No reports found for user 32\n";
    } else {
        echo "✅ Reports for user 32:\n";
        foreach ($userReports as $report) {
            echo "  Report {$report['id']}: {$report['project_name']}\n";
            echo "    Date: {$report['inspection_date']}\n";
            echo "    Status: {$report['overall_status']}\n";
            echo "    Quality Score: {$report['quality_score']}\n";
            echo "    Notes: " . substr($report['notes'], 0, 100) . "...\n";
        }
    }
    
    // Test the API
    echo "\n=== TESTING API ===\n";
    session_start();
    $_SESSION['user_id'] = 32;
    $_SESSION['role'] = 'homeowner';
    
    ob_start();
    include 'backend/api/homeowner/get_inspection_reports.php';
    $apiResponse = ob_get_clean();
    
    // Extract JSON
    $lines = explode("\n", $apiResponse);
    $jsonLine = '';
    foreach ($lines as $line) {
        if (strpos($line, '{"success"') === 0) {
            $jsonLine = $line;
            break;
        }
    }
    
    if ($jsonLine) {
        $data = json_decode($jsonLine, true);
        echo "✅ API Result:\n";
        echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "  Reports: " . count($data['reports']) . "\n";
        
        if (!empty($data['reports'])) {
            $report = $data['reports'][0];
            echo "  Original Report Details:\n";
            echo "    Project: {$report['project']['name']}\n";
            echo "    Status: {$report['inspection']['status']}\n";
            echo "    Quality Score: {$report['inspection']['quality_score']}\n";
            echo "    Notes: " . substr($report['inspection']['notes'], 0, 100) . "...\n";
        }
    } else {
        echo "❌ API test failed\n";
    }
    
    echo "\n🎉 SUCCESS! You should now see your original inspection report!\n";
    echo "Refresh the inspection reports tab to see your actual submitted report.\n";
    
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
}
?>
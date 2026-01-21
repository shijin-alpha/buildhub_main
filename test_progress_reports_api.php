<?php
session_start();

echo "=== Session Information ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "Email: " . ($_SESSION['email'] ?? 'Not set') . "\n";

echo "\n=== Testing API Directly ===\n";

// Simulate the API call
require_once 'backend/config/database.php';

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
        echo "❌ User not authenticated or not a contractor\n";
        
        // Let's check what contractors exist
        echo "\nAvailable contractors:\n";
        $stmt = $db->query("SELECT id, email, first_name, last_name, role FROM users WHERE role = 'contractor'");
        $contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($contractors as $contractor) {
            echo "- ID: {$contractor['id']}, Email: {$contractor['email']}, Name: {$contractor['first_name']} {$contractor['last_name']}\n";
        }
        
        // Check the report's contractor_id
        echo "\nReport details:\n";
        $stmt = $db->query("SELECT * FROM progress_reports WHERE id = 1");
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($report) {
            echo "Report contractor_id: {$report['contractor_id']}\n";
            echo "Report project_id: {$report['project_id']}\n";
            echo "Report homeowner_id: {$report['homeowner_id']}\n";
        }
        
        exit;
    }
    
    $contractor_id = $_SESSION['user_id'];
    echo "✅ Authenticated contractor ID: {$contractor_id}\n";
    
    // Test the query from the API
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
    
    echo "Found " . count($reports) . " reports for contractor {$contractor_id}\n";
    
    if (empty($reports)) {
        echo "❌ No reports found for this contractor\n";
        
        // Check what contractor_id the report actually has
        $stmt = $db->query("SELECT contractor_id FROM progress_reports WHERE id = 1");
        $actualContractor = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($actualContractor) {
            echo "The report belongs to contractor_id: {$actualContractor['contractor_id']}\n";
            echo "But current session contractor_id is: {$contractor_id}\n";
        }
    } else {
        foreach ($reports as $report) {
            echo "✅ Report ID: {$report['id']}, Type: {$report['report_type']}, Status: {$report['status']}\n";
            echo "   Project: {$report['project_name']}\n";
            echo "   Homeowner: {$report['homeowner_name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
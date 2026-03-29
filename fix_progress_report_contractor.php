<?php
require_once 'backend/config/database.php';

try {
    // Update the report to use contractor ID 29 (Shijin Thomas)
    $stmt = $db->prepare("UPDATE progress_reports SET contractor_id = 29 WHERE id = 1");
    $stmt->execute();
    
    echo "✅ Updated progress report contractor_id from 27 to 29\n";
    
    // Verify the update
    $stmt = $db->query("SELECT id, contractor_id, project_id, homeowner_id FROM progress_reports WHERE id = 1");
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Report details after update:\n";
    echo "- ID: {$report['id']}\n";
    echo "- Contractor ID: {$report['contractor_id']}\n";
    echo "- Project ID: {$report['project_id']}\n";
    echo "- Homeowner ID: {$report['homeowner_id']}\n";
    
    // Check if contractor exists
    $stmt = $db->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ? AND role = 'contractor'");
    $stmt->execute([29]);
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contractor) {
        echo "✅ Contractor exists: {$contractor['first_name']} {$contractor['last_name']} ({$contractor['email']})\n";
    } else {
        echo "❌ Contractor with ID 29 not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
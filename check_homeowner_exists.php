<?php
require_once 'backend/config/database.php';

try {
    // Check if homeowner ID 28 exists
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, role FROM users WHERE id = 28");
    $stmt->execute();
    $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($homeowner) {
        echo "✅ Homeowner exists: {$homeowner['first_name']} {$homeowner['last_name']} ({$homeowner['email']}) - Role: {$homeowner['role']}\n";
    } else {
        echo "❌ Homeowner with ID 28 not found\n";
        
        // Find available homeowners
        echo "\nAvailable homeowners:\n";
        $stmt = $db->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'homeowner' LIMIT 5");
        $homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($homeowners as $ho) {
            echo "- ID: {$ho['id']}, Email: {$ho['email']}, Name: {$ho['first_name']} {$ho['last_name']}\n";
        }
        
        // Update to use first available homeowner
        if (!empty($homeowners)) {
            $newHomeownerId = $homeowners[0]['id'];
            $stmt = $db->prepare("UPDATE progress_reports SET homeowner_id = ? WHERE id = 1");
            $stmt->execute([$newHomeownerId]);
            echo "\n✅ Updated progress report homeowner_id to {$newHomeownerId}\n";
        }
    }
    
    // Also check if project ID 105 exists in layout_requests
    echo "\nChecking project ID 105:\n";
    $stmt = $db->prepare("SELECT id, plot_size, preferred_style, status FROM layout_requests WHERE id = 105");
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "✅ Project exists: {$project['plot_size']} - {$project['preferred_style']} Style (Status: {$project['status']})\n";
    } else {
        echo "❌ Project with ID 105 not found\n";
        
        // Find available projects
        echo "\nAvailable projects:\n";
        $stmt = $db->query("SELECT id, plot_size, preferred_style, status FROM layout_requests WHERE status != 'deleted' LIMIT 5");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($projects as $proj) {
            echo "- ID: {$proj['id']}, Size: {$proj['plot_size']}, Style: {$proj['preferred_style']}, Status: {$proj['status']}\n";
        }
        
        // Update to use first available project
        if (!empty($projects)) {
            $newProjectId = $projects[0]['id'];
            $stmt = $db->prepare("UPDATE progress_reports SET project_id = ? WHERE id = 1");
            $stmt->execute([$newProjectId]);
            echo "\n✅ Updated progress report project_id to {$newProjectId}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
require_once 'backend/config/database.php';

try {
    echo "=== Creating Valid Project ===\n\n";
    
    // Create a valid layout request (project)
    $stmt = $db->prepare("
        INSERT INTO layout_requests (
            user_id, homeowner_id, plot_size, budget_range, location, 
            timeline, num_floors, preferred_style, requirements, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $requirements = json_encode([
        'plot_shape' => 'Rectangular',
        'topography' => 'Flat',
        'development_laws' => 'Standard',
        'family_needs' => 'Modern family home',
        'rooms' => 'master_bedroom,bedrooms,bathrooms,kitchen,living_room,dining_room',
        'aesthetic' => 'Modern',
        'notes' => 'Construction progress tracking project'
    ]);
    
    $result = $stmt->execute([
        28, // user_id (homeowner)
        28, // homeowner_id
        '2000 sq ft',
        '10-15 lakhs',
        'Mumbai, Maharashtra',
        '6-12 months',
        '2',
        'Modern',
        $requirements,
        'approved'
    ]);
    
    if ($result) {
        $project_id = $db->lastInsertId();
        echo "✓ Created valid project with ID: $project_id\n";
        
        // Update the existing progress report to use this project
        $updateStmt = $db->prepare("UPDATE progress_reports SET project_id = ? WHERE id = 1");
        $updateResult = $updateStmt->execute([$project_id]);
        
        if ($updateResult) {
            echo "✓ Updated progress report to use project ID: $project_id\n";
        }
        
        // Also create a contractor assignment if needed
        $checkAssignment = $db->prepare("SELECT id FROM layout_request_assignments WHERE layout_request_id = ? AND architect_id = ?");
        $checkAssignment->execute([$project_id, 27]);
        
        if (!$checkAssignment->fetch()) {
            $assignStmt = $db->prepare("
                INSERT INTO layout_request_assignments (layout_request_id, architect_id, assigned_at, status)
                VALUES (?, ?, NOW(), 'accepted')
            ");
            $assignStmt->execute([$project_id, 27]);
            echo "✓ Created contractor assignment\n";
        }
        
        // Test the query again
        echo "\n=== Testing Updated Query ===\n";
        
        $query = "
            SELECT 
                pr.*,
                lr.requirements as project_requirements,
                CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
                u.email as homeowner_email,
                CONCAT(lr.plot_size, ' - ', lr.preferred_style, ' Style') as project_display_name
            FROM progress_reports pr
            LEFT JOIN layout_requests lr ON pr.project_id = lr.id
            LEFT JOIN users u ON pr.homeowner_id = u.id
            WHERE pr.contractor_id = 27
            ORDER BY pr.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($reports as $report) {
            echo "\n--- Updated Report ---\n";
            echo "ID: {$report['id']}\n";
            echo "Type: {$report['report_type']}\n";
            echo "Status: {$report['status']}\n";
            echo "Project Display: " . ($report['project_display_name'] ?? 'NULL') . "\n";
            echo "Homeowner: " . ($report['homeowner_name'] ?? 'NULL') . "\n";
            echo "Period: {$report['report_period_start']} to {$report['report_period_end']}\n";
        }
        
    } else {
        echo "✗ Failed to create project\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
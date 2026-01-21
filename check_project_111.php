<?php
require_once 'backend/config/database.php';

echo "=== Checking Project 111 ===\n";

$stmt = $db->prepare('SELECT * FROM layout_requests WHERE id = 111');
$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if ($project) {
    echo "Project 111 found:\n";
    echo "Status: " . $project['status'] . "\n";
    echo "Homeowner ID: " . $project['homeowner_id'] . "\n";
    echo "Plot Size: " . $project['plot_size'] . "\n";
    echo "Style: " . $project['preferred_style'] . "\n";
} else {
    echo "Project 111 not found in layout_requests table\n";
    
    // Let's create a valid project for the existing report
    echo "\n=== Creating Project 111 ===\n";
    
    $insertStmt = $db->prepare("
        INSERT INTO layout_requests (
            id, homeowner_id, plot_size, preferred_style, 
            requirements, status, created_at, updated_at
        ) VALUES (
            111, 28, '30x40 feet', 'Modern', 
            '{\"plot_shape\":\"Rectangular\",\"topography\":\"Flat\",\"development_type\":\"Residential\"}',
            'approved', NOW(), NOW()
        )
    ");
    
    if ($insertStmt->execute()) {
        echo "✓ Project 111 created successfully\n";
    } else {
        echo "✗ Failed to create project 111\n";
        print_r($insertStmt->errorInfo());
    }
}

echo "\n=== Checking Progress Report ===\n";
$reportStmt = $db->prepare('SELECT * FROM progress_reports WHERE project_id = 111');
$reportStmt->execute();
$report = $reportStmt->fetch(PDO::FETCH_ASSOC);

if ($report) {
    echo "Progress report found for project 111:\n";
    echo "Type: " . $report['report_type'] . "\n";
    echo "Status: " . $report['status'] . "\n";
    echo "Period: " . $report['report_period_start'] . " to " . $report['report_period_end'] . "\n";
} else {
    echo "No progress report found for project 111\n";
}
?>
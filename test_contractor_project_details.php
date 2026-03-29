<?php
/**
 * Test Contractor Project Details
 * Verify each project has unique data
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get contractor ID (adjust as needed)
    $contractor_id = 29; // Change this to your contractor ID
    
    echo "Testing project details for contractor $contractor_id...\n\n";
    
    // Fetch projects
    $query = "
        SELECT 
            cse.id,
            cse.send_id,
            cls.homeowner_id,
            cls.project_name,
            cls.plot_size,
            cls.built_up_area,
            cls.floors,
            cls.location,
            cse.total_cost,
            cse.timeline,
            cse.status,
            u.name as homeowner_name,
            u.email as homeowner_email,
            u.phone as homeowner_phone
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        INNER JOIN users u ON cls.homeowner_id = u.id
        WHERE cse.contractor_id = :contractor_id
        AND cse.status IN ('accepted', 'ready_for_construction', 'in_progress', 'completed')
        ORDER BY cse.id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($projects) . " projects\n";
    echo str_repeat("=", 80) . "\n\n";
    
    foreach ($projects as $project) {
        echo "Project ID: {$project['id']}\n";
        echo "  Project Name: {$project['project_name']}\n";
        echo "  Homeowner: {$project['homeowner_name']} (ID: {$project['homeowner_id']})\n";
        echo "  Email: {$project['homeowner_email']}\n";
        echo "  Phone: {$project['homeowner_phone']}\n";
        echo "  Plot Size: {$project['plot_size']}\n";
        echo "  Built-up Area: {$project['built_up_area']}\n";
        echo "  Floors: {$project['floors']}\n";
        echo "  Location: {$project['location']}\n";
        echo "  Total Cost: ₹" . number_format($project['total_cost'], 2) . "\n";
        echo "  Timeline: {$project['timeline']}\n";
        echo "  Status: {$project['status']}\n";
        echo str_repeat("-", 80) . "\n\n";
    }
    
    // Check if any projects have identical data
    echo "Checking for duplicate data...\n";
    $duplicates = [];
    for ($i = 0; $i < count($projects); $i++) {
        for ($j = $i + 1; $j < count($projects); $j++) {
            $same_fields = [];
            if ($projects[$i]['homeowner_id'] == $projects[$j]['homeowner_id']) {
                $same_fields[] = 'homeowner_id';
            }
            if ($projects[$i]['project_name'] == $projects[$j]['project_name']) {
                $same_fields[] = 'project_name';
            }
            if ($projects[$i]['plot_size'] == $projects[$j]['plot_size']) {
                $same_fields[] = 'plot_size';
            }
            
            if (count($same_fields) >= 2) {
                echo "⚠ Projects {$projects[$i]['id']} and {$projects[$j]['id']} have similar data: " . implode(', ', $same_fields) . "\n";
            }
        }
    }
    
    echo "\n✓ Test complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>

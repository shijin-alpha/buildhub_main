<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $contractor_id = 29;
    $project_id = 37;
    
    echo "Testing progress updates query...\n\n";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'construction_progress_updates'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "❌ Table 'construction_progress_updates' does NOT exist!\n";
        echo "This is why the API is failing.\n\n";
        
        // Show available tables
        echo "Available tables:\n";
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "  - " . $row[0] . "\n";
        }
        exit;
    }
    
    echo "✓ Table exists\n\n";
    
    // Try the query
    $stmt = $db->prepare("
        SELECT 
            cpu.*,
            cse.total_cost,
            cse.timeline,
            cse.materials,
            cse.structured,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email
        FROM construction_progress_updates cpu
        LEFT JOIN contractor_send_estimates cse ON cpu.project_id = cse.id
        LEFT JOIN users u_homeowner ON cpu.homeowner_id = u_homeowner.id
        WHERE cpu.contractor_id = :contractor_id AND cpu.project_id = :project_id
        ORDER BY cpu.created_at DESC
    ");
    
    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query executed successfully!\n";
    echo "Found " . count($results) . " progress updates\n\n";
    
    if (count($results) === 0) {
        echo "No progress updates found for contractor_id=29, project_id=37\n";
        echo "This is expected if no updates have been submitted yet.\n";
    } else {
        echo "Progress updates:\n";
        print_r($results);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

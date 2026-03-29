<?php
/**
 * Debug Table Structure
 * Check the actual structure of contractor_send_estimates table
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Checking Table Structures\n\n";
    
    // 1. Check contractor_send_estimates structure
    echo "1. contractor_send_estimates table structure:\n";
    $stmt = $pdo->query("DESCRIBE contractor_send_estimates");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
    
    // 2. Check actual data in contractor_send_estimates
    echo "2. Sample data from contractor_send_estimates:\n";
    $stmt = $pdo->query("SELECT * FROM contractor_send_estimates LIMIT 3");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        echo "   No data found\n";
    } else {
        foreach ($data as $row) {
            echo "   - ID: {$row['id']}, Send ID: {$row['send_id']}, Contractor: {$row['contractor_id']}, Status: {$row['status']}, Cost: ₹" . number_format($row['total_cost']) . "\n";
        }
    }
    echo "\n";
    
    // 3. Check if there's a related table for project names
    echo "3. Checking related tables:\n";
    
    // Check homeowner_send_layout table
    echo "homeowner_send_layout table:\n";
    $stmt = $pdo->query("DESCRIBE homeowner_send_layout");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
    
    // 4. Check the relationship between tables
    echo "4. Checking table relationships:\n";
    $stmt = $pdo->query("
        SELECT 
            cse.id as estimate_id,
            cse.send_id,
            cse.contractor_id,
            cse.status,
            cse.total_cost,
            hsl.project_name,
            hsl.homeowner_id,
            hsl.location
        FROM contractor_send_estimates cse
        LEFT JOIN homeowner_send_layout hsl ON cse.send_id = hsl.id
        WHERE cse.contractor_id = 29
        LIMIT 5
    ");
    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($relationships)) {
        echo "   No relationships found for contractor 29\n";
    } else {
        foreach ($relationships as $rel) {
            echo "   - Estimate ID: {$rel['estimate_id']}, Send ID: {$rel['send_id']}, Project: " . ($rel['project_name'] ?? 'NULL') . ", Status: {$rel['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
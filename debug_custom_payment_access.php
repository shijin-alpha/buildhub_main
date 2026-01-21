<?php
/**
 * Debug Custom Payment Access Issue
 * Check project access validation for custom payment requests
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Debugging Custom Payment Access Issue\n\n";
    
    $contractor_id = 29;
    
    // 1. Check contractor_send_estimates table structure and data
    echo "1. Checking contractor_send_estimates table:\n";
    $stmt = $pdo->query("DESCRIBE contractor_send_estimates");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    echo "\n";
    
    // 2. Check available projects for contractor 29
    echo "2. Available projects for contractor $contractor_id:\n";
    $stmt = $pdo->prepare("SELECT id, project_name, status, homeowner_id, total_cost FROM contractor_send_estimates WHERE contractor_id = ?");
    $stmt->execute([$contractor_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        echo "   ❌ No projects found for contractor $contractor_id\n";
    } else {
        foreach ($projects as $project) {
            echo "   - ID: {$project['id']}, Name: {$project['project_name']}, Status: {$project['status']}, Homeowner: {$project['homeowner_id']}, Cost: ₹" . number_format($project['total_cost']) . "\n";
        }
    }
    echo "\n";
    
    // 3. Test the exact validation query used in the API
    $project_id = 37; // The project we're trying to access
    echo "3. Testing validation query for project $project_id:\n";
    
    $projectCheckQuery = "
        SELECT COUNT(*) as count, cse.id, cse.project_name, cse.status, cse.contractor_id
        FROM contractor_send_estimates cse
        WHERE cse.id = :project_id 
        AND cse.contractor_id = :contractor_id 
        AND cse.status = 'accepted'
    ";
    
    $projectCheckStmt = $pdo->prepare($projectCheckQuery);
    $projectCheckStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $projectCheck = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Query result:\n";
    echo "   Count: {$projectCheck['count']}\n";
    echo "   Project ID: " . ($projectCheck['id'] ?? 'NULL') . "\n";
    echo "   Project Name: " . ($projectCheck['project_name'] ?? 'NULL') . "\n";
    echo "   Status: " . ($projectCheck['status'] ?? 'NULL') . "\n";
    echo "   Contractor ID: " . ($projectCheck['contractor_id'] ?? 'NULL') . "\n";
    echo "\n";
    
    if ($projectCheck['count'] == 0) {
        echo "❌ Validation failed! Let's check why:\n";
        
        // Check if project exists at all
        $existsStmt = $pdo->prepare("SELECT id, project_name, status, contractor_id FROM contractor_send_estimates WHERE id = ?");
        $existsStmt->execute([$project_id]);
        $exists = $existsStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exists) {
            echo "   - Project $project_id does not exist\n";
        } else {
            echo "   - Project exists: ID {$exists['id']}, Name: {$exists['project_name']}\n";
            echo "   - Current status: {$exists['status']} (needs to be 'accepted')\n";
            echo "   - Current contractor: {$exists['contractor_id']} (needs to be $contractor_id)\n";
            
            if ($exists['contractor_id'] != $contractor_id) {
                echo "   ❌ Contractor ID mismatch!\n";
            }
            if ($exists['status'] != 'accepted') {
                echo "   ❌ Project status is not 'accepted'!\n";
            }
        }
    } else {
        echo "✅ Validation passed! Project access should work.\n";
    }
    
    // 4. Check what the get_contractor_projects API returns
    echo "\n4. Testing get_contractor_projects API data:\n";
    
    $contractorProjectsQuery = "
        SELECT 
            cse.id,
            cse.project_name,
            cse.status,
            cse.homeowner_id,
            cse.total_cost as estimate_cost,
            cse.location,
            u.first_name as homeowner_first_name,
            u.last_name as homeowner_last_name
        FROM contractor_send_estimates cse
        LEFT JOIN users u ON cse.homeowner_id = u.id
        WHERE cse.contractor_id = ? 
        AND cse.status IN ('accepted', 'ready_for_construction', 'in_progress')
        ORDER BY cse.created_at DESC
    ";
    
    $contractorStmt = $pdo->prepare($contractorProjectsQuery);
    $contractorStmt->execute([$contractor_id]);
    $contractorProjects = $contractorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($contractorProjects)) {
        echo "   ❌ No projects returned by get_contractor_projects API\n";
    } else {
        echo "   Projects returned by API:\n";
        foreach ($contractorProjects as $project) {
            $homeowner_name = ($project['homeowner_first_name'] && $project['homeowner_last_name']) 
                ? $project['homeowner_first_name'] . ' ' . $project['homeowner_last_name'] 
                : 'Unknown';
            echo "   - ID: {$project['id']}, Name: {$project['project_name']}, Status: {$project['status']}, Homeowner: $homeowner_name\n";
        }
    }
    
    // 5. Suggest fix
    echo "\n💡 Suggested Fix:\n";
    if ($projectCheck['count'] == 0) {
        echo "The validation query is too restrictive. It only allows 'accepted' status.\n";
        echo "We should also allow 'ready_for_construction' and 'in_progress' statuses.\n";
        echo "This matches what the get_contractor_projects API returns.\n";
    } else {
        echo "The validation should work. There might be a session issue or different contractor ID being used.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
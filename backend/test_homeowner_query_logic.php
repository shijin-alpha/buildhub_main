<?php
/**
 * Test the exact query logic used by the homeowner API
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Testing Homeowner API Query Logic ===\n\n";

    // Use the homeowner ID we know has a submitted plan
    $homeowner_id = 28; // SHIJIN THOMAS MCA2024-2026
    
    echo "Testing for Homeowner ID: $homeowner_id\n\n";

    // This is the EXACT query from get_house_plans.php
    $whereConditions = ["lr.user_id = :homeowner_id"];
    $params = [':homeowner_id' => $homeowner_id];

    // Only show submitted plans to homeowners (not drafts)
    $whereConditions[] = "hp.status IN ('submitted', 'approved', 'rejected')";

    $whereClause = implode(' AND ', $whereConditions);
    
    // Add the second homeowner_id parameter for the LEFT JOIN
    $params[':homeowner_id_review'] = $homeowner_id;

    $query = "
        SELECT 
            hp.*,
            lr.plot_size as request_plot_size,
            lr.budget_range,
            lr.requirements,
            CONCAT(u.first_name, ' ', u.last_name) as architect_name,
            u.email as architect_email,
            u.specialization as architect_specialization,
            hpr.status as review_status,
            hpr.feedback as review_feedback,
            hpr.reviewed_at
        FROM house_plans hp
        INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
        INNER JOIN users u ON hp.architect_id = u.id
        LEFT JOIN house_plan_reviews hpr ON hp.id = hpr.house_plan_id AND hpr.homeowner_id = :homeowner_id_review
        WHERE {$whereClause}
        ORDER BY hp.updated_at DESC
    ";

    echo "Query:\n";
    echo $query . "\n\n";
    
    echo "Parameters:\n";
    print_r($params);
    echo "\n";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $plans = [];
    $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Raw Results Count: " . count($rawResults) . "\n\n";
    
    if (empty($rawResults)) {
        echo "❌ No results found!\n";
        
        // Let's debug why
        echo "\nDebugging - checking each part of the query:\n";
        
        // Check house_plans
        $hpStmt = $db->prepare("SELECT COUNT(*) as count FROM house_plans WHERE status IN ('submitted', 'approved', 'rejected')");
        $hpStmt->execute();
        $hpCount = $hpStmt->fetch()['count'];
        echo "- House plans with submitted/approved/rejected status: $hpCount\n";
        
        // Check layout_requests for this homeowner
        $lrStmt = $db->prepare("SELECT COUNT(*) as count FROM layout_requests WHERE user_id = :homeowner_id");
        $lrStmt->execute([':homeowner_id' => $homeowner_id]);
        $lrCount = $lrStmt->fetch()['count'];
        echo "- Layout requests for homeowner $homeowner_id: $lrCount\n";
        
        // Check the join
        $joinStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM house_plans hp
            INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
            WHERE lr.user_id = :homeowner_id AND hp.status IN ('submitted', 'approved', 'rejected')
        ");
        $joinStmt->execute([':homeowner_id' => $homeowner_id]);
        $joinCount = $joinStmt->fetch()['count'];
        echo "- House plans joined with layout requests for homeowner $homeowner_id: $joinCount\n";
        
    } else {
        echo "✅ Found " . count($rawResults) . " result(s):\n\n";
        
        foreach ($rawResults as $row) {
            // Parse plan_data JSON
            $plan_data = json_decode($row['plan_data'], true) ?? [];
            
            // Parse technical_details JSON
            $technical_details = json_decode($row['technical_details'], true) ?? [];
            
            $plan = [
                'id' => (int)$row['id'],
                'plan_name' => $row['plan_name'],
                'layout_request_id' => (int)$row['layout_request_id'],
                'plot_width' => (float)$row['plot_width'],
                'plot_height' => (float)$row['plot_height'],
                'total_area' => (float)$row['total_area'],
                'status' => $row['status'],
                'version' => (int)$row['version'],
                'notes' => $row['notes'],
                'plan_data' => $plan_data,
                'technical_details' => $technical_details,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                // Architect details
                'architect_info' => [
                    'name' => $row['architect_name'],
                    'email' => $row['architect_email'],
                    'specialization' => $row['architect_specialization']
                ],
                // Request details
                'request_info' => [
                    'plot_size' => $row['request_plot_size'],
                    'budget_range' => $row['budget_range'],
                    'requirements' => $row['requirements']
                ],
                // Review status
                'review_info' => [
                    'status' => $row['review_status'],
                    'feedback' => $row['review_feedback'],
                    'reviewed_at' => $row['reviewed_at']
                ]
            ];
            
            $plans[] = $plan;
            
            echo sprintf(
                "Plan ID: %d\n" .
                "Name: %s\n" .
                "Status: %s\n" .
                "Architect: %s\n" .
                "Layout Request ID: %d\n" .
                "Technical Details: %s\n" .
                "Review Status: %s\n" .
                "Created: %s\n" .
                "Updated: %s\n\n",
                $plan['id'],
                $plan['plan_name'],
                $plan['status'],
                $plan['architect_info']['name'],
                $plan['layout_request_id'],
                !empty($plan['technical_details']) ? 'Present' : 'Missing',
                $plan['review_info']['status'] ?? 'No review',
                $plan['created_at'],
                $plan['updated_at']
            );
        }
        
        // Simulate the API response
        $apiResponse = [
            'success' => true,
            'plans' => $plans,
            'total' => count($plans)
        ];
        
        echo "Simulated API Response:\n";
        echo json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
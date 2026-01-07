<?php
/**
 * Test house plan visibility for homeowner
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Testing House Plan Visibility ===\n\n";

    // Test homeowner ID
    $homeowner_id = 28; // SHIJIN THOMAS MCA2024-2026
    
    echo "Testing for Homeowner ID: $homeowner_id\n\n";

    // First, verify the homeowner exists and has the right role
    $userStmt = $db->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $homeowner_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ User not found!\n";
        exit;
    }

    echo "✅ User found:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Name: {$user['first_name']} {$user['last_name']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Role: {$user['role']}\n\n";

    if ($user['role'] !== 'homeowner') {
        echo "❌ User is not a homeowner!\n";
        exit;
    }

    // Check layout requests for this homeowner
    $lrStmt = $db->prepare("SELECT id, plot_size, budget_range, created_at FROM layout_requests WHERE user_id = :homeowner_id ORDER BY created_at DESC");
    $lrStmt->execute([':homeowner_id' => $homeowner_id]);
    $layoutRequests = $lrStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Layout Requests for this homeowner: " . count($layoutRequests) . "\n";
    foreach ($layoutRequests as $lr) {
        echo "- Request ID: {$lr['id']}, Plot: {$lr['plot_size']}, Budget: {$lr['budget_range']}, Created: {$lr['created_at']}\n";
    }
    echo "\n";

    // Check house plans for these requests
    if (!empty($layoutRequests)) {
        $requestIds = array_column($layoutRequests, 'id');
        $placeholders = str_repeat('?,', count($requestIds) - 1) . '?';
        
        $hpStmt = $db->prepare("
            SELECT hp.*, u.first_name, u.last_name, u.email as architect_email
            FROM house_plans hp
            INNER JOIN users u ON hp.architect_id = u.id
            WHERE hp.layout_request_id IN ($placeholders)
            ORDER BY hp.updated_at DESC
        ");
        $hpStmt->execute($requestIds);
        $housePlans = $hpStmt->fetchAll(PDO::FETCH_ASSOC);

        echo "House Plans for these requests: " . count($housePlans) . "\n";
        foreach ($housePlans as $hp) {
            echo "- Plan ID: {$hp['id']}\n";
            echo "  Name: {$hp['plan_name']}\n";
            echo "  Status: {$hp['status']}\n";
            echo "  Request ID: {$hp['layout_request_id']}\n";
            echo "  Architect: {$hp['first_name']} {$hp['last_name']} ({$hp['architect_email']})\n";
            echo "  Created: {$hp['created_at']}\n";
            echo "  Updated: {$hp['updated_at']}\n";
            echo "  Technical Details: " . (!empty($hp['technical_details']) ? 'Present' : 'Missing') . "\n";
            echo "\n";
        }
    }

    // Now test the exact API query
    echo "=== Testing API Query ===\n";
    
    $whereConditions = ["lr.user_id = :homeowner_id"];
    $params = [':homeowner_id' => $homeowner_id];
    $whereConditions[] = "hp.status IN ('submitted', 'approved', 'rejected')";
    $whereClause = implode(' AND ', $whereConditions);
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

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $apiResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "API Query Results: " . count($apiResults) . "\n";
    
    if (empty($apiResults)) {
        echo "❌ No results from API query!\n";
        
        // Debug each condition
        echo "\nDebugging conditions:\n";
        
        // Check if there are any house plans at all
        $allPlansStmt = $db->prepare("SELECT COUNT(*) as count FROM house_plans");
        $allPlansStmt->execute();
        $allPlansCount = $allPlansStmt->fetch()['count'];
        echo "- Total house plans in database: $allPlansCount\n";
        
        // Check submitted/approved/rejected plans
        $submittedStmt = $db->prepare("SELECT COUNT(*) as count FROM house_plans WHERE status IN ('submitted', 'approved', 'rejected')");
        $submittedStmt->execute();
        $submittedCount = $submittedStmt->fetch()['count'];
        echo "- House plans with submitted/approved/rejected status: $submittedCount\n";
        
        // Check the join condition
        $joinStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM house_plans hp
            INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
            WHERE lr.user_id = :homeowner_id
        ");
        $joinStmt->execute([':homeowner_id' => $homeowner_id]);
        $joinCount = $joinStmt->fetch()['count'];
        echo "- House plans for this homeowner (any status): $joinCount\n";
        
        // Check with status filter
        $statusJoinStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM house_plans hp
            INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
            WHERE lr.user_id = :homeowner_id AND hp.status IN ('submitted', 'approved', 'rejected')
        ");
        $statusJoinStmt->execute([':homeowner_id' => $homeowner_id]);
        $statusJoinCount = $statusJoinStmt->fetch()['count'];
        echo "- House plans for this homeowner with correct status: $statusJoinCount\n";
        
    } else {
        echo "✅ API query successful!\n";
        foreach ($apiResults as $result) {
            echo "- Plan: {$result['plan_name']} (ID: {$result['id']}, Status: {$result['status']})\n";
        }
        
        // Simulate the full API response
        $plans = [];
        foreach ($apiResults as $row) {
            $plan_data = json_decode($row['plan_data'], true) ?? [];
            $technical_details = json_decode($row['technical_details'], true) ?? [];
            
            $plans[] = [
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
                'architect_info' => [
                    'name' => $row['architect_name'],
                    'email' => $row['architect_email'],
                    'specialization' => $row['architect_specialization']
                ],
                'request_info' => [
                    'plot_size' => $row['request_plot_size'],
                    'budget_range' => $row['budget_range'],
                    'requirements' => $row['requirements']
                ],
                'review_info' => [
                    'status' => $row['review_status'],
                    'feedback' => $row['review_feedback'],
                    'reviewed_at' => $row['reviewed_at']
                ]
            ];
        }
        
        $apiResponse = [
            'success' => true,
            'plans' => $plans,
            'total' => count($plans)
        ];
        
        echo "\nFull API Response:\n";
        echo json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
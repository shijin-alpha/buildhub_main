<?php
/**
 * Test Custom Payment Fix
 * Verify that the custom payment request now works with project_created status
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🧪 Testing Custom Payment Request Fix\n\n";
    
    $contractor_id = 29;
    $project_id = 37;
    
    // 1. Test the fixed validation query
    echo "1. Testing fixed validation query:\n";
    $projectCheckQuery = "
        SELECT COUNT(*) as count, cse.id, cse.status, cse.contractor_id, cse.total_cost
        FROM contractor_send_estimates cse
        WHERE cse.id = :project_id 
        AND cse.contractor_id = :contractor_id 
        AND cse.status IN ('accepted', 'project_created')
    ";
    
    $projectCheckStmt = $pdo->prepare($projectCheckQuery);
    $projectCheckStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $projectCheck = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Count: {$projectCheck['count']}\n";
    echo "   Project ID: " . ($projectCheck['id'] ?? 'NULL') . "\n";
    echo "   Status: " . ($projectCheck['status'] ?? 'NULL') . "\n";
    echo "   Contractor ID: " . ($projectCheck['contractor_id'] ?? 'NULL') . "\n";
    echo "   Total Cost: ₹" . number_format($projectCheck['total_cost'] ?? 0) . "\n";
    echo "\n";
    
    if ($projectCheck['count'] > 0) {
        echo "✅ Validation passed! Custom payment request should work now.\n\n";
        
        // 2. Test the budget summary API
        echo "2. Testing budget summary API:\n";
        $estimateQuery = "
            SELECT 
                cse.total_cost as original_estimate,
                cp.project_name,
                u.first_name as homeowner_first_name,
                u.last_name as homeowner_last_name
            FROM contractor_send_estimates cse
            LEFT JOIN construction_projects cp ON cse.id = cp.estimate_id
            LEFT JOIN users u ON cp.homeowner_id = u.id
            WHERE cse.id = :project_id 
            AND cse.contractor_id = :contractor_id 
            AND cse.status IN ('accepted', 'project_created')
        ";
        
        $estimateStmt = $pdo->prepare($estimateQuery);
        $estimateStmt->execute([
            ':project_id' => $project_id,
            ':contractor_id' => $contractor_id
        ]);
        
        $project = $estimateStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            echo "   ✅ Budget summary API should work\n";
            echo "   Original Estimate: ₹" . number_format($project['original_estimate']) . "\n";
            echo "   Project Name: " . ($project['project_name'] ?? 'NULL') . "\n";
            echo "   Homeowner: " . ($project['homeowner_first_name'] ?? 'Unknown') . " " . ($project['homeowner_last_name'] ?? '') . "\n";
        } else {
            echo "   ⚠️ Budget summary might need adjustment for construction_projects relationship\n";
        }
        echo "\n";
        
        // 3. Simulate a custom payment request
        echo "3. Simulating custom payment request data:\n";
        $customRequest = [
            'project_id' => $project_id,
            'contractor_id' => $contractor_id,
            'homeowner_id' => 28, // Assuming homeowner ID 28
            'request_title' => 'Additional Electrical Work',
            'request_reason' => 'Need to install additional power outlets in the kitchen and living room due to client requirements that were not in the original scope.',
            'requested_amount' => 25000,
            'work_description' => 'Installation of 6 additional power outlets, 2 ceiling fans, and upgraded electrical panel to handle increased load.',
            'urgency_level' => 'medium',
            'category' => 'Additional Work Required',
            'contractor_notes' => 'This work is essential for the kitchen appliances and will improve the overall functionality of the home.'
        ];
        
        echo "   Request Title: {$customRequest['request_title']}\n";
        echo "   Amount: ₹" . number_format($customRequest['requested_amount']) . "\n";
        echo "   Category: {$customRequest['category']}\n";
        echo "   Urgency: {$customRequest['urgency_level']}\n";
        echo "   ✅ This request should now be accepted by the API\n";
        
    } else {
        echo "❌ Validation still failed. Check project status and contractor ID.\n";
    }
    
    // 4. Show what the contractor dashboard should display
    echo "\n4. Expected contractor dashboard behavior:\n";
    echo "   - Project 37 should appear in the project dropdown\n";
    echo "   - Budget summary should show:\n";
    echo "     * Original Estimate: ₹10,69,745\n";
    echo "     * Current Total Cost: ₹2,13,949 (Foundation payment)\n";
    echo "     * Budget Status: Under budget by ₹8,55,796\n";
    echo "   - Custom payment form should accept submissions\n";
    echo "   - After submission, budget should update to include custom payment\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
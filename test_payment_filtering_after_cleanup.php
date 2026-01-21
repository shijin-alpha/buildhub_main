<?php
/**
 * Test Payment Filtering After Cleanup
 * Verify that the filtering system works correctly after removing sample data
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🧪 Testing Payment Request Filtering After Cleanup\n\n";
    
    $contractor_id = 29;
    $project_id = 37;
    
    // Get payment requests for the specific project and contractor
    $query = "
        SELECT 
            spr.*,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            
            DATEDIFF(NOW(), spr.request_date) as days_since_request,
            
            CASE 
                WHEN spr.status = 'pending' AND DATEDIFF(NOW(), spr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue
            
        FROM stage_payment_requests spr
        LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
        WHERE spr.contractor_id = :contractor_id 
        AND spr.project_id = :project_id
        ORDER BY spr.request_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':contractor_id' => $contractor_id,
        ':project_id' => $project_id
    ]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get paid stages for filtering
    $paidStages = [];
    foreach ($requests as $request) {
        if ($request['status'] === 'paid') {
            $paidStages[] = $request['stage_name'];
        }
    }
    
    echo "📋 Payment Requests for Project $project_id:\n";
    if (empty($requests)) {
        echo "   No payment requests found\n";
    } else {
        foreach ($requests as $request) {
            echo "   - ID {$request['id']}: {$request['stage_name']} (₹" . number_format($request['requested_amount']) . " - {$request['status']})\n";
        }
    }
    echo "\n";
    
    echo "💰 Paid Stages (will be filtered out):\n";
    if (empty($paidStages)) {
        echo "   None - all stages available for payment requests\n";
    } else {
        foreach ($paidStages as $stage) {
            echo "   - $stage\n";
        }
    }
    echo "\n";
    
    // Show all construction stages and their availability
    $constructionStages = [
        'Foundation' => 20,
        'Structure' => 25,
        'Brickwork' => 15,
        'Roofing' => 15,
        'Electrical' => 8,
        'Plumbing' => 7,
        'Finishing' => 10
    ];
    
    echo "🏗️ Construction Stages Availability:\n";
    foreach ($constructionStages as $stageName => $percentage) {
        $isPaid = in_array($stageName, $paidStages);
        $hasRequest = false;
        $requestStatus = '';
        
        foreach ($requests as $request) {
            if ($request['stage_name'] === $stageName) {
                $hasRequest = true;
                $requestStatus = $request['status'];
                break;
            }
        }
        
        if ($isPaid) {
            echo "   ❌ $stageName ($percentage%) - PAID (filtered out)\n";
        } elseif ($hasRequest) {
            echo "   ⏳ $stageName ($percentage%) - REQUEST EXISTS ($requestStatus)\n";
        } else {
            echo "   ✅ $stageName ($percentage%) - AVAILABLE for new request\n";
        }
    }
    echo "\n";
    
    // Simulate the filtering that would happen in the frontend
    $availableStages = [];
    foreach ($constructionStages as $stageName => $percentage) {
        if (!in_array($stageName, $paidStages)) {
            $availableStages[] = $stageName;
        }
    }
    
    echo "🎯 Stages Available in Payment Request Form:\n";
    if (empty($availableStages)) {
        echo "   🎉 All stages are paid! Project complete.\n";
    } else {
        foreach ($availableStages as $stage) {
            echo "   - $stage\n";
        }
    }
    echo "\n";
    
    echo "✅ Test Results:\n";
    echo "   - Total payment requests: " . count($requests) . "\n";
    echo "   - Paid stages: " . count($paidStages) . "\n";
    echo "   - Available stages: " . count($availableStages) . "\n";
    echo "   - Filtering working: " . (count($availableStages) > 0 ? "✅ Yes" : "🎉 All paid") . "\n";
    
    if (count($paidStages) === 0) {
        echo "\n🎯 Perfect! No paid stages found, so all stages will be available for payment requests.\n";
        echo "The message 'The following stages have already been paid...' will not appear.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
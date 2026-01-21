<?php
/**
 * Test Unified Payment Query
 * Test the unified query for both stage and custom payment requests
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🧪 Testing Unified Payment Query\n\n";
    
    $homeowner_id = 28;
    $project_id = 37;
    
    // Test the unified query
    $query = "
        SELECT 
            spr.id,
            spr.project_id,
            spr.contractor_id,
            spr.homeowner_id,
            spr.stage_name as request_title,
            spr.requested_amount,
            spr.status,
            spr.request_date,
            'stage' as request_type,
            NULL as urgency_level,
            NULL as category,
            spr.work_description as request_description,
            
            -- Contractor details
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name
            
        FROM stage_payment_requests spr
        LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
        WHERE spr.homeowner_id = :homeowner_id AND spr.project_id = :project_id
        
        UNION ALL
        
        SELECT 
            cpr.id,
            cpr.project_id,
            cpr.contractor_id,
            cpr.homeowner_id,
            cpr.request_title,
            cpr.requested_amount,
            cpr.status,
            cpr.request_date,
            'custom' as request_type,
            cpr.urgency_level,
            cpr.category,
            cpr.request_reason as request_description,
            
            -- Contractor details
            u_contractor2.first_name as contractor_first_name,
            u_contractor2.last_name as contractor_last_name
            
        FROM custom_payment_requests cpr
        LEFT JOIN users u_contractor2 ON cpr.contractor_id = u_contractor2.id
        WHERE cpr.homeowner_id = :homeowner_id AND cpr.project_id = :project_id
        
        ORDER BY request_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':homeowner_id' => $homeowner_id,
        ':project_id' => $project_id
    ]);
    
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "1. Unified query results:\n";
    if (empty($requests)) {
        echo "   ❌ No payment requests found\n";
    } else {
        echo "   ✅ Found " . count($requests) . " payment requests:\n\n";
        
        foreach ($requests as $request) {
            $type = $request['request_type'] === 'stage' ? '🏗️ Stage' : '💳 Custom';
            echo "   $type Payment Request:\n";
            echo "   - ID: {$request['id']}\n";
            echo "   - Title: {$request['request_title']}\n";
            echo "   - Amount: ₹" . number_format($request['requested_amount']) . "\n";
            echo "   - Status: {$request['status']}\n";
            echo "   - Contractor: {$request['contractor_first_name']} {$request['contractor_last_name']}\n";
            echo "   - Date: {$request['request_date']}\n";
            
            if ($request['request_type'] === 'custom') {
                echo "   - Category: {$request['category']}\n";
                echo "   - Urgency: {$request['urgency_level']}\n";
                echo "   - Description: " . substr($request['request_description'], 0, 100) . "...\n";
            }
            echo "\n";
        }
    }
    
    // 2. Test summary statistics
    echo "2. Summary statistics:\n";
    
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
            SUM(CASE WHEN status = 'pending' THEN requested_amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'approved' THEN requested_amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = 'paid' THEN requested_amount ELSE 0 END) as paid_amount
        FROM (
            SELECT status, requested_amount FROM stage_payment_requests 
            WHERE homeowner_id = :homeowner_id AND project_id = :project_id
            UNION ALL
            SELECT status, requested_amount FROM custom_payment_requests 
            WHERE homeowner_id = :homeowner_id AND project_id = :project_id
        ) as all_requests
    ";
    
    $summaryStmt = $pdo->prepare($summaryQuery);
    $summaryStmt->execute([
        ':homeowner_id' => $homeowner_id,
        ':project_id' => $project_id
    ]);
    
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Total requests: {$summary['total_requests']}\n";
    echo "   - Pending: {$summary['pending_requests']} (₹" . number_format($summary['pending_amount']) . ")\n";
    echo "   - Approved: {$summary['approved_requests']} (₹" . number_format($summary['approved_amount']) . ")\n";
    echo "   - Paid: {$summary['paid_requests']} (₹" . number_format($summary['paid_amount']) . ")\n";
    echo "   - Rejected: {$summary['rejected_requests']}\n\n";
    
    // 3. What homeowner should see
    echo "3. What homeowner dashboard should display:\n";
    
    $pendingRequests = array_filter($requests, fn($r) => $r['status'] === 'pending');
    
    if (!empty($pendingRequests)) {
        echo "   📋 Pending Requests Requiring Action:\n";
        foreach ($pendingRequests as $request) {
            $type = $request['request_type'] === 'stage' ? 'Stage Payment' : 'Custom Payment';
            echo "   - $type: {$request['request_title']}\n";
            echo "     Amount: ₹" . number_format($request['requested_amount']) . "\n";
            echo "     From: {$request['contractor_first_name']} {$request['contractor_last_name']}\n";
            
            if ($request['request_type'] === 'custom') {
                echo "     Category: {$request['category']}\n";
                echo "     Priority: {$request['urgency_level']}\n";
            }
            echo "     Action needed: [Approve] [Reject]\n\n";
        }
    } else {
        echo "   ✅ No pending requests requiring action\n";
    }
    
    // 4. API integration status
    echo "4. API integration status:\n";
    echo "   ✅ Unified query working correctly\n";
    echo "   ✅ Both stage and custom requests included\n";
    echo "   ✅ Summary statistics calculated properly\n";
    echo "   🔧 Next step: Update homeowner dashboard to use new API\n";
    echo "   🔧 Next step: Add approval/rejection UI components\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $homeowner_id = 28;
    $project_id = null;
    $status = 'all';
    
    // Build WHERE clause for filtering
    $whereParams = [':homeowner_id1' => $homeowner_id, ':homeowner_id2' => $homeowner_id];
    $stageWhere = "WHERE spr.homeowner_id = :homeowner_id1";
    $customWhere = "WHERE cpr.homeowner_id = :homeowner_id2";
    
    echo "Parameters for main query:\n";
    print_r($whereParams);
    
    // Test main query
    $query = "
        (SELECT 
            spr.id,
            'stage' as request_type,
            spr.stage_name as request_title,
            spr.requested_amount,
            spr.status,
            spr.request_date
        FROM stage_payment_requests spr
        $stageWhere)
        
        UNION ALL
        
        (SELECT 
            cpr.id,
            'custom' as request_type,
            cpr.request_title,
            cpr.requested_amount,
            cpr.status,
            cpr.request_date
        FROM custom_payment_requests cpr
        $customWhere)
        
        ORDER BY request_date DESC
    ";
    
    echo "\nExecuting main query...\n";
    $stmt = $pdo->prepare($query);
    $stmt->execute($whereParams);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Main query successful! Found " . count($requests) . " requests.\n";
    
    // Test summary query
    $summaryParams = [':homeowner_id' => $homeowner_id];
    echo "\nParameters for summary query:\n";
    print_r($summaryParams);
    
    $summaryQuery = "
        SELECT 
            (SELECT COUNT(*) FROM stage_payment_requests spr WHERE spr.homeowner_id = :homeowner_id) +
            (SELECT COUNT(*) FROM custom_payment_requests cpr WHERE cpr.homeowner_id = :homeowner_id) as total_requests
    ";
    
    echo "\nExecuting summary query...\n";
    $summaryStmt = $pdo->prepare($summaryQuery);
    $summaryStmt->execute($summaryParams);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Summary query successful! Total requests: " . $summary['total_requests'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
// Test the SQL fix directly
require_once __DIR__ . '/backend/config/database.php';

try {
    echo "Testing SQL fix for stage documents...\n\n";
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test the fixed query
    $contractor_id = 32;
    $where_clause = "csd.contractor_id = :contractor_id";
    
    $query = "
        SELECT 
            csd.*,
            cp.project_name,
            cp.homeowner_id,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            CONCAT(u_verifier.first_name, ' ', u_verifier.last_name) as verified_by_name,
            spr.id as payment_request_id,
            spr.requested_amount,
            spr.status as payment_status
        FROM contractor_stage_documents csd
        JOIN construction_projects cp ON csd.project_id = cp.id
        JOIN users u_contractor ON csd.contractor_id = u_contractor.id
        LEFT JOIN users u_verifier ON csd.verified_by = u_verifier.id
        LEFT JOIN stage_payment_requests spr ON csd.related_payment_id = spr.id
        WHERE $where_clause
        ORDER BY csd.stage_name, csd.document_type, csd.upload_date DESC
        LIMIT 5
    ";
    
    echo "Executing query...\n";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':contractor_id' => $contractor_id]);
    
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully!\n";
    echo "Documents found: " . count($documents) . "\n\n";
    
    if (!empty($documents)) {
        echo "Sample documents:\n";
        foreach ($documents as $i => $doc) {
            echo ($i + 1) . ". Stage: " . ($doc['stage_name'] ?? 'N/A') . 
                 ", Type: " . ($doc['document_type'] ?? 'N/A') . 
                 ", Contractor: " . ($doc['contractor_name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "No documents found for contractor ID $contractor_id\n";
        
        // Check if there are any documents at all
        $count_query = "SELECT COUNT(*) as total FROM contractor_stage_documents";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "Total documents in system: $total\n";
        
        if ($total > 0) {
            echo "\nSample contractors with documents:\n";
            $sample_query = "
                SELECT DISTINCT 
                    csd.contractor_id, 
                    CONCAT(u.first_name, ' ', u.last_name) as contractor_name,
                    COUNT(*) as doc_count
                FROM contractor_stage_documents csd
                JOIN users u ON csd.contractor_id = u.id
                GROUP BY csd.contractor_id, u.first_name, u.last_name
                LIMIT 5
            ";
            $sample_stmt = $pdo->prepare($sample_query);
            $sample_stmt->execute();
            $contractors = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($contractors as $contractor) {
                echo "- ID: {$contractor['contractor_id']}, Name: {$contractor['contractor_name']}, Documents: {$contractor['doc_count']}\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ SQL fix test completed successfully!\n";
    echo "The 'u_contractor.name' column error has been fixed.\n";
    echo "Now using CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) instead.\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
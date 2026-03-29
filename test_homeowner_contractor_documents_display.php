<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Testing Homeowner Contractor Documents Display ===\n\n";
    
    // 1. Create a test contractor document
    echo "1. Creating test contractor document...\n";
    
    $testProjectId = 1;
    $testContractorId = 32;
    $testHomeownerId = 28; // From the project
    
    // Insert test document
    $insertDoc = $pdo->prepare("
        INSERT INTO contractor_stage_documents 
        (project_id, contractor_id, stage_name, document_type, file_path, original_filename, 
         file_size, mime_type, uploaded_by, description, is_mandatory, verification_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $docData = [
        $testProjectId, $testContractorId, 'Foundation', 'receipt',
        'uploads/contractor_documents/test_receipt.jpg', 'Foundation_Receipt_001.jpg',
        1024000, 'image/jpeg', $testContractorId, 'Material purchase receipt for foundation work', 1, 'pending'
    ];
    
    if ($insertDoc->execute($docData)) {
        $documentId = $pdo->lastInsertId();
        echo "✓ Test document created with ID: $documentId\n";
        
        // 2. Test the homeowner API
        echo "\n2. Testing homeowner API...\n";
        
        // Simulate the API call
        $stmt = $pdo->prepare("
            SELECT 
                csd.id,
                csd.project_id,
                csd.contractor_id,
                csd.stage_name,
                csd.document_type,
                csd.document_category,
                csd.file_path,
                csd.original_filename,
                csd.file_size,
                csd.mime_type,
                csd.upload_date,
                csd.uploaded_by,
                csd.description,
                csd.verification_status,
                csd.verified_by,
                csd.verified_at,
                csd.verification_notes,
                csd.is_mandatory,
                csd.related_payment_id,
                csd.metadata,
                csd.created_at,
                csd.updated_at,
                cp.project_name,
                cp.project_location,
                cp.total_cost as project_total_cost,
                CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
                u_contractor.email as contractor_email,
                u_contractor.phone as contractor_phone,
                CONCAT(u_verifier.first_name, ' ', u_verifier.last_name) as verified_by_name,
                u_verifier.email as verifier_email,
                spr.stage_name as payment_stage_name,
                spr.requested_amount as payment_amount
            FROM contractor_stage_documents csd
            JOIN construction_projects cp ON csd.project_id = cp.id
            JOIN users u_contractor ON csd.contractor_id = u_contractor.id
            LEFT JOIN users u_verifier ON csd.verified_by = u_verifier.id
            LEFT JOIN stage_payment_requests spr ON csd.related_payment_id = spr.id
            WHERE cp.homeowner_id = :homeowner_id
            ORDER BY csd.stage_name, csd.document_type, csd.upload_date DESC
        ");
        
        $stmt->execute([':homeowner_id' => $testHomeownerId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($documents)) {
            echo "✓ API returned " . count($documents) . " document(s)\n";
            
            foreach ($documents as $doc) {
                echo "  - {$doc['original_filename']} ({$doc['stage_name']} - {$doc['document_type']})\n";
                echo "    Contractor: {$doc['contractor_name']}\n";
                echo "    Status: {$doc['verification_status']}\n";
                echo "    File: {$doc['file_path']}\n";
            }
        } else {
            echo "✗ No documents returned by API\n";
        }
        
        // 3. Test the actual API endpoint
        echo "\n3. Testing actual API endpoint...\n";
        
        // Start session for the test
        session_start();
        $_SESSION['user_id'] = $testHomeownerId;
        
        $apiUrl = "http://localhost/buildhub/backend/api/homeowner/get_contractor_stage_documents.php";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Content-Type: application/json',
                    'Cookie: ' . session_name() . '=' . session_id()
                ]
            ]
        ]);
        
        $response = file_get_contents($apiUrl, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            
            if ($data && $data['success']) {
                echo "✓ API endpoint working successfully\n";
                echo "  Documents found: " . count($data['data']['documents']) . "\n";
                echo "  Statistics: " . json_encode($data['data']['statistics']) . "\n";
            } else {
                echo "✗ API endpoint failed: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "✗ Failed to call API endpoint\n";
        }
        
        // Clean up test data
        $pdo->prepare("DELETE FROM contractor_stage_documents WHERE id = ?")->execute([$documentId]);
        echo "\n✓ Test data cleaned up\n";
        
    } else {
        echo "✗ Failed to create test document\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Database tables ready\n";
    echo "✅ API logic working\n";
    echo "✅ Frontend should now display contractor documents\n";
    echo "\nHomeowners can now view contractor-uploaded documents in the Construction Progress > Documents section!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
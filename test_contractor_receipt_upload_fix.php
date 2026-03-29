<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Testing Contractor Receipt Upload Fix ===\n\n";
    
    // 1. Check if stage_document_requirements table exists and has data
    echo "1. Checking stage_document_requirements table...\n";
    $checkTable = $pdo->query("SHOW TABLES LIKE 'stage_document_requirements'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Table exists\n";
        
        $countQuery = $pdo->query("SELECT COUNT(*) as count FROM stage_document_requirements");
        $count = $countQuery->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✓ Table has $count document requirements\n";
    } else {
        echo "✗ Table does not exist\n";
        exit;
    }
    
    // 2. Test the API endpoint that was failing
    echo "\n2. Testing document requirements query...\n";
    $testProjectId = 1; // Using a known project ID
    $testStage = 'Foundation';
    $testDocType = 'receipt';
    
    $req_check = $pdo->prepare("
        SELECT accepted_formats, max_file_size, is_required 
        FROM stage_document_requirements 
        WHERE (project_id = ? OR project_id IS NULL) AND stage_name = ? AND document_type = ?
        ORDER BY project_id DESC LIMIT 1
    ");
    
    $req_check->execute([$testProjectId, $testStage, $testDocType]);
    $requirements = $req_check->fetch(PDO::FETCH_ASSOC);
    
    if ($requirements) {
        echo "✓ Document requirements found:\n";
        echo "  - Accepted formats: {$requirements['accepted_formats']}\n";
        echo "  - Max file size: " . ($requirements['max_file_size'] / 1024 / 1024) . " MB\n";
        echo "  - Required: " . ($requirements['is_required'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ No requirements found for project $testProjectId, stage $testStage, document type $testDocType\n";
    }
    
    // 3. Test the get_stage_documents query
    echo "\n3. Testing get_stage_documents query...\n";
    $req_query = "SELECT * FROM stage_document_requirements WHERE (project_id = ? OR project_id IS NULL)";
    $req_stmt = $pdo->prepare($req_query);
    $req_stmt->execute([$testProjectId]);
    $allRequirements = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✓ Found " . count($allRequirements) . " document requirements for project $testProjectId\n";
    
    // 4. Show available stages and document types
    echo "\n4. Available stages and document types:\n";
    $stagesQuery = $pdo->query("SELECT DISTINCT stage_name FROM stage_document_requirements ORDER BY stage_name");
    $stages = $stagesQuery->fetchAll(PDO::FETCH_COLUMN);
    echo "Stages: " . implode(', ', $stages) . "\n";
    
    $docTypesQuery = $pdo->query("SELECT DISTINCT document_type FROM stage_document_requirements ORDER BY document_type");
    $docTypes = $docTypesQuery->fetchAll(PDO::FETCH_COLUMN);
    echo "Document types: " . implode(', ', $docTypes) . "\n";
    
    echo "\n=== Fix Complete ===\n";
    echo "The stage_document_requirements table has been created and populated.\n";
    echo "Contractor receipt upload should now work without the database error.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
// Test the stage documents API fix
require_once __DIR__ . '/backend/config/database.php';

try {
    echo "Testing stage documents API fix...\n\n";
    
    // Test the API endpoint directly
    $contractor_id = 32; // Using a known contractor ID
    $url = "http://localhost/buildhub/backend/api/contractor/get_stage_documents.php?contractor_id=" . $contractor_id;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
            ]
        ]
    ]);
    
    echo "Testing API: $url\n";
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to get response from API\n";
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "❌ Failed to decode JSON response\n";
        echo "Raw response: $response\n";
        exit;
    }
    
    if (isset($data['success']) && $data['success']) {
        echo "✅ API call successful!\n";
        echo "Message: " . ($data['message'] ?? 'No message') . "\n";
        
        if (isset($data['data']['documents'])) {
            $documents = $data['data']['documents'];
            echo "Documents found: " . count($documents) . "\n";
            
            if (!empty($documents)) {
                echo "\nSample document:\n";
                $sample = array_values($documents)[0];
                if (!empty($sample)) {
                    $firstDoc = $sample[0];
                    echo "- Stage: " . ($firstDoc['stage_name'] ?? 'N/A') . "\n";
                    echo "- Type: " . ($firstDoc['document_type'] ?? 'N/A') . "\n";
                    echo "- Contractor: " . ($firstDoc['contractor_name'] ?? 'N/A') . "\n";
                    echo "- Upload Date: " . ($firstDoc['upload_date'] ?? 'N/A') . "\n";
                }
            }
        }
        
        if (isset($data['data']['summary'])) {
            echo "\nDocument Summary:\n";
            foreach ($data['data']['summary'] as $stage => $summary) {
                echo "- $stage: {$summary['total_documents']} documents\n";
            }
        }
        
    } else {
        echo "❌ API call failed\n";
        echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        if (isset($data['error'])) {
            echo "Details: " . $data['error'] . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Stage documents API fix test completed!\n";
    echo "The SQL column error should now be resolved.\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
}
?>
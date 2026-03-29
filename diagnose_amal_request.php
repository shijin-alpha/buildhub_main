<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Diagnosing Amal Samuel's request...\n";
echo str_repeat("=", 79) . "\n\n";

// Check contractor_layout_sends
$stmt = $conn->query("
    SELECT * FROM contractor_layout_sends
    WHERE contractor_id = 29
    ORDER BY created_at DESC
    LIMIT 1
");

$send = $stmt->fetch(PDO::FETCH_ASSOC);

echo "contractor_layout_sends record:\n";
foreach ($send as $key => $value) {
    echo "  $key: " . ($value ?: 'NULL') . "\n";
}

echo "\n" . str_repeat("=", 79) . "\n";
echo "SOLUTION\n";
echo str_repeat("=", 79) . "\n\n";

if (!$send['layout_id']) {
    echo "This request was sent WITHOUT a layout_id.\n";
    echo "This means AI predictions cannot be stored in layout_requests table.\n\n";
    
    echo "Options:\n";
    echo "1. Submit a NEW homeowner request that goes through proper flow\n";
    echo "2. Test with contractor ID 51 who has proper requests\n";
    echo "3. Modify the system to handle direct sends\n\n";
    
    echo "RECOMMENDED: Test with contractor 51\n";
    echo "  - Login as contractor with ID = 51\n";
    echo "  - That contractor has requests with AI predictions\n";
    echo "  - You'll see the AI Risk Assessment working\n";
}

$conn = null;
?>

<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING DELETION ISSUE ===\n\n";

echo "Current layout requests:\n";
$stmt = $db->query("SELECT id, user_id, homeowner_id, plot_size, budget_range, status, created_at FROM layout_requests WHERE id >= 105 ORDER BY id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, User: {$row['user_id']}, Status: '{$row['status']}', Created: {$row['created_at']}\n";
}

echo "\nCurrent assignments:\n";
$stmt = $db->query("SELECT id, layout_request_id, homeowner_id, architect_id, status FROM layout_request_assignments ORDER BY id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Request: {$row['layout_request_id']}, Homeowner: {$row['homeowner_id']}, Architect: {$row['architect_id']}, Status: {$row['status']}\n";
}

echo "\nTesting deletion of a specific request...\n";

// Let's test with request 105 which still exists
$requestId = 105;
$homeownerId = 28; // From the data above

echo "Testing deletion of request $requestId by homeowner $homeownerId\n";

// Check ownership
$stmt = $db->prepare("SELECT id FROM layout_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$requestId, $homeownerId]);
if ($stmt->rowCount() > 0) {
    echo "✓ Ownership verified\n";
    
    // Try the deletion
    $stmt = $db->prepare("UPDATE layout_requests SET status = 'deleted' WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$requestId, $homeownerId]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo "✓ Request marked as deleted\n";
        
        // Delete assignments
        $stmt = $db->prepare("DELETE FROM layout_request_assignments WHERE layout_request_id = ?");
        $stmt->execute([$requestId]);
        echo "✓ Assignments deleted (rows affected: " . $stmt->rowCount() . ")\n";
        
        // Restore for testing
        $stmt = $db->prepare("UPDATE layout_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$requestId]);
        echo "✓ Request status restored for testing\n";
        
    } else {
        echo "✗ Failed to delete request (rows affected: " . $stmt->rowCount() . ")\n";
    }
} else {
    echo "✗ Request not found or access denied\n";
}

echo "\nPossible issues:\n";
echo "1. Session not properly set (user_id not matching)\n";
echo "2. Request ID not found\n";
echo "3. Request already deleted\n";
echo "4. Database connection issue\n";
echo "5. Frontend not sending correct request ID\n";

echo "\nTo debug frontend issue:\n";
echo "1. Check browser developer tools Network tab\n";
echo "2. Look for the DELETE request to /buildhub/backend/api/homeowner/delete_request.php\n";
echo "3. Check the request payload and response\n";
echo "4. Verify session is active (user logged in)\n";
?>
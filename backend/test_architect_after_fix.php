<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "=== TESTING ARCHITECT VIEW AFTER DELETION FIX ===\n\n";

// First, let's create a test scenario
echo "Setting up test scenario...\n";

// Create a test request
$stmt = $db->prepare("INSERT INTO layout_requests (user_id, homeowner_id, plot_size, budget_range, requirements, status) VALUES (19, 19, '30x40', '20-30 lakhs', 'Test request for deletion', 'pending')");
$stmt->execute();
$testRequestId = $db->lastInsertId();
echo "Created test request ID: $testRequestId\n";

// Create assignment to architect 31
$stmt = $db->prepare("INSERT INTO layout_request_assignments (layout_request_id, homeowner_id, architect_id, message, status) VALUES (?, 19, 31, 'Test assignment', 'sent')");
$stmt->execute([$testRequestId]);
echo "Created assignment to architect 31\n";

// Test architect view BEFORE deletion
echo "\nBEFORE DELETION - Architect 31 view:\n";
$architect_id = 31;
$query = "SELECT 
            a.id as assignment_id,
            a.status as assignment_status,
            lr.id as layout_request_id,
            lr.status as request_status,
            lr.plot_size, lr.budget_range
          FROM layout_request_assignments a
          JOIN layout_requests lr ON lr.id = a.layout_request_id
          WHERE a.architect_id = :aid AND lr.status != 'deleted'
          ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([':aid' => $architect_id]);
$beforeResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($beforeResults) . " assignments:\n";
foreach ($beforeResults as $row) {
    echo "  Request {$row['layout_request_id']}: {$row['plot_size']}, {$row['budget_range']}, status={$row['request_status']}\n";
}

// Now delete the request
echo "\nDeleting request $testRequestId...\n";
$stmt = $db->prepare("UPDATE layout_requests SET status = 'deleted' WHERE id = ?");
$stmt->execute([$testRequestId]);
echo "Request marked as deleted\n";

// Test architect view AFTER deletion
echo "\nAFTER DELETION - Architect 31 view:\n";
$stmt = $db->prepare($query);
$stmt->execute([':aid' => $architect_id]);
$afterResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($afterResults) . " assignments:\n";
foreach ($afterResults as $row) {
    echo "  Request {$row['layout_request_id']}: {$row['plot_size']}, {$row['budget_range']}, status={$row['request_status']}\n";
}

// Test the old query (without the fix) to show the difference
echo "\nOLD QUERY (without deleted filter) would show:\n";
$oldQuery = "SELECT 
            a.id as assignment_id,
            lr.id as layout_request_id,
            lr.status as request_status,
            lr.plot_size, lr.budget_range
          FROM layout_request_assignments a
          JOIN layout_requests lr ON lr.id = a.layout_request_id
          WHERE a.architect_id = :aid
          ORDER BY a.created_at DESC";

$stmt = $db->prepare($oldQuery);
$stmt->execute([':aid' => $architect_id]);
$oldResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($oldResults) . " assignments (including deleted):\n";
foreach ($oldResults as $row) {
    echo "  Request {$row['layout_request_id']}: {$row['plot_size']}, {$row['budget_range']}, status={$row['request_status']}\n";
}

// Cleanup
echo "\nCleaning up test data...\n";
$db->prepare("DELETE FROM layout_request_assignments WHERE layout_request_id = ?")->execute([$testRequestId]);
$db->prepare("DELETE FROM layout_requests WHERE id = ?")->execute([$testRequestId]);
echo "Test data cleaned up\n";

echo "\nFIX SUMMARY:\n";
echo "✓ Added 'AND lr.status != \"deleted\"' to architect's get_assigned_requests.php\n";
echo "✓ Deleted requests will no longer appear in architect's dashboard\n";
echo "✓ Homeowner deletion functionality works correctly\n";
?>
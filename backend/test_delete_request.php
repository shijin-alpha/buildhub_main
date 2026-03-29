<?php
require_once 'config/database.php';

echo "=== TESTING LAYOUT REQUEST DELETION ===\n\n";

// Test with homeowner ID 19 (who owns request 109)
session_start();
$_SESSION['user_id'] = 19;

echo "Testing deletion of request 109 by homeowner 19...\n";

// Simulate the API call
$_POST = [];
$input = json_encode(['layout_request_id' => 109]);
file_put_contents('php://temp', $input);

// Check current status before deletion
$database = new Database();
$db = $database->getConnection();

echo "\nBEFORE DELETION:\n";
$stmt = $db->prepare("SELECT id, user_id, status FROM layout_requests WHERE id = 109");
$stmt->execute();
$before = $stmt->fetch(PDO::FETCH_ASSOC);
if ($before) {
    echo "Request 109: user_id={$before['user_id']}, status={$before['status']}\n";
} else {
    echo "Request 109 not found\n";
}

// Check assignments
$stmt = $db->prepare("SELECT id, architect_id, status FROM layout_request_assignments WHERE layout_request_id = 109");
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Assignments: " . count($assignments) . "\n";
foreach ($assignments as $assignment) {
    echo "  Assignment {$assignment['id']}: architect={$assignment['architect_id']}, status={$assignment['status']}\n";
}

// Test the deletion logic directly
echo "\nTESTING DELETION LOGIC:\n";

$homeowner_id = 19;
$layout_request_id = 109;

// Verify ownership
$checkQuery = "SELECT id FROM layout_requests WHERE id = :request_id AND user_id = :homeowner_id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':request_id', $layout_request_id);
$checkStmt->bindParam(':homeowner_id', $homeowner_id);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    echo "ERROR: Request not found or access denied\n";
    echo "Rows found: " . $checkStmt->rowCount() . "\n";
} else {
    echo "✓ Ownership verified\n";
    
    // Perform soft delete
    $deleteQuery = "UPDATE layout_requests SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = :request_id AND user_id = :homeowner_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':request_id', $layout_request_id);
    $deleteStmt->bindParam(':homeowner_id', $homeowner_id);
    
    if ($deleteStmt->execute()) {
        echo "✓ Request status updated to 'deleted'\n";
        echo "Rows affected: " . $deleteStmt->rowCount() . "\n";
        
        // Delete assignments
        $deleteAssignmentsQuery = "DELETE FROM layout_request_assignments WHERE layout_request_id = :request_id";
        $deleteAssignmentsStmt = $db->prepare($deleteAssignmentsQuery);
        $deleteAssignmentsStmt->bindParam(':request_id', $layout_request_id);
        $deleteAssignmentsStmt->execute();
        
        echo "✓ Assignments deleted\n";
        echo "Assignment rows affected: " . $deleteAssignmentsStmt->rowCount() . "\n";
    } else {
        echo "ERROR: Failed to update request status\n";
    }
}

echo "\nAFTER DELETION:\n";
$stmt = $db->prepare("SELECT id, user_id, status FROM layout_requests WHERE id = 109");
$stmt->execute();
$after = $stmt->fetch(PDO::FETCH_ASSOC);
if ($after) {
    echo "Request 109: user_id={$after['user_id']}, status={$after['status']}\n";
} else {
    echo "Request 109 not found\n";
}

// Check assignments
$stmt = $db->prepare("SELECT id, architect_id, status FROM layout_request_assignments WHERE layout_request_id = 109");
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Assignments: " . count($assignments) . "\n";
foreach ($assignments as $assignment) {
    echo "  Assignment {$assignment['id']}: architect={$assignment['architect_id']}, status={$assignment['status']}\n";
}
?>
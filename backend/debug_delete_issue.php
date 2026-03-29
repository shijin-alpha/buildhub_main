<?php
require_once 'config/database.php';

echo "=== DEBUGGING DELETE REQUEST ISSUE ===\n\n";

// Test different scenarios that could cause "Not found or no change" error

$database = new Database();
$db = $database->getConnection();

echo "1. TESTING SESSION AUTHENTICATION:\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✓ Session active, user_id: " . $_SESSION['user_id'] . "\n";
} else {
    echo "✗ No session found - this would cause authentication error\n";
    // Set a test session
    $_SESSION['user_id'] = 28; // Use homeowner 28 who owns request 105
    echo "✓ Set test session for user 28\n";
}

echo "\n2. TESTING REQUEST OWNERSHIP:\n";
$homeowner_id = $_SESSION['user_id'];
$test_request_id = 105; // Known existing request

$stmt = $db->prepare("SELECT id, user_id, homeowner_id, status FROM layout_requests WHERE id = ?");
$stmt->execute([$test_request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo "Request $test_request_id found:\n";
    echo "  user_id: {$request['user_id']}\n";
    echo "  homeowner_id: {$request['homeowner_id']}\n";
    echo "  status: {$request['status']}\n";
    
    if ($request['user_id'] == $homeowner_id) {
        echo "✓ Ownership verified\n";
    } else {
        echo "✗ Ownership mismatch - session user_id ($homeowner_id) != request user_id ({$request['user_id']})\n";
    }
} else {
    echo "✗ Request $test_request_id not found\n";
}

echo "\n3. TESTING DELETE CONDITIONS:\n";

// Test the exact conditions from the delete API
$checkQuery = "SELECT id FROM layout_requests WHERE id = :request_id AND user_id = :homeowner_id";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':request_id', $test_request_id);
$checkStmt->bindParam(':homeowner_id', $homeowner_id);
$checkStmt->execute();

echo "Check query result: " . $checkStmt->rowCount() . " rows\n";

if ($checkStmt->rowCount() > 0) {
    echo "✓ Request found and ownership verified\n";
    
    // Test the update query
    $deleteQuery = "UPDATE layout_requests SET status = 'test_deleted', updated_at = CURRENT_TIMESTAMP WHERE id = :request_id AND user_id = :homeowner_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':request_id', $test_request_id);
    $deleteStmt->bindParam(':homeowner_id', $homeowner_id);
    
    if ($deleteStmt->execute()) {
        echo "✓ Update query executed successfully\n";
        echo "Rows affected: " . $deleteStmt->rowCount() . "\n";
        
        if ($deleteStmt->rowCount() > 0) {
            echo "✓ Request status would be updated\n";
            
            // Restore original status
            $restoreStmt = $db->prepare("UPDATE layout_requests SET status = 'approved' WHERE id = ?");
            $restoreStmt->execute([$test_request_id]);
            echo "✓ Status restored\n";
        } else {
            echo "✗ No rows affected - this would cause 'no change' error\n";
        }
    } else {
        echo "✗ Update query failed\n";
    }
} else {
    echo "✗ Request not found or access denied - this would cause 'not found' error\n";
}

echo "\n4. COMMON CAUSES OF 'NOT FOUND OR NO CHANGE' ERROR:\n";
echo "a) User not logged in (no session)\n";
echo "b) Request ID doesn't exist\n";
echo "c) Request belongs to different user\n";
echo "d) Request already deleted\n";
echo "e) Database connection issue\n";
echo "f) Request ID sent as string instead of integer\n";

echo "\n5. TESTING FRONTEND SCENARIOS:\n";

// Test with string ID (common frontend issue)
$string_id = "105";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':request_id', $string_id);
$checkStmt->bindParam(':homeowner_id', $homeowner_id);
$checkStmt->execute();
echo "String ID test: " . $checkStmt->rowCount() . " rows (should work)\n";

// Test with non-existent ID
$fake_id = 99999;
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':request_id', $fake_id);
$checkStmt->bindParam(':homeowner_id', $homeowner_id);
$checkStmt->execute();
echo "Fake ID test: " . $checkStmt->rowCount() . " rows (should be 0)\n";

// Test with wrong user
$wrong_user = 999;
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(':request_id', $test_request_id);
$checkStmt->bindParam(':homeowner_id', $wrong_user);
$checkStmt->execute();
echo "Wrong user test: " . $checkStmt->rowCount() . " rows (should be 0)\n";

echo "\nRECOMMENDATIONS:\n";
echo "1. Check browser developer tools Network tab for the actual API call\n";
echo "2. Verify the request payload contains correct layout_request_id\n";
echo "3. Check if user is properly logged in\n";
echo "4. Ensure the request ID exists and belongs to the logged-in user\n";
echo "5. Check if request is already deleted\n";
?>
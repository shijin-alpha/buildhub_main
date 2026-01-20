<?php
// Test the delete API endpoint directly

echo "=== TESTING DELETE REQUEST API ENDPOINT ===\n\n";

// Create a test request first
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Insert a test request
echo "Creating test request...\n";
$stmt = $db->prepare("INSERT INTO layout_requests (user_id, homeowner_id, plot_size, budget_range, requirements, status) VALUES (19, 19, '30x40', '20-30 lakhs', 'Test deletion request', 'pending')");
$stmt->execute();
$testRequestId = $db->lastInsertId();
echo "Created test request ID: $testRequestId\n";

// Create assignment
$stmt = $db->prepare("INSERT INTO layout_request_assignments (layout_request_id, homeowner_id, architect_id, message, status) VALUES (?, 19, 31, 'Test assignment', 'sent')");
$stmt->execute([$testRequestId]);
echo "Created test assignment\n";

// Now test the API
echo "\nTesting API endpoint...\n";

// Start session and set user
session_start();
$_SESSION['user_id'] = 19;

// Simulate POST request
$postData = json_encode(['layout_request_id' => $testRequestId]);

// Capture output from the API
ob_start();

// Simulate the API call by including the file with proper input
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Mock the input stream
$tempFile = tmpfile();
fwrite($tempFile, $postData);
rewind($tempFile);

// Override php://input for this test
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpInputStream");

class MockPhpInputStream {
    public static $data = '';
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        global $postData;
        $result = substr($postData, 0, $count);
        $postData = substr($postData, $count);
        return $result;
    }
    
    public function stream_eof() {
        global $postData;
        return empty($postData);
    }
    
    public function stream_stat() {
        return array();
    }
}

// Include the API file
try {
    include 'api/homeowner/delete_request.php';
} catch (Exception $e) {
    echo "Error including API: " . $e->getMessage() . "\n";
}

$apiOutput = ob_get_clean();

echo "API Response:\n";
echo $apiOutput . "\n";

// Verify the deletion worked
echo "\nVerifying deletion...\n";
$stmt = $db->prepare("SELECT status FROM layout_requests WHERE id = ?");
$stmt->execute([$testRequestId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "Request status: " . $result['status'] . "\n";
} else {
    echo "Request not found\n";
}

// Check assignments
$stmt = $db->prepare("SELECT COUNT(*) as count FROM layout_request_assignments WHERE layout_request_id = ?");
$stmt->execute([$testRequestId]);
$assignmentCount = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Remaining assignments: " . $assignmentCount['count'] . "\n";

// Cleanup
$db->prepare("DELETE FROM layout_request_assignments WHERE layout_request_id = ?")->execute([$testRequestId]);
$db->prepare("DELETE FROM layout_requests WHERE id = ?")->execute([$testRequestId]);
echo "Test data cleaned up\n";
?>
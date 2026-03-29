<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// First, fix the status of request 109
echo "=== FIXING REQUEST 109 STATUS ===\n";
$stmt = $db->prepare('UPDATE layout_requests SET status = "pending" WHERE id = 109 AND status = ""');
$result = $stmt->execute();
echo "Status update result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Check available architects
echo "\n=== AVAILABLE ARCHITECTS ===\n";
$stmt = $db->query('SELECT id, first_name, last_name, email FROM users WHERE role = "architect" AND is_verified = 1 ORDER BY id');
$architects = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($architects as $architect) {
    echo "ID: {$architect['id']}, Name: {$architect['first_name']} {$architect['last_name']}, Email: {$architect['email']}\n";
}

// Assign request 109 to architect 31 (shijin thomas) as a test
echo "\n=== ASSIGNING REQUEST 109 TO ARCHITECT 31 ===\n";
try {
    $stmt = $db->prepare("INSERT INTO layout_request_assignments (layout_request_id, homeowner_id, architect_id, message, status) VALUES (109, 19, 31, 'Test assignment for debugging', 'sent')");
    $result = $stmt->execute();
    echo "Assignment result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "Assignment error: " . $e->getMessage() . "\n";
}

// Verify the assignment was created
echo "\n=== VERIFYING ASSIGNMENT ===\n";
$stmt = $db->prepare('SELECT * FROM layout_request_assignments WHERE layout_request_id = 109');
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($assignments) {
    foreach ($assignments as $assignment) {
        echo "Assignment ID: {$assignment['id']}, Architect: {$assignment['architect_id']}, Status: {$assignment['status']}\n";
    }
} else {
    echo "No assignments found\n";
}
?>
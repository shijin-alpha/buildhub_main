<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "=== REQUEST 109 DETAILS ===\n";
$stmt = $db->prepare('SELECT * FROM layout_requests WHERE id = 109');
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if ($request) {
    foreach ($request as $key => $value) {
        echo "$key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
} else {
    echo "Request 109 not found\n";
}

echo "\n=== ASSIGNMENTS FOR REQUEST 109 ===\n";
$stmt = $db->prepare('SELECT * FROM layout_request_assignments WHERE layout_request_id = 109');
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($assignments) {
    foreach ($assignments as $assignment) {
        echo "Assignment ID: {$assignment['id']}, Architect: {$assignment['architect_id']}, Status: {$assignment['status']}\n";
    }
} else {
    echo "No assignments found for request 109\n";
}

echo "\n=== USER 19 DETAILS ===\n";
$stmt = $db->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id = 19');
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    foreach ($user as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "User 19 not found\n";
}
?>
<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "=== RECENT LAYOUT REQUESTS ===\n";
$stmt = $db->query('SELECT id, user_id, homeowner_id, plot_size, budget_range, status, created_at FROM layout_requests ORDER BY created_at DESC LIMIT 10');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, User: {$row['user_id']}, Homeowner: {$row['homeowner_id']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
}

echo "\n=== LAYOUT REQUEST ASSIGNMENTS ===\n";
$stmt = $db->query('SELECT id, layout_request_id, homeowner_id, architect_id, status, created_at FROM layout_request_assignments ORDER BY created_at DESC LIMIT 10');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Request: {$row['layout_request_id']}, Homeowner: {$row['homeowner_id']}, Architect: {$row['architect_id']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
}

echo "\n=== ARCHITECTS ===\n";
$stmt = $db->query('SELECT id, first_name, last_name, email, is_verified FROM users WHERE role = "architect" ORDER BY id DESC LIMIT 10');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}, Email: {$row['email']}, Verified: {$row['is_verified']}\n";
}
?>
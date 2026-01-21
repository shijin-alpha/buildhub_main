<?php
// Test delete API directly
session_start();
$_SESSION['user_id'] = 27;
$_SESSION['role'] = 'architect';

// Simulate POST data
$_POST = [];
file_put_contents('php://input', json_encode(['preview_id' => 2]));

echo "=== Testing Delete API Direct ===\n\n";

// Capture output
ob_start();
include 'backend/api/architect/delete_concept_preview.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output . "\n";

// Check if concept was actually deleted
require_once 'backend/config/database.php';

$stmt = $db->prepare("SELECT id FROM concept_previews WHERE id = 2");
$stmt->execute();
$exists = $stmt->fetch();

echo "\nConcept ID 2 still exists: " . ($exists ? 'YES' : 'NO') . "\n";

echo "\n=== Test Complete ===\n";
?>
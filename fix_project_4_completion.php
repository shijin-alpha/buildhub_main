<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Fixing Project #4 Completion Percentage\n";
echo "========================================\n\n";

// Check current status
$check = $conn->prepare("SELECT id, project_name, status, completion_percentage FROM construction_projects WHERE id = 4");
$check->execute();
$project = $check->fetch(PDO::FETCH_ASSOC);

echo "Before:\n";
echo "  Status: {$project['status']}\n";
echo "  Completion: {$project['completion_percentage']}%\n\n";

// Update completion to 100% since it's marked as completed
$update = $conn->prepare("UPDATE construction_projects SET completion_percentage = 100.00 WHERE id = 4 AND status = 'completed'");
$update->execute();

echo "✅ Updated Project #4 completion to 100%\n\n";

// Verify
$verify = $conn->prepare("SELECT id, project_name, status, completion_percentage FROM construction_projects WHERE id = 4");
$verify->execute();
$project = $verify->fetch(PDO::FETCH_ASSOC);

echo "After:\n";
echo "  Status: {$project['status']}\n";
echo "  Completion: {$project['completion_percentage']}%\n\n";

echo "✅ Project #4 data is now consistent!\n";
?>

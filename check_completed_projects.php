<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Completed Projects:\n";
echo "===================\n\n";

$result = $conn->query("
    SELECT id, project_name, status, completion_percentage, contractor_id
    FROM construction_projects
    WHERE status = 'completed' OR completion_percentage >= 100
    ORDER BY id
    LIMIT 10
");

$count = 0;
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | {$row['project_name']} | Status: {$row['status']} | Progress: {$row['completion_percentage']}% | Contractor: {$row['contractor_id']}\n";
    $count++;
}

if ($count == 0) {
    echo "No completed projects found.\n";
} else {
    echo "\nTotal: $count projects\n";
}
?>

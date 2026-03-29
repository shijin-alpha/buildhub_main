<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Checking Project Completion Percentages\n";
echo "========================================\n\n";

$projects = [1, 3, 4, 37];

foreach ($projects as $project_id) {
    $query = "SELECT id, project_name, status, completion_percentage FROM construction_projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        echo "Project #{$project['id']}\n";
        echo "  Name: {$project['project_name']}\n";
        echo "  Status: {$project['status']}\n";
        echo "  Completion: {$project['completion_percentage']}%\n\n";
    }
}
?>

<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $db->query('SELECT id, estimate_id, project_name, status, completion_percentage FROM construction_projects WHERE estimate_id = 37 OR id = 37');
echo "Projects with ID 37 or estimate_id 37:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Project ID: {$row['id']}, Estimate: {$row['estimate_id']}, Name: {$row['project_name']}, Status: {$row['status']}, Completion: {$row['completion_percentage']}%\n";
}

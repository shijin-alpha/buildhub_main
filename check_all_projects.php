<?php
$db = new PDO('sqlite:buildhub.db');
$stmt = $db->query('SELECT id, homeowner_name, project_type, estimated_cost, status FROM projects ORDER BY id');
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total projects: " . count($projects) . "\n\n";

foreach($projects as $p) {
    echo "ID: " . $p['id'] . "\n";
    echo "Name: " . $p['homeowner_name'] . "\n";
    echo "Type: " . $p['project_type'] . "\n";
    echo "Cost: " . $p['estimated_cost'] . "\n";
    echo "Status: " . $p['status'] . "\n";
    echo "---\n";
}

<?php
require_once 'backend/config/database.php';

$db = (new Database())->getConnection();
$stmt = $db->query('DESCRIBE contractor_stage_documents');

echo "contractor_stage_documents table structure:\n";
echo str_repeat("=", 60) . "\n";

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>

<?php
$conn = new mysqli('localhost', 'root', '', 'buildhub');
echo "ai_evaluation_metrics columns:\n";
$result = $conn->query('DESCRIBE ai_evaluation_metrics');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>

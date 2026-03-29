<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "Tables with 'request' or 'send':\n";
$stmt = $db->query('SHOW TABLES');
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    if (stripos($row[0], 'request') !== false || stripos($row[0], 'send') !== false) {
        echo "  " . $row[0] . "\n";
    }
}
?>

<?php
require_once 'backend/config/database.php';
$db = (new Database())->getConnection();
$r = $db->query('DESCRIBE contractor_layout_sends')->fetchAll(PDO::FETCH_ASSOC);
foreach($r as $col) {
    echo "{$col['Field']} - {$col['Type']}\n";
}
?>

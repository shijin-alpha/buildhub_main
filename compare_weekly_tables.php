<?php
require_once 'backend/config/database.php';

$db = (new Database())->getConnection();

echo "weekly_progress_summary structure:\n";
$r = $db->query('DESCRIBE weekly_progress_summary')->fetchAll(PDO::FETCH_ASSOC);
foreach($r as $row) {
    echo "  {$row['Field']} - {$row['Type']}\n";
}

echo "\nweekly_progress_summaries structure:\n";
$r = $db->query('DESCRIBE weekly_progress_summaries')->fetchAll(PDO::FETCH_ASSOC);
foreach($r as $row) {
    echo "  {$row['Field']} - {$row['Type']}\n";
}
?>

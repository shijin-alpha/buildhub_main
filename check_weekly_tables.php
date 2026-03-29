<?php
require_once 'backend/config/database.php';

$db = (new Database())->getConnection();
$tables = $db->query("SHOW TABLES LIKE '%weekly%'")->fetchAll(PDO::FETCH_COLUMN);

echo "Tables with 'weekly' in name:\n";
foreach($tables as $t) {
    echo "  - $t\n";
}

// Check if data exists
if (in_array('weekly_progress_summary', $tables)) {
    $count = $db->query("SELECT COUNT(*) FROM weekly_progress_summary")->fetchColumn();
    echo "\nweekly_progress_summary has $count records\n";
}

if (in_array('weekly_progress_summaries', $tables)) {
    $count = $db->query("SELECT COUNT(*) FROM weekly_progress_summaries")->fetchColumn();
    echo "weekly_progress_summaries has $count records\n";
}
?>

<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $db->query('SELECT update_date, construction_stage FROM daily_progress_updates WHERE project_id = 37 ORDER BY update_date');
echo "Used dates for Project 37:\n\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['update_date'] . ' - ' . $row['construction_stage'] . "\n";
}

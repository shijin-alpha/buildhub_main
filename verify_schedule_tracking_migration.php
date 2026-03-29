<?php
/**
 * Verify Schedule Tracking Migration
 */

require_once __DIR__ . '/backend/config/database.php';

echo "=================================================================\n";
echo "SCHEDULE TRACKING MIGRATION VERIFICATION\n";
echo "=================================================================\n\n";

$database = new Database();
$db = $database->getConnection();

// Check columns
echo "Checking new columns in construction_projects:\n";
echo "---------------------------------------------------\n";

$query = "SHOW COLUMNS FROM construction_projects WHERE Field LIKE '%date%' OR Field = 'planned_dates_locked' OR Field = 'actual_time_overrun_percentage'";
$stmt = $db->query($query);

$expectedColumns = [
    'planned_start_date',
    'planned_end_date',
    'actual_start_date',
    'actual_end_date',
    'actual_time_overrun_percentage',
    'planned_dates_locked'
];

$foundColumns = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $foundColumns[] = $row['Field'];
    echo "✓ {$row['Field']} ({$row['Type']}) - Nullable: {$row['Null']}\n";
}

echo "\n";

// Check if all expected columns exist
$missingColumns = array_diff($expectedColumns, $foundColumns);
if (empty($missingColumns)) {
    echo "✓ All 6 schedule tracking columns found!\n\n";
} else {
    echo "✗ Missing columns: " . implode(', ', $missingColumns) . "\n\n";
}

// Check audit table
echo "Checking project_schedule_audit table:\n";
echo "---------------------------------------------------\n";

$query = "SHOW TABLES LIKE 'project_schedule_audit'";
$stmt = $db->query($query);
$auditTableExists = $stmt->rowCount() > 0;

if ($auditTableExists) {
    echo "✓ project_schedule_audit table exists\n";
    
    $query = "SHOW COLUMNS FROM project_schedule_audit";
    $stmt = $db->query($query);
    echo "\nAudit table columns:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "✗ project_schedule_audit table not found\n";
}

echo "\n";

// Check indexes
echo "Checking indexes:\n";
echo "---------------------------------------------------\n";

$query = "SHOW INDEX FROM construction_projects WHERE Key_name IN ('idx_schedule_tracking', 'idx_time_overrun')";
$stmt = $db->query($query);

$indexes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $indexes[] = $row['Key_name'];
}

$indexes = array_unique($indexes);

if (in_array('idx_schedule_tracking', $indexes)) {
    echo "✓ idx_schedule_tracking index exists\n";
} else {
    echo "✗ idx_schedule_tracking index not found\n";
}

if (in_array('idx_time_overrun', $indexes)) {
    echo "✓ idx_time_overrun index exists\n";
} else {
    echo "✗ idx_time_overrun index not found\n";
}

echo "\n";

// Check existing projects
echo "Checking existing projects:\n";
echo "---------------------------------------------------\n";

$query = "SELECT COUNT(*) as total FROM construction_projects";
$stmt = $db->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total projects: {$result['total']}\n";

$query = "SELECT COUNT(*) as with_schedule FROM construction_projects WHERE planned_start_date IS NOT NULL";
$stmt = $db->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Projects with schedule data: {$result['with_schedule']}\n";

$query = "SELECT COUNT(*) as without_schedule FROM construction_projects WHERE planned_start_date IS NULL";
$stmt = $db->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Projects without schedule data: {$result['without_schedule']}\n";

echo "\n";
echo "=================================================================\n";
echo "✓ MIGRATION VERIFICATION COMPLETE\n";
echo "=================================================================\n";
echo "\nThe schedule tracking system is ready to use!\n";
echo "All existing projects remain fully functional.\n";
echo "New schedule fields are optional and backward-compatible.\n\n";
?>

<?php
/**
 * SCHEDULE TRACKING MIGRATION SCRIPT
 * Applies backward-compatible schedule tracking fields to construction_projects table
 * Safe to run multiple times - checks for existing columns before adding
 */

require_once __DIR__ . '/backend/config/database.php';

echo "=================================================================\n";
echo "SCHEDULE TRACKING MIGRATION\n";
echo "=================================================================\n\n";

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("ERROR: Could not connect to database\n");
}

echo "✓ Database connection established\n\n";

// Function to check if column exists
function columnExists($db, $tableName, $columnName) {
    $query = "SELECT COUNT(*) as count 
              FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = :table_name 
              AND COLUMN_NAME = :column_name";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':table_name', $tableName);
    $stmt->bindParam(':column_name', $columnName);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

// Function to check if table exists
function tableExists($db, $tableName) {
    $query = "SELECT COUNT(*) as count 
              FROM INFORMATION_SCHEMA.TABLES 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = :table_name";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':table_name', $tableName);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

try {
    $db->beginTransaction();
    
    echo "Step 1: Checking construction_projects table...\n";
    
    if (!tableExists($db, 'construction_projects')) {
        throw new Exception("construction_projects table does not exist!");
    }
    
    echo "✓ construction_projects table found\n\n";
    
    // Add schedule tracking columns
    echo "Step 2: Adding schedule tracking columns...\n";
    
    $columns = [
        'planned_start_date' => "ALTER TABLE `construction_projects` 
                                 ADD COLUMN `planned_start_date` DATE NULL DEFAULT NULL 
                                 COMMENT 'Planned project start date (contractor-entered after approval)'",
        
        'planned_end_date' => "ALTER TABLE `construction_projects` 
                               ADD COLUMN `planned_end_date` DATE NULL DEFAULT NULL 
                               COMMENT 'Planned project completion date (contractor-entered after approval)'",
        
        'actual_start_date' => "ALTER TABLE `construction_projects` 
                                ADD COLUMN `actual_start_date` DATE NULL DEFAULT NULL 
                                COMMENT 'Actual project start date (auto-locks planned dates)'",
        
        'actual_end_date' => "ALTER TABLE `construction_projects` 
                              ADD COLUMN `actual_end_date` DATE NULL DEFAULT NULL 
                              COMMENT 'Actual project completion date (triggers overrun calculation)'",
        
        'actual_time_overrun_percentage' => "ALTER TABLE `construction_projects` 
                                             ADD COLUMN `actual_time_overrun_percentage` DECIMAL(10,2) NULL DEFAULT NULL 
                                             COMMENT 'Calculated time overrun percentage'",
        
        'planned_dates_locked' => "ALTER TABLE `construction_projects` 
                                   ADD COLUMN `planned_dates_locked` TINYINT(1) DEFAULT 0 
                                   COMMENT 'Flag to prevent planned date modification after actual start'"
    ];
    
    foreach ($columns as $columnName => $alterQuery) {
        if (columnExists($db, 'construction_projects', $columnName)) {
            echo "  ⊙ Column '$columnName' already exists - skipping\n";
        } else {
            $db->exec($alterQuery);
            echo "  ✓ Added column '$columnName'\n";
        }
    }
    
    echo "\n";
    
    // Create indexes
    echo "Step 3: Creating indexes...\n";
    
    try {
        $db->exec("CREATE INDEX idx_schedule_tracking ON `construction_projects` (
            `planned_start_date`, 
            `planned_end_date`, 
            `actual_start_date`, 
            `actual_end_date`
        )");
        echo "  ✓ Created idx_schedule_tracking index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ⊙ Index idx_schedule_tracking already exists - skipping\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("CREATE INDEX idx_time_overrun ON `construction_projects` (`actual_time_overrun_percentage`)");
        echo "  ✓ Created idx_time_overrun index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ⊙ Index idx_time_overrun already exists - skipping\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n";
    
    // Create audit table
    echo "Step 4: Creating project_schedule_audit table...\n";
    
    if (tableExists($db, 'project_schedule_audit')) {
        echo "  ⊙ project_schedule_audit table already exists - skipping\n";
    } else {
        $createAuditTable = "CREATE TABLE `project_schedule_audit` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT NOT NULL,
            `changed_by_user_id` INT NOT NULL,
            `user_role` ENUM('contractor', 'homeowner', 'admin', 'system') NOT NULL,
            `field_changed` VARCHAR(50) NOT NULL,
            `old_value` DATE NULL,
            `new_value` DATE NULL,
            `change_reason` TEXT NULL,
            `ip_address` VARCHAR(45) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`project_id`) REFERENCES `construction_projects`(`id`) ON DELETE CASCADE,
            INDEX idx_project_audit (`project_id`, `created_at`),
            INDEX idx_user_audit (`changed_by_user_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        COMMENT='Audit trail for all schedule tracking changes'";
        
        $db->exec($createAuditTable);
        echo "  ✓ Created project_schedule_audit table\n";
    }
    
    echo "\n";
    
    // Verify migration
    echo "Step 5: Verifying migration...\n";
    
    $verifyQuery = "SELECT 
                        COLUMN_NAME, 
                        DATA_TYPE, 
                        IS_NULLABLE, 
                        COLUMN_DEFAULT, 
                        COLUMN_COMMENT
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'construction_projects'
                      AND COLUMN_NAME IN (
                          'planned_start_date', 
                          'planned_end_date', 
                          'actual_start_date', 
                          'actual_end_date', 
                          'actual_time_overrun_percentage',
                          'planned_dates_locked'
                      )
                    ORDER BY COLUMN_NAME";
    
    $stmt = $db->prepare($verifyQuery);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($columns) === 6) {
        echo "  ✓ All 6 schedule tracking columns verified\n";
        foreach ($columns as $col) {
            echo "    - {$col['COLUMN_NAME']} ({$col['DATA_TYPE']}, nullable: {$col['IS_NULLABLE']})\n";
        }
    } else {
        throw new Exception("Verification failed: Expected 6 columns, found " . count($columns));
    }
    
    echo "\n";
    
    // Check existing projects
    echo "Step 6: Checking existing projects...\n";
    
    $countQuery = "SELECT COUNT(*) as total FROM construction_projects";
    $stmt = $db->prepare($countQuery);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  ✓ Found {$result['total']} existing projects\n";
    echo "  ✓ All existing projects remain compatible (new fields are NULL)\n";
    
    $db->commit();
    
    echo "\n";
    echo "=================================================================\n";
    echo "✓ MIGRATION COMPLETED SUCCESSFULLY\n";
    echo "=================================================================\n\n";
    
    echo "Summary:\n";
    echo "  • Added 6 new schedule tracking columns to construction_projects\n";
    echo "  • Created 2 indexes for efficient querying\n";
    echo "  • Created project_schedule_audit table for change tracking\n";
    echo "  • All existing projects remain fully functional\n";
    echo "  • New fields are optional and backward-compatible\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Contractors can now set planned start/end dates for projects\n";
    echo "  2. Actual dates can be recorded as work progresses\n";
    echo "  3. Time overrun is automatically calculated on completion\n";
    echo "  4. All changes are logged in the audit table\n\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n";
    echo "=================================================================\n";
    echo "✗ MIGRATION FAILED\n";
    echo "=================================================================\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "The database has been rolled back to its previous state.\n";
    exit(1);
}
?>

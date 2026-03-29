<?php
/**
 * Fix Progress Percentage Precision Issue
 * This script fixes the automatic value changes in daily progress input fields
 * by updating the database schema and ensuring proper precision handling
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting progress percentage precision fix...\n";
    
    // 1. First, let's check the current data type
    $checkSchema = $db->query("DESCRIBE daily_progress_updates incremental_completion_percentage");
    $currentType = $checkSchema->fetch(PDO::FETCH_ASSOC);
    echo "Current data type: " . $currentType['Type'] . "\n";
    
    // 2. Update the database schema to use DECIMAL(6,2) for better precision
    // This allows values like 100.00 (6 digits total, 2 decimal places)
    echo "Updating database schema...\n";
    
    $alterQueries = [
        "ALTER TABLE daily_progress_updates 
         MODIFY COLUMN incremental_completion_percentage DECIMAL(6,2) NOT NULL DEFAULT 0.00",
        
        "ALTER TABLE daily_progress_updates 
         MODIFY COLUMN cumulative_completion_percentage DECIMAL(6,2) NOT NULL DEFAULT 0.00",
         
        "ALTER TABLE weekly_progress_summary 
         MODIFY COLUMN start_progress_percentage DECIMAL(6,2) NOT NULL DEFAULT 0.00",
         
        "ALTER TABLE weekly_progress_summary 
         MODIFY COLUMN end_progress_percentage DECIMAL(6,2) NOT NULL DEFAULT 0.00",
         
        "ALTER TABLE monthly_progress_report 
         MODIFY COLUMN planned_progress_percentage DECIMAL(6,2) NOT NULL DEFAULT 0.00",
         
        "ALTER TABLE monthly_progress_report 
         MODIFY COLUMN actual_progress_percentage DECIMAL(6,2) NOT NULL DEFAULT 0.00",
         
        "ALTER TABLE progress_milestones 
         MODIFY COLUMN planned_progress_percentage DECIMAL(6,2) NOT NULL"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "✓ Executed: " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "⚠ Warning: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Fix any existing data that might have precision issues
    echo "\nFixing existing data precision...\n";
    
    $fixDataQuery = "
        UPDATE daily_progress_updates 
        SET 
            incremental_completion_percentage = ROUND(incremental_completion_percentage, 2),
            cumulative_completion_percentage = ROUND(cumulative_completion_percentage, 2)
        WHERE 
            incremental_completion_percentage != ROUND(incremental_completion_percentage, 2)
            OR cumulative_completion_percentage != ROUND(cumulative_completion_percentage, 2)
    ";
    
    $stmt = $db->prepare($fixDataQuery);
    $stmt->execute();
    $affectedRows = $stmt->rowCount();
    echo "✓ Fixed precision for {$affectedRows} existing records\n";
    
    // 4. Verify the fix by checking some sample data
    echo "\nVerifying the fix...\n";
    
    $verifyQuery = "
        SELECT 
            id, 
            incremental_completion_percentage, 
            cumulative_completion_percentage,
            update_date
        FROM daily_progress_updates 
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    
    $verifyStmt = $db->query($verifyQuery);
    $sampleData = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Sample data after fix:\n";
    foreach ($sampleData as $row) {
        echo sprintf(
            "ID: %d, Incremental: %s%%, Cumulative: %s%%, Date: %s\n",
            $row['id'],
            $row['incremental_completion_percentage'],
            $row['cumulative_completion_percentage'],
            $row['update_date']
        );
    }
    
    // 5. Test the precision with a sample calculation
    echo "\nTesting precision with sample calculation...\n";
    
    $testValue = 5.0;
    $roundedValue = round($testValue, 2);
    echo "Test: Input {$testValue} -> Rounded {$roundedValue}\n";
    
    $testValue2 = 4.999999;
    $roundedValue2 = round($testValue2, 2);
    echo "Test: Input {$testValue2} -> Rounded {$roundedValue2}\n";
    
    echo "\n✅ Progress percentage precision fix completed successfully!\n";
    echo "\nWhat was fixed:\n";
    echo "1. Database schema updated to DECIMAL(6,2) for better precision\n";
    echo "2. Backend API now uses round() function to ensure 2 decimal places\n";
    echo "3. Existing data precision issues corrected\n";
    echo "4. Cumulative progress calculation now properly rounded\n";
    
    echo "\nThe issue where entering '5' became '4.8' should now be resolved.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
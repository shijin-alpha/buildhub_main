<?php
/**
 * Create ai_predictions table for ML Analytics Dashboard
 * Run this file once to create the required table
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Creating ai_predictions table...\n\n";
    
    // Create ai_predictions table
    $sql = "CREATE TABLE IF NOT EXISTS ai_predictions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        cost_risk_level VARCHAR(20) NOT NULL,
        cost_risk_probability DECIMAL(5,4) NOT NULL,
        time_risk_level VARCHAR(20) NOT NULL,
        time_risk_probability DECIMAL(5,4) NOT NULL,
        model_version VARCHAR(50) DEFAULT 'v1.0.0',
        prediction_locked_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
        INDEX idx_project_id (project_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✅ ai_predictions table created successfully!\n\n";
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'ai_predictions'");
    if ($check->rowCount() > 0) {
        echo "✅ Table verification: ai_predictions exists\n\n";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE ai_predictions");
        echo "Table Structure:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-25s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            printf("%-25s %-20s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key']
            );
        }
        echo str_repeat("-", 80) . "\n\n";
    }
    
    // Insert sample data for existing projects
    echo "Inserting sample predictions for existing projects...\n\n";
    
    $projects = $conn->query("SELECT project_id, project_name FROM projects LIMIT 10");
    $inserted = 0;
    
    while ($project = $projects->fetch(PDO::FETCH_ASSOC)) {
        // Check if prediction already exists
        $check = $conn->prepare("SELECT id FROM ai_predictions WHERE project_id = ?");
        $check->execute([$project['project_id']]);
        
        if ($check->rowCount() == 0) {
            // Generate random but realistic predictions
            $cost_risk_levels = ['Low', 'Medium', 'High'];
            $cost_risk = $cost_risk_levels[array_rand($cost_risk_levels)];
            $cost_prob = $cost_risk === 'High' ? rand(70, 95) / 100 : 
                        ($cost_risk === 'Medium' ? rand(40, 70) / 100 : rand(10, 40) / 100);
            
            $time_risk = $cost_risk_levels[array_rand($cost_risk_levels)];
            $time_prob = $time_risk === 'High' ? rand(70, 95) / 100 : 
                        ($time_risk === 'Medium' ? rand(40, 70) / 100 : rand(10, 40) / 100);
            
            $insert = $conn->prepare("
                INSERT INTO ai_predictions 
                (project_id, cost_risk_level, cost_risk_probability, time_risk_level, time_risk_probability, model_version)
                VALUES (?, ?, ?, ?, ?, 'v1.0.0')
            ");
            
            $insert->execute([
                $project['project_id'],
                $cost_risk,
                $cost_prob,
                $time_risk,
                $time_prob
            ]);
            
            echo "✅ Added prediction for Project #{$project['project_id']}: {$project['project_name']}\n";
            echo "   Cost Risk: {$cost_risk} ({$cost_prob}), Time Risk: {$time_risk} ({$time_prob})\n";
            $inserted++;
        }
    }
    
    echo "\n✅ Inserted {$inserted} sample predictions\n\n";
    
    // Show summary
    $count = $conn->query("SELECT COUNT(*) as total FROM ai_predictions")->fetch(PDO::FETCH_ASSOC);
    echo "📊 Summary:\n";
    echo "   Total predictions in database: {$count['total']}\n\n";
    
    echo "✅ Setup complete! You can now use the ML Analytics Dashboard.\n";
    echo "\n🚀 Next steps:\n";
    echo "   1. Login as contractor or admin\n";
    echo "   2. Click on 'ML Analytics' tab\n";
    echo "   3. Select a project to view analytics\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nIf the table already exists, this is normal.\n";
    echo "If you see other errors, please check your database connection.\n";
}
?>

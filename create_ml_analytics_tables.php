<?php
/**
 * Create all required tables for ML Analytics Dashboard
 * Run this file once to set up the complete ML analytics system
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=================================================================\n";
    echo "  ML Analytics Dashboard - Database Setup\n";
    echo "=================================================================\n\n";
    
    // 1. Create ai_predictions table
    echo "1. Creating ai_predictions table...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS ai_predictions (
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
        FOREIGN KEY (project_id) REFERENCES construction_projects(id) ON DELETE CASCADE,
        INDEX idx_project_id (project_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql1);
    echo "   ✅ ai_predictions table created\n\n";
    
    // 2. Create ai_evaluation_metrics table
    echo "2. Creating ai_evaluation_metrics table...\n";
    $sql2 = "CREATE TABLE IF NOT EXISTS ai_evaluation_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_type VARCHAR(20) NOT NULL COMMENT 'cost or time',
        accuracy DECIMAL(5,4) NOT NULL,
        precision_val DECIMAL(5,4) NOT NULL,
        recall_val DECIMAL(5,4) NOT NULL,
        f1_score DECIMAL(5,4) NOT NULL,
        true_positives INT DEFAULT 0,
        false_positives INT DEFAULT 0,
        true_negatives INT DEFAULT 0,
        false_negatives INT DEFAULT 0,
        calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_metric_type (metric_type),
        INDEX idx_calculated_at (calculated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql2);
    echo "   ✅ ai_evaluation_metrics table created\n\n";
    
    // 3. Insert sample predictions for existing projects
    echo "3. Inserting sample predictions...\n";
    
    $projects = $conn->query("SELECT id as project_id, project_name FROM construction_projects ORDER BY id LIMIT 20");
    $inserted = 0;
    
    while ($project = $projects->fetch(PDO::FETCH_ASSOC)) {
        // Check if prediction already exists
        $check = $conn->prepare("SELECT id FROM ai_predictions WHERE project_id = ?");
        $check->execute([$project['project_id']]);
        
        if ($check->rowCount() == 0) {
            // Generate realistic predictions
            $cost_risk_levels = ['Low', 'Medium', 'High'];
            $weights = [0.3, 0.2, 0.5]; // 30% Low, 20% Medium, 50% High
            $rand = mt_rand() / mt_getrandmax();
            $cumulative = 0;
            $cost_risk = 'Medium';
            
            foreach ($cost_risk_levels as $i => $level) {
                $cumulative += $weights[$i];
                if ($rand <= $cumulative) {
                    $cost_risk = $level;
                    break;
                }
            }
            
            $cost_prob = $cost_risk === 'High' ? rand(75, 95) / 100 : 
                        ($cost_risk === 'Medium' ? rand(45, 70) / 100 : rand(15, 40) / 100);
            
            $time_risk = $cost_risk_levels[array_rand($cost_risk_levels)];
            $time_prob = $time_risk === 'High' ? rand(75, 95) / 100 : 
                        ($time_risk === 'Medium' ? rand(45, 70) / 100 : rand(15, 40) / 100);
            
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
            
            echo "   ✅ Project #{$project['project_id']}: Cost={$cost_risk}({$cost_prob}), Time={$time_risk}({$time_prob})\n";
            $inserted++;
        }
    }
    
    echo "\n   Total predictions inserted: {$inserted}\n\n";
    
    // 4. Insert sample evaluation metrics
    echo "4. Inserting sample evaluation metrics...\n";
    
    $check_metrics = $conn->query("SELECT COUNT(*) as count FROM ai_evaluation_metrics");
    if ($check_metrics->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
        // Cost model metrics (from ML_IMPLEMENTATION_SUMMARY.md)
        $conn->exec("
            INSERT INTO ai_evaluation_metrics 
            (metric_type, accuracy, precision_val, recall_val, f1_score, true_positives, false_positives, true_negatives, false_negatives)
            VALUES 
            ('cost', 0.9470, 0.9320, 0.9470, 0.9390, 127, 9, 63, 7)
        ");
        
        // Time model metrics
        $conn->exec("
            INSERT INTO ai_evaluation_metrics 
            (metric_type, accuracy, precision_val, recall_val, f1_score, true_positives, false_positives, true_negatives, false_negatives)
            VALUES 
            ('time', 0.9890, 0.9850, 0.9930, 0.9890, 141, 2, 17, 1)
        ");
        
        echo "   ✅ Cost model metrics: Accuracy=94.7%, F1=93.9%\n";
        echo "   ✅ Time model metrics: Accuracy=98.9%, F1=98.9%\n\n";
    } else {
        echo "   ℹ️  Metrics already exist, skipping...\n\n";
    }
    
    // 5. Verify tables
    echo "5. Verifying tables...\n";
    
    $tables = ['ai_predictions', 'ai_evaluation_metrics'];
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($check->rowCount() > 0) {
            $count = $conn->query("SELECT COUNT(*) as count FROM {$table}")->fetch(PDO::FETCH_ASSOC);
            echo "   ✅ {$table}: {$count['count']} rows\n";
        } else {
            echo "   ❌ {$table}: NOT FOUND\n";
        }
    }
    
    echo "\n=================================================================\n";
    echo "  ✅ ML Analytics Dashboard Setup Complete!\n";
    echo "=================================================================\n\n";
    
    echo "📊 Database Summary:\n";
    $pred_count = $conn->query("SELECT COUNT(*) as count FROM ai_predictions")->fetch(PDO::FETCH_ASSOC);
    $metrics_count = $conn->query("SELECT COUNT(*) as count FROM ai_evaluation_metrics")->fetch(PDO::FETCH_ASSOC);
    
    echo "   • AI Predictions: {$pred_count['count']} projects\n";
    echo "   • Evaluation Metrics: {$metrics_count['count']} models\n\n";
    
    echo "🚀 Next Steps:\n";
    echo "   1. Login as contractor or admin\n";
    echo "   2. Click on '🤖 ML Analytics' in the sidebar\n";
    echo "   3. Select a project from the dropdown\n";
    echo "   4. View interactive charts and AI insights!\n\n";
    
    echo "📝 Test the API:\n";
    echo "   Open: test_ml_analytics_api.html\n";
    echo "   Or visit: /buildhub/backend/api/ml/get_project_analytics.php?project_id=1\n\n";
    
    echo "🎨 View Demo:\n";
    echo "   Open: ml_analytics_dashboard_demo.html\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️  Tables already exist. This is normal if you've run this script before.\n";
        echo "   The ML Analytics Dashboard should work fine.\n\n";
    } else {
        echo "⚠️  Please check:\n";
        echo "   1. Database connection in backend/config/database.php\n";
        echo "   2. MySQL/MariaDB is running\n";
        echo "   3. Database user has CREATE TABLE permissions\n\n";
    }
}
?>

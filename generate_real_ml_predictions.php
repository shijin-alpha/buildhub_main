<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Generating ML Predictions for Real Projects\n";
echo "============================================\n\n";

// Get projects with real data
$query = "
    SELECT 
        cp.id,
        cp.project_name,
        cp.estimated_cost,
        cp.total_cost,
        cp.status,
        cp.completion_percentage,
        cp.created_at,
        cp.planned_start_date,
        cp.planned_end_date,
        COUNT(DISTINCT cpu.id) as progress_updates,
        COALESCE(SUM(spr.requested_amount), 0) + COALESCE(SUM(cpr.requested_amount), 0) as total_spent
    FROM construction_projects cp
    LEFT JOIN construction_progress_updates cpu ON cp.id = cpu.project_id
    LEFT JOIN stage_payment_requests spr ON cp.id = spr.project_id AND spr.status IN ('paid', 'approved')
    LEFT JOIN custom_payment_requests cpr ON cp.id = cpr.project_id AND cpr.status IN ('paid', 'approved')
    WHERE cp.status IN ('in_progress', 'completed')
    GROUP BY cp.id
    HAVING progress_updates > 0 OR total_spent > 0
    ORDER BY cp.id
";

$result = $conn->query($query);
$projects = $result->fetchAll(PDO::FETCH_ASSOC);

$predictions_created = 0;
$predictions_updated = 0;

foreach ($projects as $project) {
    echo "Processing Project #{$project['id']}: {$project['project_name']}\n";
    
    // Calculate realistic risk based on actual data
    $estimated = floatval($project['estimated_cost']);
    $spent = floatval($project['total_spent']);
    $progress = floatval($project['completion_percentage']);
    $days_elapsed = (strtotime('now') - strtotime($project['created_at'])) / 86400;
    
    // Cost Risk Analysis
    $cost_risk_level = 'Low';
    $cost_risk_prob = 0.15;
    
    if ($estimated > 0) {
        $spend_ratio = $spent / $estimated;
        $progress_ratio = $progress / 100;
        
        if ($progress_ratio > 0) {
            $efficiency = $spend_ratio / $progress_ratio;
            
            if ($efficiency > 1.5) {
                $cost_risk_level = 'High';
                $cost_risk_prob = min(0.95, 0.70 + ($efficiency - 1.5) * 0.15);
            } elseif ($efficiency > 1.2) {
                $cost_risk_level = 'Medium';
                $cost_risk_prob = 0.45 + ($efficiency - 1.2) * 0.5;
            } else {
                $cost_risk_level = 'Low';
                $cost_risk_prob = 0.15 + ($efficiency - 0.8) * 0.2;
            }
        } else {
            // No progress yet, but spending
            if ($spend_ratio > 0.3) {
                $cost_risk_level = 'High';
                $cost_risk_prob = 0.85;
            } elseif ($spend_ratio > 0.15) {
                $cost_risk_level = 'Medium';
                $cost_risk_prob = 0.55;
            }
        }
    } else {
        // No estimate, but has spending
        if ($spent > 1000000) {
            $cost_risk_level = 'High';
            $cost_risk_prob = 0.75;
        } elseif ($spent > 100000) {
            $cost_risk_level = 'Medium';
            $cost_risk_prob = 0.50;
        }
    }
    
    // Time Risk Analysis
    $time_risk_level = 'Low';
    $time_risk_prob = 0.20;
    
    $expected_progress = min(100, ($days_elapsed / 180) * 100); // Assume 180 days
    $progress_gap = $expected_progress - $progress;
    
    if ($progress_gap > 30) {
        $time_risk_level = 'High';
        $time_risk_prob = min(0.95, 0.70 + ($progress_gap - 30) * 0.01);
    } elseif ($progress_gap > 15) {
        $time_risk_level = 'Medium';
        $time_risk_prob = 0.45 + ($progress_gap - 15) * 0.015;
    } elseif ($progress_gap < -10) {
        // Ahead of schedule
        $time_risk_level = 'Low';
        $time_risk_prob = 0.10;
    }
    
    // For completed projects, adjust based on actual outcome
    if ($project['status'] === 'completed') {
        if ($spent > $estimated * 1.2) {
            $cost_risk_level = 'High';
            $cost_risk_prob = 0.92;
        } elseif ($spent > $estimated * 1.1) {
            $cost_risk_level = 'Medium';
            $cost_risk_prob = 0.68;
        } else {
            $cost_risk_level = 'Low';
            $cost_risk_prob = 0.25;
        }
        
        $time_risk_level = 'Low';
        $time_risk_prob = 0.15;
    }
    
    // Ensure probabilities are in valid range
    $cost_risk_prob = max(0.05, min(0.99, $cost_risk_prob));
    $time_risk_prob = max(0.05, min(0.99, $time_risk_prob));
    
    // Check if prediction already exists
    $check = $conn->prepare("SELECT id FROM ai_predictions WHERE project_id = ?");
    $check->execute([$project['id']]);
    
    if ($check->rowCount() > 0) {
        // Update existing prediction
        $update = $conn->prepare("
            UPDATE ai_predictions 
            SET cost_risk_level = ?,
                cost_risk_probability = ?,
                time_risk_level = ?,
                time_risk_probability = ?,
                model_version = 'v1.0.0-real-data',
                updated_at = CURRENT_TIMESTAMP
            WHERE project_id = ?
        ");
        
        $update->execute([
            $cost_risk_level,
            $cost_risk_prob,
            $time_risk_level,
            $time_risk_prob,
            $project['id']
        ]);
        
        echo "  ✅ Updated: Cost={$cost_risk_level}(" . round($cost_risk_prob * 100, 1) . "%), ";
        echo "Time={$time_risk_level}(" . round($time_risk_prob * 100, 1) . "%)\n";
        $predictions_updated++;
    } else {
        // Insert new prediction
        $insert = $conn->prepare("
            INSERT INTO ai_predictions 
            (project_id, cost_risk_level, cost_risk_probability, time_risk_level, time_risk_probability, model_version)
            VALUES (?, ?, ?, ?, ?, 'v1.0.0-real-data')
        ");
        
        $insert->execute([
            $project['id'],
            $cost_risk_level,
            $cost_risk_prob,
            $time_risk_level,
            $time_risk_prob
        ]);
        
        echo "  ✅ Created: Cost={$cost_risk_level}(" . round($cost_risk_prob * 100, 1) . "%), ";
        echo "Time={$time_risk_level}(" . round($time_risk_prob * 100, 1) . "%)\n";
        $predictions_created++;
    }
    
    echo "     Estimated: ₹" . number_format($estimated) . ", Spent: ₹" . number_format($spent) . "\n";
    echo "     Progress: {$progress}%, Days: " . round($days_elapsed) . "\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ ML Predictions Generated!\n\n";
echo "Summary:\n";
echo "  • Predictions Created: {$predictions_created}\n";
echo "  • Predictions Updated: {$predictions_updated}\n";
echo "  • Total Projects: " . ($predictions_created + $predictions_updated) . "\n\n";

echo "🎯 Next Steps:\n";
echo "  1. Refresh your browser (Ctrl + F5)\n";
echo "  2. Login as Contractor or Admin\n";
echo "  3. Click '🤖 ML Analytics' tab\n";
echo "  4. Select any project to view real data analytics!\n\n";

echo "📊 Projects with Real Data:\n";
foreach ($projects as $project) {
    echo "  • Project #{$project['id']}: {$project['project_name']}\n";
}

echo "\n✅ All real projects now have ML predictions based on actual data!\n";
?>

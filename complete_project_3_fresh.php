<?php
/**
 * Complete Project 3 - SHIJIN THOMAS (Fresh Start)
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Completing Project 3 (SHIJIN THOMAS) ===\n\n";
    
    $project_id = 3;
    $contractor_id = 29;
    
    // Get project details
    $stmt = $db->prepare("SELECT * FROM construction_projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        die("ERROR: Project not found!\n");
    }
    
    echo "Project: {$project['project_name']}\n";
    echo "Current Status: {$project['status']}\n";
    echo "Current Stage: {$project['current_stage']}\n";
    echo "Current Completion: {$project['completion_percentage']}%\n\n";
    
    // Clear existing progress reports for fresh start
    echo "Clearing existing progress reports...\n";
    $stmt = $db->prepare("DELETE FROM daily_progress_updates WHERE project_id = ?");
    $stmt->execute([$project_id]);
    echo "✓ Cleared old progress reports\n\n";
    
    // Clear existing payment requests
    echo "Clearing existing payment requests...\n";
    $stmt = $db->prepare("DELETE FROM stage_payment_requests WHERE project_id = ?");
    $stmt->execute([$project_id]);
    echo "✓ Cleared old payment requests\n\n";
    
    // Define construction stages
    $stages = [
        ['stage' => 'Foundation', 'pct' => 15, 'desc' => 'Foundation excavation and concrete pouring completed', 'materials' => 'Cement: 50 bags, Steel: 500kg, Sand: 2 tons', 'workers' => 8, 'amount' => 225697],
        ['stage' => 'Plinth Beam', 'pct' => 25, 'desc' => 'Plinth beam construction with reinforcement', 'materials' => 'Cement: 40 bags, Steel: 400kg, Bricks: 2000 units', 'workers' => 6, 'amount' => 150465],
        ['stage' => 'Superstructure', 'pct' => 45, 'desc' => 'Column and beam work up to roof level', 'materials' => 'Cement: 80 bags, Steel: 800kg, Formwork', 'workers' => 10, 'amount' => 300929],
        ['stage' => 'Roofing', 'pct' => 60, 'desc' => 'Roof slab casting and waterproofing', 'materials' => 'Cement: 60 bags, Steel: 600kg, Waterproofing', 'workers' => 8, 'amount' => 225697],
        ['stage' => 'Masonry', 'pct' => 75, 'desc' => 'Wall construction and plastering', 'materials' => 'Bricks: 5000 units, Cement: 70 bags, Sand: 3 tons', 'workers' => 7, 'amount' => 225697],
        ['stage' => 'Finishing', 'pct' => 90, 'desc' => 'Flooring, painting, and electrical work', 'materials' => 'Tiles: 200 sqm, Paint: 100L, Electrical fittings', 'workers' => 6, 'amount' => 225697],
        ['stage' => 'Final Inspection', 'pct' => 100, 'desc' => 'Final inspection and handover', 'materials' => 'Touch-up materials, cleaning supplies', 'workers' => 4, 'amount' => 150463]
    ];
    
    echo "=== Creating Complete Construction Timeline ===\n\n";
    
    $base_date = new DateTime('2026-02-01');
    $day_counter = 0;
    $previous_pct = 0;
    
    foreach ($stages as $stage) {
        $days_in_stage = rand(4, 8);
        
        for ($day = 0; $day < $days_in_stage; $day++) {
            $current_date = clone $base_date;
            $current_date->modify("+{$day_counter} days");
            
            // Calculate progressive percentage
            $progress_in_stage = ($day + 1) / $days_in_stage;
            $cumulative_pct = $previous_pct + (($stage['pct'] - $previous_pct) * $progress_in_stage);
            $incremental_pct = $cumulative_pct - $previous_pct;
            
            // Insert daily update
            $stmt = $db->prepare("INSERT INTO daily_progress_updates 
                (project_id, contractor_id, homeowner_id, update_date, construction_stage,
                 work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
                 working_hours, weather_condition, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 8.0, 'Sunny', NOW())");
            
            $stmt->execute([
                $project_id,
                $contractor_id,
                $project['homeowner_id'],
                $current_date->format('Y-m-d'),
                $stage['stage'],
                $stage['desc'],
                round($incremental_pct, 2),
                round($cumulative_pct, 2)
            ]);
            
            echo "✓ Day " . ($day_counter + 1) . ": {$stage['stage']} - " . round($cumulative_pct, 1) . "% ({$current_date->format('M d')})\n";
            
            $previous_pct = $cumulative_pct;
            $day_counter++;
        }
        
        // Create payment request for completed stage
        $payment_date = clone $base_date;
        $payment_date->modify("+{$day_counter} days");
        
        $stmt = $db->prepare("INSERT INTO stage_payment_requests 
            (project_id, contractor_id, homeowner_id, stage_name, requested_amount,
             completion_percentage, work_description, materials_used, labor_count,
             status, request_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())");
        
        $stmt->execute([
            $project_id,
            $contractor_id,
            $project['homeowner_id'],
            $stage['stage'],
            $stage['amount'],
            $stage['pct'],
            $stage['desc'],
            $stage['materials'],
            $stage['workers']
        ]);
        
        echo "  💰 Payment Request: ₹" . number_format($stage['amount']) . " (Approved)\n\n";
    }
    
    // Update project to completed
    $completion_date = clone $base_date;
    $completion_date->modify("+{$day_counter} days");
    
    $stmt = $db->prepare("UPDATE construction_projects SET 
        status = 'completed',
        current_stage = 'Final Inspection',
        completion_percentage = 100,
        actual_completion_date = ?,
        actual_end_date = ?,
        updated_at = NOW()
        WHERE id = ?");
    
    $stmt->execute([
        $completion_date->format('Y-m-d'),
        $completion_date->format('Y-m-d'),
        $project_id
    ]);
    
    echo "=== PROJECT COMPLETION SUMMARY ===\n\n";
    
    // Get counts
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM daily_progress_updates WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $progress_count = $stmt->fetch()['total'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(requested_amount) as total_paid 
                         FROM stage_payment_requests WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $payment_data = $stmt->fetch();
    
    echo "✓ Project: SHIJIN THOMAS MCA2024-2026 Construction\n";
    echo "✓ Status: COMPLETED\n";
    echo "✓ Completion: 100%\n";
    echo "✓ Total Budget: ₹" . number_format(1504645) . "\n";
    echo "✓ Total Paid: ₹" . number_format($payment_data['total_paid']) . "\n";
    echo "✓ Daily Progress Reports: {$progress_count}\n";
    echo "✓ Payment Requests: {$payment_data['total']}\n";
    echo "✓ Stages Completed: 7/7\n";
    echo "✓ Completion Date: {$completion_date->format('M d, Y')}\n";
    echo "\n🎉 Construction project completed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

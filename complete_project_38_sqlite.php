<?php
/**
 * Complete Project 38 - SHIJIN THOMAS MCA2024-2026 Construction (SQLite)
 */

try {
    $db = new PDO('sqlite:buildhub.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Starting Project 38 Completion Process ===\n\n";
    
    $project_id = 38;
    $contractor_id = 32;
    
    // Verify project exists
    $stmt = $db->prepare("SELECT * FROM real_projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        die("ERROR: Project 38 not found!\n");
    }
    
    echo "Project Found:\n";
    echo "- Name: {$project['project_name']}\n";
    echo "- Status: {$project['status']}\n";
    echo "- Current Stage: {$project['current_stage']}\n";
    echo "- Budget: ₹" . number_format($project['estimate_cost']) . "\n\n";
    
    // Define all construction stages
    $stages = [
        [
            'stage' => 'Foundation',
            'percentage' => 15,
            'description' => 'Foundation work completed with proper excavation and concrete pouring',
            'materials' => 'Cement: 50 bags, Steel: 500kg, Sand: 2 tons, Aggregate: 3 tons',
            'workers' => 8,
            'amount' => 225697
        ],
        [
            'stage' => 'Plinth Beam',
            'percentage' => 25,
            'description' => 'Plinth beam construction completed with reinforcement',
            'materials' => 'Cement: 40 bags, Steel: 400kg, Bricks: 2000 units',
            'workers' => 6,
            'amount' => 150465
        ],
        [
            'stage' => 'Superstructure',
            'percentage' => 45,
            'description' => 'Column and beam work completed up to roof level',
            'materials' => 'Cement: 80 bags, Steel: 800kg, Formwork materials',
            'workers' => 10,
            'amount' => 300929
        ],
        [
            'stage' => 'Roofing',
            'percentage' => 60,
            'description' => 'Roof slab casting and waterproofing completed',
            'materials' => 'Cement: 60 bags, Steel: 600kg, Waterproofing materials',
            'workers' => 8,
            'amount' => 225697
        ],
        [
            'stage' => 'Masonry',
            'percentage' => 75,
            'description' => 'Wall construction and plastering completed',
            'materials' => 'Bricks: 5000 units, Cement: 70 bags, Sand: 3 tons',
            'workers' => 7,
            'amount' => 225697
        ],
        [
            'stage' => 'Finishing',
            'percentage' => 90,
            'description' => 'Flooring, painting, and electrical work completed',
            'materials' => 'Tiles: 200 sqm, Paint: 100 liters, Electrical fittings',
            'workers' => 6,
            'amount' => 225697
        ],
        [
            'stage' => 'Final Inspection',
            'percentage' => 100,
            'description' => 'Final inspection completed, all work verified and approved',
            'materials' => 'Touch-up materials, cleaning supplies',
            'workers' => 4,
            'amount' => 150463
        ]
    ];
    
    echo "=== Creating Daily Progress Reports ===\n\n";
    
    $base_date = new DateTime('2026-01-23');
    $report_number = 1;
    
    foreach ($stages as $stage_index => $stage_data) {
        $days_for_stage = rand(3, 7);
        
        for ($day = 0; $day < $days_for_stage; $day++) {
            $current_date = clone $base_date;
            $current_date->modify("+{$report_number} days");
            $date_str = $current_date->format('Y-m-d');
            $time_str = $current_date->format('Y-m-d H:i:s');
            
            // Calculate progressive percentage
            $stage_progress = ($day + 1) / $days_for_stage;
            $previous_stage_percentage = $stage_index > 0 ? $stages[$stage_index - 1]['percentage'] : 0;
            $current_percentage = $previous_stage_percentage + (($stage_data['percentage'] - $previous_stage_percentage) * $stage_progress);
            
            // Insert daily progress report
            $stmt = $db->prepare("INSERT INTO daily_progress_reports 
                (project_id, contractor_id, report_date, stage, progress_percentage, 
                 work_description, materials_used, workers_count, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $project_id,
                $contractor_id,
                $date_str,
                $stage_data['stage'],
                round($current_percentage, 2),
                $stage_data['description'],
                $stage_data['materials'],
                $stage_data['workers'],
                $time_str
            ]);
            
            echo "✓ Day {$report_number}: {$stage_data['stage']} - " . round($current_percentage, 2) . "% ({$date_str})\n";
            
            $report_number++;
        }
        
        // Create stage payment request
        echo "  → Creating payment request for {$stage_data['stage']}: ₹" . number_format($stage_data['amount']) . "\n";
        
        $payment_date = clone $base_date;
        $payment_date->modify("+{$report_number} days");
        $payment_date_str = $payment_date->format('Y-m-d H:i:s');
        
        $stmt = $db->prepare("INSERT INTO stage_payment_requests 
            (project_id, contractor_id, stage, amount_requested, request_date, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'approved', ?)");
        
        $stmt->execute([
            $project_id,
            $contractor_id,
            $stage_data['stage'],
            $stage_data['amount'],
            $payment_date_str,
            $payment_date_str
        ]);
        
        echo "\n";
    }
    
    // Update project status
    echo "=== Updating Project Status ===\n";
    
    $completion_date = clone $base_date;
    $completion_date->modify("+{$report_number} days");
    $completion_date_str = $completion_date->format('Y-m-d');
    
    $stmt = $db->prepare("UPDATE real_projects SET 
        status = 'completed',
        current_stage = 'Final Inspection',
        completion_percentage = 100,
        actual_completion_date = ?,
        updated_at = datetime('now')
        WHERE id = ?");
    
    $stmt->execute([$completion_date_str, $project_id]);
    
    echo "✓ Project status updated to 'completed'\n";
    echo "✓ Completion date set to: {$completion_date_str}\n\n";
    
    // Generate summary
    echo "=== PROJECT COMPLETION SUMMARY ===\n\n";
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM daily_progress_reports WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $report_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(amount_requested) as total_amount 
                         FROM stage_payment_requests WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $payment_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Project: SHIJIN THOMAS MCA2024-2026 Construction\n";
    echo "Project ID: 38\n";
    echo "Status: COMPLETED ✓\n";
    echo "Completion: 100%\n";
    echo "Total Budget: ₹" . number_format(1504645) . "\n";
    echo "Total Paid: ₹" . number_format($payment_data['total_amount']) . "\n";
    echo "Daily Reports: {$report_count}\n";
    echo "Payment Requests: {$payment_data['total']}\n";
    echo "Stages Completed: 7/7\n";
    echo "Completion Date: {$completion_date_str}\n";
    echo "\n✓ All construction stages completed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

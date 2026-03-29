<?php
/**
 * Complete Project 38 - SHIJIN THOMAS MCA2024-2026 Construction
 * This script will complete all construction stages for the project
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Starting Project 3 (SHIJIN THOMAS) Completion Process ===\n\n";
    
    $project_id = 3;
    $contractor_id = 29; // Contractor assigned to this project
    
    // Verify project exists
    $query = "SELECT * FROM construction_projects WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    $project_table = 'construction_projects';
    
    if (!$project) {
        die("ERROR: Project 38 not found!\n");
    }
    
    echo "Project Found:\n";
    echo "- Name: {$project['project_name']}\n";
    echo "- Status: {$project['status']}\n";
    echo "- Current Stage: {$project['current_stage']}\n";
    echo "- Budget: ₹" . number_format($project['total_cost']) . "\n\n";
    
    // Define all construction stages with realistic data
    $stages = [
        [
            'stage' => 'Foundation',
            'percentage' => 15,
            'description' => 'Foundation work completed with proper excavation and concrete pouring',
            'materials' => 'Cement: 50 bags, Steel: 500kg, Sand: 2 tons, Aggregate: 3 tons',
            'workers' => 8,
            'amount' => 225697 // 15% of 1,504,645
        ],
        [
            'stage' => 'Plinth Beam',
            'percentage' => 25,
            'description' => 'Plinth beam construction completed with reinforcement',
            'materials' => 'Cement: 40 bags, Steel: 400kg, Bricks: 2000 units',
            'workers' => 6,
            'amount' => 150465 // 10% of budget
        ],
        [
            'stage' => 'Superstructure',
            'percentage' => 45,
            'description' => 'Column and beam work completed up to roof level',
            'materials' => 'Cement: 80 bags, Steel: 800kg, Formwork materials',
            'workers' => 10,
            'amount' => 300929 // 20% of budget
        ],
        [
            'stage' => 'Roofing',
            'percentage' => 60,
            'description' => 'Roof slab casting and waterproofing completed',
            'materials' => 'Cement: 60 bags, Steel: 600kg, Waterproofing materials',
            'workers' => 8,
            'amount' => 225697 // 15% of budget
        ],
        [
            'stage' => 'Masonry',
            'percentage' => 75,
            'description' => 'Wall construction and plastering completed',
            'materials' => 'Bricks: 5000 units, Cement: 70 bags, Sand: 3 tons',
            'workers' => 7,
            'amount' => 225697 // 15% of budget
        ],
        [
            'stage' => 'Finishing',
            'percentage' => 90,
            'description' => 'Flooring, painting, and electrical work completed',
            'materials' => 'Tiles: 200 sqm, Paint: 100 liters, Electrical fittings',
            'workers' => 6,
            'amount' => 225697 // 15% of budget
        ],
        [
            'stage' => 'Final Inspection',
            'percentage' => 100,
            'description' => 'Final inspection completed, all work verified and approved',
            'materials' => 'Touch-up materials, cleaning supplies',
            'workers' => 4,
            'amount' => 150463 // Remaining 10% of budget
        ]
    ];
    
    echo "=== Creating Daily Progress Reports ===\n\n";
    
    $base_date = new DateTime('2026-01-23'); // Start day after project creation
    $report_number = 1;
    
    foreach ($stages as $stage_data) {
        // Add some days between stages (3-7 days per stage)
        $days_for_stage = rand(3, 7);
        
        for ($day = 0; $day < $days_for_stage; $day++) {
            $current_date = clone $base_date;
            $current_date->modify("+{$report_number} days");
            $date_str = $current_date->format('Y-m-d');
            $time_str = $current_date->format('Y-m-d H:i:s');
            
            // Calculate progressive percentage within the stage
            $stage_progress = ($day + 1) / $days_for_stage;
            $previous_stage_percentage = $report_number > 1 ? $stages[array_search($stage_data, $stages) - 1]['percentage'] ?? 0 : 0;
            $current_percentage = $previous_stage_percentage + (($stage_data['percentage'] - $previous_stage_percentage) * $stage_progress);
            
            // Insert daily progress report
            $insert_query = "INSERT INTO daily_progress_updates 
                (project_id, contractor_id, homeowner_id, update_date, construction_stage, 
                 work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
                 working_hours, weather_condition, created_at) 
                VALUES 
                (:project_id, :contractor_id, :homeowner_id, :update_date, :stage, 
                 :work_description, :incremental_pct, :cumulative_pct,
                 :working_hours, :weather, :created_at)";
            
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->bindParam(':contractor_id', $contractor_id);
            $homeowner_id = $project['homeowner_id'];
            $stmt->bindParam(':homeowner_id', $homeowner_id);
            $stmt->bindParam(':update_date', $date_str);
            $stmt->bindParam(':stage', $stage_data['stage']);
            $stmt->bindParam(':work_description', $stage_data['description']);
            $incremental_pct = round($current_percentage - ($report_number > 1 ? $previous_percentage : 0), 2);
            $stmt->bindParam(':incremental_pct', $incremental_pct);
            $stmt->bindParam(':cumulative_pct', $current_percentage);
            $working_hours = 8.0;
            $stmt->bindParam(':working_hours', $working_hours);
            $weather = 'Sunny';
            $stmt->bindParam(':weather', $weather);
            $stmt->bindParam(':created_at', $time_str);
            $stmt->execute();
            
            $previous_percentage = $current_percentage;
            
            echo "✓ Day {$report_number}: {$stage_data['stage']} - " . round($current_percentage, 2) . "% ({$date_str})\n";
            
            $report_number++;
        }
        
        // Create stage payment request at the end of each stage
        echo "  → Creating payment request for {$stage_data['stage']}: ₹" . number_format($stage_data['amount']) . "\n";
        
        $payment_date = clone $base_date;
        $payment_date->modify("+{$report_number} days");
        $payment_date_str = $payment_date->format('Y-m-d H:i:s');
        
        $payment_query = "INSERT INTO stage_payment_requests 
            (project_id, contractor_id, homeowner_id, stage_name, requested_amount, 
             completion_percentage, work_description, materials_used, labor_count,
             status, request_date, created_at) 
            VALUES 
            (:project_id, :contractor_id, :homeowner_id, :stage_name, :amount, 
             :completion_pct, :work_desc, :materials, :labor_count,
             'approved', :request_date, :created_at)";
        
        $stmt = $db->prepare($payment_query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':contractor_id', $contractor_id);
        $homeowner_id = $project['homeowner_id'];
        $stmt->bindParam(':homeowner_id', $homeowner_id);
        $stmt->bindParam(':stage_name', $stage_data['stage']);
        $stmt->bindParam(':amount', $stage_data['amount']);
        $stmt->bindParam(':completion_pct', $stage_data['percentage']);
        $stmt->bindParam(':work_desc', $stage_data['description']);
        $stmt->bindParam(':materials', $stage_data['materials']);
        $stmt->bindParam(':labor_count', $stage_data['workers']);
        $stmt->bindParam(':request_date', $payment_date_str);
        $stmt->bindParam(':created_at', $payment_date_str);
        $stmt->execute();
        
        echo "\n";
    }
    
    // Update project status to completed
    echo "=== Updating Project Status ===\n";
    
    $completion_date = clone $base_date;
    $completion_date->modify("+{$report_number} days");
    $completion_date_str = $completion_date->format('Y-m-d');
    
    $update_query = "UPDATE {$project_table} SET 
        status = 'completed',
        current_stage = 'Final Inspection',
        completion_percentage = 100,
        actual_completion_date = :completion_date,
        updated_at = NOW()
        WHERE id = :project_id";
    
    $stmt = $db->prepare($update_query);
    $stmt->bindParam(':completion_date', $completion_date_str);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    
    echo "✓ Project status updated to 'completed'\n";
    echo "✓ Completion date set to: {$completion_date_str}\n\n";
    
    // Generate summary
    echo "=== PROJECT COMPLETION SUMMARY ===\n\n";
    
    // Count reports
    $count_query = "SELECT COUNT(*) as total FROM daily_progress_updates WHERE project_id = :project_id";
    $stmt = $db->prepare($count_query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $report_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count payments
    $payment_count_query = "SELECT COUNT(*) as total, SUM(requested_amount) as total_amount 
                           FROM stage_payment_requests WHERE project_id = :project_id";
    $stmt = $db->prepare($payment_count_query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $payment_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Project: SHIJIN THOMAS MCA2024-2026 Construction\n";
    echo "Project ID: 3\n";
    echo "Status: COMPLETED ✓\n";
    echo "Completion: 100%\n";
    echo "Total Budget: ₹" . number_format(1504645) . "\n";
    echo "Total Paid: ₹" . number_format($payment_data['total_amount']) . "\n";
    echo "Daily Reports: {$report_count}\n";
    echo "Payment Requests: {$payment_data['total']}\n";
    echo "Stages Completed: 7/7\n";
    echo "Completion Date: {$completion_date_str}\n";
    echo "\nAll construction stages completed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

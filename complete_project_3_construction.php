<?php
/**
 * Complete Project 3 Construction - Full Lifecycle Simulation
 * This script fills all construction data from start to finish
 */

require_once 'backend/config/database.php';

$db = (new Database())->getConnection();
$project_id = 3;

// Start transaction
$db->beginTransaction();

try {
    // Get project details
    $stmt = $db->prepare("SELECT * FROM construction_projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception("Project 3 not found");
    }
    
    $contractor_id = $project['contractor_id'];
    $homeowner_id = $project['homeowner_id'];
    
    echo "<!DOCTYPE html><html><head><title>Completing Project 3</title>";
    echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}.container{max-width:1200px;margin:0 auto;background:white;padding:30px;border-radius:10px;}.success{color:#10b981;background:#d1fae5;padding:10px;border-radius:5px;margin:10px 0;}.section{margin:20px 0;padding:15px;background:#f8f9fa;border-radius:5px;border-left:4px solid #667eea;}h2{color:#667eea;}</style>";
    echo "</head><body><div class='container'>";
    echo "<h1>🏗️ Completing Project 3 Construction</h1>";
    echo "<p>Simulating complete construction lifecycle with all data...</p>";
    
    // Step 1: Set Project Dates
    echo "<div class='section'><h2>Step 1: Setting Project Schedule</h2>";
    $sql = "UPDATE construction_projects SET 
            planned_start_date = '2026-01-15',
            planned_end_date = '2026-06-15',
            actual_start_date = '2026-01-20',
            actual_end_date = '2026-06-25',
            schedule_locked = 1,
            estimated_cost = ?,
            status = 'completed',
            completion_percentage = 100.00
            WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project['total_cost'], $project_id]);
    echo "<div class='success'>✅ Schedule set: Jan 15 - Jun 15 (planned), Jan 20 - Jun 25 (actual)</div>";
    echo "<div class='success'>✅ Project marked as 100% complete</div>";
    echo "</div>";
    
    // Step 2: Clear existing test data
    echo "<div class='section'><h2>Step 2: Clearing Old Test Data</h2>";
    $db->exec("DELETE FROM daily_progress_reports WHERE project_id = $project_id");
    $db->exec("DELETE FROM stage_payment_requests WHERE project_id = $project_id");
    $db->exec("DELETE FROM custom_payment_requests WHERE project_id = $project_id");
    echo "<div class='success'>✅ Cleared existing data</div>";
    echo "</div>";
    
    // Step 3: Create Daily Progress Reports (90 days of construction)
    echo "<div class='section'><h2>Step 3: Creating Daily Progress Reports</h2>";
    
    $stages = [
        ['Foundation', '2026-01-20', 30, 0, 20],      // Days 1-30, progress 0-20%
        ['Structure', '2026-02-19', 40, 20, 50],      // Days 31-70, progress 20-50%
        ['Roofing', '2026-03-31', 20, 50, 65],        // Days 71-90, progress 50-65%
        ['Electrical', '2026-04-20', 15, 65, 75],     // Days 91-105, progress 65-75%
        ['Plumbing', '2026-05-05', 15, 75, 85],       // Days 106-120, progress 75-85%
        ['Finishing', '2026-05-20', 20, 85, 95],      // Days 121-140, progress 85-95%
        ['Final Touches', '2026-06-09', 16, 95, 100]  // Days 141-157, progress 95-100%
    ];
    
    $report_count = 0;
    $current_progress = 0;
    
    foreach ($stages as $stage) {
        list($stage_name, $start_date, $days, $start_progress, $end_progress) = $stage;
        $progress_per_day = ($end_progress - $start_progress) / $days;
        
        for ($day = 0; $day < $days; $day++) {
            $current_progress = $start_progress + ($progress_per_day * $day);
            $report_date = date('Y-m-d', strtotime($start_date . " +$day days"));
            
            $workers = rand(8, 15);
            $hours = rand(7, 9);
            $weather = ['sunny', 'cloudy', 'partly_cloudy'][rand(0, 2)];
            
            $descriptions = [
                'Foundation' => "Excavation and foundation work completed. Concrete pouring in progress.",
                'Structure' => "Column and beam construction ongoing. Steel reinforcement installed.",
                'Roofing' => "Roof structure assembly. Waterproofing layers being applied.",
                'Electrical' => "Electrical wiring and conduit installation. Panel boxes mounted.",
                'Plumbing' => "Water supply and drainage pipes installed. Fixtures being fitted.",
                'Finishing' => "Plastering and painting work in progress. Flooring installation ongoing.",
                'Final Touches' => "Final inspections and touch-ups. Cleaning and handover preparation."
            ];
            
            $sql = "INSERT INTO daily_progress_reports (
                project_id, contractor_id, construction_stage, progress_percentage,
                work_description, worker_count, hours_worked, weather_condition,
                report_date, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $project_id, $contractor_id, $stage_name, round($current_progress, 2),
                $descriptions[$stage_name], $workers, $hours, $weather, $report_date
            ]);
            $report_count++;
        }
    }
    
    echo "<div class='success'>✅ Created $report_count daily progress reports across 7 construction stages</div>";
    echo "<ul>";
    foreach ($stages as $stage) {
        echo "<li><strong>{$stage[0]}</strong>: {$stage[1]} days, Progress {$stage[3]}% → {$stage[4]}%</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Step 4: Create Stage Payment Requests
    echo "<div class='section'><h2>Step 4: Creating Stage Payment Requests</h2>";
    
    $stage_payments = [
        ['Foundation Work', 400000, '2026-02-15', 'paid', 20],
        ['Structure Construction', 500000, '2026-03-25', 'paid', 50],
        ['Roofing & Waterproofing', 250000, '2026-04-15', 'paid', 65],
        ['Electrical Installation', 200000, '2026-05-01', 'paid', 75],
        ['Plumbing Work', 180000, '2026-05-15', 'paid', 85],
        ['Finishing Work', 300000, '2026-06-05', 'paid', 95],
        ['Final Completion', 170000, '2026-06-20', 'paid', 100]
    ];
    
    $total_stage = 0;
    foreach ($stage_payments as $payment) {
        list($stage, $amount, $date, $status, $completion) = $payment;
        
        $sql = "INSERT INTO stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name, requested_amount,
            completion_percentage, work_description, labor_count, status, request_date,
            response_date, approved_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 10, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $project_id, $contractor_id, $homeowner_id, $stage, $amount,
            $completion, "Completed $stage as per schedule", $status, $date, $date, $amount
        ]);
        $total_stage += $amount;
        
        echo "<div class='success'>✅ $stage: ₹" . number_format($amount) . " ($completion% complete) - $status</div>";
    }
    
    echo "<p><strong>Total Stage Payments: ₹" . number_format($total_stage) . "</strong></p>";
    echo "</div>";
    
    // Step 5: Create Custom Payment Requests (Additional Work)
    echo "<div class='section'><h2>Step 5: Creating Custom Payment Requests</h2>";
    
    $custom_payments = [
        ['Additional Bathroom', 'Homeowner requested extra bathroom on first floor', 120000, '2026-03-10', 'paid'],
        ['Extended Balcony', 'Balcony extension by 4 feet as per homeowner request', 150000, '2026-04-05', 'paid'],
        ['Premium Flooring Upgrade', 'Upgraded from standard to premium marble flooring', 180000, '2026-05-10', 'paid'],
        ['Landscaping Work', 'Garden landscaping and outdoor lighting installation', 100000, '2026-06-15', 'paid']
    ];
    
    $total_custom = 0;
    foreach ($custom_payments as $payment) {
        list($title, $reason, $amount, $date, $status) = $payment;
        
        $sql = "INSERT INTO custom_payment_requests (
            project_id, contractor_id, homeowner_id, request_title, request_reason,
            requested_amount, work_description, status, request_date,
            response_date, approved_amount, payment_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $project_id, $contractor_id, $homeowner_id, $title, $reason,
            $amount, $reason, $status, $date, $date, $amount, $date
        ]);
        $total_custom += $amount;
        
        echo "<div class='success'>✅ $title: ₹" . number_format($amount) . " - $status</div>";
    }
    
    echo "<p><strong>Total Custom Payments: ₹" . number_format($total_custom) . "</strong></p>";
    echo "</div>";
    
    // Step 6: Calculate Time Overrun
    echo "<div class='section'><h2>Step 6: Calculating Time Overrun</h2>";
    
    $sql = "SELECT 
                DATEDIFF(planned_end_date, planned_start_date) as planned_duration,
                DATEDIFF(actual_end_date, actual_start_date) as actual_duration
            FROM construction_projects WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $planned_days = $result['planned_duration'];
    $actual_days = $result['actual_duration'];
    $delay_days = $actual_days - $planned_days;
    $time_overrun = (($actual_days - $planned_days) / $planned_days) * 100;
    
    $sql = "UPDATE construction_projects SET actual_time_overrun_percentage = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$time_overrun, $project_id]);
    
    echo "<p><strong>Planned Duration:</strong> $planned_days days</p>";
    echo "<p><strong>Actual Duration:</strong> $actual_days days</p>";
    echo "<p><strong>Delay:</strong> $delay_days days</p>";
    echo "<div class='success'>✅ <strong>Time Overrun: " . round($time_overrun, 2) . "%</strong></div>";
    echo "</div>";
    
    // Step 7: Calculate Cost Overrun
    echo "<div class='section'><h2>Step 7: Calculating Cost Overrun</h2>";
    
    $original_estimate = $project['total_cost'];
    $total_cost = $total_stage + $total_custom;
    $budget_diff = $total_cost - $original_estimate;
    $cost_overrun = ($budget_diff / $original_estimate) * 100;
    
    echo "<p><strong>Original Estimate:</strong> ₹" . number_format($original_estimate) . "</p>";
    echo "<p><strong>Stage Payments:</strong> ₹" . number_format($total_stage) . "</p>";
    echo "<p><strong>Custom Payments:</strong> ₹" . number_format($total_custom) . "</p>";
    echo "<p><strong>Total Project Cost:</strong> ₹" . number_format($total_cost) . "</p>";
    echo "<p><strong>Budget Difference:</strong> " . ($budget_diff >= 0 ? '+' : '') . "₹" . number_format($budget_diff) . "</p>";
    echo "<div class='success'>✅ <strong>Cost Overrun: " . round($cost_overrun, 2) . "%</strong></div>";
    echo "</div>";
    
    // Commit transaction
    $db->commit();
    
    // Final Summary
    echo "<div class='section' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;'>";
    echo "<h2 style='color: white;'>🎉 Project 3 Construction Complete!</h2>";
    echo "<h3>Final Summary:</h3>";
    echo "<table style='width:100%; color:white;'>";
    echo "<tr><td><strong>Project Status:</strong></td><td>✅ COMPLETED (100%)</td></tr>";
    echo "<tr><td><strong>Total Duration:</strong></td><td>$actual_days days ($delay_days days delay)</td></tr>";
    echo "<tr><td><strong>Time Overrun:</strong></td><td>" . round($time_overrun, 2) . "%</td></tr>";
    echo "<tr><td><strong>Total Cost:</strong></td><td>₹" . number_format($total_cost) . "</td></tr>";
    echo "<tr><td><strong>Cost Overrun:</strong></td><td>" . round($cost_overrun, 2) . "%</td></tr>";
    echo "<tr><td><strong>Daily Reports:</strong></td><td>$report_count reports filed</td></tr>";
    echo "<tr><td><strong>Stage Payments:</strong></td><td>" . count($stage_payments) . " payments (₹" . number_format($total_stage) . ")</td></tr>";
    echo "<tr><td><strong>Custom Payments:</strong></td><td>" . count($custom_payments) . " payments (₹" . number_format($total_custom) . ")</td></tr>";
    echo "</table>";
    echo "<br>";
    echo "<div style='background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px;'>";
    echo "<h3 style='margin-top:0;'>✅ What Was Created:</h3>";
    echo "<ul>";
    echo "<li>✅ Complete project schedule (planned & actual dates)</li>";
    echo "<li>✅ $report_count daily progress reports across 7 construction stages</li>";
    echo "<li>✅ 7 stage payment requests (all paid)</li>";
    echo "<li>✅ 4 custom payment requests (all paid)</li>";
    echo "<li>✅ Time overrun calculation (" . round($time_overrun, 2) . "%)</li>";
    echo "<li>✅ Cost overrun calculation (" . round($cost_overrun, 2) . "%)</li>";
    echo "<li>✅ Project marked as 100% complete</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>🔍 View the Results</h2>";
    echo "<p>You can now view the completed project data in:</p>";
    echo "<ul>";
    echo "<li>📊 <strong>Contractor Dashboard</strong> - View all daily reports and payment requests</li>";
    echo "<li>🏠 <strong>Homeowner Dashboard</strong> - See construction progress and payment history</li>";
    echo "<li>📈 <strong>Admin Dashboard</strong> - Review complete project analytics</li>";
    echo "</ul>";
    echo "<p><a href='test_overrun_project_3.php' style='display:inline-block;padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Run Overrun Test Again</a></p>";
    echo "</div>";
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div style='color:#ef4444;background:#fee2e2;padding:20px;border-radius:10px;'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<?php
/**
 * Verify Project 3 Data - Check what was created
 */

require_once 'backend/config/database.php';

$db = (new Database())->getConnection();
$project_id = 3;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Project 3 Data</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #667eea; }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; background: #fef3c7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Project 3 Data Verification</h1>
        
        <?php
        // Check Project Details
        echo "<div class='section'>";
        echo "<h2>1. Project Details</h2>";
        $stmt = $db->prepare("SELECT * FROM construction_projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Project ID</td><td>{$project['id']}</td></tr>";
            echo "<tr><td>Project Name</td><td>{$project['project_name']}</td></tr>";
            echo "<tr><td>Status</td><td><strong>{$project['status']}</strong></td></tr>";
            echo "<tr><td>Completion %</td><td><strong>{$project['completion_percentage']}%</strong></td></tr>";
            echo "<tr><td>Estimated Cost</td><td>₹" . number_format($project['estimated_cost'] ?? 0) . "</td></tr>";
            echo "<tr><td>Planned Start</td><td>{$project['planned_start_date']}</td></tr>";
            echo "<tr><td>Planned End</td><td>{$project['planned_end_date']}</td></tr>";
            echo "<tr><td>Actual Start</td><td>{$project['actual_start_date']}</td></tr>";
            echo "<tr><td>Actual End</td><td>{$project['actual_end_date']}</td></tr>";
            echo "<tr><td>Time Overrun %</td><td><strong>" . round($project['actual_time_overrun_percentage'] ?? 0, 2) . "%</strong></td></tr>";
            echo "<tr><td>Schedule Locked</td><td>" . ($project['schedule_locked'] ? 'Yes' : 'No') . "</td></tr>";
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Project 3 not found!</div>";
        }
        echo "</div>";
        
        // Check Daily Progress Reports
        echo "<div class='section'>";
        echo "<h2>2. Daily Progress Reports</h2>";
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_progress_reports WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            echo "<div class='success'>✅ Found $count daily progress reports</div>";
            
            // Show sample reports
            $stmt = $db->prepare("SELECT * FROM daily_progress_reports WHERE project_id = ? ORDER BY report_date LIMIT 5");
            $stmt->execute([$project_id]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Sample Reports (first 5):</strong></p>";
            echo "<table>";
            echo "<tr><th>Date</th><th>Stage</th><th>Progress %</th><th>Workers</th><th>Hours</th></tr>";
            foreach ($reports as $report) {
                echo "<tr>";
                echo "<td>{$report['report_date']}</td>";
                echo "<td>{$report['construction_stage']}</td>";
                echo "<td>{$report['progress_percentage']}%</td>";
                echo "<td>{$report['worker_count']}</td>";
                echo "<td>{$report['hours_worked']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No daily progress reports found</div>";
        }
        echo "</div>";
        
        // Check Stage Payments
        echo "<div class='section'>";
        echo "<h2>3. Stage Payment Requests</h2>";
        $stmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE project_id = ? ORDER BY request_date");
        $stmt->execute([$project_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($payments) > 0) {
            echo "<div class='success'>✅ Found " . count($payments) . " stage payment requests</div>";
            
            $total = 0;
            echo "<table>";
            echo "<tr><th>Stage</th><th>Amount</th><th>Completion %</th><th>Status</th><th>Date</th></tr>";
            foreach ($payments as $payment) {
                echo "<tr>";
                echo "<td>{$payment['stage_name']}</td>";
                echo "<td>₹" . number_format($payment['requested_amount']) . "</td>";
                echo "<td>{$payment['completion_percentage']}%</td>";
                echo "<td>{$payment['status']}</td>";
                echo "<td>{$payment['request_date']}</td>";
                echo "</tr>";
                $total += $payment['requested_amount'];
            }
            echo "<tr style='background:#f0f0f0;font-weight:bold;'><td>Total</td><td>₹" . number_format($total) . "</td><td colspan='3'></td></tr>";
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No stage payment requests found</div>";
        }
        echo "</div>";
        
        // Check Custom Payments
        echo "<div class='section'>";
        echo "<h2>4. Custom Payment Requests</h2>";
        $stmt = $db->prepare("SELECT * FROM custom_payment_requests WHERE project_id = ? ORDER BY request_date");
        $stmt->execute([$project_id]);
        $customs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($customs) > 0) {
            echo "<div class='success'>✅ Found " . count($customs) . " custom payment requests</div>";
            
            $total = 0;
            echo "<table>";
            echo "<tr><th>Title</th><th>Amount</th><th>Status</th><th>Date</th></tr>";
            foreach ($customs as $custom) {
                echo "<tr>";
                echo "<td>{$custom['request_title']}</td>";
                echo "<td>₹" . number_format($custom['requested_amount']) . "</td>";
                echo "<td>{$custom['status']}</td>";
                echo "<td>{$custom['request_date']}</td>";
                echo "</tr>";
                $total += $custom['requested_amount'];
            }
            echo "<tr style='background:#f0f0f0;font-weight:bold;'><td>Total</td><td>₹" . number_format($total) . "</td><td colspan='2'></td></tr>";
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No custom payment requests found</div>";
        }
        echo "</div>";
        
        // Summary
        echo "<div class='section' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;'>";
        echo "<h2 style='color: white;'>📊 Summary</h2>";
        
        $daily_count = $db->prepare("SELECT COUNT(*) as c FROM daily_progress_reports WHERE project_id = ?");
        $daily_count->execute([$project_id]);
        $daily_total = $daily_count->fetch(PDO::FETCH_ASSOC)['c'];
        
        $stage_count = $db->prepare("SELECT COUNT(*) as c FROM stage_payment_requests WHERE project_id = ?");
        $stage_count->execute([$project_id]);
        $stage_total = $stage_count->fetch(PDO::FETCH_ASSOC)['c'];
        
        $custom_count = $db->prepare("SELECT COUNT(*) as c FROM custom_payment_requests WHERE project_id = ?");
        $custom_count->execute([$project_id]);
        $custom_total = $custom_count->fetch(PDO::FETCH_ASSOC)['c'];
        
        echo "<table style='color:white;'>";
        echo "<tr><td><strong>Daily Reports:</strong></td><td>$daily_total</td></tr>";
        echo "<tr><td><strong>Stage Payments:</strong></td><td>$stage_total</td></tr>";
        echo "<tr><td><strong>Custom Payments:</strong></td><td>$custom_total</td></tr>";
        echo "<tr><td><strong>Project Status:</strong></td><td>{$project['status']}</td></tr>";
        echo "<tr><td><strong>Completion:</strong></td><td>{$project['completion_percentage']}%</td></tr>";
        echo "</table>";
        
        if ($daily_total == 0 && $stage_total == 0 && $custom_total == 0) {
            echo "<div style='background:rgba(255,255,255,0.2);padding:15px;border-radius:10px;margin-top:15px;'>";
            echo "<h3 style='margin-top:0;'>⚠️ No Data Found</h3>";
            echo "<p>The script may not have run successfully. Try running it again:</p>";
            echo "<a href='complete_project_3_construction.php' class='btn'>Run Data Creation Script</a>";
            echo "</div>";
        } else {
            echo "<div style='background:rgba(255,255,255,0.2);padding:15px;border-radius:10px;margin-top:15px;'>";
            echo "<h3 style='margin-top:0;'>✅ Data Successfully Created!</h3>";
            echo "<p>You can now view this data in your application dashboards.</p>";
            echo "</div>";
        }
        echo "</div>";
        
        // Where to view
        echo "<div class='section'>";
        echo "<h2>🔍 Where to View This Data</h2>";
        echo "<p>The data has been added to the database. To see it in your application:</p>";
        echo "<ol>";
        echo "<li><strong>Contractor Dashboard:</strong> Login as contractor to see daily reports and payment requests</li>";
        echo "<li><strong>Homeowner Dashboard:</strong> Login as homeowner to see construction progress</li>";
        echo "<li><strong>Admin Dashboard:</strong> View complete project analytics</li>";
        echo "</ol>";
        echo "<p><strong>Note:</strong> Make sure you're logged in with the correct user account and viewing Project ID 3.</p>";
        echo "<br>";
        echo "<a href='complete_project_3_construction.php' class='btn'>🔄 Run Creation Script Again</a>";
        echo "<a href='test_overrun_project_3.php' class='btn'>📊 Test Overrun Calculations</a>";
        echo "</div>";
        ?>
        
    </div>
</body>
</html>

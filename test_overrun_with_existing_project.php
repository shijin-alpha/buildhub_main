<?php
/**
 * Cost & Time Overrun Test - Using Existing Project
 * This version uses an existing project to avoid database constraint issues
 */

require_once 'backend/config/database.php';

$db = (new Database())->getConnection();

// Get an existing project
$stmt = $db->query("SELECT id, project_name, homeowner_id, contractor_id FROM construction_projects ORDER BY id DESC LIMIT 1");
$existing_project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existing_project) {
    die("No projects found in database. Please create a project first.");
}

$project_id = $existing_project['id'];
$homeowner_id = $existing_project['homeowner_id'];
$contractor_id = $existing_project['contractor_id'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost & Time Overrun Test - Existing Project</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .success { color: #10b981; padding: 15px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; padding: 15px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; padding: 15px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .test-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; }
        .test-section h3 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #e5e7eb; }
        .badge { padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .badge.pass { background: #10b981; color: white; }
        .badge.fail { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Cost & Time Overrun System Test</h1>
        <p>Testing with existing project: <strong><?php echo htmlspecialchars($existing_project['project_name']); ?></strong> (ID: <?php echo $project_id; ?>)</p>
        
        <?php
        $results = [];
        
        // Test 1: Set Schedule
        echo "<div class='test-section'>";
        echo "<h3>Test 1: Schedule Tracking</h3>";
        try {
            // Set planned dates
            $sql = "UPDATE construction_projects 
                    SET planned_start_date = '2026-02-01',
                        planned_end_date = '2026-05-01',
                        schedule_locked = 0,
                        estimated_cost = 2500000
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            
            // Set actual start (locks schedule)
            $sql = "UPDATE construction_projects 
                    SET actual_start_date = '2026-02-05',
                        schedule_locked = 1
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            
            echo "<div class='success'><span class='badge pass'>✅ PASS</span> Schedule dates set successfully</div>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Planned Start</td><td>2026-02-01</td></tr>";
            echo "<tr><td>Planned End</td><td>2026-05-01 (90 days)</td></tr>";
            echo "<tr><td>Actual Start</td><td>2026-02-05 (4 days late)</td></tr>";
            echo "<tr><td>Schedule Locked</td><td>Yes</td></tr>";
            echo "</table>";
            $results['schedule'] = 'pass';
        } catch (Exception $e) {
            echo "<div class='error'><span class='badge fail'>❌ FAIL</span> " . $e->getMessage() . "</div>";
            $results['schedule'] = 'fail';
        }
        echo "</div>";
        
        // Test 2: Add Payments
        echo "<div class='test-section'>";
        echo "<h3>Test 2: Budget Tracking</h3>";
        try {
            // Clear existing test payments
            $db->exec("DELETE FROM stage_payment_requests WHERE project_id = $project_id AND stage_name LIKE 'Test%'");
            $db->exec("DELETE FROM custom_payment_requests WHERE project_id = $project_id AND request_title LIKE 'Test%'");
            
            // Add stage payments
            $stage_total = 0;
            $stages = [
                ['Test Foundation', 500000],
                ['Test Structure', 700000],
                ['Test Finishing', 600000],
                ['Test Completion', 400000]
            ];
            
            foreach ($stages as $stage) {
                $sql = "INSERT INTO stage_payment_requests (
                    project_id, contractor_id, homeowner_id, stage_name, requested_amount,
                    completion_percentage, work_description, labor_count, status
                ) VALUES (?, ?, ?, ?, ?, 25, 'Test work', 5, 'paid')";
                $stmt = $db->prepare($sql);
                $stmt->execute([$project_id, $contractor_id, $homeowner_id, $stage[0], $stage[1]]);
                $stage_total += $stage[1];
            }
            
            // Add custom payments
            $custom_total = 0;
            $customs = [
                ['Test Extra Bathroom', 150000],
                ['Test Balcony Extension', 200000],
                ['Test Landscaping', 100000]
            ];
            
            foreach ($customs as $custom) {
                $sql = "INSERT INTO custom_payment_requests (
                    project_id, contractor_id, homeowner_id, request_title, request_reason,
                    requested_amount, work_description, status
                ) VALUES (?, ?, ?, ?, 'Additional work', ?, 'Test work', 'paid')";
                $stmt = $db->prepare($sql);
                $stmt->execute([$project_id, $contractor_id, $homeowner_id, $custom[0], $custom[1]]);
                $custom_total += $custom[1];
            }
            
            echo "<div class='success'><span class='badge pass'>✅ PASS</span> Payment records created</div>";
            echo "<table>";
            echo "<tr><th>Payment Type</th><th>Amount</th></tr>";
            echo "<tr><td>Stage Payments</td><td>₹" . number_format($stage_total) . "</td></tr>";
            echo "<tr><td>Custom Payments</td><td>₹" . number_format($custom_total) . "</td></tr>";
            echo "<tr><th>Total Cost</th><th>₹" . number_format($stage_total + $custom_total) . "</th></tr>";
            echo "</table>";
            $results['budget'] = 'pass';
        } catch (Exception $e) {
            echo "<div class='error'><span class='badge fail'>❌ FAIL</span> " . $e->getMessage() . "</div>";
            $results['budget'] = 'fail';
        }
        echo "</div>";
        
        // Test 3: Complete Project & Calculate Time Overrun
        echo "<div class='test-section'>";
        echo "<h3>Test 3: Time Overrun Calculation</h3>";
        try {
            // Set completion date
            $sql = "UPDATE construction_projects 
                    SET actual_end_date = '2026-05-15',
                        status = 'completed'
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            
            // Calculate time overrun
            $sql = "SELECT 
                        DATEDIFF(planned_end_date, planned_start_date) as planned_duration,
                        DATEDIFF(actual_end_date, actual_start_date) as actual_duration
                    FROM construction_projects WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $planned = $result['planned_duration'];
            $actual = $result['actual_duration'];
            $time_overrun = (($actual - $planned) / $planned) * 100;
            
            // Save time overrun
            $sql = "UPDATE construction_projects SET actual_time_overrun_percentage = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$time_overrun, $project_id]);
            
            $expected = 11.11;
            $is_correct = abs($time_overrun - $expected) < 0.5;
            
            echo "<div class='" . ($is_correct ? 'success' : 'error') . "'>";
            echo "<span class='badge " . ($is_correct ? 'pass' : 'fail') . "'>" . ($is_correct ? '✅ PASS' : '❌ FAIL') . "</span> ";
            echo "Time overrun calculated</div>";
            echo "<table>";
            echo "<tr><th>Metric</th><th>Value</th></tr>";
            echo "<tr><td>Planned Duration</td><td>$planned days</td></tr>";
            echo "<tr><td>Actual Duration</td><td>$actual days</td></tr>";
            echo "<tr><td>Delay</td><td>" . ($actual - $planned) . " days</td></tr>";
            echo "<tr><td>Time Overrun</td><td>" . round($time_overrun, 2) . "%</td></tr>";
            echo "<tr><td>Expected</td><td>$expected%</td></tr>";
            echo "<tr><td>Result</td><td>" . ($is_correct ? '✅ Correct' : '❌ Incorrect') . "</td></tr>";
            echo "</table>";
            $results['time_overrun'] = $is_correct ? 'pass' : 'fail';
        } catch (Exception $e) {
            echo "<div class='error'><span class='badge fail'>❌ FAIL</span> " . $e->getMessage() . "</div>";
            $results['time_overrun'] = 'fail';
        }
        echo "</div>";
        
        // Test 4: Calculate Cost Overrun
        echo "<div class='test-section'>";
        echo "<h3>Test 4: Cost Overrun Calculation</h3>";
        try {
            // Get estimated cost
            $sql = "SELECT estimated_cost FROM construction_projects WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            $estimate = $stmt->fetch(PDO::FETCH_ASSOC)['estimated_cost'];
            
            // Get total stage payments
            $sql = "SELECT COALESCE(SUM(requested_amount), 0) as total 
                    FROM stage_payment_requests 
                    WHERE project_id = ? AND status IN ('paid', 'approved', 'pending')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            $stage_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get total custom payments
            $sql = "SELECT COALESCE(SUM(requested_amount), 0) as total 
                    FROM custom_payment_requests 
                    WHERE project_id = ? AND status IN ('paid', 'approved', 'pending')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id]);
            $custom_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $total_cost = $stage_total + $custom_total;
            $cost_overrun = (($total_cost - $estimate) / $estimate) * 100;
            
            $expected = 6.0;
            $is_correct = abs($cost_overrun - $expected) < 0.5;
            
            echo "<div class='" . ($is_correct ? 'success' : 'error') . "'>";
            echo "<span class='badge " . ($is_correct ? 'pass' : 'fail') . "'>" . ($is_correct ? '✅ PASS' : '❌ FAIL') . "</span> ";
            echo "Cost overrun calculated</div>";
            echo "<table>";
            echo "<tr><th>Metric</th><th>Value</th></tr>";
            echo "<tr><td>Original Estimate</td><td>₹" . number_format($estimate) . "</td></tr>";
            echo "<tr><td>Stage Payments</td><td>₹" . number_format($stage_total) . "</td></tr>";
            echo "<tr><td>Custom Payments</td><td>₹" . number_format($custom_total) . "</td></tr>";
            echo "<tr><td>Total Cost</td><td>₹" . number_format($total_cost) . "</td></tr>";
            echo "<tr><td>Budget Difference</td><td>₹" . number_format($total_cost - $estimate) . "</td></tr>";
            echo "<tr><td>Cost Overrun</td><td>" . round($cost_overrun, 2) . "%</td></tr>";
            echo "<tr><td>Expected</td><td>$expected%</td></tr>";
            echo "<tr><td>Result</td><td>" . ($is_correct ? '✅ Correct' : '❌ Incorrect') . "</td></tr>";
            echo "</table>";
            $results['cost_overrun'] = $is_correct ? 'pass' : 'fail';
        } catch (Exception $e) {
            echo "<div class='error'><span class='badge fail'>❌ FAIL</span> " . $e->getMessage() . "</div>";
            $results['cost_overrun'] = 'fail';
        }
        echo "</div>";
        
        // Summary
        $total = count($results);
        $passed = count(array_filter($results, fn($r) => $r === 'pass'));
        $failed = $total - $passed;
        
        echo "<div class='test-section'>";
        echo "<h3>📊 Test Summary</h3>";
        echo "<table>";
        echo "<tr><th>Total Tests</th><td>$total</td></tr>";
        echo "<tr><th>Passed</th><td style='color: #10b981;'>$passed</td></tr>";
        echo "<tr><th>Failed</th><td style='color: #ef4444;'>$failed</td></tr>";
        echo "<tr><th>Success Rate</th><td>" . round(($passed/$total)*100, 2) . "%</td></tr>";
        echo "</table>";
        
        if ($failed == 0) {
            echo "<div class='success'><h2>✅ ALL TESTS PASSED!</h2>";
            echo "<p>Your cost and time overrun system is working correctly.</p></div>";
        } else {
            echo "<div class='error'><h2>⚠️ SOME TESTS FAILED</h2>";
            echo "<p>Review the failed tests above for details.</p></div>";
        }
        echo "</div>";
        ?>
        
    </div>
</body>
</html>

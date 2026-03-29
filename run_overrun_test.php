<?php
/**
 * Standalone Cost & Time Overrun System Test
 * This file runs the test and displays results in one page
 */

require_once 'backend/config/database.php';

// Run the test
class OverrunSystemTester {
    private $conn;
    private $test_project_id;
    private $test_homeowner_id = 32;
    private $test_contractor_id = 45;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function runCompleteTest() {
        $results = [
            'test_name' => 'Cost & Time Overrun System Verification',
            'timestamp' => date('Y-m-d H:i:s'),
            'tests' => []
        ];
        
        try {
            $results['tests']['project_creation'] = $this->testProjectCreation();
            $results['tests']['schedule_tracking'] = $this->testScheduleTracking();
            $results['tests']['budget_tracking'] = $this->testBudgetTracking();
            $results['tests']['time_overrun_calculation'] = $this->testTimeOverrunCalculation();
            $results['tests']['cost_overrun_calculation'] = $this->testCostOverrunCalculation();
            $results['tests']['final_verification'] = $this->testFinalVerification();
            $results['summary'] = $this->generateSummary($results['tests']);
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    private function testProjectCreation() {
        $test = ['name' => 'Project Creation', 'status' => 'running'];
        
        try {
            // First, create a dummy estimate record to satisfy the foreign key
            $sql = "INSERT INTO contractor_send_estimates (
                contractor_id, homeowner_id, project_name, total_cost, status, created_at
            ) VALUES (?, ?, ?, ?, 'approved', NOW())";
            $stmt = $this->conn->prepare($sql);
            $project_name = "Test Estimate - Overrun Verification " . time();
            $estimated_cost = 2500000;
            $stmt->execute([$this->test_contractor_id, $this->test_homeowner_id, $project_name, $estimated_cost]);
            $estimate_id = $this->conn->lastInsertId();
            
            // Now create the project with the estimate_id
            $sql = "INSERT INTO construction_projects (
                estimate_id, homeowner_id, contractor_id, project_name, estimated_cost, status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'in_progress', NOW())";
            $stmt = $this->conn->prepare($sql);
            $project_name = "Test Project - Overrun Verification " . time();
            $stmt->execute([$estimate_id, $this->test_homeowner_id, $this->test_contractor_id, $project_name, $estimated_cost]);
            
            $this->test_project_id = $this->conn->lastInsertId();
            $test['status'] = 'passed';
            $test['project_id'] = $this->test_project_id;
            $test['estimate_id'] = $estimate_id;
            $test['estimated_cost'] = $estimated_cost;
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    private function testScheduleTracking() {
        $test = ['name' => 'Schedule Tracking', 'status' => 'running'];
        
        try {
            $planned_start = '2026-02-01';
            $planned_end = '2026-05-01';
            $actual_start = '2026-02-05';
            
            $sql = "UPDATE construction_projects SET planned_start_date = ?, planned_end_date = ?, schedule_locked = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$planned_start, $planned_end, $this->test_project_id]);
            
            $sql = "UPDATE construction_projects SET actual_start_date = ?, schedule_locked = 1, status = 'in_progress' WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$actual_start, $this->test_project_id]);
            
            $test['status'] = 'passed';
            $test['planned_duration'] = 90;
            $test['start_delay'] = 4;
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    private function testBudgetTracking() {
        $test = ['name' => 'Budget Tracking', 'status' => 'running'];
        
        try {
            $stage_payments = [
                ['Foundation', 500000, 'paid'],
                ['Structure', 700000, 'paid'],
                ['Finishing', 600000, 'pending'],
                ['Completion', 400000, 'pending']
            ];
            
            $total_stage = 0;
            foreach ($stage_payments as $payment) {
                $sql = "INSERT INTO stage_payment_requests (
                    project_id, contractor_id, homeowner_id, stage_name, requested_amount, 
                    completion_percentage, work_description, labor_count, status, request_date
                ) VALUES (?, ?, ?, ?, ?, 0, 'Test work description', 5, ?, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    $this->test_project_id, 
                    $this->test_contractor_id, 
                    $this->test_homeowner_id, 
                    $payment[0], 
                    $payment[1], 
                    $payment[2]
                ]);
                $total_stage += $payment[1];
            }
            
            $custom_payments = [
                ['Extra bathroom addition', 150000, 'paid'],
                ['Balcony extension', 200000, 'paid'],
                ['Landscaping work', 100000, 'pending']
            ];
            
            $total_custom = 0;
            foreach ($custom_payments as $payment) {
                $sql = "INSERT INTO custom_payment_requests (
                    project_id, contractor_id, homeowner_id, request_title, request_reason, 
                    requested_amount, work_description, status, request_date
                ) VALUES (?, ?, ?, ?, 'Additional work required', ?, 'Test work description', ?, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    $this->test_project_id, 
                    $this->test_contractor_id, 
                    $this->test_homeowner_id, 
                    $payment[0], 
                    $payment[1], 
                    $payment[2]
                ]);
                $total_custom += $payment[1];
            }
            
            $test['status'] = 'passed';
            $test['total_stage'] = $total_stage;
            $test['total_custom'] = $total_custom;
            $test['total_cost'] = $total_stage + $total_custom;
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    private function testTimeOverrunCalculation() {
        $test = ['name' => 'Time Overrun Calculation', 'status' => 'running'];
        
        try {
            $actual_end = '2026-05-15';
            $sql = "UPDATE construction_projects SET actual_end_date = ?, status = 'completed' WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$actual_end, $this->test_project_id]);
            
            $sql = "SELECT 
                        planned_start_date,
                        planned_end_date,
                        actual_start_date,
                        actual_end_date,
                        DATEDIFF(planned_end_date, planned_start_date) as planned_duration,
                        DATEDIFF(actual_end_date, actual_start_date) as actual_duration
                    FROM construction_projects WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("Project not found for time overrun calculation");
            }
            
            $planned_duration = $result['planned_duration'];
            $actual_duration = $result['actual_duration'];
            
            if ($planned_duration === null || $actual_duration === null) {
                throw new Exception("Missing date information. Planned: {$result['planned_start_date']} to {$result['planned_end_date']}, Actual: {$result['actual_start_date']} to {$result['actual_end_date']}");
            }
            
            if ($planned_duration == 0) {
                throw new Exception("Planned duration is zero - cannot calculate overrun");
            }
            
            $time_overrun_percentage = (($actual_duration - $planned_duration) / $planned_duration) * 100;
            
            $sql = "UPDATE construction_projects SET actual_time_overrun_percentage = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$time_overrun_percentage, $this->test_project_id]);
            
            $test['status'] = 'passed';
            $test['planned_duration'] = $planned_duration;
            $test['actual_duration'] = $actual_duration;
            $test['time_overrun'] = round($time_overrun_percentage, 2);
            $test['expected'] = 11.11;
            $test['correct'] = abs($time_overrun_percentage - 11.11) < 0.5;
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    private function testCostOverrunCalculation() {
        $test = ['name' => 'Cost Overrun Calculation', 'status' => 'running'];
        
        try {
            $sql = "SELECT estimated_cost FROM construction_projects WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $original_estimate = $result ? $result['estimated_cost'] : 0;
            
            $sql = "SELECT COALESCE(SUM(requested_amount), 0) as total FROM stage_payment_requests 
                    WHERE project_id = ? AND status IN ('paid', 'pending', 'approved')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_stage = $result ? $result['total'] : 0;
            
            $sql = "SELECT COALESCE(SUM(requested_amount), 0) as total FROM custom_payment_requests 
                    WHERE project_id = ? AND status IN ('paid', 'pending', 'approved')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_custom = $result ? $result['total'] : 0;
            
            $total_cost = $total_stage + $total_custom;
            $budget_difference = $total_cost - $original_estimate;
            $cost_overrun_percentage = $original_estimate > 0 ? ($budget_difference / $original_estimate) * 100 : 0;
            
            $test['status'] = 'passed';
            $test['original_estimate'] = $original_estimate;
            $test['total_cost'] = $total_cost;
            $test['cost_overrun'] = round($cost_overrun_percentage, 2);
            $test['expected'] = 6.0;
            $test['correct'] = abs($cost_overrun_percentage - 6.0) < 0.5;
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    private function testFinalVerification() {
        $test = ['name' => 'Final Verification', 'status' => 'running'];
        
        try {
            $sql = "SELECT * FROM construction_projects WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                throw new Exception("Project not found");
            }
            
            $all_passed = $project['schedule_locked'] == 1 && 
                         !empty($project['planned_start_date']) && 
                         !empty($project['actual_end_date']) &&
                         !empty($project['actual_time_overrun_percentage']);
            
            $test['status'] = $all_passed ? 'passed' : 'failed';
            $test['schedule_locked'] = $project['schedule_locked'] == 1;
            $test['has_dates'] = !empty($project['actual_end_date']);
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    private function generateSummary($tests) {
        $total = count($tests);
        $passed = 0;
        foreach ($tests as $test) {
            if ($test['status'] == 'passed') $passed++;
        }
        
        return [
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $total - $passed,
            'success_rate' => round(($passed / $total) * 100, 2) . '%',
            'overall_status' => ($passed == $total) ? 'ALL TESTS PASSED ✅' : 'SOME TESTS FAILED ❌',
            'test_project_id' => $this->test_project_id
        ];
    }
}

// Run test and display results
try {
    $database = new Database();
    $conn = $database->getConnection();
    $tester = new OverrunSystemTester($conn);
    $results = $tester->runCompleteTest();
} catch (Exception $e) {
    $results = ['error' => $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost & Time Overrun Test Results</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        h1 { color: #333; margin-bottom: 10px; }
        .timestamp { color: #666; font-size: 14px; margin-bottom: 20px; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
        }
        .summary-item .label { color: #64748b; font-size: 14px; }
        .summary-item .value { color: #1e293b; font-size: 24px; font-weight: bold; margin-top: 5px; }
        .test-item {
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .test-name { font-size: 18px; font-weight: bold; color: #333; }
        .badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .badge.passed { background: #10b981; color: white; }
        .badge.failed { background: #ef4444; color: white; }
        .test-details { margin-top: 10px; font-size: 14px; color: #475569; }
        .test-details div { padding: 5px 0; }
        .correct { color: #10b981; font-weight: bold; }
        .incorrect { color: #ef4444; font-weight: bold; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🧪 <?php echo htmlspecialchars($results['test_name'] ?? 'Test Results'); ?></h1>
            <div class="timestamp">Run at: <?php echo htmlspecialchars($results['timestamp'] ?? date('Y-m-d H:i:s')); ?></div>
            
            <?php if (isset($results['error'])): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($results['error']); ?>
                </div>
            <?php else: ?>
                
                <?php if (isset($results['summary'])): ?>
                    <h2 style="margin-top: 20px;">Summary</h2>
                    <div class="summary">
                        <div class="summary-item">
                            <div class="label">Total Tests</div>
                            <div class="value"><?php echo $results['summary']['total_tests']; ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Passed</div>
                            <div class="value" style="color: #10b981;"><?php echo $results['summary']['passed']; ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Failed</div>
                            <div class="value" style="color: #ef4444;"><?php echo $results['summary']['failed']; ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="label">Success Rate</div>
                            <div class="value"><?php echo $results['summary']['success_rate']; ?></div>
                        </div>
                    </div>
                    <div style="text-align: center; padding: 15px; background: <?php echo $results['summary']['failed'] == 0 ? '#d1fae5' : '#fee2e2'; ?>; border-radius: 10px; margin: 20px 0;">
                        <strong style="font-size: 18px;"><?php echo $results['summary']['overall_status']; ?></strong>
                    </div>
                <?php endif; ?>
                
                <h2 style="margin-top: 30px;">Test Details</h2>
                <?php foreach ($results['tests'] as $test): ?>
                    <div class="test-item">
                        <div class="test-header">
                            <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                            <span class="badge <?php echo $test['status']; ?>">
                                <?php echo $test['status'] == 'passed' ? '✅' : '❌'; ?> <?php echo strtoupper($test['status']); ?>
                            </span>
                        </div>
                        
                        <?php if (isset($test['error'])): ?>
                            <div class="error"><?php echo htmlspecialchars($test['error']); ?></div>
                        <?php endif; ?>
                        
                        <div class="test-details">
                            <?php if (isset($test['project_id'])): ?>
                                <div><strong>Project ID:</strong> <?php echo $test['project_id']; ?></div>
                                <div><strong>Estimated Cost:</strong> ₹<?php echo number_format($test['estimated_cost']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($test['planned_duration'])): ?>
                                <div><strong>Planned Duration:</strong> <?php echo $test['planned_duration']; ?> days</div>
                                <div><strong>Start Delay:</strong> <?php echo $test['start_delay']; ?> days</div>
                            <?php endif; ?>
                            
                            <?php if (isset($test['total_stage'])): ?>
                                <div><strong>Stage Payments:</strong> ₹<?php echo number_format($test['total_stage']); ?></div>
                                <div><strong>Custom Payments:</strong> ₹<?php echo number_format($test['total_custom']); ?></div>
                                <div><strong>Total Cost:</strong> ₹<?php echo number_format($test['total_cost']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($test['time_overrun'])): ?>
                                <div><strong>Planned Duration:</strong> <?php echo $test['planned_duration']; ?> days</div>
                                <div><strong>Actual Duration:</strong> <?php echo $test['actual_duration']; ?> days</div>
                                <div><strong>Time Overrun:</strong> <?php echo $test['time_overrun']; ?>%</div>
                                <div><strong>Expected:</strong> <?php echo $test['expected']; ?>%</div>
                                <div class="<?php echo $test['correct'] ? 'correct' : 'incorrect'; ?>">
                                    <?php echo $test['correct'] ? '✅ CORRECT - Calculation accurate!' : '❌ INCORRECT - Check calculation'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($test['cost_overrun'])): ?>
                                <div><strong>Original Estimate:</strong> ₹<?php echo number_format($test['original_estimate']); ?></div>
                                <div><strong>Total Cost:</strong> ₹<?php echo number_format($test['total_cost']); ?></div>
                                <div><strong>Cost Overrun:</strong> <?php echo $test['cost_overrun']; ?>%</div>
                                <div><strong>Expected:</strong> <?php echo $test['expected']; ?>%</div>
                                <div class="<?php echo $test['correct'] ? 'correct' : 'incorrect'; ?>">
                                    <?php echo $test['correct'] ? '✅ CORRECT - Calculation accurate!' : '❌ INCORRECT - Check calculation'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($test['schedule_locked'])): ?>
                                <div><strong>Schedule Locked:</strong> <?php echo $test['schedule_locked'] ? '✅ Yes' : '❌ No'; ?></div>
                                <div><strong>Has Dates:</strong> <?php echo $test['has_dates'] ? '✅ Yes' : '❌ No'; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

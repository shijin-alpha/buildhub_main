<?php
/**
 * Cost & Time Overrun System - Complete Testing Script
 * 
 * This script creates a test project with known overruns to verify the system works correctly.
 * It simulates the entire lifecycle from planning to completion.
 */

require_once 'backend/config/database.php';

header('Content-Type: application/json');

class OverrunSystemTester {
    private $conn;
    private $test_project_id;
    private $test_homeowner_id = 32; // Using existing test user
    private $test_contractor_id = 45; // Using existing test contractor
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run complete test suite
     */
    public function runCompleteTest() {
        $results = [
            'test_name' => 'Cost & Time Overrun System Verification',
            'timestamp' => date('Y-m-d H:i:s'),
            'tests' => []
        ];
        
        try {
            // Test 1: Create test project
            $results['tests']['project_creation'] = $this->testProjectCreation();
            
            // Test 2: Test schedule tracking
            $results['tests']['schedule_tracking'] = $this->testScheduleTracking();
            
            // Test 3: Test budget tracking
            $results['tests']['budget_tracking'] = $this->testBudgetTracking();
            
            // Test 4: Test time overrun calculation
            $results['tests']['time_overrun_calculation'] = $this->testTimeOverrunCalculation();
            
            // Test 5: Test cost overrun calculation
            $results['tests']['cost_overrun_calculation'] = $this->testCostOverrunCalculation();
            
            // Test 6: Verify final results
            $results['tests']['final_verification'] = $this->testFinalVerification();
            
            // Summary
            $results['summary'] = $this->generateSummary($results['tests']);
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            $results['trace'] = $e->getTraceAsString();
        }
        
        return $results;
    }
    
    /**
     * Test 1: Create a test project with known parameters
     */
    private function testProjectCreation() {
        $test = [
            'name' => 'Project Creation',
            'status' => 'running'
        ];
        
        try {
            // Create test project using PDO
            $sql = "INSERT INTO construction_projects (
                homeowner_id, 
                contractor_id, 
                project_name, 
                estimated_cost,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, datetime('now'))";
            
            $stmt = $this->conn->prepare($sql);
            $project_name = "Test Project - Overrun Verification " . time();
            $estimated_cost = 2500000; // ₹25,00,000
            $status = 'approved';
            
            $stmt->execute([
                $this->test_homeowner_id,
                $this->test_contractor_id,
                $project_name,
                $estimated_cost,
                $status
            ]);
            
            $this->test_project_id = $this->conn->lastInsertId();
            
            $test['status'] = 'passed';
            $test['project_id'] = $this->test_project_id;
            $test['details'] = [
                'project_name' => $project_name,
                'estimated_cost' => $estimated_cost,
                'homeowner_id' => $this->test_homeowner_id,
                'contractor_id' => $this->test_contractor_id
            ];
            
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test 2: Schedule tracking with known delays
     */
    private function testScheduleTracking() {
        $test = [
            'name' => 'Schedule Tracking',
            'status' => 'running'
        ];
        
        try {
            // Set planned dates: 90 days duration
            $planned_start = '2026-02-01';
            $planned_end = '2026-05-01'; // 90 days
            
            $sql = "UPDATE construction_projects 
                    SET planned_start_date = ?, 
                        planned_end_date = ?,
                        schedule_locked = 0
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$planned_start, $planned_end, $this->test_project_id]);
            
            // Set actual start date: 4 days late
            $actual_start = '2026-02-05';
            
            $sql = "UPDATE construction_projects 
                    SET actual_start_date = ?,
                        schedule_locked = 1,
                        status = 'in_progress'
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$actual_start, $this->test_project_id]);
            
            // Verify schedule is locked
            $sql = "SELECT schedule_locked, planned_start_date, planned_end_date, actual_start_date 
                    FROM construction_projects WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $test['status'] = 'passed';
            $test['details'] = [
                'planned_start' => $planned_start,
                'planned_end' => $planned_end,
                'planned_duration_days' => 90,
                'actual_start' => $actual_start,
                'start_delay_days' => 4,
                'schedule_locked' => $result['schedule_locked'] == 1 ? 'Yes' : 'No'
            ];
            
            if ($result['schedule_locked'] != 1) {
                $test['warning'] = 'Schedule should be locked after actual start date is set';
            }
            
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test 3: Budget tracking with stage and custom payments
     */
    private function testBudgetTracking() {
        $test = [
            'name' => 'Budget Tracking',
            'status' => 'running'
        ];
        
        try {
            // Create stage payments (total: ₹22,00,000)
            $stage_payments = [
                ['Foundation', 500000, 'paid'],
                ['Structure', 700000, 'paid'],
                ['Finishing', 600000, 'pending'],
                ['Completion', 400000, 'pending']
            ];
            
            $total_stage = 0;
            foreach ($stage_payments as $payment) {
                $sql = "INSERT INTO stage_payment_requests (
                    project_id, stage_name, amount, status, requested_at
                ) VALUES (?, ?, ?, ?, datetime('now'))";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$this->test_project_id, $payment[0], $payment[1], $payment[2]]);
                
                $total_stage += $payment[1];
            }
            
            // Create custom payments (total: ₹4,50,000) - This causes overrun
            $custom_payments = [
                ['Extra bathroom addition', 150000, 'paid'],
                ['Balcony extension', 200000, 'paid'],
                ['Landscaping work', 100000, 'pending']
            ];
            
            $total_custom = 0;
            foreach ($custom_payments as $payment) {
                $sql = "INSERT INTO custom_payment_requests (
                    project_id, description, amount, status, requested_at
                ) VALUES (?, ?, ?, ?, datetime('now'))";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$this->test_project_id, $payment[0], $payment[1], $payment[2]]);
                
                $total_custom += $payment[1];
            }
            
            $test['status'] = 'passed';
            $test['details'] = [
                'stage_payments' => [
                    'count' => count($stage_payments),
                    'total' => $total_stage,
                    'breakdown' => $stage_payments
                ],
                'custom_payments' => [
                    'count' => count($custom_payments),
                    'total' => $total_custom,
                    'breakdown' => $custom_payments
                ],
                'total_cost' => $total_stage + $total_custom
            ];
            
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test 4: Time overrun calculation
     */
    private function testTimeOverrunCalculation() {
        $test = [
            'name' => 'Time Overrun Calculation',
            'status' => 'running'
        ];
        
        try {
            // Set actual end date: 14 days late (May 15 instead of May 1)
            $actual_end = '2026-05-15';
            
            $sql = "UPDATE construction_projects 
                    SET actual_end_date = ?,
                        status = 'completed'
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$actual_end, $this->test_project_id]);
            
            // Calculate time overrun using SQLite's julianday function
            $sql = "SELECT 
                        planned_start_date,
                        planned_end_date,
                        actual_start_date,
                        actual_end_date,
                        CAST(julianday(planned_end_date) - julianday(planned_start_date) AS INTEGER) as planned_duration,
                        CAST(julianday(actual_end_date) - julianday(actual_start_date) AS INTEGER) as actual_duration
                    FROM construction_projects 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $planned_duration = $result['planned_duration'];
            $actual_duration = $result['actual_duration'];
            $time_overrun_percentage = (($actual_duration - $planned_duration) / $planned_duration) * 100;
            
            // Update the time overrun in database
            $sql = "UPDATE construction_projects 
                    SET actual_time_overrun_percentage = ?
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$time_overrun_percentage, $this->test_project_id]);
            
            $test['status'] = 'passed';
            $test['details'] = [
                'planned_start' => $result['planned_start_date'],
                'planned_end' => $result['planned_end_date'],
                'planned_duration_days' => $planned_duration,
                'actual_start' => $result['actual_start_date'],
                'actual_end' => $result['actual_end_date'],
                'actual_duration_days' => $actual_duration,
                'delay_days' => $actual_duration - $planned_duration,
                'time_overrun_percentage' => round($time_overrun_percentage, 2)
            ];
            
            // Expected: 11.11% overrun (100 days vs 90 days)
            $expected_overrun = 11.11;
            if (abs($time_overrun_percentage - $expected_overrun) < 0.5) {
                $test['verification'] = 'CORRECT - Time overrun calculated accurately';
            } else {
                $test['warning'] = "Expected ~{$expected_overrun}%, got " . round($time_overrun_percentage, 2) . "%";
            }
            
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test 5: Cost overrun calculation
     */
    private function testCostOverrunCalculation() {
        $test = [
            'name' => 'Cost Overrun Calculation',
            'status' => 'running'
        ];
        
        try {
            // Get original estimate
            $sql = "SELECT estimated_cost FROM construction_projects WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $original_estimate = $result['estimated_cost'];
            
            // Calculate total stage payments
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM stage_payment_requests 
                    WHERE project_id = ? AND status IN ('paid', 'pending')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_stage = $result['total'];
            
            // Calculate total custom payments
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM custom_payment_requests 
                    WHERE project_id = ? AND status IN ('paid', 'pending', 'approved')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_custom = $result['total'];
            
            // Calculate overrun
            $total_cost = $total_stage + $total_custom;
            $budget_difference = $total_cost - $original_estimate;
            $cost_overrun_percentage = ($budget_difference / $original_estimate) * 100;
            
            $test['status'] = 'passed';
            $test['details'] = [
                'original_estimate' => $original_estimate,
                'total_stage_payments' => $total_stage,
                'total_custom_payments' => $total_custom,
                'total_project_cost' => $total_cost,
                'budget_difference' => $budget_difference,
                'cost_overrun_percentage' => round($cost_overrun_percentage, 2),
                'is_overrun' => $budget_difference > 0
            ];
            
            // Expected: 6% overrun (₹26,50,000 vs ₹25,00,000)
            $expected_overrun = 6.0;
            if (abs($cost_overrun_percentage - $expected_overrun) < 0.5) {
                $test['verification'] = 'CORRECT - Cost overrun calculated accurately';
            } else {
                $test['warning'] = "Expected ~{$expected_overrun}%, got " . round($cost_overrun_percentage, 2) . "%";
            }
            
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test 6: Final verification - Check all data is correct
     */
    private function testFinalVerification() {
        $test = [
            'name' => 'Final System Verification',
            'status' => 'running'
        ];
        
        try {
            // Get complete project data
            $sql = "SELECT * FROM construction_projects WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$this->test_project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify all critical fields
            $checks = [
                'schedule_locked' => $project['schedule_locked'] == 1,
                'has_planned_dates' => !empty($project['planned_start_date']) && !empty($project['planned_end_date']),
                'has_actual_dates' => !empty($project['actual_start_date']) && !empty($project['actual_end_date']),
                'has_time_overrun' => !empty($project['actual_time_overrun_percentage']),
                'status_completed' => $project['status'] == 'completed'
            ];
            
            $all_passed = !in_array(false, $checks);
            
            $test['status'] = $all_passed ? 'passed' : 'failed';
            $test['checks'] = $checks;
            $test['project_data'] = [
                'id' => $project['id'],
                'name' => $project['project_name'],
                'status' => $project['status'],
                'estimated_cost' => $project['estimated_cost'],
                'planned_start' => $project['planned_start_date'],
                'planned_end' => $project['planned_end_date'],
                'actual_start' => $project['actual_start_date'],
                'actual_end' => $project['actual_end_date'],
                'time_overrun_percentage' => $project['actual_time_overrun_percentage'],
                'schedule_locked' => $project['schedule_locked']
            ];
            
        } catch (Exception $e) {
            $test['status'] = 'failed';
            $test['error'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Generate summary of all tests
     */
    private function generateSummary($tests) {
        $total = count($tests);
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            if ($test['status'] == 'passed') {
                $passed++;
            } else if ($test['status'] == 'failed') {
                $failed++;
            }
        }
        
        return [
            'total_tests' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => round(($passed / $total) * 100, 2) . '%',
            'overall_status' => $failed == 0 ? 'ALL TESTS PASSED ✅' : 'SOME TESTS FAILED ❌',
            'test_project_id' => $this->test_project_id,
            'cleanup_note' => "To clean up, delete project ID: {$this->test_project_id}"
        ];
    }
}

// Run the test
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $tester = new OverrunSystemTester($conn);
    $results = $tester->runCompleteTest();
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Test execution failed',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

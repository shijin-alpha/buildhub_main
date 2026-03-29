<?php
/**
 * Check Real Projects Data in Database
 * Analyze actual construction projects and their details
 */

echo "🔍 Checking Real Projects Data in Database...\n\n";

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Check all construction projects
    echo "📋 1. All Construction Projects:\n";
    echo "==============================\n";
    
    $projects_query = "SELECT 
                         cp.id,
                         cp.project_name,
                         cp.project_description,
                         cp.status,
                         cp.current_stage,
                         cp.completion_percentage,
                         cp.project_location,
                         cp.start_date,
                         cp.expected_completion_date,
                         cp.total_cost,
                         cp.timeline,
                         cp.homeowner_name,
                         cp.homeowner_email,
                         cp.contractor_id,
                         cp.created_at
                       FROM construction_projects cp
                       ORDER BY cp.id";
    
    $stmt = $db->prepare($projects_query);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($projects) . " construction projects:\n\n";
    
    foreach ($projects as $project) {
        echo "🏗️ Project {$project['id']}: {$project['project_name']}\n";
        echo "   Description: " . ($project['project_description'] ?: 'Not specified') . "\n";
        echo "   Status: {$project['status']}\n";
        echo "   Current Stage: {$project['current_stage']}\n";
        echo "   Progress: {$project['completion_percentage']}%\n";
        echo "   Location: " . ($project['project_location'] ?: 'Not specified') . "\n";
        echo "   Homeowner: {$project['homeowner_name']} ({$project['homeowner_email']})\n";
        echo "   Contractor ID: {$project['contractor_id']}\n";
        echo "   Start Date: {$project['start_date']}\n";
        echo "   Expected Completion: {$project['expected_completion_date']}\n";
        echo "   Total Cost: ₹" . ($project['total_cost'] ? number_format($project['total_cost']) : 'Not specified') . "\n";
        echo "   Timeline: {$project['timeline']}\n";
        echo "   Created: {$project['created_at']}\n";
        echo "\n";
    }
    
    // 2. Check contractors for these projects
    echo "👷 2. Contractors for Projects:\n";
    echo "==============================\n";
    
    $contractors_query = "SELECT DISTINCT
                            cp.id as project_id,
                            cp.project_name,
                            cp.contractor_id,
                            u.first_name,
                            u.last_name,
                            u.email,
                            u.phone,
                            u.role
                          FROM construction_projects cp
                          LEFT JOIN users u ON cp.contractor_id = u.id
                          ORDER BY cp.id";
    
    $stmt = $db->prepare($contractors_query);
    $stmt->execute();
    $contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contractors as $contractor) {
        echo "🏗️ Project {$contractor['project_id']}: {$contractor['project_name']}\n";
        if ($contractor['contractor_id']) {
            echo "   👷 Contractor: {$contractor['first_name']} {$contractor['last_name']}\n";
            echo "   📧 Email: {$contractor['email']}\n";
            echo "   📱 Phone: " . ($contractor['phone'] ?: 'Not provided') . "\n";
            echo "   🔧 Role: {$contractor['role']}\n";
        } else {
            echo "   ❌ No contractor assigned\n";
        }
        echo "\n";
    }
    
    // 3. Check stage payment requests for real progress
    echo "💰 3. Stage Payment Requests (Real Progress Data):\n";
    echo "=================================================\n";
    
    $payments_query = "SELECT 
                         spr.project_id,
                         cp.project_name,
                         spr.stage_name,
                         spr.completion_percentage,
                         spr.requested_amount,
                         spr.status,
                         spr.request_date,
                         spr.payment_date,
                         spr.work_description
                       FROM stage_payment_requests spr
                       JOIN construction_projects cp ON spr.project_id = cp.id
                       ORDER BY spr.project_id, spr.request_date";
    
    $stmt = $db->prepare($payments_query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $current_project = null;
    foreach ($payments as $payment) {
        if ($current_project !== $payment['project_id']) {
            if ($current_project !== null) echo "\n";
            echo "🏗️ Project {$payment['project_id']}: {$payment['project_name']}\n";
            $current_project = $payment['project_id'];
        }
        
        $status_icon = $payment['status'] === 'paid' ? '✅' : 
                      ($payment['status'] === 'approved' ? '🟡' : '⏳');
        
        echo "   {$status_icon} {$payment['stage_name']}: {$payment['completion_percentage']}% - ₹" . 
             number_format($payment['requested_amount']) . " ({$payment['status']})\n";
        echo "      Date: {$payment['request_date']}" . 
             ($payment['payment_date'] ? " | Paid: {$payment['payment_date']}" : "") . "\n";
        if ($payment['work_description']) {
            echo "      Work: " . substr($payment['work_description'], 0, 100) . "...\n";
        }
    }
    
    // 4. Check construction progress updates
    echo "\n\n📈 4. Construction Progress Updates:\n";
    echo "===================================\n";
    
    try {
        $progress_query = "SELECT 
                             cpu.project_id,
                             cp.project_name,
                             cpu.stage_name,
                             cpu.completion_percentage,
                             cpu.description,
                             cpu.created_at
                           FROM construction_progress_updates cpu
                           JOIN construction_projects cp ON cpu.project_id = cp.id
                           ORDER BY cpu.project_id, cpu.created_at DESC";
        
        $stmt = $db->prepare($progress_query);
        $stmt->execute();
        $progress_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($progress_updates)) {
            echo "No construction progress updates found.\n";
        } else {
            $current_project = null;
            foreach ($progress_updates as $update) {
                if ($current_project !== $update['project_id']) {
                    if ($current_project !== null) echo "\n";
                    echo "🏗️ Project {$update['project_id']}: {$update['project_name']}\n";
                    $current_project = $update['project_id'];
                }
                
                echo "   📊 {$update['stage_name']}: {$update['completion_percentage']}%\n";
                echo "      Date: {$update['created_at']}\n";
                if ($update['description']) {
                    echo "      Description: " . substr($update['description'], 0, 100) . "...\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "Construction progress updates table not accessible: " . $e->getMessage() . "\n";
    }
    
    // 5. Summary of real data
    echo "\n\n📊 5. Real Data Summary:\n";
    echo "=======================\n";
    
    $total_projects = count($projects);
    $projects_with_contractors = count(array_filter($contractors, function($c) { return $c['contractor_id']; }));
    $total_payments = count($payments);
    $paid_payments = count(array_filter($payments, function($p) { return $p['status'] === 'paid'; }));
    
    echo "Total Construction Projects: {$total_projects}\n";
    echo "Projects with Contractors: {$projects_with_contractors}\n";
    echo "Total Stage Payment Requests: {$total_payments}\n";
    echo "Paid Stage Payments: {$paid_payments}\n";
    
    // Calculate real progress for each project
    echo "\n📈 Real Progress Calculation:\n";
    $project_progress = [];
    foreach ($payments as $payment) {
        $pid = $payment['project_id'];
        if (!isset($project_progress[$pid])) {
            $project_progress[$pid] = ['total' => 0, 'paid' => 0, 'name' => $payment['project_name']];
        }
        $project_progress[$pid]['total'] += $payment['completion_percentage'];
        if ($payment['status'] === 'paid') {
            $project_progress[$pid]['paid'] += $payment['completion_percentage'];
        }
    }
    
    foreach ($project_progress as $pid => $progress) {
        echo "   Project {$pid} ({$progress['name']}): {$progress['paid']}% real progress (out of {$progress['total']}% total stages)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Real Projects Data Analysis Complete!\n";
?>
<?php
/**
 * Get Financial Summary from Database
 * Analyzes all financial data in the BUILDHUB system
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "📊 BUILDHUB Database Analysis - Financial Summary\n";
    echo "================================================\n\n";
    
    // Check construction projects and their costs
    echo "🏗️ CONSTRUCTION PROJECTS:\n";
    $projectStmt = $db->prepare("
        SELECT 
            id, project_name, total_cost, timeline, status, 
            homeowner_name, completion_percentage, current_stage
        FROM construction_projects 
        ORDER BY id
    ");
    $projectStmt->execute();
    $projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalProjectValue = 0;
    foreach ($projects as $project) {
        $cost = $project['total_cost'] ? (float)$project['total_cost'] : 0;
        $totalProjectValue += $cost;
        echo "   • Project ID: {$project['id']}\n";
        echo "     Name: {$project['project_name']}\n";
        echo "     Cost: ₹" . number_format($cost, 2) . "\n";
        echo "     Status: {$project['status']}\n";
        echo "     Progress: {$project['completion_percentage']}%\n";
        echo "     Stage: {$project['current_stage']}\n";
        echo "     Homeowner: {$project['homeowner_name']}\n";
        echo "     Timeline: {$project['timeline']}\n\n";
    }
    
    echo "💰 TOTAL PROJECT VALUE: ₹" . number_format($totalProjectValue, 2) . "\n\n";
    
    // Check stage payment requests
    echo "💳 STAGE PAYMENT REQUESTS:\n";
    $paymentStmt = $db->prepare("
        SELECT 
            spr.id, spr.project_id, spr.stage_name, spr.requested_amount,
            spr.approved_amount, spr.status, spr.verification_status,
            spr.completion_percentage, cp.project_name
        FROM stage_payment_requests spr
        LEFT JOIN construction_projects cp ON spr.project_id = cp.id
        ORDER BY spr.project_id, spr.id
    ");
    $paymentStmt->execute();
    $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalRequested = 0;
    $totalApproved = 0;
    foreach ($payments as $payment) {
        $requested = (float)$payment['requested_amount'];
        $approved = $payment['approved_amount'] ? (float)$payment['approved_amount'] : 0;
        $totalRequested += $requested;
        $totalApproved += $approved;
        
        echo "   • Payment ID: {$payment['id']}\n";
        echo "     Project: {$payment['project_name']} (ID: {$payment['project_id']})\n";
        echo "     Stage: {$payment['stage_name']}\n";
        echo "     Requested: ₹" . number_format($requested, 2) . "\n";
        echo "     Approved: ₹" . number_format($approved, 2) . "\n";
        echo "     Status: {$payment['status']}\n";
        echo "     Verification: {$payment['verification_status']}\n";
        echo "     Completion: {$payment['completion_percentage']}%\n\n";
    }
    
    echo "💰 PAYMENT SUMMARY:\n";
    echo "   Total Requested: ₹" . number_format($totalRequested, 2) . "\n";
    echo "   Total Approved: ₹" . number_format($totalApproved, 2) . "\n\n";
    
    // Check alternative payments
    echo "🏦 ALTERNATIVE PAYMENTS:\n";
    $altPaymentStmt = $db->prepare("
        SELECT 
            ap.id, ap.project_id, ap.amount, ap.payment_method,
            ap.status, ap.verification_status, cp.project_name
        FROM alternative_payments ap
        LEFT JOIN construction_projects cp ON ap.project_id = cp.id
        ORDER BY ap.project_id, ap.id
    ");
    $altPaymentStmt->execute();
    $altPayments = $altPaymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalAltPayments = 0;
    foreach ($altPayments as $payment) {
        $amount = (float)$payment['amount'];
        $totalAltPayments += $amount;
        
        echo "   • Payment ID: {$payment['id']}\n";
        echo "     Project: {$payment['project_name']} (ID: {$payment['project_id']})\n";
        echo "     Amount: ₹" . number_format($amount, 2) . "\n";
        echo "     Method: {$payment['payment_method']}\n";
        echo "     Status: {$payment['status']}\n";
        echo "     Verification: {$payment['verification_status']}\n\n";
    }
    
    echo "💰 TOTAL ALTERNATIVE PAYMENTS: ₹" . number_format($totalAltPayments, 2) . "\n\n";
    
    // Check users and their roles
    echo "👥 USER STATISTICS:\n";
    $userStmt = $db->prepare("
        SELECT 
            role, status, admin_scope,
            COUNT(*) as count
        FROM users 
        WHERE deleted_at IS NULL OR deleted_at = ''
        GROUP BY role, status, admin_scope
        ORDER BY role, status
    ");
    $userStmt->execute();
    $userStats = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userStats as $stat) {
        $scope = $stat['admin_scope'] ? " ({$stat['admin_scope']})" : '';
        echo "   • {$stat['role']}{$scope}: {$stat['count']} ({$stat['status']})\n";
    }
    
    // Check inspector assignments
    echo "\n🔍 INSPECTOR ASSIGNMENTS:\n";
    $inspectorStmt = $db->prepare("
        SELECT 
            ipa.id, ipa.inspector_id, ipa.project_id, ipa.status,
            CONCAT(u.first_name, ' ', u.last_name) as inspector_name,
            cp.project_name, cp.total_cost
        FROM inspector_project_assignments ipa
        LEFT JOIN users u ON ipa.inspector_id = u.id
        LEFT JOIN construction_projects cp ON ipa.project_id = cp.id
        ORDER BY ipa.inspector_id, ipa.project_id
    ");
    $inspectorStmt->execute();
    $assignments = $inspectorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalAssignedValue = 0;
    foreach ($assignments as $assignment) {
        $projectCost = $assignment['total_cost'] ? (float)$assignment['total_cost'] : 0;
        $totalAssignedValue += $projectCost;
        
        echo "   • Assignment ID: {$assignment['id']}\n";
        echo "     Inspector: {$assignment['inspector_name']} (User ID: {$assignment['inspector_id']})\n";
        echo "     Project: {$assignment['project_name']} (Project ID: {$assignment['project_id']})\n";
        echo "     Project Value: ₹" . number_format($projectCost, 2) . "\n";
        echo "     Status: {$assignment['status']}\n\n";
    }
    
    echo "💰 TOTAL ASSIGNED PROJECT VALUE: ₹" . number_format($totalAssignedValue, 2) . "\n\n";
    
    // Check inspection reports
    echo "📋 INSPECTION REPORTS:\n";
    $reportStmt = $db->prepare("
        SELECT 
            sir.id, sir.project_id, sir.inspection_type, sir.quality_rating,
            sir.safety_compliance, sir.status, cp.project_name,
            CONCAT(u.first_name, ' ', u.last_name) as inspector_name
        FROM site_inspection_reports sir
        LEFT JOIN construction_projects cp ON sir.project_id = cp.id
        LEFT JOIN users u ON sir.inspector_id = u.id
        ORDER BY sir.project_id, sir.id
    ");
    $reportStmt->execute();
    $reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reports as $report) {
        echo "   • Report ID: {$report['id']}\n";
        echo "     Project: {$report['project_name']} (ID: {$report['project_id']})\n";
        echo "     Inspector: {$report['inspector_name']}\n";
        echo "     Type: {$report['inspection_type']}\n";
        echo "     Quality Rating: {$report['quality_rating']}/5\n";
        echo "     Safety: {$report['safety_compliance']}\n";
        echo "     Status: {$report['status']}\n\n";
    }
    
    // Check site notes
    echo "📝 SITE NOTES:\n";
    $noteStmt = $db->prepare("
        SELECT 
            sn.id, sn.project_id, sn.note_type, sn.priority,
            sn.title, sn.is_resolved, cp.project_name,
            CONCAT(u.first_name, ' ', u.last_name) as inspector_name
        FROM site_notes sn
        LEFT JOIN construction_projects cp ON sn.project_id = cp.id
        LEFT JOIN users u ON sn.inspector_id = u.id
        ORDER BY sn.project_id, sn.id
    ");
    $noteStmt->execute();
    $notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($notes as $note) {
        $resolved = $note['is_resolved'] ? 'Resolved' : 'Open';
        echo "   • Note ID: {$note['id']}\n";
        echo "     Project: {$note['project_name']} (ID: {$note['project_id']})\n";
        echo "     Inspector: {$note['inspector_name']}\n";
        echo "     Type: {$note['note_type']}\n";
        echo "     Priority: {$note['priority']}\n";
        echo "     Title: {$note['title']}\n";
        echo "     Status: {$resolved}\n\n";
    }
    
    // Final summary
    echo "📋 COMPREHENSIVE FINANCIAL OVERVIEW:\n";
    echo "===================================\n";
    echo "Total Project Portfolio Value: ₹" . number_format($totalProjectValue, 2) . "\n";
    echo "Total Payment Requests: ₹" . number_format($totalRequested, 2) . "\n";
    echo "Total Approved Payments: ₹" . number_format($totalApproved, 2) . "\n";
    echo "Total Alternative Payments: ₹" . number_format($totalAltPayments, 2) . "\n";
    echo "Total Under Inspector Oversight: ₹" . number_format($totalAssignedValue, 2) . "\n";
    echo "Payment Approval Rate: " . ($totalRequested > 0 ? round(($totalApproved / $totalRequested) * 100, 2) : 0) . "%\n";
    echo "Number of Projects: " . count($projects) . "\n";
    echo "Number of Payment Requests: " . count($payments) . "\n";
    echo "Number of Alternative Payments: " . count($altPayments) . "\n";
    echo "Number of Inspector Assignments: " . count($assignments) . "\n";
    echo "Number of Inspection Reports: " . count($reports) . "\n";
    echo "Number of Site Notes: " . count($notes) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
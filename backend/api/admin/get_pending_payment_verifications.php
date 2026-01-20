<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    
    // Check admin authentication
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin authentication required'
        ]);
        exit;
    }
    
    // Get all pending payment verifications across all projects
    $query = "
        SELECT 
            spr.*,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            h.email as homeowner_email,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email,
            lr.project_name,
            lr.location as project_location,
            lr.budget as project_budget
        FROM stage_payment_requests spr
        LEFT JOIN users h ON spr.homeowner_id = h.id
        LEFT JOIN users c ON spr.contractor_id = c.id
        LEFT JOIN layout_requests lr ON spr.project_id = lr.id
        WHERE spr.receipt_file_path IS NOT NULL 
        AND spr.verification_status IN ('pending', 'verified')
        AND spr.status IN ('approved', 'paid')
        ORDER BY 
            CASE 
                WHEN spr.verification_status = 'pending' THEN 1
                WHEN spr.verification_status = 'verified' THEN 2
                ELSE 3
            END,
            spr.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the payment data
    $formatted_payments = [];
    foreach ($payments as $payment) {
        // Parse receipt files
        $receipt_files = [];
        if ($payment['receipt_file_path']) {
            $receipt_files = json_decode($payment['receipt_file_path'], true) ?: [];
        }
        
        $formatted_payments[] = [
            'id' => $payment['id'],
            'project_id' => $payment['project_id'],
            'project_name' => $payment['project_name'],
            'project_location' => $payment['project_location'],
            'project_budget' => $payment['project_budget'],
            'stage_name' => $payment['stage_name'],
            'requested_amount' => (float)$payment['requested_amount'],
            'approved_amount' => $payment['approved_amount'] ? (float)$payment['approved_amount'] : null,
            'completion_percentage' => (float)$payment['completion_percentage'],
            'work_description' => $payment['work_description'],
            'materials_used' => $payment['materials_used'],
            'labor_count' => (int)$payment['labor_count'],
            'work_start_date' => $payment['work_start_date'],
            'work_end_date' => $payment['work_end_date'],
            'contractor_notes' => $payment['contractor_notes'],
            'homeowner_notes' => $payment['homeowner_notes'],
            'status' => $payment['status'],
            'request_date' => $payment['request_date'],
            'response_date' => $payment['response_date'],
            
            // Homeowner details
            'homeowner_name' => $payment['homeowner_name'],
            'homeowner_email' => $payment['homeowner_email'],
            
            // Contractor details
            'contractor_name' => $payment['contractor_name'],
            'contractor_email' => $payment['contractor_email'],
            
            // Receipt information
            'transaction_reference' => $payment['transaction_reference'],
            'payment_date' => $payment['payment_date'],
            'payment_method' => $payment['payment_method'],
            'receipt_files' => $receipt_files,
            'verification_status' => $payment['verification_status'],
            'verified_by' => $payment['verified_by'],
            'verified_at' => $payment['verified_at'],
            'verification_notes' => $payment['verification_notes'],
            
            // Admin verification fields
            'admin_verified' => $payment['admin_verified'] ?? false,
            'admin_verified_by' => $payment['admin_verified_by'] ?? null,
            'admin_verified_at' => $payment['admin_verified_at'] ?? null,
            'admin_notes' => $payment['admin_notes'] ?? null,
            
            // Priority scoring for admin review
            'priority_score' => calculatePriorityScore($payment),
            'days_pending' => calculateDaysPending($payment['created_at'])
        ];
    }
    
    // Calculate summary statistics
    $total_payments = count($formatted_payments);
    $pending_verification = count(array_filter($formatted_payments, function($p) {
        return $p['verification_status'] === 'pending';
    }));
    $verified_payments = count(array_filter($formatted_payments, function($p) {
        return $p['verification_status'] === 'verified';
    }));
    $total_amount = array_sum(array_column($formatted_payments, 'requested_amount'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payments' => $formatted_payments,
            'summary' => [
                'total_payments' => $total_payments,
                'pending_verification' => $pending_verification,
                'verified_payments' => $verified_payments,
                'total_amount' => $total_amount,
                'average_amount' => $total_payments > 0 ? $total_amount / $total_payments : 0
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get pending payment verifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment verifications: ' . $e->getMessage()
    ]);
}

function calculatePriorityScore($payment) {
    $score = 0;
    
    // Higher amount = higher priority
    $amount = (float)$payment['requested_amount'];
    if ($amount > 100000) $score += 3;
    elseif ($amount > 50000) $score += 2;
    elseif ($amount > 25000) $score += 1;
    
    // Completion percentage
    $completion = (float)$payment['completion_percentage'];
    if ($completion >= 90) $score += 3;
    elseif ($completion >= 70) $score += 2;
    elseif ($completion >= 50) $score += 1;
    
    // Days pending
    $days_pending = calculateDaysPending($payment['created_at']);
    if ($days_pending > 7) $score += 3;
    elseif ($days_pending > 3) $score += 2;
    elseif ($days_pending > 1) $score += 1;
    
    return $score;
}

function calculateDaysPending($created_at) {
    $created = new DateTime($created_at);
    $now = new DateTime();
    return $now->diff($created)->days;
}
?>
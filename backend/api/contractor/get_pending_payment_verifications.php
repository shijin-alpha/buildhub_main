<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get contractor ID from query parameters
    $contractor_id = isset($_GET['contractor_id']) ? intval($_GET['contractor_id']) : 0;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor ID is required'
        ]);
        exit;
    }
    
    // Get payments that need verification (have receipts uploaded but not yet verified)
    $query = "
        SELECT 
            spr.*,
            CONCAT(u_homeowner.first_name, ' ', u_homeowner.last_name) as homeowner_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone,
            cp.project_name,
            cp.project_location
        FROM stage_payment_requests spr
        LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
        LEFT JOIN construction_projects cp ON spr.project_id = cp.id
        WHERE spr.contractor_id = :contractor_id
        AND spr.receipt_file_path IS NOT NULL 
        AND spr.receipt_file_path != ''
        AND spr.verification_status = 'pending'
        ORDER BY spr.updated_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':contractor_id' => $contractor_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the payments data
    $formatted_payments = [];
    foreach ($payments as $payment) {
        // Parse receipt files
        $receipt_files = [];
        if (!empty($payment['receipt_file_path'])) {
            $decoded_files = json_decode($payment['receipt_file_path'], true);
            if (is_array($decoded_files)) {
                $receipt_files = $decoded_files;
            }
        }
        
        $formatted_payment = [
            'id' => intval($payment['id']),
            'project_id' => intval($payment['project_id']),
            'project_name' => $payment['project_name'],
            'project_location' => $payment['project_location'],
            'homeowner_id' => intval($payment['homeowner_id']),
            'homeowner_name' => $payment['homeowner_name'],
            'homeowner_email' => $payment['homeowner_email'],
            'homeowner_phone' => $payment['homeowner_phone'],
            'stage_name' => $payment['stage_name'],
            'requested_amount' => floatval($payment['requested_amount']),
            'completion_percentage' => floatval($payment['completion_percentage']),
            'work_description' => $payment['work_description'],
            'status' => $payment['status'],
            'verification_status' => $payment['verification_status'],
            'transaction_reference' => $payment['transaction_reference'],
            'payment_date' => $payment['payment_date'],
            'payment_method' => $payment['payment_method'],
            'homeowner_notes' => $payment['homeowner_notes'],
            'receipt_files' => $receipt_files,
            'created_at' => $payment['created_at'],
            'updated_at' => $payment['updated_at'],
            
            // Formatted data
            'requested_amount_formatted' => '₹' . number_format($payment['requested_amount'], 2),
            'payment_date_formatted' => $payment['payment_date'] ? date('M j, Y', strtotime($payment['payment_date'])) : null,
            'updated_at_formatted' => date('M j, Y g:i A', strtotime($payment['updated_at'])),
            'days_since_upload' => floor((time() - strtotime($payment['updated_at'])) / (60 * 60 * 24))
        ];
        
        $formatted_payments[] = $formatted_payment;
    }
    
    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(*) as total_pending,
            COALESCE(SUM(requested_amount), 0) as total_amount,
            COUNT(CASE WHEN DATEDIFF(NOW(), updated_at) > 2 THEN 1 END) as overdue_count
        FROM stage_payment_requests 
        WHERE contractor_id = :contractor_id
        AND receipt_file_path IS NOT NULL 
        AND receipt_file_path != ''
        AND verification_status = 'pending'
    ";
    
    $summary_stmt = $db->prepare($summary_query);
    $summary_stmt->execute([':contractor_id' => $contractor_id]);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payments' => $formatted_payments,
            'summary' => [
                'total_pending' => intval($summary['total_pending']),
                'total_amount' => floatval($summary['total_amount']),
                'total_amount_formatted' => '₹' . number_format($summary['total_amount'], 2),
                'overdue_count' => intval($summary['overdue_count'])
            ]
        ],
        'message' => 'Pending payment verifications retrieved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Get pending payment verifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving pending payment verifications: ' . $e->getMessage()
    ]);
}
?>
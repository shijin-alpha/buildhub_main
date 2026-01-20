<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $contractor_id = $_SESSION['user_id'] ?? $_GET['contractor_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    // Get pending payment verifications for this contractor
    $query = "
        SELECT 
            ap.*,
            CONCAT(u_homeowner.first_name, ' ', u_homeowner.last_name) as homeowner_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone
        FROM alternative_payments ap
        LEFT JOIN users u_homeowner ON ap.homeowner_id = u_homeowner.id
        WHERE ap.contractor_id = :contractor_id 
        AND ap.payment_status = 'pending_verification'
        AND ap.verification_status = 'pending'
        AND ap.receipt_file_path IS NOT NULL
        ORDER BY ap.updated_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':contractor_id' => $contractor_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process receipt files for each payment
    foreach ($payments as &$payment) {
        if ($payment['receipt_file_path']) {
            $receiptFiles = json_decode($payment['receipt_file_path'], true);
            $payment['receipt_files'] = $receiptFiles ?: [];
        } else {
            $payment['receipt_files'] = [];
        }
        
        // Remove the raw JSON field
        unset($payment['receipt_file_path']);
    }
    
    // Get summary statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_pending,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(CASE WHEN payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAYS) THEN 1 END) as recent_uploads
        FROM alternative_payments 
        WHERE contractor_id = :contractor_id 
        AND payment_status = 'pending_verification'
        AND verification_status = 'pending'
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute([':contractor_id' => $contractor_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payments' => $payments,
            'statistics' => [
                'total_pending' => (int)$stats['total_pending'],
                'total_amount' => (float)$stats['total_amount'],
                'recent_uploads' => (int)$stats['recent_uploads'],
                'formatted_total_amount' => '₹' . number_format($stats['total_amount'], 2)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get pending payment verifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>
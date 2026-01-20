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
    $contractor_id = $_SESSION['user_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 2;
    
    // Get recent paid payments for this contractor
    $query = "
        SELECT 
            spr.*,
            u.first_name as homeowner_first_name,
            u.last_name as homeowner_last_name,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
            cp.project_name,
            cp.location as project_location
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        LEFT JOIN construction_projects cp ON spr.project_id = cp.id
        WHERE spr.contractor_id = :contractor_id 
        AND spr.status = 'paid'
        ORDER BY spr.payment_date DESC, spr.updated_at DESC
        LIMIT :limit
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the payments
    $formatted_payments = [];
    foreach ($payments as $payment) {
        $formatted_payments[] = [
            'id' => $payment['id'],
            'project_id' => $payment['project_id'],
            'project_name' => $payment['project_name'] ?? 'Project #' . $payment['project_id'],
            'project_location' => $payment['project_location'] ?? 'N/A',
            'stage_name' => $payment['stage_name'],
            'requested_amount' => (float)$payment['requested_amount'],
            'approved_amount' => $payment['approved_amount'] ? (float)$payment['approved_amount'] : null,
            'paid_amount' => $payment['approved_amount'] ? (float)$payment['approved_amount'] : (float)$payment['requested_amount'],
            'completion_percentage' => (float)$payment['completion_percentage'],
            'work_description' => $payment['work_description'],
            'status' => $payment['status'],
            'request_date' => $payment['request_date'],
            'payment_date' => $payment['payment_date'],
            'homeowner_name' => $payment['homeowner_name'],
            'homeowner_first_name' => $payment['homeowner_first_name'],
            'homeowner_last_name' => $payment['homeowner_last_name'],
            'payment_method' => $payment['payment_method'] ?? 'Razorpay',
            'verification_status' => $payment['verification_status'] ?? 'pending',
            'verified_at' => $payment['verified_at'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $formatted_payments,
        'count' => count($formatted_payments)
    ]);
    
} catch (Exception $e) {
    error_log("Get recent paid payments error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving recent payments: ' . $e->getMessage()
    ]);
}
?>

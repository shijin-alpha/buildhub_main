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
    
    $project_id = $_GET['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit;
    }
    
    // First, check if stage_payment_requests table exists
    $table_check = $db->query("SHOW TABLES LIKE 'stage_payment_requests'");
    
    if ($table_check->rowCount() == 0) {
        // Create the table if it doesn't exist
        $create_table = "
            CREATE TABLE stage_payment_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT,
                contractor_id INT,
                homeowner_id INT,
                stage_name VARCHAR(100),
                requested_amount DECIMAL(12,2),
                approved_amount DECIMAL(12,2) NULL,
                completion_percentage DECIMAL(5,2),
                work_description TEXT,
                materials_used TEXT,
                labor_count INT,
                work_start_date DATE,
                work_end_date DATE,
                contractor_notes TEXT,
                homeowner_notes TEXT,
                quality_check BOOLEAN DEFAULT FALSE,
                safety_compliance BOOLEAN DEFAULT FALSE,
                status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
                request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                response_date TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        $db->exec($create_table);
    }
    
    // For now, allow access to any project for testing purposes
    // In production, you would want proper access control
    // Check if project exists in construction_projects table first, then layout_requests
    $project_exists_query = "
        SELECT 
            CASE 
                WHEN cp.id IS NOT NULL THEN 'construction_projects'
                WHEN lr.id IS NOT NULL THEN 'layout_requests'
                ELSE NULL
            END as project_source,
            COALESCE(cp.id, lr.id) as project_id,
            COALESCE(cp.homeowner_id, lr.user_id) as homeowner_id
        FROM (SELECT :project_id as search_id) s
        LEFT JOIN construction_projects cp ON cp.id = s.search_id
        LEFT JOIN layout_requests lr ON lr.id = s.search_id
        WHERE cp.id IS NOT NULL OR lr.id IS NOT NULL
    ";
    
    $project_stmt = $db->prepare($project_exists_query);
    $project_stmt->execute([':project_id' => $project_id]);
    $project_result = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project_result) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit;
    }
    
    // Get payment requests for this project
    $payment_query = "
        SELECT 
            spr.*,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = :project_id 
        AND spr.contractor_id = :contractor_id
        ORDER BY spr.request_date DESC
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $payment_requests = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the payment requests
    $formatted_requests = [];
    foreach ($payment_requests as $request) {
        $homeowner_name = '';
        if ($request['first_name'] && $request['last_name']) {
            $homeowner_name = $request['first_name'] . ' ' . $request['last_name'];
        }
        
        $formatted_requests[] = [
            'id' => $request['id'],
            'stage_name' => $request['stage_name'],
            'requested_amount' => (float)$request['requested_amount'],
            'approved_amount' => $request['approved_amount'] ? (float)$request['approved_amount'] : null,
            'completion_percentage' => (float)$request['completion_percentage'],
            'work_description' => $request['work_description'],
            'materials_used' => $request['materials_used'],
            'labor_count' => (int)$request['labor_count'],
            'work_start_date' => $request['work_start_date'],
            'work_end_date' => $request['work_end_date'],
            'contractor_notes' => $request['contractor_notes'],
            'homeowner_notes' => $request['homeowner_notes'],
            'quality_check' => (bool)$request['quality_check'],
            'safety_compliance' => (bool)$request['safety_compliance'],
            'status' => $request['status'],
            'request_date' => $request['request_date'],
            'response_date' => $request['response_date'],
            'project_budget' => null, // We don't have this in layout_requests
            'homeowner_name' => $homeowner_name,
            // Receipt information
            'transaction_reference' => $request['transaction_reference'] ?? null,
            'payment_date' => $request['payment_date'] ?? null,
            'payment_method' => $request['payment_method'] ?? null,
            'receipt_file_path' => $request['receipt_file_path'] ? json_decode($request['receipt_file_path'], true) : null,
            'verification_status' => $request['verification_status'] ?? null,
            'verified_by' => $request['verified_by'] ?? null,
            'verified_at' => $request['verified_at'] ?? null,
            'verification_notes' => $request['verification_notes'] ?? null
        ];
    }
    
    // Calculate payment summary
    $total_requested = array_sum(array_column($formatted_requests, 'requested_amount'));
    $total_approved = 0;
    $total_paid = 0;
    $pending_count = 0;
    $approved_count = 0;
    $rejected_count = 0;
    
    foreach ($formatted_requests as $request) {
        if ($request['approved_amount']) {
            $total_approved += $request['approved_amount'];
        }
        
        switch ($request['status']) {
            case 'pending':
                $pending_count++;
                break;
            case 'approved':
                $approved_count++;
                break;
            case 'rejected':
                $rejected_count++;
                break;
            case 'paid':
                $total_paid += $request['approved_amount'] ?: $request['requested_amount'];
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payment_requests' => $formatted_requests,
            'summary' => [
                'total_requests' => count($formatted_requests),
                'total_requested' => $total_requested,
                'total_approved' => $total_approved,
                'total_paid' => $total_paid,
                'pending_count' => $pending_count,
                'approved_count' => $approved_count,
                'rejected_count' => $rejected_count
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get payment history error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment history: ' . $e->getMessage()
    ]);
}
?>
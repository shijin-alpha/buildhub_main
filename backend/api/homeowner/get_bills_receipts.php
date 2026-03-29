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
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get all payment requests with uploaded receipts for this homeowner
    $stmt = $db->prepare("
        SELECT 
            spr.id,
            spr.project_id,
            spr.contractor_id,
            spr.homeowner_id,
            spr.stage_name as request_title,
            spr.requested_amount,
            spr.work_description,
            spr.status,
            spr.request_date,
            spr.response_date,
            spr.homeowner_notes,
            spr.approved_amount,
            spr.rejection_reason,
            spr.created_at,
            spr.updated_at,
            spr.transaction_reference,
            spr.payment_date,
            spr.receipt_file_path,
            spr.payment_method,
            spr.verification_status,
            spr.verification_notes,
            spr.verified_at,
            spr.verified_by,
            'stage' as payment_type,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name,
            u.email as contractor_email,
            u.phone as contractor_phone,
            verifier.first_name as verified_by_name,
            verifier.last_name as verified_by_lastname,
            COALESCE(cp.project_name, JSON_UNQUOTE(JSON_EXTRACT(cse.structured, '$.project_name'))) as project_name,
            COALESCE(cp.project_location, JSON_UNQUOTE(JSON_EXTRACT(cse.structured, '$.project_address'))) as project_location,
            COALESCE(cp.total_cost, cse.total_cost) as project_total_cost
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.contractor_id = u.id
        LEFT JOIN users verifier ON spr.verified_by = verifier.id
        LEFT JOIN contractor_send_estimates cse ON spr.project_id = cse.id
        LEFT JOIN construction_projects cp ON cse.id = cp.estimate_id
        WHERE spr.homeowner_id = :homeowner_id 
        AND spr.receipt_file_path IS NOT NULL 
        AND spr.receipt_file_path != ''
        AND spr.receipt_file_path != 'null'
        
        UNION ALL
        
        SELECT 
            cpr.id,
            cpr.project_id,
            cpr.contractor_id,
            cpr.homeowner_id,
            cpr.request_title,
            cpr.requested_amount,
            cpr.work_description,
            cpr.status,
            cpr.request_date,
            cpr.response_date,
            cpr.homeowner_notes,
            cpr.approved_amount,
            cpr.rejection_reason,
            cpr.created_at,
            cpr.updated_at,
            cpr.transaction_reference,
            cpr.payment_date,
            cpr.receipt_file_path,
            cpr.payment_method,
            cpr.verification_status,
            cpr.verification_notes,
            cpr.verified_at,
            cpr.verified_by,
            'custom' as payment_type,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name,
            u.email as contractor_email,
            u.phone as contractor_phone,
            verifier.first_name as verified_by_name,
            verifier.last_name as verified_by_lastname,
            NULL as project_name,
            NULL as project_location,
            NULL as project_total_cost
        FROM custom_payment_requests cpr
        LEFT JOIN users u ON cpr.contractor_id = u.id
        LEFT JOIN users verifier ON cpr.verified_by = verifier.id
        WHERE cpr.homeowner_id = :homeowner_id2 
        AND cpr.receipt_file_path IS NOT NULL 
        AND cpr.receipt_file_path != ''
        AND cpr.receipt_file_path != 'null'
        
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([':homeowner_id' => $homeowner_id, ':homeowner_id2' => $homeowner_id]);
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process receipt files and format data
    $processed_receipts = [];
    
    foreach ($receipts as $receipt) {
        // Parse receipt files JSON
        $receipt_files = [];
        if ($receipt['receipt_file_path']) {
            $files_data = json_decode($receipt['receipt_file_path'], true);
            if (is_array($files_data)) {
                $receipt_files = $files_data;
            }
        }
        
        // Format dates
        $receipt['payment_date_formatted'] = $receipt['payment_date'] ? 
            date('M j, Y', strtotime($receipt['payment_date'])) : null;
        $receipt['verified_at_formatted'] = $receipt['verified_at'] ? 
            date('M j, Y g:i A', strtotime($receipt['verified_at'])) : null;
        $receipt['created_at_formatted'] = date('M j, Y g:i A', strtotime($receipt['created_at']));
        
        // Add receipt files
        $receipt['receipt_files'] = $receipt_files;
        $receipt['receipt_files_count'] = count($receipt_files);
        
        // Add verification details
        if ($receipt['verified_by']) {
            $receipt['verified_by_full_name'] = trim($receipt['verified_by_name'] . ' ' . $receipt['verified_by_lastname']);
        }
        
        // Calculate total file size
        $total_size = 0;
        foreach ($receipt_files as $file) {
            if (isset($file['file_size'])) {
                $total_size += $file['file_size'];
            }
        }
        $receipt['total_file_size'] = $total_size;
        $receipt['total_file_size_mb'] = round($total_size / (1024 * 1024), 2);
        
        // Add payment method icon
        $payment_method_icons = [
            'bank_transfer' => '🏦',
            'upi' => '📱',
            'cash' => '💵',
            'cheque' => '📝',
            'online' => '💳',
            'other' => '💰'
        ];
        $receipt['payment_method_icon'] = $payment_method_icons[$receipt['payment_method']] ?? '💰';
        
        // Add payment type badge
        $payment_type_badges = [
            'stage' => ['icon' => '🏗️', 'text' => 'Stage Payment', 'color' => '#007bff'],
            'custom' => ['icon' => '💼', 'text' => 'Custom Request', 'color' => '#6f42c1']
        ];
        $receipt['payment_type_badge'] = $payment_type_badges[$receipt['payment_type']] ?? 
            ['icon' => '💰', 'text' => 'Payment', 'color' => '#6c757d'];
        
        $processed_receipts[] = $receipt;
    }
    
    // Calculate summary statistics
    $total_receipts = count($processed_receipts);
    $verified_count = count(array_filter($processed_receipts, function($r) { 
        return $r['verification_status'] === 'verified'; 
    }));
    $pending_count = count(array_filter($processed_receipts, function($r) { 
        return $r['verification_status'] === 'pending'; 
    }));
    $rejected_count = count(array_filter($processed_receipts, function($r) { 
        return $r['verification_status'] === 'rejected'; 
    }));
    
    $total_amount = array_sum(array_map(function($r) { 
        return floatval($r['requested_amount']); 
    }, $processed_receipts));
    
    $verified_amount = array_sum(array_map(function($r) { 
        return $r['verification_status'] === 'verified' ? floatval($r['requested_amount']) : 0; 
    }, $processed_receipts));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'receipts' => $processed_receipts,
            'statistics' => [
                'total_receipts' => $total_receipts,
                'verified_count' => $verified_count,
                'pending_count' => $pending_count,
                'rejected_count' => $rejected_count,
                'total_amount' => $total_amount,
                'verified_amount' => $verified_amount,
                'verification_rate' => $total_receipts > 0 ? round(($verified_count / $total_receipts) * 100, 1) : 0
            ]
        ],
        'message' => "Found {$total_receipts} receipt(s) with {$verified_count} verified"
    ]);
    
} catch (Exception $e) {
    error_log("Bills & Receipts API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching bills and receipts: ' . $e->getMessage()
    ]);
}
?>
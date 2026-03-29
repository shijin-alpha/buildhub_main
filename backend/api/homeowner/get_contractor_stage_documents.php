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
    
    // Get all contractor-uploaded stage documents for this homeowner's projects
    $stmt = $db->prepare("
        SELECT 
            csd.id,
            csd.project_id,
            csd.contractor_id,
            csd.stage_name,
            csd.document_type,
            csd.document_category,
            csd.file_path,
            csd.original_filename,
            csd.file_size,
            csd.mime_type,
            csd.upload_date,
            csd.uploaded_by,
            csd.description,
            csd.verification_status,
            csd.verified_by,
            csd.verified_at,
            csd.verification_notes,
            csd.is_mandatory,
            csd.related_payment_id,
            csd.metadata,
            csd.created_at,
            csd.updated_at,
            cp.project_name,
            cp.project_location,
            cp.total_cost as project_total_cost,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            CONCAT(u_verifier.first_name, ' ', u_verifier.last_name) as verified_by_name,
            u_verifier.email as verifier_email,
            spr.stage_name as payment_stage_name,
            spr.requested_amount as payment_amount
        FROM contractor_stage_documents csd
        JOIN construction_projects cp ON csd.project_id = cp.id
        JOIN users u_contractor ON csd.contractor_id = u_contractor.id
        LEFT JOIN users u_verifier ON csd.verified_by = u_verifier.id
        LEFT JOIN stage_payment_requests spr ON csd.related_payment_id = spr.id
        WHERE cp.homeowner_id = :homeowner_id
        ORDER BY csd.stage_name, csd.document_type, csd.upload_date DESC
    ");
    
    $stmt->execute([':homeowner_id' => $homeowner_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process documents and format data
    $processed_documents = [];
    $stage_summary = [];
    $document_type_summary = [];
    
    foreach ($documents as $doc) {
        // Format dates
        $doc['upload_date_formatted'] = date('M j, Y g:i A', strtotime($doc['upload_date']));
        $doc['verified_at_formatted'] = $doc['verified_at'] ? 
            date('M j, Y g:i A', strtotime($doc['verified_at'])) : null;
        $doc['created_at_formatted'] = date('M j, Y g:i A', strtotime($doc['created_at']));
        
        // Format file size
        $doc['file_size_mb'] = round($doc['file_size'] / (1024 * 1024), 2);
        $doc['file_size_kb'] = round($doc['file_size'] / 1024, 1);
        
        // Add document type icon
        $document_type_icons = [
            'receipt' => '🧾',
            'bill' => '📄',
            'invoice' => '📋',
            'material_certificate' => '📜',
            'quality_report' => '📊',
            'safety_certificate' => '🛡️',
            'permit' => '📝',
            'inspection_report' => '🔍',
            'other' => '📁'
        ];
        $doc['document_type_icon'] = $document_type_icons[$doc['document_type']] ?? '📁';
        
        // Add stage badge
        $stage_badges = [
            'Foundation' => ['icon' => '🏗️', 'color' => '#007bff'],
            'Structure' => ['icon' => '🏢', 'color' => '#28a745'],
            'Brickwork' => ['icon' => '🧱', 'color' => '#fd7e14'],
            'Roofing' => ['icon' => '🏠', 'color' => '#6f42c1'],
            'Electrical' => ['icon' => '⚡', 'color' => '#ffc107'],
            'Plumbing' => ['icon' => '🚰', 'color' => '#20c997'],
            'Finishing' => ['icon' => '🎨', 'color' => '#e83e8c'],
            'Other' => ['icon' => '📋', 'color' => '#6c757d']
        ];
        $doc['stage_badge'] = $stage_badges[$doc['stage_name']] ?? 
            ['icon' => '📋', 'color' => '#6c757d'];
        
        // Add file type icon based on mime type
        if (strpos($doc['mime_type'], 'pdf') !== false) {
            $doc['file_type_icon'] = '📄';
        } elseif (strpos($doc['mime_type'], 'image') !== false) {
            $doc['file_type_icon'] = '🖼️';
        } elseif (strpos($doc['mime_type'], 'word') !== false || strpos($doc['mime_type'], 'document') !== false) {
            $doc['file_type_icon'] = '📝';
        } elseif (strpos($doc['mime_type'], 'excel') !== false || strpos($doc['mime_type'], 'spreadsheet') !== false) {
            $doc['file_type_icon'] = '📊';
        } else {
            $doc['file_type_icon'] = '📁';
        }
        
        // Create receipt_files array for compatibility with existing frontend
        $doc['receipt_files'] = [[
            'original_name' => $doc['original_filename'],
            'stored_name' => basename($doc['file_path']),
            'file_path' => $doc['file_path'],
            'file_size' => $doc['file_size'],
            'file_type' => $doc['mime_type']
        ]];
        
        // Add to stage summary
        if (!isset($stage_summary[$doc['stage_name']])) {
            $stage_summary[$doc['stage_name']] = [
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0
            ];
        }
        $stage_summary[$doc['stage_name']]['total']++;
        $stage_summary[$doc['stage_name']][$doc['verification_status']]++;
        
        // Add to document type summary
        if (!isset($document_type_summary[$doc['document_type']])) {
            $document_type_summary[$doc['document_type']] = [
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0
            ];
        }
        $document_type_summary[$doc['document_type']]['total']++;
        $document_type_summary[$doc['document_type']][$doc['verification_status']]++;
        
        $processed_documents[] = $doc;
    }
    
    // Calculate summary statistics
    $total_documents = count($processed_documents);
    $approved_count = count(array_filter($processed_documents, function($d) { 
        return $d['verification_status'] === 'approved'; 
    }));
    $pending_count = count(array_filter($processed_documents, function($d) { 
        return $d['verification_status'] === 'pending'; 
    }));
    $rejected_count = count(array_filter($processed_documents, function($d) { 
        return $d['verification_status'] === 'rejected'; 
    }));
    
    $total_file_size = array_sum(array_map(function($d) { 
        return $d['file_size']; 
    }, $processed_documents));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'documents' => $processed_documents,
            'statistics' => [
                'total_documents' => $total_documents,
                'document_types' => count($document_type_summary),
                'total_file_size' => $total_file_size,
                'total_file_size_mb' => round($total_file_size / (1024 * 1024), 2),
                'stage_summary' => $stage_summary,
                'document_type_summary' => $document_type_summary
            ]
        ],
        'message' => "Found {$total_documents} contractor document(s) with {$approved_count} approved"
    ]);
    
} catch (Exception $e) {
    error_log("Contractor Stage Documents API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching contractor documents: ' . $e->getMessage()
    ]);
}
?>
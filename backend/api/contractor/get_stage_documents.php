<?php
/**
 * Get Stage Documents API
 * Retrieves documents uploaded by contractors for specific construction stages
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Get parameters
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $stage_name = isset($_GET['stage_name']) ? trim($_GET['stage_name']) : '';
    $document_type = isset($_GET['document_type']) ? trim($_GET['document_type']) : '';
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : null;
    
    // Validation
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    // Build access control conditions
    $access_conditions = [];
    $access_params = [$project_id];
    
    if ($user_role === 'contractor') {
        // Contractors can only see their own documents
        $access_conditions[] = "csd.contractor_id = ?";
        $access_params[] = $user_id;
    } elseif ($user_role === 'homeowner') {
        // Homeowners can see documents for their projects
        $access_conditions[] = "cp.homeowner_id = ?";
        $access_params[] = $user_id;
    } elseif ($user_role === 'admin' || $user_role === 'site_inspector') {
        // Admins and inspectors can see all documents
        if ($contractor_id) {
            $access_conditions[] = "csd.contractor_id = ?";
            $access_params[] = $contractor_id;
        }
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Build query conditions
    $where_conditions = ["csd.project_id = ?"];
    $query_params = $access_params;
    
    if (!empty($stage_name)) {
        $where_conditions[] = "csd.stage_name = ?";
        $query_params[] = $stage_name;
    }
    
    if (!empty($document_type)) {
        $where_conditions[] = "csd.document_type = ?";
        $query_params[] = $document_type;
    }
    
    // Combine all conditions
    $all_conditions = array_merge($where_conditions, $access_conditions);
    $where_clause = implode(' AND ', $all_conditions);
    
    // Main query to get documents
    $query = "
        SELECT 
            csd.id,
            csd.project_id,
            csd.contractor_id,
            csd.stage_name,
            csd.document_type,
            csd.file_path,
            csd.original_filename,
            csd.file_size,
            csd.mime_type,
            csd.upload_date,
            csd.description,
            csd.metadata,
            cp.project_name,
            cp.homeowner_id,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            spr.id as payment_request_id,
            spr.requested_amount,
            spr.status as payment_status
        FROM contractor_stage_documents csd
        JOIN construction_projects cp ON csd.project_id = cp.id
        JOIN users u_contractor ON csd.contractor_id = u_contractor.id
        LEFT JOIN stage_payment_requests spr ON csd.related_payment_id = spr.id
        WHERE $where_clause
        ORDER BY csd.stage_name, csd.document_type, csd.upload_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($query_params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get document requirements for the project/stages
    $req_query = "
        SELECT * FROM stage_document_requirements 
        WHERE (project_id = ? OR project_id IS NULL)
    ";
    $req_params = [$project_id];
    
    if (!empty($stage_name)) {
        $req_query .= " AND stage_name = ?";
        $req_params[] = $stage_name;
    }
    
    $req_query .= " ORDER BY project_id DESC, stage_name, document_type";
    
    $req_stmt = $db->prepare($req_query);
    $req_stmt->execute($req_params);
    $requirements = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize documents by stage and type
    $organized_documents = [];
    $document_summary = [];
    
    foreach ($documents as $doc) {
        $stage = $doc['stage_name'];
        $type = $doc['document_type'];
        
        if (!isset($organized_documents[$stage])) {
            $organized_documents[$stage] = [];
            $document_summary[$stage] = [
                'total_documents' => 0,
                'document_types' => []
            ];
        }
        
        if (!isset($organized_documents[$stage][$type])) {
            $organized_documents[$stage][$type] = [];
            $document_summary[$stage]['document_types'][$type] = [
                'count' => 0
            ];
        }
        
        // Add file size in human readable format
        $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
        
        // Add file extension
        $doc['file_extension'] = strtolower(pathinfo($doc['original_filename'], PATHINFO_EXTENSION));
        
        // Parse metadata if exists
        if ($doc['metadata']) {
            $doc['metadata'] = json_decode($doc['metadata'], true);
        }
        
        $organized_documents[$stage][$type][] = $doc;
        
        // Update summary counts
        $document_summary[$stage]['total_documents']++;
        $document_summary[$stage]['document_types'][$type]['count']++;
    }
    
    // Organize requirements by stage
    $organized_requirements = [];
    foreach ($requirements as $req) {
        $stage = $req['stage_name'];
        if (!isset($organized_requirements[$stage])) {
            $organized_requirements[$stage] = [];
        }
        $organized_requirements[$stage][$req['document_type']] = $req;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'documents' => $organized_documents,
            'requirements' => $organized_requirements,
            'summary' => $document_summary,
            'total_documents' => count($documents),
            'project_id' => $project_id,
            'stage_name' => $stage_name,
            'document_type' => $document_type
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get stage documents error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
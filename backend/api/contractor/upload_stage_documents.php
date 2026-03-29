<?php
/**
 * Upload Stage Documents API
 * Allows contractors to upload receipts, bills, and other documents for specific construction stages
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a contractor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $contractor_id = $_SESSION['user_id'];
    
    // Extract form data
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $stage_name = trim($_POST['stage_name'] ?? '');
    $document_type = trim($_POST['document_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $related_payment_id = isset($_POST['related_payment_id']) ? (int)$_POST['related_payment_id'] : null;
    
    // Validation
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    if (empty($stage_name)) {
        echo json_encode(['success' => false, 'message' => 'Stage name is required']);
        exit;
    }
    
    if (empty($document_type)) {
        echo json_encode(['success' => false, 'message' => 'Document type is required']);
        exit;
    }
    
    // Verify contractor has access to this project
    $access_check = $db->prepare("SELECT id, project_name FROM construction_projects WHERE id = ? AND contractor_id = ?");
    $access_check->execute([$project_id, $contractor_id]);
    $project = $access_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this project']);
        exit;
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['documents']) || empty($_FILES['documents']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded']);
        exit;
    }
    
    // Get document requirements for validation
    $req_check = $db->prepare("
        SELECT accepted_formats, max_file_size, is_required 
        FROM stage_document_requirements 
        WHERE (project_id = ? OR project_id IS NULL) AND stage_name = ? AND document_type = ?
        ORDER BY project_id DESC LIMIT 1
    ");
    $req_check->execute([$project_id, $stage_name, $document_type]);
    $requirements = $req_check->fetch(PDO::FETCH_ASSOC);
    
    // Default requirements if not found
    if (!$requirements) {
        $requirements = [
            'accepted_formats' => 'pdf,jpg,jpeg,png,doc,docx',
            'max_file_size' => 10485760, // 10MB
            'is_required' => 0
        ];
    }
    
    $accepted_formats = explode(',', strtolower($requirements['accepted_formats']));
    $max_file_size = $requirements['max_file_size'];
    
    // Create upload directory
    $upload_base_dir = '../../uploads/stage_documents';
    $project_dir = $upload_base_dir . '/' . $project_id;
    $stage_dir = $project_dir . '/' . str_replace(' ', '_', strtolower($stage_name));
    $document_dir = $stage_dir . '/' . $document_type;
    
    if (!file_exists($document_dir)) {
        if (!mkdir($document_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    $uploaded_files = [];
    $errors = [];
    
    // Process multiple files
    $file_count = count($_FILES['documents']['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        $file_name = $_FILES['documents']['name'][$i];
        $file_tmp = $_FILES['documents']['tmp_name'][$i];
        $file_size = $_FILES['documents']['size'][$i];
        $file_error = $_FILES['documents']['error'][$i];
        
        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error for file: $file_name";
            continue;
        }
        
        if (empty($file_name)) {
            continue;
        }
        
        // Validate file size
        if ($file_size > $max_file_size) {
            $errors[] = "File too large: $file_name (max " . ($max_file_size / 1024 / 1024) . "MB)";
            continue;
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $accepted_formats)) {
            $errors[] = "Invalid file format: $file_name (allowed: " . implode(', ', $accepted_formats) . ")";
            continue;
        }
        
        // Generate unique filename
        $timestamp = time();
        $unique_filename = $document_type . '_' . $timestamp . '_' . ($i + 1) . '.' . $file_extension;
        $file_path = $document_dir . '/' . $unique_filename;
        $relative_path = 'uploads/stage_documents/' . $project_id . '/' . str_replace(' ', '_', strtolower($stage_name)) . '/' . $document_type . '/' . $unique_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Get MIME type
            $mime_type = mime_content_type($file_path);
            
            // Insert into database
            $insert_stmt = $db->prepare("
                INSERT INTO contractor_stage_documents 
                (project_id, contractor_id, stage_name, document_type, file_path, original_filename, 
                 file_size, mime_type, uploaded_by, description, is_mandatory, related_payment_id, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $metadata = json_encode([
                'upload_timestamp' => $timestamp,
                'file_index' => $i + 1,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $insert_stmt->execute([
                $project_id,
                $contractor_id,
                $stage_name,
                $document_type,
                $relative_path,
                $file_name,
                $file_size,
                $mime_type,
                $contractor_id,
                $description,
                $requirements['is_required'],
                $related_payment_id,
                $metadata
            ]);
            
            $document_id = $db->lastInsertId();
            
            // Log audit trail
            $audit_stmt = $db->prepare("
                INSERT INTO contractor_document_audit 
                (document_id, action, performed_by, notes, ip_address, user_agent)
                VALUES (?, 'uploaded', ?, ?, ?, ?)
            ");
            $audit_stmt->execute([
                $document_id,
                $contractor_id,
                "Document uploaded: $file_name",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $uploaded_files[] = [
                'id' => $document_id,
                'original_filename' => $file_name,
                'file_path' => $relative_path,
                'file_size' => $file_size,
                'mime_type' => $mime_type,
                'document_type' => $document_type,
                'stage_name' => $stage_name
            ];
        } else {
            $errors[] = "Failed to save file: $file_name";
        }
    }
    
    // Update stage payment request if related
    if ($related_payment_id && !empty($uploaded_files)) {
        $update_payment = $db->prepare("
            UPDATE stage_payment_requests 
            SET documents_submitted = JSON_ARRAY_APPEND(
                COALESCE(documents_submitted, JSON_ARRAY()), 
                '$', JSON_OBJECT('document_ids', JSON_ARRAY(?), 'upload_date', NOW())
            ),
            document_verification_status = 'partial'
            WHERE id = ? AND contractor_id = ?
        ");
        $document_ids = array_column($uploaded_files, 'id');
        $update_payment->execute([json_encode($document_ids), $related_payment_id, $contractor_id]);
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => count($uploaded_files) . ' document(s) uploaded successfully',
        'data' => [
            'uploaded_files' => $uploaded_files,
            'upload_count' => count($uploaded_files),
            'error_count' => count($errors),
            'errors' => $errors
        ]
    ];
    
    if (!empty($errors)) {
        $response['message'] .= ' with ' . count($errors) . ' error(s)';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Stage document upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
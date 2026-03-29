<?php
/**
 * Verify Stage Documents API
 * Allows admins and site inspectors to verify contractor-uploaded documents
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

// Check if user is logged in and has verification permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'site_inspector', 'homeowner'])) {
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
    
    $verifier_id = $_SESSION['user_id'];
    $verifier_role = $_SESSION['role'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $document_id = isset($input['document_id']) ? (int)$input['document_id'] : 0;
    $verification_status = isset($input['verification_status']) ? trim($input['verification_status']) : '';
    $verification_notes = isset($input['verification_notes']) ? trim($input['verification_notes']) : '';
    
    // Validation
    if ($document_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit;
    }
    
    if (!in_array($verification_status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification status']);
        exit;
    }
    
    if ($verification_status === 'rejected' && empty($verification_notes)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit;
    }
    
    // Get document details and verify access
    $doc_query = "
        SELECT 
            csd.*,
            cp.project_name,
            cp.homeowner_id,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name,
            u.email as contractor_email
        FROM contractor_stage_documents csd
        JOIN construction_projects cp ON csd.project_id = cp.id
        JOIN users u ON csd.contractor_id = u.id
        WHERE csd.id = ?
    ";
    
    $doc_stmt = $db->prepare($doc_query);
    $doc_stmt->execute([$document_id]);
    $document = $doc_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Document not found']);
        exit;
    }
    
    // Check access permissions
    if ($verifier_role === 'homeowner' && $document['homeowner_id'] != $verifier_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this document']);
        exit;
    }
    
    // Check if document is already verified by someone else
    if ($document['verification_status'] !== 'pending' && $document['verified_by'] != $verifier_id) {
        echo json_encode([
            'success' => false, 
            'message' => 'Document already verified by ' . ($document['verified_by_name'] ?? 'another user')
        ]);
        exit;
    }
    
    // Update document verification status
    $update_stmt = $db->prepare("
        UPDATE contractor_stage_documents 
        SET 
            verification_status = ?,
            verified_by = ?,
            verified_at = NOW(),
            verification_notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $update_stmt->execute([
        $verification_status,
        $verifier_id,
        $verification_notes,
        $document_id
    ]);
    
    // Log audit trail
    $audit_stmt = $db->prepare("
        INSERT INTO contractor_document_audit 
        (document_id, action, performed_by, notes, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $audit_action = $verification_status === 'approved' ? 'verified' : 'rejected';
    $audit_notes = "Document $audit_action" . ($verification_notes ? ": $verification_notes" : '');
    
    $audit_stmt->execute([
        $document_id,
        $audit_action,
        $verifier_id,
        $audit_notes,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Update related payment request document verification status if applicable
    if ($document['related_payment_id']) {
        updatePaymentDocumentStatus($db, $document['related_payment_id']);
    }
    
    // Get verifier details for response
    $verifier_stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $verifier_stmt->execute([$verifier_id]);
    $verifier = $verifier_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Send notification to contractor (optional - implement if notification system exists)
    // sendDocumentVerificationNotification($document, $verification_status, $verification_notes, $verifier);
    
    echo json_encode([
        'success' => true,
        'message' => "Document $audit_action successfully",
        'data' => [
            'document_id' => $document_id,
            'verification_status' => $verification_status,
            'verified_by' => $verifier['name'],
            'verified_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $verification_notes,
            'document_type' => $document['document_type'],
            'stage_name' => $document['stage_name'],
            'project_name' => $document['project_name']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Document verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}

/**
 * Update payment request document verification status based on related documents
 */
function updatePaymentDocumentStatus($db, $payment_id) {
    try {
        // Get all documents related to this payment
        $doc_stmt = $db->prepare("
            SELECT verification_status, COUNT(*) as count
            FROM contractor_stage_documents 
            WHERE related_payment_id = ?
            GROUP BY verification_status
        ");
        $doc_stmt->execute([$payment_id]);
        $status_counts = $doc_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $total_docs = array_sum($status_counts);
        $approved_docs = $status_counts['approved'] ?? 0;
        $rejected_docs = $status_counts['rejected'] ?? 0;
        $pending_docs = $status_counts['pending'] ?? 0;
        
        // Determine overall document verification status
        $overall_status = 'pending';
        if ($total_docs > 0) {
            if ($rejected_docs > 0) {
                $overall_status = 'rejected';
            } elseif ($approved_docs === $total_docs) {
                $overall_status = 'complete';
            } elseif ($approved_docs > 0) {
                $overall_status = 'partial';
            }
        }
        
        // Update payment request
        $update_payment = $db->prepare("
            UPDATE stage_payment_requests 
            SET document_verification_status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $update_payment->execute([$overall_status, $payment_id]);
        
    } catch (Exception $e) {
        error_log("Error updating payment document status: " . $e->getMessage());
    }
}
?>
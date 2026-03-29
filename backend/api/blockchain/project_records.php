<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';

/**
 * Project Blockchain Records API
 * 
 * Returns blockchain payment records for a specific project
 */

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    if (!isset($_GET['project_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing project_id parameter']);
        exit;
    }
    
    $projectId = (int) $_GET['project_id'];
    
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get payment records with verification counts
    $stmt = $db->prepare("
        SELECT 
            bpr.*,
            COUNT(bvr.id) as verification_count,
            GROUP_CONCAT(bvr.verifier_type) as verifier_types
        FROM blockchain_payment_records bpr
        LEFT JOIN blockchain_verification_records bvr ON bpr.proof_hash = bvr.proof_hash
        WHERE bpr.project_id = ?
        GROUP BY bpr.proof_hash
        ORDER BY bpr.created_at DESC
    ");
    
    $stmt->execute([$projectId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the records
    foreach ($records as &$record) {
        $record['verification_count'] = (int) $record['verification_count'];
        $record['amount'] = (float) $record['amount'];
        
        if ($record['payment_data']) {
            $record['payment_data'] = json_decode($record['payment_data'], true);
        }
        
        if ($record['verifier_types']) {
            $record['verifier_types'] = array_map('intval', explode(',', $record['verifier_types']));
        } else {
            $record['verifier_types'] = [];
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $records,
        'count' => count($records)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
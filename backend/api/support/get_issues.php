<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $issue_id = $_GET['issue_id'] ?? null;
    
    if ($issue_id) {
        // Get specific issue with replies
        $stmt = $pdo->prepare("
            SELECT si.*, u.first_name, u.last_name, u.email 
            FROM support_issues si 
            JOIN users u ON si.user_id = u.id 
            WHERE si.id = ? AND si.user_id = ?
        ");
        $stmt->execute([$issue_id, $user_id]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$issue) {
            echo json_encode(['success' => false, 'message' => 'Issue not found']);
            exit;
        }
        
        // Get replies for this issue
        $repliesStmt = $pdo->prepare("
            SELECT * FROM support_replies 
            WHERE issue_id = ? 
            ORDER BY created_at ASC
        ");
        $repliesStmt->execute([$issue_id]);
        $replies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'issue' => $issue,
            'replies' => $replies
        ]);
        
    } else {
        // Get all issues for the user
        $stmt = $pdo->prepare("
            SELECT si.*, u.first_name, u.last_name, u.email 
            FROM support_issues si 
            JOIN users u ON si.user_id = u.id 
            WHERE si.user_id = ? 
            ORDER BY si.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'issues' => $issues
        ]);
    }
    
} catch (Exception $e) {
    error_log("Support get_issues error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
<?php
// Simple test to simulate what the admin API should return
session_start();

// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "<h1>Admin API Simulation Test</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is admin (simulate the API check)
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        $result = ['success' => false, 'message' => 'Admin access required'];
    } else {
        // Get all support issues with user information (same query as API)
        $stmt = $pdo->prepare("
            SELECT si.*, u.first_name, u.last_name, u.email, u.role,
                   (SELECT COUNT(*) FROM support_replies sr WHERE sr.issue_id = si.id) as reply_count
            FROM support_issues si 
            JOIN users u ON si.user_id = u.id 
            ORDER BY si.updated_at DESC, si.created_at DESC
        ");
        $stmt->execute();
        $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [
            'success' => true,
            'issues' => $issues
        ];
    }
    
    echo "<h2>API Result:</h2>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h2>Summary:</h2>";
    echo "<p>Success: " . ($result['success'] ? 'YES' : 'NO') . "</p>";
    if ($result['success']) {
        echo "<p>Issues found: " . count($result['issues']) . "</p>";
        if (count($result['issues']) > 0) {
            echo "<p>Latest issue: " . htmlspecialchars($result['issues'][0]['subject']) . " by " . htmlspecialchars($result['issues'][0]['first_name'] . ' ' . $result['issues'][0]['last_name']) . "</p>";
        }
    } else {
        echo "<p>Error: " . htmlspecialchars($result['message']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Session Check:</h2>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
?>
<?php
// Test admin API directly
session_start();

// Simulate admin session for testing
$_SESSION['user_id'] = 1; // Assuming admin user ID is 1
$_SESSION['role'] = 'admin';

echo "<h1>Testing Admin Support API</h1>";

echo "<h2>Direct API Test</h2>";

// Test the admin API directly
$url = 'http://localhost/buildhub/backend/api/admin/get_support_issues.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
]);

$response = file_get_contents($url, false, $context);

echo "<h3>API Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Also test database directly
echo "<h2>Direct Database Test</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT si.*, u.first_name, u.last_name, u.email, u.role,
               (SELECT COUNT(*) FROM support_replies sr WHERE sr.issue_id = si.id) as reply_count
        FROM support_issues si 
        JOIN users u ON si.user_id = u.id 
        ORDER BY si.updated_at DESC, si.created_at DESC
    ");
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Database Query Result:</h3>";
    echo "<p>Found " . count($issues) . " issues</p>";
    
    if (count($issues) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Subject</th><th>User</th><th>Category</th><th>Status</th><th>Replies</th><th>Created</th></tr>";
        foreach ($issues as $issue) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($issue['id']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['subject']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['category']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['status']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['reply_count']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Session Info</h2>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
?>
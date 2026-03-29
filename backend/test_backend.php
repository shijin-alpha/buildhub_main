<?php
echo "<h1>Backend Server Test</h1>";
echo "<p>✅ Backend server is running!</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? '/') . "</p>";

// Test database connection
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection working</p>";
    
    // Test support issues count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM support_issues");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>📊 Support issues in database: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='/buildhub/backend/api/test_proxy.php'>Test Proxy API</a></li>";
echo "<li><a href='/buildhub/backend/api/debug_session.php'>Debug Session</a></li>";
echo "<li><a href='/buildhub/backend/api/admin/get_support_issues.php'>Admin Support Issues API</a></li>";
echo "</ul>";
?>
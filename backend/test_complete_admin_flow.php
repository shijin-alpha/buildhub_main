<?php
echo "<h1>Complete Admin Flow Test</h1>";

// Test 1: Admin Login
echo "<h2>1. Testing Admin Login</h2>";
$loginData = json_encode([
    'username' => 'admin',
    'password' => 'admin123'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $loginData
    ]
]);

$loginResponse = file_get_contents('http://localhost/buildhub/backend/api/admin/admin_login.php', false, $context);
echo "<p>Login Response: " . htmlspecialchars($loginResponse) . "</p>";

// Test 2: Check Session
echo "<h2>2. Testing Session Status</h2>";
$sessionResponse = file_get_contents('http://localhost/buildhub/backend/api/admin/test_session.php');
echo "<p>Session Response: " . htmlspecialchars($sessionResponse) . "</p>";

// Test 3: Test Support Issues API
echo "<h2>3. Testing Support Issues API</h2>";
$supportResponse = file_get_contents('http://localhost/buildhub/backend/api/admin/get_support_issues.php');
echo "<p>Support Issues Response: " . htmlspecialchars($supportResponse) . "</p>";

// Test 4: Direct Database Query
echo "<h2>4. Direct Database Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM support_issues");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Database accessible. Found " . $result['count'] . " support issues.</p>";
    
    // Show recent issues
    $recentStmt = $pdo->query("
        SELECT si.id, si.subject, si.category, si.status, si.created_at,
               u.first_name, u.last_name, u.email
        FROM support_issues si 
        JOIN users u ON si.user_id = u.id 
        ORDER BY si.created_at DESC 
        LIMIT 3
    ");
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recent) > 0) {
        echo "<h3>Recent Issues:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Subject</th><th>Customer</th><th>Category</th><th>Status</th><th>Created</th></tr>";
        foreach ($recent as $issue) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($issue['id']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['subject']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['category']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['status']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>The admin support system should work if:</p>";
echo "<ul>";
echo "<li>✅ Admin login creates proper session</li>";
echo "<li>✅ Session cookies are sent with API requests</li>";
echo "<li>✅ Admin APIs recognize the session</li>";
echo "<li>✅ Database contains support issues</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Login to admin dashboard via frontend</li>";
echo "<li>Go to 'Customer Support' tab</li>";
echo "<li>Check browser developer tools for any API errors</li>";
echo "<li>Verify that session cookies are being sent with requests</li>";
echo "</ol>";
?>
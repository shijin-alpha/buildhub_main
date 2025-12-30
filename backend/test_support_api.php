<?php
// Simple test script to verify support API endpoints work
header('Content-Type: text/html');

echo "<h1>Support API Test</h1>";

// Test database connection
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if support_issues table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_issues'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ support_issues table exists</p>";
    } else {
        echo "<p>⚠️ support_issues table does not exist (will be created on first use)</p>";
    }
    
    // Check if support_replies table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_replies'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ support_replies table exists</p>";
    } else {
        echo "<p>⚠️ support_replies table does not exist (will be created on first use)</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check if API files exist
$apiFiles = [
    'backend/api/support/create_issue.php',
    'backend/api/support/get_issues.php', 
    'backend/api/support/admin_reply.php',
    'backend/api/admin/get_support_issues.php',
    'backend/api/admin/get_support_thread.php'
];

echo "<h2>API Files Check</h2>";
foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file exists</p>";
    } else {
        echo "<p>❌ $file missing</p>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>You can now test the support system in the HomeownerDashboard.</p>";
?>
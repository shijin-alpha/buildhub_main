<?php
// Test script for admin support system
header('Content-Type: text/html');

echo "<h1>Admin Support System Test</h1>";

// Test database connection and tables
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check support_issues table
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_issues'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ support_issues table exists</p>";
        
        // Count issues
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM support_issues");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Total issues: " . $count['count'] . "</p>";
        
        // Show recent issues
        $recentStmt = $pdo->query("SELECT * FROM support_issues ORDER BY created_at DESC LIMIT 5");
        $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent) > 0) {
            echo "<h3>Recent Issues:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Subject</th><th>Category</th><th>Status</th><th>Created</th></tr>";
            foreach ($recent as $issue) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($issue['id']) . "</td>";
                echo "<td>" . htmlspecialchars($issue['subject']) . "</td>";
                echo "<td>" . htmlspecialchars($issue['category']) . "</td>";
                echo "<td>" . htmlspecialchars($issue['status']) . "</td>";
                echo "<td>" . htmlspecialchars($issue['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>⚠️ support_issues table does not exist</p>";
    }
    
    // Check support_replies table
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_replies'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ support_replies table exists</p>";
        
        // Count replies
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM support_replies");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Total replies: " . $count['count'] . "</p>";
    } else {
        echo "<p>⚠️ support_replies table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test API endpoints
echo "<h2>API Endpoints Test</h2>";

$endpoints = [
    'Support APIs' => [
        'backend/api/support/create_issue.php',
        'backend/api/support/get_issues.php',
        'backend/api/support/admin_reply.php'
    ],
    'Admin APIs' => [
        'backend/api/admin/get_support_issues.php',
        'backend/api/admin/get_support_thread.php'
    ]
];

foreach ($endpoints as $category => $files) {
    echo "<h3>$category</h3>";
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "<p>✅ $file exists</p>";
        } else {
            echo "<p>❌ $file missing</p>";
        }
    }
}

echo "<h2>Usage Instructions</h2>";
echo "<ol>";
echo "<li>Login as a homeowner and click the ❓ help button to submit a support issue</li>";
echo "<li>Login as admin and go to 'Customer Support' tab to view and reply to issues</li>";
echo "<li>Test the conversation flow by sending replies back and forth</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Make sure you have admin privileges to access the admin support panel.</p>";
?>
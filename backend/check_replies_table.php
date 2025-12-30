<?php
// Check if support_replies table exists and create if needed
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>Support Replies Table Check</h1>";
    
    // Check if support_replies table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'support_replies'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ support_replies table exists</p>";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE support_replies");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Table structure:</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ support_replies table does not exist. Creating...</p>";
        
        $createTableSQL = "
            CREATE TABLE support_replies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                issue_id INT NOT NULL,
                sender VARCHAR(20) NOT NULL DEFAULT 'admin',
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (issue_id) REFERENCES support_issues(id) ON DELETE CASCADE
            )
        ";
        $pdo->exec($createTableSQL);
        echo "<p>✅ support_replies table created successfully</p>";
    }
    
    // Test the query with reply count
    echo "<h2>Testing query with reply count:</h2>";
    $stmt = $pdo->prepare("
        SELECT si.id, si.subject, si.status,
               (SELECT COUNT(*) FROM support_replies sr WHERE sr.issue_id = si.id) as reply_count
        FROM support_issues si 
        ORDER BY si.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Query executed successfully! Found " . count($issues) . " issues.</p>";
    
    if (count($issues) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Subject</th><th>Status</th><th>Reply Count</th></tr>";
        foreach ($issues as $issue) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($issue['id']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['subject']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['status']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['reply_count']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
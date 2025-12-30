<?php
// Check support table structure
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>Support Table Structure Check</h1>";
    
    // Check support_issues table structure
    $stmt = $pdo->query("DESCRIBE support_issues");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>support_issues table structure:</h2>";
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
    
    // Check if updated_at column exists
    $hasUpdatedAt = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'updated_at') {
            $hasUpdatedAt = true;
            break;
        }
    }
    
    if (!$hasUpdatedAt) {
        echo "<p><strong>❌ Missing updated_at column!</strong></p>";
        echo "<p>Adding updated_at column...</p>";
        
        $alterSQL = "ALTER TABLE support_issues ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $pdo->exec($alterSQL);
        
        echo "<p>✅ Added updated_at column successfully!</p>";
    } else {
        echo "<p>✅ updated_at column exists</p>";
    }
    
    // Test the query that was failing
    echo "<h2>Testing the admin query:</h2>";
    $stmt = $pdo->prepare("
        SELECT si.*, u.first_name, u.last_name, u.email, u.role,
               (SELECT COUNT(*) FROM support_replies sr WHERE sr.issue_id = si.id) as reply_count
        FROM support_issues si 
        JOIN users u ON si.user_id = u.id 
        ORDER BY si.updated_at DESC, si.created_at DESC
    ");
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Query executed successfully! Found " . count($issues) . " issues.</p>";
    
    if (count($issues) > 0) {
        echo "<h3>Sample issues:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Subject</th><th>User</th><th>Category</th><th>Status</th><th>Created</th><th>Updated</th></tr>";
        foreach (array_slice($issues, 0, 5) as $issue) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($issue['id']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['subject']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['category']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['status']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
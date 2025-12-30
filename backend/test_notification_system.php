<?php
echo "<h1>Notification System Test</h1>";

// Test database connection
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ notifications table exists</p>";
        
        // Count notifications
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>📊 Total notifications: " . $count['count'] . "</p>";
        
        // Show recent notifications
        $recentStmt = $pdo->query("
            SELECT n.*, u.first_name, u.last_name, u.email 
            FROM notifications n 
            LEFT JOIN users u ON n.user_id = u.id 
            ORDER BY n.created_at DESC 
            LIMIT 10
        ");
        $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent) > 0) {
            echo "<h3>Recent Notifications:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
            foreach ($recent as $notification) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($notification['id']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['type']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($notification['message'], 0, 50)) . "...</td>";
                echo "<td>" . ($notification['is_read'] ? '✅' : '❌') . "</td>";
                echo "<td>" . htmlspecialchars($notification['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>⚠️ notifications table does not exist</p>";
    }
    
    // Test notification creation
    echo "<h2>Testing Notification Creation</h2>";
    require_once 'utils/notification_helper.php';
    
    // Get a test user
    $userStmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'homeowner' LIMIT 1");
    $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        echo "<p>📝 Creating test notification for user: " . $testUser['first_name'] . " " . $testUser['last_name'] . "</p>";
        
        $notificationId = createNotification(
            $pdo,
            $testUser['id'],
            'test_notification',
            'Test Notification',
            'This is a test notification created by the notification system test.',
            null
        );
        
        if ($notificationId) {
            echo "<p>✅ Test notification created successfully with ID: " . $notificationId . "</p>";
        } else {
            echo "<p>❌ Failed to create test notification</p>";
        }
    } else {
        echo "<p>⚠️ No homeowner users found for testing</p>";
    }
    
    // Test API endpoints
    echo "<h2>API Endpoints Test</h2>";
    $endpoints = [
        'backend/api/homeowner/get_notifications.php',
        'backend/api/homeowner/mark_notifications_read.php',
        'backend/api/homeowner/create_notification.php',
        'backend/api/homeowner/get_messages.php'
    ];
    
    foreach ($endpoints as $endpoint) {
        if (file_exists($endpoint)) {
            echo "<p>✅ $endpoint exists</p>";
        } else {
            echo "<p>❌ $endpoint missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Usage Instructions</h2>";
echo "<ol>";
echo "<li>Login as a homeowner and check the notification bell icon in the top header</li>";
echo "<li>Click the bell icon to open the Message Center</li>";
echo "<li>Submit a layout request or interact with contractors to generate notifications</li>";
echo "<li>Check the sidebar for notification badges on relevant sections</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Notifications are created automatically when:</p>";
echo "<ul>";
echo "<li>Contractors submit estimates</li>";
echo "<li>Layouts are approved</li>";
echo "<li>Construction starts</li>";
echo "<li>Messages are sent/received</li>";
echo "</ul>";
?>
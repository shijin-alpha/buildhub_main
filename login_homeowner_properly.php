<?php
/**
 * Properly login homeowner using the same session configuration as the main login API
 */

// First, let's check if there's a homeowner user in the database
try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check for homeowner user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = ?");
    $stmt->execute([28, 'homeowner']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Homeowner user (ID: 28) not found in database.\n";
        echo "Let me create a test homeowner user...\n";
        
        // Create a test homeowner user
        $insertStmt = $pdo->prepare("
            INSERT INTO users (id, first_name, last_name, email, password, role, is_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            email = VALUES(email),
            role = VALUES(role),
            is_verified = VALUES(is_verified)
        ");
        
        $result = $insertStmt->execute([
            28,
            'Test',
            'Homeowner',
            'homeowner@test.com',
            password_hash('password123', PASSWORD_DEFAULT),
            'homeowner',
            1
        ]);
        
        if ($result) {
            echo "✅ Test homeowner user created successfully.\n";
            // Fetch the user again
            $stmt->execute([28, 'homeowner']);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo "❌ Failed to create test homeowner user.\n";
            exit;
        }
    }
    
    echo "✅ Found homeowner user: {$user['first_name']} {$user['last_name']} ({$user['email']})\n";
    
    // Now set up the session exactly like the login API does
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/buildhub',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    echo "✅ Session established with proper cookie parameters.\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Path: /buildhub\n";
    
    // Test the session by calling the API
    echo "\n🧪 Testing API with proper session...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/homeowner/get_all_payment_requests.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✅ API Authentication Test: SUCCESS\n";
        echo "Found " . count($data['data']['requests']) . " payment requests\n";
        
        foreach ($data['data']['requests'] as $request) {
            $type_icon = $request['request_type'] === 'custom' ? '💰' : '🏗️';
            echo "  $type_icon {$request['request_type']}: {$request['request_title']} - ₹{$request['requested_amount']} ({$request['status']})\n";
        }
    } else {
        echo "❌ API Authentication Test: FAILED\n";
        echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        echo "Response: $response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
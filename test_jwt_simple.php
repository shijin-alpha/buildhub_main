<?php
/**
 * Simple JWT Test - Direct PHP testing without HTTP requests
 */

require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/utils/JWTManager.php';
require_once __DIR__ . '/backend/middleware/JWTAuthMiddleware.php';

echo "🚀 JWT Implementation Test\n";
echo "==========================\n\n";

try {
    // Test 1: Database Connection
    echo "1. Testing Database Connection...\n";
    $database = new Database();
    $conn = $database->getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    echo "   ✓ Database connected successfully\n\n";
    
    // Test 2: JWT Manager
    echo "2. Testing JWT Manager...\n";
    $jwtManager = new JWTManager($database);
    
    // Generate tokens
    $tokens = $jwtManager->generateTokens(1, 'homeowner', 'test@example.com');
    echo "   ✓ Tokens generated successfully\n";
    echo "   Access Token: " . substr($tokens['access_token'], 0, 50) . "...\n";
    echo "   Refresh Token: " . substr($tokens['refresh_token'], 0, 50) . "...\n";
    
    // Validate access token
    $payload = $jwtManager->validateAccessToken($tokens['access_token']);
    echo "   ✓ Access token validated successfully\n";
    echo "   User ID: " . $payload['user_id'] . "\n";
    echo "   Role: " . $payload['role'] . "\n";
    echo "   Email: " . $payload['email'] . "\n";
    
    // Test refresh token (create a real user first)
    $stmt = $conn->prepare("SELECT id FROM users WHERE status = 'approved' AND deleted_at IS NULL LIMIT 1");
    $stmt->execute();
    $realUser = $stmt->fetch();
    
    if ($realUser) {
        $testTokens = $jwtManager->generateTokens($realUser['id'], 'homeowner', 'test@example.com');
        $newTokens = $jwtManager->refreshAccessToken($testTokens['refresh_token']);
        echo "   ✓ Token refresh successful\n";
    } else {
        echo "   ⚠️  Token refresh test skipped (no users found)\n";
    }
    
    // Test blacklisting
    $jwtManager->blacklistToken($payload['jti']);
    echo "   ✓ Token blacklisted successfully\n";
    
    try {
        $jwtManager->validateAccessToken($tokens['access_token']);
        throw new Exception("Blacklisted token should be invalid");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'revoked') !== false) {
            echo "   ✓ Blacklisted token correctly rejected\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n";
    
    // Test 3: Middleware
    echo "3. Testing JWT Middleware...\n";
    $middleware = new JWTAuthMiddleware();
    echo "   ✓ Middleware initialized successfully\n";
    
    // Test token extraction
    $testToken = JWTManager::extractTokenFromHeader();
    echo "   ✓ Token extraction method available\n";
    
    echo "\n";
    
    // Test 4: Database Tables
    echo "4. Verifying Database Tables...\n";
    $tables = ['jwt_tokens', 'jwt_blacklist', 'api_rate_limits', 'auth_audit_log'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "   ✓ Table '$table' accessible (rows: " . $result['count'] . ")\n";
    }
    
    echo "\n";
    
    // Test 5: User Table Compatibility
    echo "5. Testing User Table Compatibility...\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
    $result = $stmt->fetch();
    echo "   ✓ Users table accessible (active users: " . $result['count'] . ")\n";
    
    // Check for required columns
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'email', 'password', 'role', 'status', 'first_name', 'last_name'];
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "   ✓ Column '$column' exists\n";
        } else {
            echo "   ⚠️  Column '$column' missing\n";
        }
    }
    
    echo "\n";
    
    // Test 6: Create Test User
    echo "6. Creating Test User...\n";
    
    // Check if test user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['jwt_test@buildhub.com']);
    
    if ($stmt->rowCount() > 0) {
        echo "   ℹ️  Test user already exists\n";
    } else {
        // Create test user
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, email, password, role, status, is_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, 'approved', 1, NOW())
        ");
        
        $stmt->execute([
            'JWT',
            'Test',
            'jwt_test@buildhub.com',
            $hashedPassword,
            'homeowner'
        ]);
        
        echo "   ✓ Test user created successfully\n";
    }
    
    echo "\n";
    
    // Test 7: Full Authentication Flow
    echo "7. Testing Full Authentication Flow...\n";
    
    // Get test user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'approved'");
    $stmt->execute(['jwt_test@buildhub.com']);
    $testUser = $stmt->fetch();
    
    if (!$testUser) {
        throw new Exception("Test user not found");
    }
    
    // Verify password
    if (!password_verify('test123', $testUser['password'])) {
        throw new Exception("Password verification failed");
    }
    echo "   ✓ User authentication successful\n";
    
    // Generate tokens for test user
    $userTokens = $jwtManager->generateTokens(
        $testUser['id'],
        $testUser['role'],
        $testUser['email']
    );
    echo "   ✓ User tokens generated\n";
    
    // Validate tokens
    $userPayload = $jwtManager->validateAccessToken($userTokens['access_token']);
    if ($userPayload['user_id'] != $testUser['id']) {
        throw new Exception("Token validation failed");
    }
    echo "   ✓ User tokens validated\n";
    
    echo "\n✅ All JWT tests passed successfully!\n\n";
    
    echo "🎉 JWT Implementation Status: READY\n";
    echo "=====================================\n";
    echo "✓ Database schema applied\n";
    echo "✓ JWT Manager functional\n";
    echo "✓ Token generation/validation working\n";
    echo "✓ Token blacklisting working\n";
    echo "✓ Middleware initialized\n";
    echo "✓ Test user created\n";
    echo "✓ Full authentication flow tested\n\n";
    
    echo "Next Steps:\n";
    echo "1. Update your frontend to use the JWT authentication utility\n";
    echo "2. Replace API endpoints with JWT-protected versions\n";
    echo "3. Test with your actual application\n";
    echo "4. Configure environment variables for production\n\n";
    
    echo "Test User Credentials:\n";
    echo "Email: jwt_test@buildhub.com\n";
    echo "Password: test123\n";
    echo "Role: homeowner\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
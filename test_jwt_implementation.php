<?php
/**
 * JWT Implementation Test Script
 * Tests all JWT functionality including login, token refresh, and protected endpoints
 */

require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/utils/JWTManager.php';

class JWTTester {
    private $baseURL;
    private $testUser;
    private $tokens;
    
    public function __construct() {
        $this->baseURL = 'http://localhost/backend/api';
        $this->testUser = [
            'email' => 'test@buildhub.com',
            'password' => 'test123',
            'role' => 'homeowner'
        ];
    }
    
    public function runAllTests() {
        echo "🚀 Starting JWT Implementation Tests\n";
        echo "=====================================\n\n";
        
        try {
            $this->testDatabaseSetup();
            $this->testJWTManager();
            $this->testLoginEndpoint();
            $this->testProtectedEndpoint();
            $this->testTokenRefresh();
            $this->testLogout();
            $this->testRateLimit();
            
            echo "\n✅ All tests completed successfully!\n";
            
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
    
    private function testDatabaseSetup() {
        echo "1. Testing Database Setup...\n";
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $requiredTables = ['jwt_tokens', 'jwt_blacklist', 'api_rate_limits', 'auth_audit_log'];
        
        foreach ($requiredTables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("Required table '$table' not found");
            }
            echo "   ✓ Table '$table' exists\n";
        }
        
        echo "   ✅ Database setup verified\n\n";
    }
    
    private function testJWTManager() {
        echo "2. Testing JWT Manager...\n";
        
        $database = new Database();
        $jwtManager = new JWTManager($database);
        
        // Test token generation
        $tokens = $jwtManager->generateTokens(1, 'homeowner', 'test@example.com');
        
        if (!isset($tokens['access_token']) || !isset($tokens['refresh_token'])) {
            throw new Exception("Token generation failed");
        }
        echo "   ✓ Token generation works\n";
        
        // Test token validation
        $payload = $jwtManager->validateAccessToken($tokens['access_token']);
        if ($payload['user_id'] !== 1 || $payload['role'] !== 'homeowner') {
            throw new Exception("Token validation failed");
        }
        echo "   ✓ Token validation works\n";
        
        // Test token blacklisting
        $jwtManager->blacklistToken($payload['jti']);
        
        try {
            $jwtManager->validateAccessToken($tokens['access_token']);
            throw new Exception("Blacklisted token should be invalid");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'revoked') === false) {
                throw $e;
            }
            echo "   ✓ Token blacklisting works\n";
        }
        
        echo "   ✅ JWT Manager tests passed\n\n";
    }
    
    private function testLoginEndpoint() {
        echo "3. Testing Login Endpoint...\n";
        
        // First, create a test user if it doesn't exist
        $this->createTestUser();
        
        $loginData = [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password']
        ];
        
        $response = $this->makeRequest('POST', '/auth/jwt_login.php', $loginData);
        
        if (!$response['success']) {
            throw new Exception("Login failed: " . ($response['error'] ?? 'Unknown error'));
        }
        
        if (!isset($response['tokens']['access_token'])) {
            throw new Exception("No access token in login response");
        }
        
        $this->tokens = $response['tokens'];
        echo "   ✓ Login successful\n";
        echo "   ✓ Tokens received\n";
        echo "   ✅ Login endpoint test passed\n\n";
    }
    
    private function testProtectedEndpoint() {
        echo "4. Testing Protected Endpoint...\n";
        
        if (!$this->tokens) {
            throw new Exception("No tokens available for testing");
        }
        
        // Test without token (should fail)
        $response = $this->makeRequest('GET', '/homeowner/jwt_get_my_projects.php');
        if ($response['success'] !== false) {
            throw new Exception("Protected endpoint should reject requests without token");
        }
        echo "   ✓ Endpoint rejects requests without token\n";
        
        // Test with valid token (should succeed)
        $headers = ['Authorization: Bearer ' . $this->tokens['access_token']];
        $response = $this->makeRequest('GET', '/homeowner/jwt_get_my_projects.php', null, $headers);
        
        if (!$response['success']) {
            throw new Exception("Protected endpoint failed with valid token: " . ($response['error'] ?? 'Unknown error'));
        }
        echo "   ✓ Endpoint accepts requests with valid token\n";
        echo "   ✅ Protected endpoint test passed\n\n";
    }
    
    private function testTokenRefresh() {
        echo "5. Testing Token Refresh...\n";
        
        if (!$this->tokens) {
            throw new Exception("No tokens available for testing");
        }
        
        $refreshData = ['refresh_token' => $this->tokens['refresh_token']];
        $response = $this->makeRequest('POST', '/auth/jwt_refresh.php', $refreshData);
        
        if (!$response['success']) {
            throw new Exception("Token refresh failed: " . ($response['error'] ?? 'Unknown error'));
        }
        
        if (!isset($response['tokens']['access_token'])) {
            throw new Exception("No new access token in refresh response");
        }
        
        // Update tokens
        $this->tokens = $response['tokens'];
        echo "   ✓ Token refresh successful\n";
        echo "   ✓ New tokens received\n";
        echo "   ✅ Token refresh test passed\n\n";
    }
    
    private function testLogout() {
        echo "6. Testing Logout...\n";
        
        if (!$this->tokens) {
            throw new Exception("No tokens available for testing");
        }
        
        $headers = ['Authorization: Bearer ' . $this->tokens['access_token']];
        $response = $this->makeRequest('POST', '/auth/jwt_logout.php', [], $headers);
        
        if (!$response['success']) {
            throw new Exception("Logout failed: " . ($response['error'] ?? 'Unknown error'));
        }
        echo "   ✓ Logout successful\n";
        
        // Test that token is now invalid
        $response = $this->makeRequest('GET', '/homeowner/jwt_get_my_projects.php', null, $headers);
        if ($response['success'] !== false) {
            throw new Exception("Token should be invalid after logout");
        }
        echo "   ✓ Token invalidated after logout\n";
        echo "   ✅ Logout test passed\n\n";
    }
    
    private function testRateLimit() {
        echo "7. Testing Rate Limiting...\n";
        
        // This is a basic test - in production you'd need more sophisticated testing
        echo "   ⚠️  Rate limiting test requires manual verification\n";
        echo "   ℹ️  Make multiple rapid requests to test rate limiting\n";
        echo "   ✅ Rate limiting test skipped (manual verification required)\n\n";
    }
    
    private function createTestUser() {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if test user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$this->testUser['email']]);
        
        if ($stmt->rowCount() > 0) {
            echo "   ℹ️  Test user already exists\n";
            return;
        }
        
        // Create test user
        $hashedPassword = password_hash($this->testUser['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, email, password, role, status, is_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, 'approved', 1, NOW())
        ");
        
        $stmt->execute([
            'Test',
            'User',
            $this->testUser['email'],
            $hashedPassword,
            $this->testUser['role']
        ]);
        
        echo "   ✓ Test user created\n";
    }
    
    private function makeRequest($method, $endpoint, $data = null, $headers = []) {
        $url = $this->baseURL . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception("cURL request failed for $url");
        }
        
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new Exception("Invalid JSON response from $url: $response");
        }
        
        return $decoded;
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $tester = new JWTTester();
    $success = $tester->runAllTests();
    exit($success ? 0 : 1);
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test_jwt_implementation.php\n";
}
?>
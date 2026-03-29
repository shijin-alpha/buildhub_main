<?php
/**
 * JWT Manager - Handles JWT token generation, validation, and management
 * Supports access tokens, refresh tokens, and token blacklisting
 */

require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTManager {
    private $secretKey;
    private $refreshSecretKey;
    private $algorithm = 'HS256';
    private $accessTokenExpiry = 3600; // 1 hour
    private $refreshTokenExpiry = 604800; // 7 days
    private $db;
    
    public function __construct($database = null) {
        // Use environment variables or fallback to secure defaults
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'buildhub_jwt_secret_key_2024_secure_' . hash('sha256', __DIR__);
        $this->refreshSecretKey = $_ENV['JWT_REFRESH_SECRET'] ?? 'buildhub_refresh_secret_key_2024_' . hash('sha256', __DIR__ . 'refresh');
        
        if ($database) {
            $this->db = $database;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $this->db = new Database();
        }
    }
    
    /**
     * Generate access and refresh tokens for a user
     */
    public function generateTokens($userId, $userRole, $userEmail, $additionalClaims = []) {
        $issuedAt = time();
        $accessExpiry = $issuedAt + $this->accessTokenExpiry;
        $refreshExpiry = $issuedAt + $this->refreshTokenExpiry;
        
        // Generate unique token ID for tracking
        $jti = bin2hex(random_bytes(16));
        $refreshJti = bin2hex(random_bytes(16));
        
        // Access token payload
        $accessPayload = array_merge([
            'iss' => 'buildhub-api',
            'aud' => 'buildhub-client',
            'iat' => $issuedAt,
            'exp' => $accessExpiry,
            'jti' => $jti,
            'user_id' => $userId,
            'role' => $userRole,
            'email' => $userEmail,
            'token_type' => 'access'
        ], $additionalClaims);
        
        // Refresh token payload
        $refreshPayload = [
            'iss' => 'buildhub-api',
            'aud' => 'buildhub-client',
            'iat' => $issuedAt,
            'exp' => $refreshExpiry,
            'jti' => $refreshJti,
            'user_id' => $userId,
            'token_type' => 'refresh'
        ];
        
        $accessToken = JWT::encode($accessPayload, $this->secretKey, $this->algorithm);
        $refreshToken = JWT::encode($refreshPayload, $this->refreshSecretKey, $this->algorithm);
        
        // Store tokens in database for tracking and blacklisting
        $this->storeTokens($userId, $jti, $refreshJti, $accessExpiry, $refreshExpiry);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTokenExpiry,
            'expires_at' => $accessExpiry
        ];
    }
    
    /**
     * Validate and decode access token
     */
    public function validateAccessToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;
            
            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($payload['jti'])) {
                throw new Exception('Token has been revoked');
            }
            
            // Verify token type
            if ($payload['token_type'] !== 'access') {
                throw new Exception('Invalid token type');
            }
            
            return $payload;
        } catch (ExpiredException $e) {
            throw new Exception('Token has expired');
        } catch (SignatureInvalidException $e) {
            throw new Exception('Invalid token signature');
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate and decode refresh token
     */
    public function validateRefreshToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->refreshSecretKey, $this->algorithm));
            $payload = (array) $decoded;
            
            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($payload['jti'])) {
                throw new Exception('Refresh token has been revoked');
            }
            
            // Verify token type
            if ($payload['token_type'] !== 'refresh') {
                throw new Exception('Invalid token type');
            }
            
            return $payload;
        } catch (ExpiredException $e) {
            throw new Exception('Refresh token has expired');
        } catch (SignatureInvalidException $e) {
            throw new Exception('Invalid refresh token signature');
        } catch (Exception $e) {
            throw new Exception('Invalid refresh token: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken($refreshToken) {
        $refreshPayload = $this->validateRefreshToken($refreshToken);
        
        // Get user details
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT id, role, email, first_name, last_name, admin_scope FROM users WHERE id = ? AND status = 'approved' AND deleted_at IS NULL");
        $stmt->execute([$refreshPayload['user_id']]);
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception('User not found or inactive');
        }
        
        $user = $result;
        
        // Generate new access token
        $additionalClaims = [];
        if ($user['admin_scope']) {
            $additionalClaims['admin_scope'] = $user['admin_scope'];
        }
        
        $tokens = $this->generateTokens(
            $user['id'],
            $user['role'],
            $user['email'],
            $additionalClaims
        );
        
        return $tokens;
    }
    
    /**
     * Blacklist a token (logout)
     */
    public function blacklistToken($jti) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO jwt_blacklist (jti, blacklisted_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE blacklisted_at = NOW()");
        return $stmt->execute([$jti]);
    }
    
    /**
     * Blacklist all user tokens (logout from all devices)
     */
    public function blacklistAllUserTokens($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO jwt_blacklist (jti, blacklisted_at) 
            SELECT jti, NOW() FROM jwt_tokens WHERE user_id = ? AND expires_at > NOW()
            ON DUPLICATE KEY UPDATE blacklisted_at = NOW()
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Check if token is blacklisted
     */
    private function isTokenBlacklisted($jti) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT 1 FROM jwt_blacklist WHERE jti = ?");
        $stmt->execute([$jti]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Store tokens in database for tracking
     */
    private function storeTokens($userId, $accessJti, $refreshJti, $accessExpiry, $refreshExpiry) {
        $conn = $this->db->getConnection();
        
        // Store access token
        $stmt = $conn->prepare("
            INSERT INTO jwt_tokens (user_id, jti, token_type, expires_at, created_at) 
            VALUES (?, ?, 'access', FROM_UNIXTIME(?), NOW())
        ");
        $stmt->execute([$userId, $accessJti, $accessExpiry]);
        
        // Store refresh token
        $stmt = $conn->prepare("
            INSERT INTO jwt_tokens (user_id, jti, token_type, expires_at, created_at) 
            VALUES (?, ?, 'refresh', FROM_UNIXTIME(?), NOW())
        ");
        $stmt->execute([$userId, $refreshJti, $refreshExpiry]);
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens() {
        $conn = $this->db->getConnection();
        
        // Remove expired tokens
        $stmt = $conn->prepare("DELETE FROM jwt_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        
        // Remove old blacklist entries (older than 30 days)
        $stmt = $conn->prepare("DELETE FROM jwt_blacklist WHERE blacklisted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        
        return true;
    }
    
    /**
     * Get user active sessions
     */
    public function getUserActiveSessions($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT jti, token_type, expires_at, created_at 
            FROM jwt_tokens 
            WHERE user_id = ? AND expires_at > NOW() 
            AND jti NOT IN (SELECT jti FROM jwt_blacklist)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Extract token from Authorization header
     */
    public static function extractTokenFromHeader() {
        // Handle CLI environment
        if (php_sapi_name() === 'cli') {
            return null;
        }
        
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        // Fallback for environments without getallheaders()
        if (empty($headers)) {
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                }
            }
        }
        
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader) {
            return null;
        }
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
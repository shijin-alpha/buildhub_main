<?php
/**
 * JWT Authentication Middleware
 * Handles JWT token validation and user authorization for API endpoints
 */

require_once __DIR__ . '/../utils/JWTManager.php';
require_once __DIR__ . '/../config/database.php';

class JWTAuthMiddleware {
    private $jwtManager;
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->jwtManager = new JWTManager($this->db);
    }
    
    /**
     * Authenticate request and return user data
     */
    public function authenticate() {
        try {
            $token = JWTManager::extractTokenFromHeader();
            
            if (!$token) {
                $this->sendUnauthorizedResponse('Missing authorization token');
                return false;
            }
            
            $payload = $this->jwtManager->validateAccessToken($token);
            
            // Verify user still exists and is active
            $user = $this->getUserById($payload['user_id']);
            if (!$user) {
                $this->sendUnauthorizedResponse('User not found or inactive');
                return false;
            }
            
            // Set user data in global scope for use in endpoints
            $GLOBALS['current_user'] = $user;
            $GLOBALS['jwt_payload'] = $payload;
            
            return $user;
            
        } catch (Exception $e) {
            $this->sendUnauthorizedResponse($e->getMessage());
            return false;
        }
    }
    
    /**
     * Authorize user based on required roles
     */
    public function authorize($requiredRoles = [], $requiredAdminScope = null) {
        $user = $this->authenticate();
        
        if (!$user) {
            return false;
        }
        
        // Check role authorization
        if (!empty($requiredRoles) && !in_array($user['role'], $requiredRoles)) {
            $this->sendForbiddenResponse('Insufficient permissions - role not authorized');
            return false;
        }
        
        // Check admin scope authorization
        if ($requiredAdminScope && $user['admin_scope'] !== $requiredAdminScope) {
            $this->sendForbiddenResponse('Insufficient permissions - admin scope not authorized');
            return false;
        }
        
        return $user;
    }
    
    /**
     * Middleware for homeowner endpoints
     */
    public function requireHomeowner() {
        return $this->authorize(['homeowner']);
    }
    
    /**
     * Middleware for contractor endpoints
     */
    public function requireContractor() {
        return $this->authorize(['contractor']);
    }
    
    /**
     * Middleware for architect endpoints
     */
    public function requireArchitect() {
        return $this->authorize(['architect']);
    }
    
    /**
     * Middleware for site inspector endpoints
     */
    public function requireSiteInspector() {
        return $this->authorize(['site_inspector']);
    }
    
    /**
     * Middleware for admin endpoints
     */
    public function requireAdmin($scope = null) {
        return $this->authorize(['admin'], $scope);
    }
    
    /**
     * Middleware for full admin access
     */
    public function requireFullAdmin() {
        return $this->requireAdmin('FULL');
    }
    
    /**
     * Middleware for inspector admin access
     */
    public function requireInspectorAdmin() {
        return $this->requireAdmin('INSPECTOR');
    }
    
    /**
     * Middleware for contractor or architect endpoints
     */
    public function requireContractorOrArchitect() {
        return $this->authorize(['contractor', 'architect']);
    }
    
    /**
     * Middleware for any authenticated user
     */
    public function requireAuthenticated() {
        return $this->authenticate();
    }
    
    /**
     * Check if current user owns a resource
     */
    public function requireResourceOwnership($resourceUserId) {
        $user = $this->authenticate();
        
        if (!$user) {
            return false;
        }
        
        if ($user['id'] != $resourceUserId) {
            $this->sendForbiddenResponse('Access denied - resource ownership required');
            return false;
        }
        
        return $user;
    }
    
    /**
     * Check if current user can access a project
     */
    public function requireProjectAccess($projectId) {
        $user = $this->authenticate();
        
        if (!$user) {
            return false;
        }
        
        // Admin users have access to all projects
        if ($user['role'] === 'admin') {
            return $user;
        }
        
        // Check project access based on role
        $hasAccess = false;
        $conn = $this->db->getConnection();
        
        switch ($user['role']) {
            case 'homeowner':
                $stmt = $conn->prepare("SELECT 1 FROM projects WHERE id = ? AND homeowner_id = ?");
                $stmt->execute([$projectId, $user['id']]);
                break;
                
            case 'contractor':
                $stmt = $conn->prepare("SELECT 1 FROM projects WHERE id = ? AND contractor_id = ?");
                $stmt->execute([$projectId, $user['id']]);
                break;
                
            case 'architect':
                $stmt = $conn->prepare("SELECT 1 FROM projects WHERE id = ? AND architect_id = ?");
                $stmt->execute([$projectId, $user['id']]);
                break;
                
            case 'site_inspector':
                $stmt = $conn->prepare("SELECT 1 FROM site_inspections WHERE project_id = ? AND inspector_id = ?");
                $stmt->execute([$projectId, $user['id']]);
                break;
                
            default:
                $this->sendForbiddenResponse('Invalid user role');
                return false;
        }
        
        $hasAccess = $stmt->rowCount() > 0;
        
        if (!$hasAccess) {
            $this->sendForbiddenResponse('Access denied - project access required');
            return false;
        }
        
        return $user;
    }
    
    /**
     * Rate limiting middleware
     */
    public function rateLimit($maxRequests = 100, $windowMinutes = 15) {
        $user = $this->authenticate();
        
        if (!$user) {
            return false;
        }
        
        $conn = $this->db->getConnection();
        $windowStart = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));
        
        // Count requests in the current window
        $stmt = $conn->prepare("
            SELECT COUNT(*) as request_count 
            FROM api_rate_limits 
            WHERE user_id = ? AND created_at >= ?
        ");
        $stmt->execute([$user['id'], $windowStart]);
        $result = $stmt->fetch();
        
        if ($result['request_count'] >= $maxRequests) {
            $this->sendRateLimitResponse($maxRequests, $windowMinutes);
            return false;
        }
        
        // Log this request
        $stmt = $conn->prepare("INSERT INTO api_rate_limits (user_id, endpoint, created_at) VALUES (?, ?, NOW())");
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $stmt->execute([$user['id'], $endpoint]);
        
        return $user;
    }
    
    /**
     * Get user by ID with status check
     */
    private function getUserById($userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, email, role, status, admin_scope, is_verified 
            FROM users 
            WHERE id = ? AND status = 'approved' AND deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? $result : null;
    }
    
    /**
     * Send unauthorized response
     */
    private function sendUnauthorizedResponse($message = 'Unauthorized') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => 'UNAUTHORIZED'
        ]);
        exit;
    }
    
    /**
     * Send forbidden response
     */
    private function sendForbiddenResponse($message = 'Forbidden') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => 'FORBIDDEN'
        ]);
        exit;
    }
    
    /**
     * Send rate limit response
     */
    private function sendRateLimitResponse($maxRequests, $windowMinutes) {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => "Rate limit exceeded. Maximum {$maxRequests} requests per {$windowMinutes} minutes.",
            'code' => 'RATE_LIMIT_EXCEEDED'
        ]);
        exit;
    }
    
    /**
     * Get current authenticated user
     */
    public static function getCurrentUser() {
        return $GLOBALS['current_user'] ?? null;
    }
    
    /**
     * Get current JWT payload
     */
    public static function getJWTPayload() {
        return $GLOBALS['jwt_payload'] ?? null;
    }
}
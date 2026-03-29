<?php
/**
 * Authorization Middleware for BUILDHUB Site Inspector System
 * 
 * Provides server-side authorization and access control for admin operations
 * Implements capability-based authorization with admin scope validation
 */

class AuthorizationMiddleware {
    
    private $db;
    private $currentUser;
    private $adminScope;
    
    // Define capabilities for each admin scope
    private const CAPABILITIES = [
        'FULL' => [
            // User Management
            'view_all_users',
            'approve_users',
            'reject_users',
            'suspend_users',
            'delete_users',
            'manage_user_roles',
            
            // Inspector Management
            'create_inspectors',
            'assign_projects_to_inspectors',
            'view_all_inspector_reports',
            'review_inspector_reports',
            
            // System Management
            'manage_materials',
            'view_system_stats',
            'manage_admin_credentials',
            'access_audit_logs',
            
            // Payment Management
            'verify_payments',
            'approve_payment_requests',
            'reject_payment_requests',
            
            // Project Management
            'view_all_projects',
            'modify_project_status',
            'access_project_financials',
            
            // Support Management
            'view_support_tickets',
            'respond_to_support',
            'manage_support_categories'
        ],
        
        'INSPECTOR' => [
            // Project Access (limited to assigned projects)
            'view_assigned_projects',
            'view_project_details',
            
            // Inspection Reports
            'create_inspection_reports',
            'edit_own_inspection_reports',
            'submit_inspection_reports',
            'view_own_inspection_reports',
            
            // Site Notes
            'create_site_notes',
            'edit_own_site_notes',
            'view_site_notes',
            'resolve_site_notes',
            
            // Progress Tracking
            'view_construction_progress',
            'upload_progress_photos',
            'add_progress_comments',
            
            // Limited User Access
            'view_project_team_members',
            'contact_project_stakeholders'
        ]
    ];
    
    // Define admin-only operations that inspectors cannot access
    private const ADMIN_ONLY_OPERATIONS = [
        'user_verification',
        'role_management', 
        'user_deletion',
        'system_settings',
        'payment_verification',
        'financial_reports',
        'admin_credential_management',
        'inspector_assignment',
        'audit_log_access'
    ];
    
    public function __construct($database) {
        $this->db = $database;
        $this->loadCurrentUser();
    }
    
    /**
     * Load current user and admin scope from session
     */
    private function loadCurrentUser() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            $this->currentUser = null;
            $this->adminScope = null;
            return;
        }
        
        $this->currentUser = [
            'id' => $_SESSION['admin_user_id'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'role' => $_SESSION['admin_role'] ?? null,
            'scope' => $_SESSION['admin_scope'] ?? null
        ];
        
        $this->adminScope = $this->currentUser['scope'];
    }
    
    /**
     * Check if user is authenticated as admin
     */
    public function isAuthenticated(): bool {
        return $this->currentUser !== null && 
               isset($this->currentUser['id']) && 
               $this->adminScope !== null;
    }
    
    /**
     * Check if user has specific capability
     */
    public function hasCapability(string $capability): bool {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $scopeCapabilities = self::CAPABILITIES[$this->adminScope] ?? [];
        return in_array($capability, $scopeCapabilities);
    }
    
    /**
     * Check if user can access specific operation
     */
    public function canAccess(string $operation): bool {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Block admin-only operations for inspectors
        if ($this->adminScope === 'INSPECTOR' && in_array($operation, self::ADMIN_ONLY_OPERATIONS)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if inspector can access specific project
     */
    public function canAccessProject(int $projectId): bool {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Full admins can access all projects
        if ($this->adminScope === 'FULL') {
            return true;
        }
        
        // Inspectors can only access assigned projects
        if ($this->adminScope === 'INSPECTOR') {
            return $this->isProjectAssignedToInspector($projectId, $this->currentUser['id']);
        }
        
        return false;
    }
    
    /**
     * Check if project is assigned to inspector
     */
    private function isProjectAssignedToInspector(int $projectId, int $inspectorId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM inspector_project_assignments 
                WHERE inspector_id = :inspector_id 
                AND project_id = :project_id 
                AND status = 'active'
            ");
            
            $stmt->execute([
                ':inspector_id' => $inspectorId,
                ':project_id' => $projectId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] ?? 0) > 0;
            
        } catch (Exception $e) {
            error_log("Error checking project assignment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get list of projects assigned to current inspector
     */
    public function getAssignedProjects(): array {
        if (!$this->isAuthenticated() || $this->adminScope !== 'INSPECTOR') {
            return [];
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    cp.id,
                    cp.project_name,
                    cp.status,
                    cp.current_stage,
                    cp.completion_percentage,
                    cp.project_location,
                    cp.homeowner_name,
                    cp.expected_completion_date,
                    ipa.assigned_at,
                    ipa.notes as assignment_notes
                FROM inspector_project_assignments ipa
                JOIN construction_projects cp ON ipa.project_id = cp.id
                WHERE ipa.inspector_id = :inspector_id 
                AND ipa.status = 'active'
                ORDER BY ipa.assigned_at DESC
            ");
            
            $stmt->execute([':inspector_id' => $this->currentUser['id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error fetching assigned projects: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Require authentication - throws exception if not authenticated
     */
    public function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTH_REQUIRED'
            ]);
            exit;
        }
    }
    
    /**
     * Require specific capability - throws exception if not authorized
     */
    public function requireCapability(string $capability): void {
        $this->requireAuth();
        
        if (!$this->hasCapability($capability)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'required_capability' => $capability
            ]);
            exit;
        }
    }
    
    /**
     * Require access to specific operation
     */
    public function requireOperation(string $operation): void {
        $this->requireAuth();
        
        if (!$this->canAccess($operation)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied for this operation',
                'error_code' => 'OPERATION_DENIED',
                'operation' => $operation
            ]);
            exit;
        }
    }
    
    /**
     * Require access to specific project
     */
    public function requireProjectAccess(int $projectId): void {
        $this->requireAuth();
        
        if (!$this->canAccessProject($projectId)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied for this project',
                'error_code' => 'PROJECT_ACCESS_DENIED',
                'project_id' => $projectId
            ]);
            exit;
        }
    }
    
    /**
     * Log inspector action for audit trail
     */
    public function logAction(string $action, ?int $projectId = null, ?string $resourceType = null, ?int $resourceId = null, ?array $details = null): void {
        if (!$this->isAuthenticated()) {
            return;
        }
        
        // Allow logging for both inspectors and full admins
        if ($this->adminScope !== 'INSPECTOR' && $this->adminScope !== 'FULL') {
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO inspector_audit_log 
                (inspector_id, project_id, action, resource_type, resource_id, details, ip_address, user_agent)
                VALUES (:inspector_id, :project_id, :action, :resource_type, :resource_id, :details, :ip_address, :user_agent)
            ");
            
            $stmt->execute([
                ':inspector_id' => $this->currentUser['id'],
                ':project_id' => $projectId,
                ':action' => $action,
                ':resource_type' => $resourceType,
                ':resource_id' => $resourceId,
                ':details' => $details ? json_encode($details) : null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("Error logging inspector action: " . $e->getMessage());
        }
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser(): ?array {
        return $this->currentUser;
    }
    
    /**
     * Get current admin scope
     */
    public function getAdminScope(): ?string {
        return $this->adminScope;
    }
    
    /**
     * Check if current user is full admin
     */
    public function isFullAdmin(): bool {
        return $this->adminScope === 'FULL';
    }
    
    /**
     * Check if current user is inspector
     */
    public function isInspector(): bool {
        return $this->adminScope === 'INSPECTOR';
    }
}
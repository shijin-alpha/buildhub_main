<?php
/**
 * JWT Protected: Get All Users (Admin)
 * Example of JWT-protected admin endpoint with rate limiting
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../middleware/JWTAuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

try {
    // JWT Authentication with rate limiting
    $auth = new JWTAuthMiddleware();
    
    // Apply rate limiting (100 requests per 15 minutes)
    $user = $auth->rateLimit(100, 15);
    if (!$user) {
        exit; // Rate limit middleware handles the response
    }
    
    // Require full admin access
    $user = $auth->requireFullAdmin();
    if (!$user) {
        exit; // Auth middleware handles the response
    }
    
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get query parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 20;
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $whereConditions = ["deleted_at IS NULL"];
    $params = [];
    
    if ($role && in_array($role, ['homeowner', 'contractor', 'architect', 'site_inspector', 'admin'])) {
        $whereConditions[] = "role = ?";
        $params[] = $role;
    }
    
    if ($status && in_array($status, ['pending', 'approved', 'rejected', 'suspended'])) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users WHERE {$whereClause}";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $totalCount = $stmt->fetch()['total'];
    
    // Get users
    $sql = "
        SELECT id, first_name, last_name, email, role, status, admin_scope, 
               is_verified, created_at, last_login_at, login_attempts
        FROM users 
        WHERE {$whereClause}
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user statistics
    $stmt = $conn->prepare("
        SELECT 
            role,
            status,
            COUNT(*) as count
        FROM users 
        WHERE deleted_at IS NULL
        GROUP BY role, status
        ORDER BY role, status
    ");
    $stmt->execute();
    $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format statistics
    $stats = [];
    foreach ($statistics as $stat) {
        if (!isset($stats[$stat['role']])) {
            $stats[$stat['role']] = [];
        }
        $stats[$stat['role']][$stat['status']] = $stat['count'];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_count' => $totalCount,
            'total_pages' => ceil($totalCount / $limit)
        ],
        'statistics' => $stats,
        'filters' => [
            'role' => $role,
            'status' => $status,
            'search' => $search
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}
?>
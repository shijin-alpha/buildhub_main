<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if users.status and users.deleted_at columns exist (schema compatibility)
    $colStmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $hasStatusCol = $colStmt && $colStmt->rowCount() > 0;
    $delStmt = $db->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
    $hasDeletedAtCol = $delStmt && $delStmt->rowCount() > 0;
    
    // Get filter parameters
    $role = $_GET['role'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sortBy'] ?? 'created_at';
    $sortOrder = $_GET['sortOrder'] ?? 'desc';
    
    // Validate sort parameters
    $allowedSortFields = ['created_at', 'first_name', 'last_name', 'email', 'role', 'status']; // safe fields only
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }
    
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    // Build the query - only include columns that exist in minimal schema
    // Derive status from is_verified when status column is absent
    $query = "SELECT 
                id, first_name, last_name, email, role,
                CASE WHEN COALESCE(status, '') <> '' THEN status
                     WHEN is_verified = 1 THEN 'approved'
                     ELSE 'pending'
                END AS status,
                license, portfolio, profile_image,
                created_at, updated_at
              FROM users 
              WHERE 1=1";
    
    $params = [];
    
    // Exclude soft-deleted users if column exists
    if ($hasDeletedAtCol) {
        $query .= " AND deleted_at IS NULL";
    }

    // Add role filter
    if ($role !== 'all') {
        $query .= " AND role = :role";
        $params[':role'] = $role;
    }
    
    // Add status filter (compatible with/without status column)
    if ($status !== 'all') {
        if ($hasStatusCol) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        } else {
            // Map filter to is_verified when status column is absent
            if ($status === 'approved') {
                $query .= " AND is_verified = 1";
            } elseif ($status === 'pending') {
                $query .= " AND is_verified = 0";
            } else {
                // rejected/suspended cannot be represented without status column; return none
                $query .= " AND 1=0";
            }
        }
    }
    
    // Add search filter (use unique named params to avoid duplicates error)
    if (!empty($search)) {
        $query .= " AND (
            first_name LIKE :search1 OR 
            last_name LIKE :search2 OR 
            email LIKE :search3
        )";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }
    
    // Add sorting
    $query .= " ORDER BY $sortBy $sortOrder";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'role' => $row['role'],
            'status' => $row['status'],
            'company_name' => $row['company_name'] ?? null,
            'experience_years' => $row['experience_years'] ?? null,
            'specialization' => $row['specialization'] ?? null,
            'license' => $row['license'] ?? null,
            'portfolio' => $row['portfolio'] ?? null,
            'profile_image' => $row['profile_image'] ?? null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    // Get summary statistics
    // Stats compatible with minimal schema (derive status via is_verified when status missing)
    $statsQuery = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'homeowner' THEN 1 ELSE 0 END) as homeowners,
                    SUM(CASE WHEN role = 'contractor' THEN 1 ELSE 0 END) as contractors,
                    SUM(CASE WHEN role = 'architect' THEN 1 ELSE 0 END) as architects,
                    SUM(CASE WHEN COALESCE(status, '') = 'pending' OR (COALESCE(status, '') = '' AND is_verified = 0) THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN COALESCE(status, '') = 'approved' OR (COALESCE(status, '') = '' AND is_verified = 1) THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN COALESCE(status, '') = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN COALESCE(status, '') = 'suspended' THEN 1 ELSE 0 END) as suspended
                   FROM users";
    if ($hasDeletedAtCol) {
        $statsQuery .= " WHERE deleted_at IS NULL";
    }
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'stats' => $stats,
        'filters' => [
            'role' => $role,
            'status' => $status,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching users: ' . $e->getMessage()
    ]);
}
?>
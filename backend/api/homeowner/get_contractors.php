<?php
header('Content-Type: application/json');
// Reflect origin for credentialed requests
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    header('Access-Control-Max-Age: 86400');
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Optional filters
    $search = $_GET['search'] ?? '';
    $specialization = $_GET['specialization'] ?? '';
    $minExp = isset($_GET['min_experience']) ? (int)$_GET['min_experience'] : null;

    // Some deployments may have users.status; detect safely
    $hasStatus = false;
    try {
        $col = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
        $hasStatus = $col && $col->rowCount() > 0;
    } catch (Throwable $e) {}

    $select = "SELECT 
                u.id, u.first_name, u.last_name, u.email, u.role, u.is_verified,
                u.phone AS phone, u.address AS address, u.company_name AS company_name,
                u.experience_years AS experience_years, u.specialization AS specialization,
                u.license AS license, u.portfolio AS portfolio,
                u.created_at AS created_at, u.city AS city, u.state AS state, u.location AS location";

    $from = " FROM users u";

    // Only approved/verified contractors
    $where = " WHERE u.role = 'contractor' AND u.is_verified = 1";
    if ($hasStatus) {
        // If status column exists, include only active/approved
        $where .= " AND (u.status IS NULL OR u.status = '' OR u.status = 'approved' OR u.status = 'active')";
    }

    $params = [];

    if (!empty($search)) {
        $where .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.company_name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    if (!empty($specialization)) {
        $where .= " AND (u.specialization LIKE :spec)";
        $params[':spec'] = '%' . $specialization . '%';
    }
    if ($minExp !== null) {
        $where .= " AND (u.experience_years IS NOT NULL AND u.experience_years >= :minexp)";
        $params[':minexp'] = $minExp;
    }

    $order = " ORDER BY u.id DESC";

    $query = $select . $from . $where . $order;
    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();

    $contractors = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contractors[] = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'company_name' => $row['company_name'],
            'experience_years' => is_null($row['experience_years']) ? null : (int)$row['experience_years'],
            'specialization' => $row['specialization'],
            'license' => $row['license'],
            'portfolio' => $row['portfolio'],
            'created_at' => $row['created_at'],
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'location' => $row['location'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'contractors' => $contractors
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching contractors: ' . $e->getMessage()
    ]);
}



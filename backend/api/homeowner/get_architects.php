<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Ensure reviews table exists so subqueries won't fail on fresh DBs
    $db->exec("CREATE TABLE IF NOT EXISTS architect_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        architect_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        design_id INT NULL,
        rating TINYINT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Get parameters
    $search = $_GET['search'] ?? '';
    $specialization = $_GET['specialization'] ?? '';
    $minExp = isset($_GET['min_experience']) ? (int)$_GET['min_experience'] : null;
    $lrid = isset($_GET['layout_request_id']) ? (int)$_GET['layout_request_id'] : 0;

    // Build base query
    $query = "SELECT 
                u.id, u.first_name, u.last_name, u.email, u.role, u.is_verified,
                u.phone AS phone, u.address AS address, u.company_name AS company_name,
                u.experience_years AS experience_years, u.specialization AS specialization,
                u.license AS license, u.portfolio AS portfolio,
                u.created_at AS created_at, u.city AS city, u.state AS state, u.location AS location,
                (SELECT ROUND(AVG(r.rating),2) FROM architect_reviews r WHERE r.architect_id = u.id) AS avg_rating,
                (SELECT COUNT(*) FROM architect_reviews r2 WHERE r2.architect_id = u.id) AS review_count";

    // Add layout request specific fields if needed
    if ($lrid > 0) {
        $query .= ", (la.id IS NOT NULL) AS already_assigned, la.status AS assignment_status";
    } else {
        $query .= ", 0 AS already_assigned, NULL AS assignment_status";
    }

    $query .= " FROM users u";

    // Add JOIN if layout request ID is provided
    if ($lrid > 0) {
        $query .= " LEFT JOIN layout_request_assignments la ON la.architect_id = u.id AND la.layout_request_id = :lrid";
    }

    $query .= " WHERE u.role = 'architect' AND u.status = 'approved'";

    // Add search conditions
    $conditions = [];
    $params = [];

    if (!empty($search)) {
        $conditions[] = "(u.first_name LIKE :search1 OR u.last_name LIKE :search2 OR u.email LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    if (!empty($specialization)) {
        $conditions[] = "u.specialization LIKE :spec";
        $params[':spec'] = '%' . $specialization . '%';
    }

    if ($minExp !== null) {
        $conditions[] = "u.experience_years IS NOT NULL AND u.experience_years >= :minexp";
        $params[':minexp'] = $minExp;
    }

    if ($lrid > 0) {
        $params[':lrid'] = $lrid;
    }

    // Add conditions to query
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY u.id DESC";

    // Prepare and execute query
    $stmt = $db->prepare($query);
    
    // Bind all parameters
    foreach ($params as $key => $value) {
        if ($key === ':lrid') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();

    $architects = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $architects[] = [
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
            'avg_rating' => is_null($row['avg_rating']) ? null : (float)$row['avg_rating'],
            'review_count' => isset($row['review_count']) ? (int)$row['review_count'] : 0,
            'already_assigned' => isset($row['already_assigned']) ? (bool)$row['already_assigned'] : false,
            'assignment_status' => $row['assignment_status'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'architects' => $architects
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching architects: ' . $e->getMessage()
    ]);
}
?>
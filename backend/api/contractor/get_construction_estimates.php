<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); } else { header('Access-Control-Allow-Origin: http://localhost'); }
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); header('Access-Control-Max-Age: 86400'); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get contractor_id from query parameter
    $contractorId = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    if ($contractorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Ensure contractor_send_estimates table exists
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        send_id INT NOT NULL,
        contractor_id INT NOT NULL,
        materials TEXT NULL,
        cost_breakdown TEXT NULL,
        total_cost DECIMAL(15,2) NULL,
        timeline VARCHAR(255) NULL,
        notes TEXT NULL,
        structured LONGTEXT NULL,
        status VARCHAR(32) DEFAULT 'submitted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(send_id),
        INDEX(contractor_id)
    )");

    // Fetch estimates that are ready for construction
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.send_id,
            e.contractor_id,
            e.materials,
            e.cost_breakdown,
            e.total_cost,
            e.timeline,
            e.notes,
            e.structured,
            e.status,
            e.created_at
        FROM contractor_send_estimates e
        WHERE e.contractor_id = :contractor_id 
        AND (e.status = 'construction_started' OR e.status = 'accepted')
        ORDER BY e.created_at DESC
    ");
    
    $stmt->bindParam(':contractor_id', $contractorId, PDO::PARAM_INT);
    $stmt->execute();
    
    $estimates = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $estimates[] = [
            'id' => (int)$row['id'],
            'send_id' => (int)$row['send_id'],
            'contractor_id' => (int)$row['contractor_id'],
            'materials' => $row['materials'],
            'cost_breakdown' => $row['cost_breakdown'],
            'total_cost' => $row['total_cost'] ? (float)$row['total_cost'] : null,
            'timeline' => $row['timeline'],
            'notes' => $row['notes'],
            'structured' => $row['structured'] ? json_decode($row['structured'], true) : null,
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'estimates' => $estimates
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching construction estimates: ' . $e->getMessage()
    ]);
}
?>

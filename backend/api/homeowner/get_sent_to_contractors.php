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
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    if (!$homeowner_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ensure table exists (defensive)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            payload JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            acknowledged_at DATETIME NULL,
            due_date DATE NULL
        )");
    } catch (Throwable $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            acknowledged_at DATETIME NULL,
            due_date DATE NULL
        )");
    }

    // Latest estimate (if any)
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        send_id INT NOT NULL,
        contractor_id INT NOT NULL,
        materials TEXT NULL,
        cost_breakdown TEXT NULL,
        total_cost DECIMAL(15,2) NULL,
        timeline VARCHAR(255) NULL,
        notes TEXT NULL,
        status VARCHAR(32) DEFAULT 'submitted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(send_id)
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimate_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estimate_id INT NOT NULL,
        path VARCHAR(512) NOT NULL,
        original_name VARCHAR(255) NULL,
        ext VARCHAR(16) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(estimate_id)
    )");

    $sql = "SELECT s.*, 
            CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS contractor_name,
            c.email AS contractor_email,
            (SELECT e.total_cost FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_total_cost,
            (SELECT e.timeline FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_timeline,
            (SELECT e.created_at FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_estimate_at,
            (SELECT e.materials FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_materials,
            (SELECT e.cost_breakdown FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_cost_breakdown,
            (SELECT e.notes FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_notes,
            (SELECT e.structured FROM contractor_send_estimates e WHERE e.send_id = s.id ORDER BY e.id DESC LIMIT 1) AS latest_structured,
            (SELECT COUNT(*) FROM contractor_send_estimate_files f JOIN contractor_send_estimates e2 ON e2.id = f.estimate_id WHERE e2.send_id = s.id) AS latest_files_count
            FROM contractor_layout_sends s
            LEFT JOIN users c ON c.id = s.contractor_id
            WHERE s.homeowner_id = :hid
            ORDER BY s.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':hid', $homeowner_id, PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payload = [];
        if (!empty($row['payload'])) {
            $decoded = json_decode($row['payload'], true);
            if (is_array($decoded)) $payload = $decoded;
        }
        $items[] = [
            'id' => (int)$row['id'],
            'contractor_id' => (int)$row['contractor_id'],
            'contractor_name' => $row['contractor_name'] ?? null,
            'contractor_email' => $row['contractor_email'] ?? null,
            'layout_id' => is_null($row['layout_id']) ? null : (int)$row['layout_id'],
            'design_id' => is_null($row['design_id']) ? null : (int)$row['design_id'],
            'message' => $row['message'] ?? null,
            'payload' => $payload,
            'created_at' => $row['created_at'],
            'acknowledged_at' => $row['acknowledged_at'] ?? null,
            'due_date' => $row['due_date'] ?? null,
            'latest_total_cost' => $row['latest_total_cost'],
            'latest_timeline' => $row['latest_timeline'],
            'latest_estimate_at' => $row['latest_estimate_at'],
            'latest_materials' => $row['latest_materials'],
            'latest_cost_breakdown' => $row['latest_cost_breakdown'],
            'latest_notes' => $row['latest_notes'],
            'latest_files_count' => isset($row['latest_files_count']) ? (int)$row['latest_files_count'] : 0,
            'latest_structured' => $row['latest_structured']
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching sent items: ' . $e->getMessage()]);
}



